<?php

namespace App\Controllers\Api\Auth;

use App\Controllers\BaseController;
use App\Entities\User; 
use App\Models\UserModel; 
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Database\Exceptions\DatabaseException; // Untuk menangani error database
use ReflectionException;  

class RegisterController extends BaseController
{
    use ResponseTrait;

    /**
     * Handles user registration via POST request.
     * POST /api/register
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function register(): ResponseInterface
    {
        // 1. Define validation rules for the incoming request data.
        $rules = [
            'name'                  => 'required|min_length[3]|max_length[255]',
            'email'                 => 'required|valid_email|is_unique[users.email]', // Pastikan tabel 'users' dan kolom 'email' benar
            'password'              => 'required|min_length[8]',
            'password_confirmation' => 'required|matches[password]'
        ];

        // 2. Validate the request input against the defined rules.
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // 3. Retrieve the validated input data from the request.
        $data = $this->request->getJSON(true);

        // 4. Instantiate UserModel and User Entity.
        $userModel = new UserModel();
        $user = new User();

        // 5. Populate User Entity properties.
        $user->name     = $data['name'];
        $user->email    = $data['email'];
        $user->password = $data['password'];
        $user->role     = 'user';

        // 6. Attempt to save the user data to the database with basic error handling.
        try {
            if (!$userModel->save($user)) {
                log_message('error', 'User model save returned false for email: ' . $user->email);
                return $this->failServerError('Failed to create user due to an unknown error. Please try again.');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error saving user: ' . $e->getMessage()); // Log error untuk debugging
            return $this->failServerError('An unexpected error occurred during registration. Please try again later.');
        }

        // 7. Prepare successful response data (excluding sensitive information like password).
        $userData = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role,
            // Check if created_at is a DateTime/Time object and convert to string
            'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
        ];

        // 8. Return a 201 Created response for successful registration
        return $this->respondCreated([
            'status'  => 201,
            'error'   => false, // Indicates a successful response, not an error
            'message' => 'User registered successfully!',
            'data'    => $userData
        ]);
        
    }
}