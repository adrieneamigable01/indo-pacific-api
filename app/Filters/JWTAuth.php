<?php

namespace App\Filters;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Exception;

class JWTAuth implements FilterInterface
{
    use ResponseTrait;
    protected $db;
    public function __construct()
    {
        // initialize helper
        helper('jwt');
        if ($this->db === null) {
            $this->db = db_connect();
        }
    }

    /**
     * Checks if the token is valid AND not blacklisted.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authenticationHeader = $request->getServer('HTTP_AUTHORIZATION');

        try {
            // 1. Extract the token from the header
            $encodedToken = getJWTFromRequest($authenticationHeader);

            // 2. CHECK THE BLACKLIST TABLE
            // We do this before heavy validation to save resources if already logged out.


            $isBlacklisted = $this->db->table('token_blacklist')
                ->where('token', $encodedToken)
                ->get()
                ->getRow();

            if ($isBlacklisted) {
                throw new Exception('Token has been revoked. Please login again.');
            }

            // 3. Validate the JWT (Signature, Expiration, etc.)
            validateJWTFromRequest($encodedToken);

            return $request;
        } 
        catch (Exception $ex) {
            return Services::response()
                ->setJSON(
                    [
                        'isError' => true,
                        'message' => 'Unauthorized access',
                        'error'   => $ex->getMessage()
                    ]
                )
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}