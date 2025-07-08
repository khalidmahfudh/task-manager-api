<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Class ProfileController
 * @package App\Controllers\Api
 *
 * @property object $request 
 */
class ProfileController extends BaseController
{
    use ResponseTrait;

    /**
     * @var UserModel $userModel The user model instance for database operations.
     */
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Get authenticated user's profile details.
     * Endpoint: GET /api/profile
     * Requires a valid JWT token.
     * 
     * @return ResponseInterface
     */
    public function show(): ResponseInterface
    {
        // user_id sudah diinjeksi ke objek request oleh JWTAuthFilter setelah token divalidasi.
        $userId = $this->request->user_id;

        // Double check.
        if (empty($userId)) {
            log_message('error', 'ProfileController: User ID not found in request despite JWTAuthFilter.');
            return $this->failServerError('Authenticated user ID not available.');
        }

        // Ambil data pengguna dari database berdasarkan ID.
        $user = $this->userModel->find($userId);

        if (!$user) {
            // Jika pengguna tidak ditemukan (misalnya, data di DB sudah dihapus tapi token masih valid)
            log_message('error', 'ProfileController: User with ID ' . $userId . ' not found in database.');
            return $this->failNotFound('User profile not found.');
        }

        // Siapkan data yang akan dikirim ke respons.
        $userData = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
            'updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
        ];

        return $this->respond([
            'status'  => 200,
            'error'   => false,
            'message' => 'User profile retrieved successfully.',
            'data'    => $userData
        ]);
    }

     /**
     * Update authenticated user's profile details.
     * Endpoint: PUT/PATCH /api/profile
     * Requires a valid JWT token.
     *
     * @return ResponseInterface
     */
    public function update(): ResponseInterface
    {
        $userId = $this->request->user_id;

        if (empty($userId)) {
            log_message('error', 'ProfileController: User ID not found in request during profile update.');
            return $this->failServerError('Authenticated user ID not available.');
        }

        // 1. Tentukan aturan validasi untuk data yang masuk.
        $rules = [
            'name'  => 'permit_empty|min_length[3]|max_length[100]',
            'email' => 'permit_empty|valid_email|is_unique[users.email,id,' . $userId . ']',
            // 'password' bisa ditambahkan di endpoint terpisah untuk keamanan
        ];

        // 2. Lakukan validasi.
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // 3. Ambil data dari JSON request body.
        $data = $this->request->getJSON(true);

        // Filter data yang hanya boleh di-update.
        $updateData = [];
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        // Jika tidak ada data yang valid untuk diupdate
        if (empty($updateData)) {
            return $this->failValidationError('No valid data provided for update.');
        }

        // 4. Update data pengguna di database.
        // Gunakan update() dengan ID untuk memastikan update data yang benar.
        try {
            $this->userModel->update($userId, $updateData);

            // Ambil data terbaru setelah update untuk dikirim kembali.
            $updatedUser = $this->userModel->find($userId);

            $responseUserData = [
                'id'         => $updatedUser->id,
                'name'       => $updatedUser->name,
                'email'      => $updatedUser->email,
                'role'       => $updatedUser->role,
                'created_at' => $updatedUser->created_at ? $updatedUser->created_at->toDateTimeString() : null,
                'updated_at' => $updatedUser->updated_at ? $updatedUser->updated_at->toDateTimeString() : null,
            ];

            return $this->respondUpdated([
                'status'  => 200,
                'error'   => false,
                'message' => 'User profile updated successfully.',
                'data'    => $responseUserData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Profile update failed for user ID ' . $userId . ': ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to update user profile.');
        }
    }

     /**
     * Update password for the authenticated user.
     * Endpoint: PUT /api/password
     *
     * Requires: old_password, new_password, confirm_password in JSON body.
     *
     * @return ResponseInterface
     */
    public function updatePassword(): ResponseInterface
    {
        $userId = $this->request->user_id;

        if (!$userId) {
            return $this->failUnauthorized('User not authenticated.');
        }

        $input = $this->request->getJson(true);

        if ($input === null || !is_array($input)) {
            return $this->failValidationError('Invalid JSON body provided. Please ensure it is valid JSON.');
        }

        $rules = [
            'old_password'     => 'required',
            'new_password'     => 'required|min_length[8]|regex_match[/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+]{8,}$/]',
            'confirm_password' => 'required|matches[new_password]'
        ];

        $messages = [
            'new_password' => [
                'regex_match' => 'Password must contain at least one uppercase letter, one number, and be at least 8 characters long.'
            ]
        ];

        if (!$this->validateData($input, $rules, $messages)) { 
            $errors = $this->validator->getErrors();
            log_message('error', 'Password Update Validation Errors: ' . json_encode($errors));
            return $this->failValidationErrors($errors);
        }

        $user = $this->userModel->find($userId);

        if (!$user) {
            log_message('error', 'ProfileController: User with ID ' . $userId . ' not found during password update.');
            return $this->failNotFound('User not found.');
        }

        if (!password_verify($input['old_password'], $user->password)) {
            return $this->failValidationError('Old password does not match.');
        }

        $user->password = $input['new_password'];

        if ($this->userModel->save($user)) {
            $response = [
                'status'  => 200,
                'error'   => false,
                'messages' => [
                    'success' => 'Password updated successfully.'
                ]
            ];
            return $this->respond($response);
        } else {
            $modelErrors = $this->userModel->errors();
            if (!empty($modelErrors)) {
                log_message('error', 'User Model Password Update Errors: ' . json_encode($modelErrors));
                return $this->failValidationErrors($modelErrors);
            }
            return $this->fail('Failed to update password. Internal server error.', 500);
        }
    }

}
