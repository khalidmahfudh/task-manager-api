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
}
