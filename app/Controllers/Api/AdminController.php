<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AdminController extends ResourceController
{
    use ResponseTrait;

    protected $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    /**
     * Get all users (Admin Only).
     * Endpoint: GET /api/admin/users
     * Requires: JWT authentication and 'admin' role.
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            $users = $this->model->findAll(); // Mengambil semua data pengguna

            // Memfilter data sensitif seperti password sebelum mengirim respons
            $filteredUsers = array_map(function($user) {
                    return [
                        'id'         => $user->id,
                        'name'       => $user->name,
                        'email'      => $user->email,
                        'role'       => $user->role,
                        'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
                        'updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
                    ];

            }, $users);

            return $this->respond([
                'status'  => 200,
                'error'   => false,
                'message' => 'Users retrieved successfully.',
                'data'    => $filteredUsers
            ]);
        } catch (\Exception $e) {
            log_message('error', 'AdminController: Failed to retrieve users. ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to retrieve users. Please try again later.');
        }
    }

     /**
     * Get a single user by ID (Admin Only).
     * Endpoint: GET /api/admin/users/{id}
     * Requires: JWT authentication and 'admin' role.
     *
     * @param int|string|null $id The user ID.
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        if (empty($id)) {
            return $this->failValidationErrors('User ID is required.');
        }

        try {
            $user = $this->model->find($id);

            if (!$user) {
                return $this->failNotFound('User with ID ' . $id . ' not found.');
            }

            // Filter data sensitif seperti password sebelum mengirim respons
            $filteredUser = [
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
                'message' => 'User retrieved successfully.',
                'data'    => $filteredUser
            ]);
        } catch (\Exception $e) {
            log_message('error', 'AdminController: Failed to retrieve user by ID. ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to retrieve user. Please try again later.');
        }
    }

     /**
     * Update an existing user by ID (Admin Only).
     * Endpoint: PUT/PATCH /api/admin/users/{id}
     * Requires: JWT authentication and 'admin' role.
     *
     * @param int|string|null $id The user ID.
     * @return ResponseInterface
     */
    public function update($id = null): ResponseInterface
    {
        if (empty($id)) {
            return $this->failValidationErrors('User ID is required for update.');
        }

        $user = $this->model->find($id);

        if (!$user) {
            return $this->failNotFound('User with ID ' . $id . ' not found.');
        }

        $input = $this->request->getJson(true);

        if ($input === null || !is_array($input)) {
            return $this->failValidationError('Invalid JSON body provided. Please ensure it is valid JSON.');
        }

        // Aturan validasi untuk update user
        // 'email' di sini menggunakan 'permit_empty' karena mungkin tidak semua field diupdate
        // 'is_unique' hanya perlu dicek jika email berubah DAN bukan email milik user itu sendiri
        $rules = [
            'name'  => 'permit_empty|alpha_space|min_length[3]|max_length[255]',
            'email' => [
                'label'  => 'Email',
                'rules'  => "permit_empty|valid_email|is_unique[users.email,id,{$id}]",
                'errors' => [
                    'is_unique' => 'Sorry, that email has already been taken.',
                ],
            ],
            'role'  => 'permit_empty|in_list[admin,user]', // Asumsi role hanya 'admin' atau 'user'
        ];

        // Tambahkan validasi password jika password juga disertakan dalam request
        if (isset($input['password']) && !empty($input['password'])) {
            $rules['password'] = 'min_length[8]|regex_match[/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+]{8,}$/]';
            $messages['password'] = [
                'regex_match' => 'Password must contain at least one uppercase letter, one number, and be at least 8 characters long.'
            ];
        }

        // Periksa jika ada konfirmasi password tanpa password utama
        if (isset($input['password_confirmation']) && !isset($input['password'])) {
            return $this->failValidationError('Password confirmation provided without a new password.');
        }
        // Tambahkan validasi password_confirmation jika password_confirmation juga disertakan
        if (isset($input['password_confirmation']) && !empty($input['password_confirmation'])) {
            $rules['password_confirmation'] = 'matches[password]';
            $messages['password_confirmation'] = [
                'matches' => 'Password confirmation does not match the new password.'
            ];
        }


        // Validasi input
        if (!$this->validate($rules, $messages ?? [])) { // $messages akan null jika tidak ada password
            $errors = $this->validator->getErrors();
            log_message('error', 'AdminController Update User Validation Errors: ' . json_encode($errors));
            return $this->failValidationErrors($errors);
        }

        // Isi objek user dengan data yang di-input
        // Method fill() dari Entity akan otomatis mengabaikan field yang tidak ada di $input
        // dan akan menangani hashing password jika properti password di Entity diatur dengan Mutator.
        $user->fill($input);

        // Jika password diubah, pastikan password_confirmation diabaikan oleh Entity
        if (isset($user->password_confirmation)) {
            unset($user->password_confirmation);
        }

        if ($this->model->save($user)) {
            // Ambil ulang user untuk mendapatkan data terbaru dan ter-filter
            $updatedUser = $this->model->find($user->id);
            $filteredUser = [
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
                'message' => 'User updated successfully.',
                'data'    => $filteredUser
            ]);
        } else {
            $modelErrors = $this->model->errors();
            if (!empty($modelErrors)) {
                log_message('error', 'User Model Update Errors: ' . json_encode($modelErrors));
                return $this->failValidationErrors($modelErrors);
            }
            return $this->fail('Failed to update user. Internal server error.', 500);
        }
    }
}
