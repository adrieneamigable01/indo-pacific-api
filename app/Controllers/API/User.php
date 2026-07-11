<?php

namespace App\Controllers\API;

use App\Models\UserModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use ReflectionException;

class User extends BaseController
{
    // use ResponseTrait;
    /**
     * Register a new user
     * @return Response
     * @throws ReflectionException
     */
    
    public function __construct()
    {
      helper('otp_helper');
    }
    public function register()
    {
        
        $rules = [
            'name' => 'required',
            'username' => 'required',
            // 'email' => 'required|min_length[6]|max_length[50]|valid_email|is_unique[user.email]',
            'password' => 'required|min_length[6]|max_length[255]'
        ];

        $input = $this->getRequestInput($this->request);
        if (!$this->validateRequest($input, $rules)) {
            return $this->getResponse(
                        $this->validator->getErrors(),
                        ResponseInterface::HTTP_BAD_REQUEST
                );
        }

        $userModel = new UserModel();
        $userModel->save($input);

        return $this->getJWTForUser(
                $input['email'],
                ResponseInterface::HTTP_CREATED
            );
    }
    

    /**
     * Checks if the provided JWT is valid and not expired.
     * * This function is typically used to protect other API endpoints
     * or for a quick token validity check without accessing protected resources.
     * * @return Response
     */
    // app/Controllers/API/User.php

    public function checkToken(): \CodeIgniter\HTTP\ResponseInterface
    {
        // The helper needs to be loaded if it's not autoloaded
        helper('jwt'); 
        
        try {
            // This is where the exception is thrown if the Authorization header is missing
            $token = getCheckJWTFromRequest($this->request); 
            
            // This validates the token (signature, expiration)
            $decodedToken = validateJWT($token); 
            // Success: Token is valid
            return $this->getResponse([
                'isError' => false,
                'message' => 'Token is valid.',
                'user_data' => $decodedToken->data
            ], \CodeIgniter\HTTP\ResponseInterface::HTTP_OK);
            
        } catch (\Exception $e) {
            // CATCH: This catches exceptions from getJWTFromRequest (missing token) 
            // AND validateJWT (invalid/expired token).
            
            // Check if the request is missing the token, which is the scenario you described
            $errorMessage = $e->getMessage();
            $statusCode = \CodeIgniter\HTTP\ResponseInterface::HTTP_UNAUTHORIZED; // 401 Unauthorized
            
            // Use a 401 status code and return a clean JSON error.
            return $this->getResponse([
                'isError' => true,
                'message' => 'Authentication failed',
                'details' => $errorMessage // e.g., "Missing or invalid JWT in request."
            ], $statusCode);
        }
    }
    

    /**
     * Authenticate Existing User
     * @return Response
     */
    public function login()
    {

        $input = $this->getRequestInput($this->request);

        $rules = [
            'email' => 'required',
            'password' => 'required|min_length[6]|max_length[255]|validateUser[email, password]'
        ];
      
        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        if (!$this->validateRequest($input, $rules, $errors)) {
           $statusCode = ResponseInterface::HTTP_BAD_REQUEST;

            return $this->getResponse(
                [
                    'isError'    => true,
                    'message'    => array_values($this->validator->getErrors())[0],
                    'data' => [],
                ],
                $statusCode
            );
        }
        
        return $this->setOTPForUser($input);
    }

    public function validateOTPForUser(int $responseCode = ResponseInterface::HTTP_OK){
        
        helper('jwt');
        try{

            $model = new UserModel();
        
            $input = $this->getRequestInput($this->request);

            $payload= array(
                'foreign_id' => $input['userid'],
                'otp' => $input['otp'],
                'action' => 'login',
            );
           
            $validOTP = validate_otp($payload,0);
            $user = $model->findUserByUserId($input['userid']);
            $token = getSignedJWTForUser($user);
            $user['access_token'] = $token;
            if($validOTP){
                return  $this->getResponse(
                    [
                    'isError' => false,
                    'data' => $user,
                    'access_token' => $token,
                    'message'   => "Success",
                    ]
                );
            }else{
                return  $this->getResponse(
                    [
                    'isError' => true,
                        'message'   => "Invalid or expired OTP.",
                    ]
                );
            }
        }catch (Exception $ex) {
            return $this->getResponse(
                [
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
        }
    }

    private function setOTPForUser($input, int $responseCode = ResponseInterface::HTTP_OK) 
    {
        helper('jwt');
        try {
            $model = new UserModel();
            $user = $model->findUserByEmail($input['email']);
            
           
            $otpType = $input['type'];

             $otp_payload = array(
                'userid'        => $user['userid'],
                'email'         => $user['email'],
                'name'          => $user['firstname'].' '.$user['lastname'],
                'mobile_number' => $user['mobile_number'],
                'otp_type'      => $otpType,
                'action'        => 'login',
                'foreign_id'    =>  $user['userid']
            );
            
            $otpData = generate_otp($otp_payload);
            $token = getSignedJWTForUser($user);
            $user['token'] = $user;
            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'OTP Send to your '.$otpType,
                    'otp' => $otpData,
                    'data' => $user,
                ]
            );

        } catch (Exception $ex) {
            return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => $ex->getMessage(),
                    ],
                    $responseCode
                );
        }
    }
    private function getJWTForUser(string $username, int $responseCode = ResponseInterface::HTTP_OK) 
    {
        helper('jwt');
        $input = $this->getRequestInput($this->request);
        try {
            $model = new UserModel();
            $user = $model->findUserByEmail($username);
            unset($user['password']);
             $otp_payload = array(
                'userid'        => $user['userid'],
                'email'         => $user['email'],
                'name'          => $user['firstname'].' '.$user['lastname'],
                'mobile_number' => $user['mobile_number'],
                'otp_method'    => 'email',
                'action'        => 'login',
                'foreign_id'    =>  $user['userid']
            );
            
            return $this->getResponse(
                    [
                        'message' => 'User authenticated successfully',
                        'user' => $user,
                        'access_token' => getSignedJWTForUser($username)
                    ]
                );
        } catch (Exception $ex) {
            return $this->getResponse(
                    [
                        'message' => $ex->getMessage(),
                    ],
                    $responseCode
                );
        }
    }

    public function logout()
    {
        helper('jwt');
        try {
            // Extract token from header manually
            $authHeader = $this->request->getServer('HTTP_AUTHORIZATION');
            $encodedToken = getJWTFromRequest($authHeader);

            try {
                // Validate to get expiration time
                $decodedToken = validateJWTFromRequest($encodedToken);

                // Add to blacklist if valid
                $db = \Config\Database::connect();
                $db->table('token_blacklist')->insert([
                    'token'      => $encodedToken,
                    'expires_at' => date('Y-m-d H:i:s', $decodedToken->exp)
                ]);

                return $this->response->setJSON([
                    'isError' => false,
                    'message' => 'Logged out successfully.'
                ]);

            } catch (\Exception $e) {
                // Token is already expired or invalid
                // We return "success" because the user should be logged out regardless
                return $this->response->setJSON([
                    'isError' => false,
                    'message' => 'Session expired. Logged out locally.'
                ]);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'isError' => true,
                'message' => 'Invalid logout request',
                'error'   => $e->getMessage()
            ])->setStatusCode(400);
        }

        
    }

    public function getCashiers()
    {
        try {

            $model = new UserModel();

            $search =
                $this->request
                    ->getGet('search');

            $data =
                $model->getCashiers(
                    $search
                );

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $data

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }

    }

}
