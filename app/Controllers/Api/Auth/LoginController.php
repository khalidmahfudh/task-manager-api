<?php

namespace App\Controllers\Api\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\AuthService;
use CodeIgniter\Api\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

class LoginController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $authService;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->authService = new AuthService();
    }

    /**
     * Handles user login via POST request.
     * Endpoint: POST /api/login
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function login(): ResponseInterface
    {
        // 1. Define validation rules for the incoming JSON request data.
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required'
        ];

        // 2. Perform validation on the input data.
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // 3. Retrieve and parse the input data from the JSON request body.
        $data = $this->request->getJSON(true);

        // 4. Attempt to find the user by email in the database.
        $user = $this->userModel->where('email', $data['email'])->first();

        // 5. Verify user existence and password.
        if (!$user || !password_verify($data['password'], $user->password)) {
            log_message('warning', 'Login attempt failed for email: ' . $data['email']);
            return $this->failUnauthorized('Invalid email or password.');
        }

        // 6. Generate JWT token and return success response.
        try {
            $payloadData = [
                'user_id' => $user->id,
                'email'   => $user->email,
                'role'    => $user->role, 
            ];

            $token = $this->authService->generateToken($payloadData);

            $userData = [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
            ];

            return $this->respond([
                'status'  => 200,
                'error'   => false,
                'message' => 'Login successful!',
                'data'    => [
                    'user'  => $userData,
                    'token' => $token
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error generating token for user ' . $user->email . ': ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failServerError('Failed to generate authentication token.');
        }

    }
}
