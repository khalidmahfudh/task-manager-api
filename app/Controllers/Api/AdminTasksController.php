<?php

namespace App\Controllers\Api;

use App\Entities\Task;
use App\Models\TaskModel;
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
class AdminTasksController extends ResourceController
{
    use ResponseTrait;

    protected $taskModel;
    protected $userModel;

    public function __construct()
    {
        $this->taskModel = new TaskModel();
        $this->userModel = new UserModel();
    }

    /**
     * Get all tasks in the system (Admin Only)
     * Endpoint: GET /api/admin/tasks
     * Requires: JWT authentication and 'admin' role.
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        try {
            // Ambil semua tugas dari database
            $tasks = $this->taskModel->findAll();

            // Format data tugas untuk respons API
            $formattedTasks = array_map(function($task) {
                return [
                    'id'          => $task->id,
                    'title'       => $task->title,
                    'description' => $task->description,
                    'user_id'     => $task->user_id, // Penting: Admin bisa melihat siapa pemilik tugas
                    'status'      => $task->status,
                    'created_at'  => $task->created_at ? $task->created_at->toDateTimeString() : null,
                    'updated_at'  => $task->updated_at ? $task->updated_at->toDateTimeString() : null,
                    'deleted_at'  => $task->deleted_at ? $task->deleted_at->toDateTimeString() : null, // Tampilkan jika soft delete aktif
                ];
            }, $tasks);

            // Kembalikan respons sukses
            if (empty($formattedTasks)) {
                return $this->respondNoContent('No tasks found in the system.'); // 204 No Content jika tidak ada tugas
            }

            return $this->respond([
                'status'  => 200,
                'error'   => false,
                'message' => 'All tasks retrieved successfully.',
                'data'    => $formattedTasks
            ]);
        } catch (\Exception $e) {
            log_message('error', 'AdminController: Failed to retrieve all tasks. ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to retrieve tasks. Please try again later.');
        }
    }

    /**
     * Get a single task by ID (Admin Only).
     * Endpoint: GET /api/admin/tasks/{id}
     * Requires: JWT authentication and 'admin' role.
     *
     * @param int|string|null $id The task ID.
     * @return ResponseInterface
     */
    public function show($id = null): ResponseInterface // Menggunakan 'show' karena ini ResourceController untuk tasks
    {
        // 1. Validasi ID tugas
        if (empty($id)) {
            return $this->failValidationError('Task ID is required.');
        }

        try {
            // 2. Cari tugas berdasarkan ID
            // Gunakan withDeleted() jika admin bisa melihat tugas yang sudah di-soft delete
            $task = $this->taskModel->find($id);

            // 3. Cek apakah tugas ditemukan
            if (!$task) {
                return $this->failNotFound('Task with ID ' . $id . ' not found.');
            }

            // 4. Filter dan format data tugas untuk respons API
            $filteredTask = [
                'id'          => $task->id,
                'title'       => $task->title,
                'description' => $task->description,
                'user_id'     => $task->user_id, // Admin bisa melihat pemilik tugas
                'status'      => $task->status,
                'created_at'  => $task->created_at ? $task->created_at->toDateTimeString() : null,
                'updated_at'  => $task->updated_at ? $task->updated_at->toDateTimeString() : null,
                'due_date'    => $task->due_date,
                'deleted_at'  => $task->deleted_at ? $task->deleted_at->toDateTimeString() : null, // Tampilkan jika soft delete aktif
            ];

            // 5. Kembalikan respons sukses
            return $this->respond([
                'status'  => 200,
                'error'   => false,
                'message' => 'Task retrieved successfully.',
                'data'    => $filteredTask
            ]);
        } catch (\Exception $e) {
            log_message('error', 'AdminTasksController: Failed to retrieve task by ID. ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to retrieve task. Please try again later.');
        }
    }

    /**
     * Create a new task (Admin Only).
     * Endpoint: POST /api/admin/tasks
     * Requires: JWT authentication and 'admin' role.
     *
     * @return ResponseInterface
     */
    public function create(): ResponseInterface 
    {
        // 1. Ambil data input dari request
        $input = $this->request->getJson(true);

        if ($input === null || !is_array($input)) {
            return $this->failValidationError('Invalid JSON body provided. Please ensure it is valid JSON.');
        }

        // 2. Definisikan aturan validasi untuk membuat tugas baru oleh admin
        $rules = [
            'user_id'     => 'required|integer|is_not_unique[users.id]', // <-- user_id harus ada dan valid
            'title'       => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty',
            'status'      => 'required|in_list[pending,in-progress,completed]',
            'due_date'    => 'permit_empty|valid_date',
        ];

        $messages = [
            'user_id' => [
                'required'      => 'User ID is required.',
                'integer'       => 'User ID must be an integer.',
                'is_not_unique' => 'The provided User ID does not exist.'
            ],
            'title' => [
                'required'   => 'Title is required.',
                'min_length' => 'Title must be at least 3 characters long.',
                'max_length' => 'Title cannot exceed 255 characters.',
            ],
            'status' => [
                'required' => 'Status is required.',
                'in_list'  => 'Status must be one of: pending, in-progress, or completed.',
            ],
            'due_date' => [
                'valid_date' => 'Due date must be a valid date format (YYYY-MM-DD HH:MM:SS).'
            ],
        ];

        // 3. Validasi input
        if (!$this->validate($rules, $messages)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'AdminTasksController Create Task Validation Errors: ' . json_encode($errors));
            return $this->failValidationErrors($errors);
        }

        // 4. Buat objek Task Entity baru dan isi dengan data input
        $task = new Task();
        $task->fill($input);

        // 5. Lakukan penyimpanan ke database
        try {
            if ($this->taskModel->save($task)) {
                // Ambil ID yang baru di-insert dari model
                $newlyInsertedId = $this->taskModel->getInsertID();

                // Ambil ulang task dari database menggunakan ID yang baru di-insert
                $createdTask = $this->taskModel->find($newlyInsertedId);

                // Pastikan $createdTask tidak null
                if ($createdTask === null) {
                    log_message('error', 'Failed to retrieve newly created task with ID: ' . $newlyInsertedId);
                    return $this->failServerError('Task created, but failed to retrieve created data.');
                }

                // 6. Siapkan respons data
                $filteredTask = [
                    'id'          => $createdTask->id,
                    'title'       => $createdTask->title,
                    'description' => $createdTask->description,
                    'user_id'     => $createdTask->user_id,
                    'status'      => $createdTask->status,
                    'created_at'  => $createdTask->created_at ? $createdTask->created_at->toDateTimeString() : null,
                    'updated_at'  => $createdTask->updated_at ? $createdTask->updated_at->toDateTimeString() : null,
                    'due_date'    => $createdTask->due_date,
                    'deleted_at'  => $createdTask->deleted_at ? $createdTask->deleted_at->toDateTimeString() : null, // Akan null, tapi konsisten dengan format
                ];

                // 7. Kembalikan respons sukses (201 Created)
                return $this->respondCreated([
                    'status'  => 201, // 201 Created
                    'error'   => false,
                    'message' => 'Task created successfully.',
                    'data'    => $filteredTask
                ]);
            } else {
                // Jika save() mengembalikan false (misalnya karena validasi model tambahan)
                $modelErrors = $this->taskModel->errors();
                if (!empty($modelErrors)) {
                    log_message('error', 'Task Model Create Errors: ' . json_encode($modelErrors));
                    return $this->failValidationErrors($modelErrors);
                }
                return $this->fail('Failed to create task. Internal server error.', 500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception during task creation: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('An unexpected error occurred during task creation. Please try again later.');
        }
    }
}
