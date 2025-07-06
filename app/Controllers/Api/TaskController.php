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
     * Get a single task by its ID for the authenticated user.
     * GET /api/tasks/{id}
     * 
     * @param int|string|null $id
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface
    {
        // Ambil ID user dari token JWT yang sudah diverifikasi oleh filter
        $userId = $this->request->user_id;

        if (!$userId) {
            return $this->failUnauthorized('User not authenticated.');
        }

        // Cari tugas berdasarkan ID dan pastikan itu milik user yang sedang login
        $task = $this->model->where('id', $id)
                            ->where('user_id', $userId)
                            ->first(); // Gunakan first() untuk mendapatkan satu record

        if (!$task) {
            // Jika tugas tidak ditemukan atau bukan milik user ini
            return $this->failNotFound('Task not found or not accessible.');
        }

        $response = [
            'status'  => 200,
            'error'   => null,
            'messages' => [
                'success' => 'Task retrieved successfully.'
            ],
            'data'    => $task
        ];

        return $this->respond($response);
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
     * Create a new task.
     * POST /api/tasks
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface
    {

        // Ambil ID user dari token JWT yang sudah diverifikasi oleh filter
        $userId = $this->request->user_id;

        if (!$userId) {
            return $this->failUnauthorized('User not authenticated.');
        }

        // Dapatkan data JSON dari request body
        $input = $this->request->getJson(true); // true untuk mendapatkan array asosiatif

        // Tambahkan user_id ke data input sebelum validasi dan penyimpanan
        // Ini memastikan tugas terkait dengan user yang benar
        $input['user_id'] = $userId;

        $rules = [
            'user_id'     => 'required|integer',
            'title'       => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty|max_length[1000]',
            'status'      => 'permit_empty|in_list[pending,in-progress,completed]',
            'due_date'    => 'permit_empty|valid_date[Y-m-d H:i:s]' // Pastikan format tanggal sesuai
        ];

        if (!$this->validateData($input, $rules)) {
            // Jika validasi gagal, kembalikan pesan error
            $errors = $this->validator->getErrors(); // validator properti sudah tersedia
            log_message('error', 'Task Creation Validation Errors: ' . json_encode($errors));
            return $this->failValidationErrors($errors);
        }

        // Buat Task Entity baru dari data input
        $task = new Task($input);

        // Simpan task ke database
        if ($this->model->save($task)) {
            // Ambil kembali task yang baru disimpan dari DB untuk mendapatkan ID dan timestamps
            $newTask = $this->model->find($this->model->getInsertID());

            $response = [
                'status'  => 201, // 201 Created
                'error'   => null,
                'messages' => [
                    'success' => 'Task created successfully.'
                ],
                'data'    => $newTask // Mengembalikan entity Task yang sudah lengkap
            ];
            return $this->respondCreated($response);
        } else {
            $modelErrors = $this->model->errors();
            if (!empty($modelErrors)) {
                log_message('error', 'Task Model Save Errors: ' . json_encode($modelErrors));
                return $this->failValidationErrors($modelErrors);
            }
            return $this->fail('Failed to create task.', 500);
        }
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
