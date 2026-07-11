<?php

use App\Models\UserModel;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use CodeIgniter\HTTP\RequestInterface; 


function validateJWT(string $encodedToken)
{
    // Retrieve the secret key from CodeIgniter's configuration
    $secretKey = getJwtSecretKey();
    
    if (empty($secretKey)) {
        throw new Exception("JWT_SECRET_KEY not configured.");
    }

    try {
        // Decode the token. The 'HS256' algorithm must match what you used for signing.
        // Replace 'HS256' if you use a different algorithm.
        $decoded = JWT::decode($encodedToken, new Key($secretKey, 'HS256'));
        
        // Return the payload object
        return $decoded;
    
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception('Token has expired.');
    } catch (\Exception $e) {
        // Handle other decoding errors (e.g., invalid signature)
        throw new Exception('Invalid token: ' . $e->getMessage());
    }
}

function getJWTFromRequest($authenticationHeader): string
{
    if (is_null($authenticationHeader)) { //JWT is absent
        throw new Exception('Missing or invalid JWT in request');
    }
    //JWT is sent from client in the format Bearer XXXXXXXXX
    return explode(' ', $authenticationHeader)[1];
}

function getCheckJWTFromRequest(RequestInterface $request): string
{
    // 1. Get the Authorization header as a string
    // This correctly extracts the string value (e.g., "Bearer XXXXXXXXX")
    // from the CodeIgniter request object.
    $authHeader = $request->getHeaderLine('Authorization');
    
    // 2. Check if the header exists
    if (empty($authHeader)) {
        throw new Exception('Missing or invalid JWT in request. Authorization header is empty.');
    }
    
    // 3. Extract the token (your original logic)
    // The header format is typically 'Bearer XXXXXXXXX'
    $parts = explode(' ', $authHeader);
    
    // Ensure it has two parts (Bearer and the Token) and the first part is 'Bearer'
    if (count($parts) < 2 || strtolower($parts[0]) !== 'bearer') {
        throw new Exception("Invalid Authorization header format. Must be 'Bearer <token>'.");
    }
    
    // Return the token part
    return $parts[1];
}

function validateJWTFromRequest(string $encodedToken)
{
    
    $key = getJwtSecretKey();

    if (!is_string($key)) {

        log_message(
            'critical',
            'JWT Key Type: ' . gettype($key)
        );

        throw new Exception(
            'JWT key is not a string.'
        );

    }

    log_message(
        'debug',
        'JWT Key Length: ' . strlen($key)
    );

    \Firebase\JWT\JWT::$leeway = 60;
    // $decodedToken = JWT::decode($encodedToken, $key, ['HS256']);
     $decodedToken = JWT::decode($encodedToken, new Key($key, 'HS256'));
    $userModel = new UserModel();
    $userModel->findUserByEmailAddress($decodedToken->data->email);
}

// function getJwtSecretKey(): string
// {
//     $key = getenv('JWT_SECRET_KEY');

//     if (
//         !is_string($key) ||
//         trim($key) === ''
//     ) {

//         throw new RuntimeException(
//             'JWT_SECRET_KEY is missing.'. $key
//         );

//     }

//     return trim($key);
// }

function getJwtSecretKey(): string
{
    $key1 = env('JWT_SECRET_KEY');
    $key2 = env('JWT_SECRET_KEY');

    log_message(
        'error',
        'env()=' . var_export($key1, true)
    );

    log_message(
        'error',
        'getenv()=' . var_export($key2, true)
    );

    if (!is_string($key1) || trim($key1) === '') {
        throw new RuntimeException(
            'JWT_SECRET_KEY is missing.'
        );
    }

    return trim($key1);
}

function getSignedJWTForUser(array $user)
{
    $issuedAtTime = time();
    $tokenTimeToLive = getenv('JWT_TIME_TO_LIVE');
    $tokenExpiration = $issuedAtTime + $tokenTimeToLive;    // expire time in seconds
    $notBeforeClaim = $issuedAtTime + 10;                   // not before in seconds
    $pvtKey = getJwtSecretKey();                    // get RSA private key (NOT IN USE)
    $payload = [
        "iss" => "Issuer of the JWT", // this can be the servername. Example: https://domain.com
        "aud" => "Audience that the JWT",
        "sub" => "Subject of the JWT",
        "nbf" => $notBeforeClaim,
        'iat' => $issuedAtTime,
        'exp' => $tokenExpiration,
        "data" => $user
    ];

    $jwt = JWT::encode($payload, $pvtKey, 'HS256');
    return $jwt;
}

/**
 * Decode and validate JWT token
 */
function decodeJWT(string $encodedToken)
{
    $secretKey = getJwtSecretKey();

    if (empty($secretKey)) {
        throw new Exception("JWT_SECRET_KEY not configured.");
    }

    try {
        return JWT::decode($encodedToken, new Key($secretKey, 'HS256'));
    } catch (\Firebase\JWT\ExpiredException $e) {
        throw new Exception('Token has expired.');
    } catch (\Exception $e) {
        throw new Exception('Invalid token: ' . $e->getMessage());
    }
}