<?php

namespace App\Controllers\Api;

use App\Models\TaskModel;
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

    public function __construct()
    {
        $this->taskModel = new TaskModel();
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
}
