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
    
}
