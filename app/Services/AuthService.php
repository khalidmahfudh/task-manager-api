<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception; // Use generic Exception for simpler handling for now

class AuthService
{
    private string $secretKey;
    private int $expirationSeconds;
    private string $algorithm; // Typically 'HS256'

    public function __construct()
    {
        // Ambil secret key dari .env
        $this->secretKey = getenv('JWT_SECRET');
        if (empty($this->secretKey)) {
            throw new Exception('JWT_SECRET not set in .env file.');
        }

        // Ambil expiration time dari .env, default 1 jam jika tidak ada
        $this->expirationSeconds = (int)getenv('JWT_EXPIRATION_SECONDS') ?: 3600;
        $this->algorithm = 'HS256'; // Algoritma standar untuk JWT dengan secret key
    }

    /**
     * Generates a new JWT token.
     *
     * @param array $payloadData Data to be encoded in the token (e.g., user_id, role).
     * @return string The generated JWT.
     * @throws Exception If token generation fails.
     */
    public function generateToken(array $payloadData): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->expirationSeconds;

        $payload = array_merge($payloadData, [
            'iat' => $issuedAt, // Issued At: waktu token dibuat
            'exp' => $expirationTime, // Expiration Time: waktu token kedaluwarsa
            'nbf' => $issuedAt, // Not Before: waktu token mulai valid (sama dengan iat)
            'iss' => base_url(), // Issuer: URL aplikasi Anda
            // 'aud' => 'your_app_audience', // Audience: siapa yang menggunakan token
        ]);

        try {
            return JWT::encode($payload, $this->secretKey, $this->algorithm);
        } catch (Exception $e) {
            // Log the actual error for debugging in production
            log_message('error', 'JWT token generation failed: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            throw new Exception('Failed to generate authentication token.');
        }
    }

    /**
     * Validates a JWT token and returns its decoded payload.
     *
     * @param string $token The JWT string to decode and validate.
     * @return object The decoded JWT payload as an object.
     * @throws Exception If the token is invalid, expired, or cannot be decoded.
     */
    public function validateToken(string $token): object
    {
        try {
            // Pastikan untuk menggunakan algoritma yang sama saat decode
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            log_message('error', 'Invalid JWT signature: ' . $e->getMessage());
            throw new Exception('Invalid token signature. Authentication failed.');
        } catch (\Firebase\JWT\ExpiredException $e) {
            log_message('error', 'Expired JWT token: ' . $e->getMessage());
            throw new Exception('Your authentication token has expired. Please log in again.');
        } catch (\Firebase\JWT\BeforeValidException $e) {
            log_message('error', 'JWT token not yet valid: ' . $e->getMessage());
            throw new Exception('Authentication token not yet valid.');
        } catch (Exception $e) {
            // Catch any other generic exceptions during decoding
            log_message('error', 'JWT token decoding failed: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
            throw new Exception('Invalid or malformed authentication token.');
        }
    }
}