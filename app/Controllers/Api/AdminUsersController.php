<?php

namespace App\Controllers\Api;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * Class AdminController
 * @package App\Controllers\Api
 *
 * @property object $request 
 */
class AdminUsersController extends ResourceController
{
    use ResponseTrait;

    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
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
            $users = $this->userModel->findAll(); // Mengambil semua data pengguna

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
            $user = $this->userModel->find($id);

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
     * Create a new user (Admin Only).
     * Endpoint: POST /api/admin/users
     * Requires: JWT authentication and 'admin' role.
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJson(true);

        if ($input === null || !is_array($input)) {
            return $this->failValidationError('Invalid JSON body provided. Please ensure it is valid JSON.');
        }

        // Aturan validasi untuk membuat user baru oleh admin
        $rules = [
            'name'             => 'required|alpha_space|min_length[3]|max_length[255]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]|regex_match[/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+]{8,}$/]',
            'password_confirm' => 'required|matches[password]',
            'role'             => 'required|in_list[admin,user]', // Admin dapat menentukan role
        ];

        $messages = [
            'name' => [
                'required'    => 'Name is required.',
                'alpha_space' => 'Name can only contain alphabetic characters and spaces.',
                'min_length'  => 'Name must be at least 3 characters long.',
                'max_length'  => 'Name cannot exceed 255 characters.',
            ],
            'email' => [
                'required'    => 'Email is required.',
                'valid_email' => 'Please enter a valid email address.',
                'is_unique'   => 'Sorry, that email has already been taken.',
            ],
            'password' => [
                'required'    => 'Password is required.',
                'min_length'  => 'Password must be at least 8 characters long.',
                'regex_match' => 'Password must contain at least one uppercase letter, one number, and be at least 8 characters long.',
            ],
            'password_confirm' => [
                'required' => 'Password confirmation is required.',
                'matches'  => 'Password confirmation does not match the password.',
            ],
            'role' => [
                'required'  => 'Role is required.',
                'in_list'   => 'Role must be either "admin" or "user".',
            ],
        ];

        // Validasi input
        if (!$this->validate($rules, $messages)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'AdminController Create User Validation Errors: ' . json_encode($errors));
            return $this->failValidationErrors($errors);
        }

        // Buat objek user baru
        $user = new User();
        $user->fill($input);

        // Hapus password_confirm sebelum menyimpan ke database
        unset($user->password_confirm);

        try {
            if ($this->userModel->save($user)) {
                // Ambil ID yang baru di-insert dari model
                $newlyInsertedId = $this->userModel->getInsertID();

                // Ambil ulang user dari database menggunakan ID yang baru di-insert.
                $createdUser = $this->userModel->find($newlyInsertedId);

                // Pastikan $createdUser tidak null sebelum diakses
                if ($createdUser === null) {
                    // Handle error: user not found after insertion (sangat jarang)
                    log_message('error', 'Failed to retrieve newly created user with ID: ' . $newlyInsertedId);
                    return $this->failServerError('Failed to retrieve created user data.');
            }

                $filteredUser = [
                    'id'         => $createdUser->id, 
                    'name'       => $createdUser->name,
                    'email'      => $createdUser->email,
                    'role'       => $createdUser->role,
                    'created_at' => $createdUser->created_at ? $createdUser->created_at->toDateTimeString() : null,
                    'updated_at' => $createdUser->updated_at ? $createdUser->updated_at->toDateTimeString() : null,
                ];

                return $this->respondCreated([
                    'status'  => 201, // 201 Created
                    'error'   => false,
                    'message' => 'User created successfully.',
                    'data'    => $filteredUser
                ]);
            } else {
                $modelErrors = $this->userModel->errors();
                if (!empty($modelErrors)) {
                    log_message('error', 'User Model Create Errors: ' . json_encode($modelErrors));
                    return $this->failValidationErrors($modelErrors);
                }
                return $this->fail('Failed to create user. Internal server error.', 500);
            }
        } catch (\Exception $e) {
            log_message('error', 'AdminController: Failed to create user. ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to create user. Please try again later.');
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

        $user = $this->userModel->find($id);

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

        if ($this->userModel->save($user)) {
            // Ambil ulang user untuk mendapatkan data terbaru dan ter-filter
            $updatedUser = $this->userModel->find($user->id);
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
            $modelErrors = $this->userModel->errors();
            if (!empty($modelErrors)) {
                log_message('error', 'User Model Update Errors: ' . json_encode($modelErrors));
                return $this->failValidationErrors($modelErrors);
            }
            return $this->fail('Failed to update user. Internal server error.', 500);
        }
    }

    /**
     * Delete a user by ID (Admin Only).
     * Endpoint: DELETE /api/admin/users/{id}
     * Requires: JWT authentication and 'admin' role.
     *
     * @param int|string|null $id The user ID.
     * @return ResponseInterface
     */
    public function delete($id = null): ResponseInterface
    {
        // 1. Validasi ID yang diberikan
        if ($id === null) {
            return $this->failValidationError('User ID is required.');
        }

        // 2. Cari pengguna yang akan dihapus
        $userToDelete = $this->userModel->find($id);

        if ($userToDelete === null) {
            return $this->failNotFound('User not found.');
        }

        // --- Pencegahan Admin Menghapus Akunnya Sendiri ---
        $currentAdminId = $this->request->user_id; // Mengambil ID admin yang sedang login

        if ($id == $currentAdminId) {
            return $this->failForbidden('You cannot delete your own admin account.');
        }

        // 3. Lakukan Soft Delete
        try {
            // Metode delete() pada Model dengan useSoftDeletes = true akan mengisi deleted_at
            if ($this->userModel->delete($id)) {
                return $this->respondDeleted([ // Menggunakan respondDeleted untuk status 200 OK atau 204 No Content (opsional)
                    'status'  => 200, // Atau 204 No Content jika tidak ada body respons
                    'error'   => false,
                    'message' => 'User successfully deleted (soft deleted).',
                    'data'    => ['id' => $id] // Mengembalikan ID user yang dihapus
                ]);
            } else {
                // Jika delete() mengembalikan false (misalnya karena validasi model atau masalah DB lain)
                $errors = $this->userModel->errors();
                if (!empty($errors)) {
                    return $this->failValidationErrors($errors); // Mengembalikan error validasi model jika ada
                }
                log_message('error', 'User soft delete failed for ID: ' . $id . ' with no specific errors.');
                return $this->failServerError('Failed to delete user due to an unknown error. Please try again.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception during user deletion for ID ' . $id . ': ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('An unexpected error occurred during user deletion. Please try again later.');
        }
    }
}
