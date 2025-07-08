<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use Config\Services;

class AdminFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Ambil user ID yang sudah diinjeksi oleh JWTAuthFilter
        $userId = (int) $request->user_id;

        // Jika user ID tidak ada, berarti pengguna tidak terautentikasi atau ada masalah dengan JWTAuthFilter.
        if (empty($userId)) { // Gunakan empty() untuk cek 0 atau null
            log_message('error', 'AdminFilter: User ID not found in request context. Is JWTAuthFilter running first?');
            // Menggunakan Services::response() masih diperlukan di sini untuk mengembalikan objek Response
            return Services::response()->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                                      ->setJSON(['status' => 401, 'error' => true, 'messages' => ['error' => 'Authentication required.']]);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        // Cek apakah user ada dan memiliki role 'admin'
        if (!$user || $user->role !== 'admin') {
            log_message('warning', 'AdminFilter: Access denied for user ID ' . $userId . ' (Role: ' . ($user ? $user->role : 'N/A') . ').');
            return Services::response()->setStatusCode(ResponseInterface::HTTP_FORBIDDEN) // 403 Forbidden
                                      ->setJSON(['status' => 403, 'error' => true, 'messages' => ['error' => 'Access denied. Administrator privileges required.']]);
        }

        // Jika role adalah 'admin', lanjutkan ke controller
        return;
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
