<?php

namespace App\Filters;

use App\Services\AuthService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class JWTAuthFilter implements FilterInterface
{
    protected $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

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
        // 1. Ekstrak Token JWT dari Header Authorization.
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        // 2. Periksa apakah Token ditemukan.
        if (empty($token)) {
            return service('response')->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setJSON(['status' => 401, 'error' => true, 'message' => 'Authentication required. Token not provided.']);
        }

        // 3. Validasi Token menggunakan AuthService.
        try {
            // Panggil AuthService untuk memvalidasi token.
            $decodedToken = $this->authService->validateToken($token);

            // Jika validasi berhasil, ambil data dari decodedToken
            // dan menyimpannya ke dalam request agar bisa diakses oleh controller.
            // Agar controller tidak perlu memvalidasi ulang token.
            $request->user_id = $decodedToken->user_id;
            $request->user_email = $decodedToken->email;
            $request->user_role = $decodedToken->role;

            // $request->decoded_token = $decodedToken;

            // Lanjutkan ke controller jika token valid.
            return;

        } catch (Exception $e) {
            // Jika AuthService melempar exception (token tidak valid, kedaluwarsa, dll.)
            return service('response')->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                                     ->setJSON(['status' => 401, 'error' => true, 'message' => $e->getMessage()]);
        }
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
