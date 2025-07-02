<?php

namespace App\Controllers\Api\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\AuthService;
use CodeIgniter\Api\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class LoginController extends BaseController
{
    use ResponseTrait;

    protected $userModel;
    protected $authService;
    protected $cache;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->authService = new AuthService();
        $this->cache = Services::cache();
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

     /**
     * Handles user logout via POST request.
     * This endpoint invalidates the current JWT token by adding it to a blacklist.
     * Requires a valid JWT token in the Authorization header.
     *
     * Endpoint: POST /api/logout
     *
     * @return ResponseInterface The HTTP response indicating successful logout.
     */
    public function logout(): ResponseInterface
    {
        // 1. Ekstrak token JWT dari header Authorization.
        // Kita harus mengambil token dari request karena filter sudah berjalan
        // dan kita butuh token aslinya untuk di-blacklist.
        $header = $this->request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $token = $matches[1];
        }

        // 2. Verifikasi keberadaan token (seharusnya sudah ada karena melewati filter jwtAuth).
        // Ini adalah double-check, filter sudah seharusnya menangani ketiadaan token.
        if (empty($token)) {
            return $this->failUnauthorized('No token provided.');
        }

        try {
            // 3. Validasi token (lagi) untuk mendapatkan expiration time.
            // Kita butuh 'exp' (expiration time) dari payload untuk menentukan
            // berapa lama token akan berada di blacklist.
            $decodedToken = $this->authService->validateToken($token);
            $expirationTime = $decodedToken->exp; // Ambil expiration time dari payload

            // 4. Hitung sisa waktu validasi token.
            // Token akan di-blacklist untuk sisa masa berlakunya.
            $timeToLive = $expirationTime - time();

            // Pastikan TTL tidak negatif (jika token sudah expired tapi masih dipakai request ini)
            if ($timeToLive <= 0) {
                // Jika token sudah expired, tidak perlu di-blacklist.
                // Cukup berikan respons sukses logout dari sisi client.
                return $this->respondDeleted([
                    'status' => 200,
                    'error' => false,
                    'message' => 'Logged out successfully (token already expired).'
                ]);
            }

            // 5. Tambahkan token ke blacklist (gunakan cache CI4).
            // Key blacklist bisa berupa hash token atau token itu sendiri.
            // Simpan token di cache dengan masa berlaku yang sama dengan sisa masa berlakunya.
            $blacklistKey = 'blacklist_jwt_' . md5($token); // Gunakan hash untuk key cache
            $this->cache->save($blacklistKey, 1, $timeToLive); // Simpan nilai arbitrer (misal 1)

            // 6. Kembalikan respons sukses.
            return $this->respondDeleted([
                'status' => 200,
                'error' => false,
                'message' => 'Logged out successfully. Token invalidated.'
            ]);

        } catch (\Exception $e) {
            // Tangani kasus jika token tidak valid atau ada masalah saat memproses logout
            // (misalnya token sudah expired sebelum sampai sini, atau invalid signature, dll.)
            log_message('error', 'Logout failed for token: ' . $token . '. Error: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            return $this->failUnauthorized('Failed to logout. Invalid token or server error: ' . $e->getMessage());
        }
    }
}
