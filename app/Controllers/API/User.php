<?php

namespace App\Controllers\API;

use App\Models\UserModel;
use App\Models\AuditLogModel;
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
    
    protected $auditLogModel;
    public function __construct()
    {
      helper('otp_helper');
      $this->auditLogModel = new AuditLogModel();
      
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

        $this->createAuditLog(

            'AUTH',

            0,

            'LOGIN_ATTEMPT',

            null,

            [

                'email' => $input['email'],

            ],

            'User attempted to login.'

        );


        
        

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
            
            $this->createAuditLog(

                'AUTH',

                0,

                'LOGIN_FAILED',

                null,

                [

                    'email' => $input['email']

                ],

                'Invalid login credentials.'

            );
        
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

                $this->createAuditLog(

                    'AUTH',

                    $user['userid'],

                    'LOGIN_SUCCESS',

                    null,

                    [

                        'email' => $user['email']

                    ],

                    'User logged in successfully.'

                );

                return  $this->getResponse(
                    [
                    'isError' => false,
                    'data' => $user,
                    'access_token' => $token,
                    'message'   => "Success",
                    ]
                );
            }else{

                $this->createAuditLog(

                    'AUTH',

                    $input['userid'],

                    'VERIFY_OTP_FAILED',

                    null,

                    [

                        'otp' => $input['otp']

                    ],

                    'Invalid or expired OTP.'

                );

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
            
            $this->createAuditLog(

                'AUTH',

                $user['userid'],

                'SEND_OTP',

                null,

                [

                    'otp_type' => $otpType,

                    'email' => $user['email']

                ],

                'Login OTP generated.'

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

    public function updateProfile()
    {
        try {

            $model = new UserModel();

            $input = $this->request->getJSON(true);

            $userid = $input['userid'];

            $data = [

                'firstname'     => $input['firstname'],
                'middlename'    => $input['middlename'],
                'lastname'      => $input['lastname'],
                'email'         => $input['email'],
                'mobile_number' => $input['mobile_number'],
                'birthdate'     => $input['birthdate']

            ];

            /*
            |--------------------------------------------------------------------------
            | GET OLD USER
            |--------------------------------------------------------------------------
            */

            $oldUser = $model->find($userid);

            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            $model->update($userid, $data);

            /*
            |--------------------------------------------------------------------------
            | GET UPDATED USER
            |--------------------------------------------------------------------------
            */

            $newUser = $model->find($userid);

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */
            $changes = $this->getChangedFields($oldUser, $newUser);
            $this->createAuditLog(

                'USER',

                $userid,

                'UPDATE',

                $changes,

                $changes,

                'User profile updated.'

            );

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Profile updated successfully.',

                'data' => $newUser

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }
    
    protected function createAuditLog(
        string $module,
        int $recordId,
        string $action,
        $oldData = null,
        $newData = null,
        string $remarks = ''
    )
    {
        try {

            helper('jwt');


            $userId = null;
            $username = null;

            try {

                $authHeader = $this->request->getHeaderLine('Authorization');

                if (!empty($authHeader)) {

                    $token = str_replace(
                        'Bearer ',
                        '',
                        $this->request->getHeaderLine('Authorization')
                    );

                           $encodedToken = decodeJWT($token);
                    
                    if (isset($encodedToken->data)) {

                        $jwtData = (array)$encodedToken->data;
                       
                        $userId = $jwtData['userid'] ?? null;
                        $username = $jwtData['email'] ?? null;
                    }
                }

            } catch (\Exception $e) {
                log_message('error', 'Audit JWT Error: ' . $e->getMessage());
            }

               
            $auditData = [
                'module'      => strtoupper($module),
                'record_id'   => $recordId,
                'action'      => strtoupper($action),
                'user_id'     => $userId,
                'username'    => $username,
                'old_data'    => json_encode($oldData),
                'new_data'    => json_encode($newData),
                'remarks'     => $remarks,
                'ip_address'  => 1,
                'user_agent'  => (string)$this->request->getUserAgent(),
                'created_at'  => date('Y-m-d H:i:s')
            ];
            $logModel->createLog($auditData);

        } catch (\Exception $e) {

            log_message('error', 'Audit Log Error: ' . $e->getMessage());
        }
        
    }

    protected function getChangedFields(array $old, array $new): array
    {
        $changes = [];

        foreach ($new as $key => $value) {

            if (!array_key_exists($key, $old)) {
                continue;
            }

            if ($old[$key] != $value) {

                $changes[$key] = [
                    'old' => $old[$key],
                    'new' => $value
                ];

            }

        }

        return $changes;
    }

    public function getUserLoginLogs()
    {
        try {

            $draw = (int)$this->request->getGet('draw');

            $start = (int)$this->request->getGet('start');

            $length = (int)$this->request->getGet('length');

            $orderColumn = $this->request->getGet('orderColumn') ?? 'created_at';

            $orderDir = $this->request->getGet('orderDir') ?? 'DESC';

            $search = $this->request->getGet('search');

            $userid = $this->request->getGet('userid');

            $action = $this->request->getGet('action');

            $data = $this->auditLogModel->getUserLogs(

                $search,

                $userid,

                $action,

                $start,

                $length,

                $orderColumn,

                $orderDir

            );

            return $this->response->setJSON([

                "draw" => $draw,

                "recordsTotal" => $this->auditLogModel->countUserLogs(
                    $userid
                ),

                "recordsFiltered" => $this->auditLogModel->countFilteredUserLogs(

                    $search,

                    $userid,

                    $action

                ),

                "data" => $data

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                "draw" => 0,

                "recordsTotal" => 0,

                "recordsFiltered" => 0,

                "data" => [],

                "error" => $e->getMessage()

            ]);

        }
    }
    public function updateProfileImage()
    {
        try {   
      
            $userid = $this->request->getPost("userid");

            if (empty($userid)) {
                throw new \Exception("User ID is required.");
            }

            $file = $this->request->getFile("profile_image");

            if (!$file || !$file->isValid()) {
                throw new \Exception("Please select a valid image.");
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDATE IMAGE
            |--------------------------------------------------------------------------
            */

            $allowedMimeTypes = [
                "image/jpeg",
                "image/jpg",
                "image/png",
                "image/webp"
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                throw new \Exception("Only JPG, JPEG, PNG and WEBP images are allowed.");
            }

            /*
            |--------------------------------------------------------------------------
            | GET USER
            |--------------------------------------------------------------------------
            */

            $userModel = new \App\Models\UserModel();

            $oldUser = $userModel->find($userid);

            if (!$oldUser) {
                throw new \Exception("User not found.");
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE DIRECTORY
            |--------------------------------------------------------------------------
            */

            $uploadPath = FCPATH . "uploads/users/";

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE OLD IMAGE
            |--------------------------------------------------------------------------
            */

            if (
                !empty($oldUser["user_image"]) &&
                file_exists(FCPATH . $oldUser["user_image"])
            ) {
                @unlink(FCPATH . $oldUser["user_image"]);
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE NEW IMAGE
            |--------------------------------------------------------------------------
            */

            $filename = "user_" . $userid . "_" . time() . "." . $file->getExtension();

            $file->move($uploadPath, $filename);

            $imagePath = "uploads/users/" . $filename;

            /*
            |--------------------------------------------------------------------------
            | UPDATE USER
            |--------------------------------------------------------------------------
            */

            $userModel->update($userid, [

                "user_image" => $imagePath

            ]);

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                "USER",

                $userid,

                "UPDATE",

                [
                    "user_image" => $oldUser["user_image"]
                ],

                [
                    "user_image" => $imagePath
                ],

                "Profile image updated."

            );

            return $this->response->setJSON([

                "isError" => false,

                "message" => "Profile image updated successfully.",

                "user_image" => $imagePath,

                "image" => base_url($imagePath)

            ]);

        } catch (\Exception $e) {

            return $this->response->setJSON([

                "isError" => true,

                "message" => $e->getMessage()

            ]);

        }
    }
    public function getProfile()
    {
        try {

            $userid = $this->request->getGet("userid");

            if (empty($userid)) {
                throw new \Exception("User ID is required.");
            }

            $model = new \App\Models\UserModel();

            $newUser = $model->find($userid);

            if (!$newUser) {
                throw new \Exception("User not found.");
            }

            // Don't expose sensitive fields
            unset($newUser["password"]);
            unset($newUser["otp"]);
            unset($newUser["otp_expiry"]);

            return $this->response->setJSON([

                "isError" => false,

                "message" => "Profile loaded successfully.",

                "data" => $newUser

            ]);

        } catch (\Exception $e) {

            return $this->response->setJSON([

                "isError" => true,

                "message" => $e->getMessage()

            ]);

        }
    }
}
