<?php

namespace App\Controllers\Api; // Atau App\Controllers\Api\Protected

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class ProtectedController extends BaseController
{
    use ResponseTrait;

    public function index(): ResponseInterface
    {
        // Pastikan data user yang di-set oleh filter bisa diakses
        $userId = $this->request->user_id ?? 'N/A';
        $userEmail = $this->request->user_email ?? 'N/A';
        $userRole = $this->request->user_role ?? 'N/A';

        return $this->respond([
            'status' => 200,
            'error' => false,
            'message' => 'You have accessed a protected endpoint!',
            'data' => [
                'userId' => $userId,
                'userEmail' => $userEmail,
                'userRole' => $userRole,
                'note' => 'This data comes from the JWT payload after filter validation.'
            ]
        ]);
    }
}