<?php

namespace App\Controllers\Api;

use App\Entities\Task;
use App\Models\TaskModel;
use App\Models\UserModel; 
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * Class TaskController
 * @package App\Controllers\Api
 *
 * @property object $request 
 */
class TaskController extends ResourceController
{
    use ResponseTrait;

    protected $model;
    protected $userModel;

    public function __construct() {
        $this->model = new TaskModel();
        $this->userModel = new UserModel();
    }

    /**
     * Get all tasks for the authenticated user.
     * GET /api/tasks
     * 
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        // Ambil ID user dari token JWT yang sudah diverifikasi oleh filter
        // ID user ini disimpan di request saat filter JWT berhasil.
        $userId = $this->request->user_id; // Properti user_id di request berasal dari AuthFilter

        if (!$userId) {
            return $this->failUnauthorized('User not authenticated.');
        }

        // Dapatkan tugas-tugas milik user tersebut
        $tasks = $this->model->where('user_id', $userId)->findAll();

        if (empty($tasks)) {
            return $this->respondNoContent('No tasks found for this user.');
        }

        // Siapkan respons data
        $response = [
            'status'  => 200,
            'error'   => false,
            'messages' => [
                'success' => 'Tasks retrieved successfully.'
            ],
            'data'    => $tasks
        ];

        return $this->respond($response);
    }

    /**
     * Return the properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties.
     *
     * @return ResponseInterface
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters.
     *
     * @return ResponseInterface
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model.
     *
     * @param int|string|null $id
     *
     * @return ResponseInterface
     */
    public function delete($id = null)
    {
        //
    }
}
