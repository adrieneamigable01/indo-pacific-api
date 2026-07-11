<?php

namespace App\Controllers\API;


use App\Models\BorrowerModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use ReflectionException;


class Borrower extends BaseController
{
    protected $borrowerModel;

    public function __construct()
    {
        $this->borrowerModel = new BorrowerModel();
    }
    public function index(){
        return $this->get();
    }

    public function get()
    {
        try {

            $search = $this->request->getGet('search');

            $borrower_id = $this->request->getGet('borrower_id');
           
            $data = $this->borrowerModel->getBorrowers($search,$borrower_id);

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

    public function getSummary()
    {
        try {

            $data = $this->borrowerModel->getSummary();

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Success',
                    'data' => $data
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }

    public function details($borrowerId)
    {
        try {

            $data = $this->borrowerModel->getBorrowerDetails($borrowerId);

            return $this->response->setJSON([
                'isError' => false,
                'data' => $data
            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function add(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();


        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'last_name'     => 'required',
                'first_name'    => 'required',
                'date_of_birth' => 'required',
                'civil_status'  => 'required',
                'gender'        => 'required',
                'mobile_no'     => 'required',
                'home_address'  => 'required'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors'  => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $db->transBegin();

            $borrowerData = [
                'last_name'     => strtoupper(trim((string)($input['last_name'] ?? ''))),
                'first_name'    => strtoupper(trim((string)($input['first_name'] ?? ''))),
                'middle_name'   => strtoupper(trim((string)($input['middle_name'] ?? ''))),
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'civil_status'  => strtoupper(trim((string)($input['civil_status'] ?? ''))),
                'gender'        => strtoupper(trim((string)($input['gender'] ?? ''))),
                'mobile_no'     => $input['mobile_no'] ?? '',
                'email_address' => $input['email_address'] ?? '',
                'home_address'  => strtoupper(trim((string)($input['home_address'] ?? ''))),
                'created_at'    => date('Y-m-d H:i:s'),
                'isActive'      => 1
            ];

            $db->table('borrowers')->insert($borrowerData);

            $borrowerId = $db->insertID();

            $credentialsData = array_filter([
                'borrower_id'  => $borrowerId,
                'tin_no'       => $input['tin_no'] ?? null,
                'id_presented' => $input['id_presented'] ?? null,
                'id_no'        => $input['id_no'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (count($credentialsData) > 1) {
                $db->table('borrower_credentials')->insert($credentialsData);
            }

            $employmentData = array_filter([
                'borrower_id'     => $borrowerId,
                'company_school'  => $input['company_school'] ?? null,
                'employer_name'   => $input['employer_name'] ?? null,
                'company_address' => $input['company_address'] ?? null,
                'employment_date' => $input['employment_date'] ?? null,
                'position_name'   => $input['position_name'] ?? null,
                'basic_salary'    => $input['basic_salary'] ?? null,
                'annual_income'   => $input['annual_income'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (count($employmentData) > 1) {
                $db->table('borrower_employment')->insert($employmentData);
            }

            $spouseData = array_filter([
                'borrower_id'      => $borrowerId,
                'last_name'        => $input['spouse_last_name'] ?? null,
                'first_name'       => $input['spouse_first_name'] ?? null,
                'middle_name'      => $input['spouse_middle_name'] ?? null,
                'date_of_birth'    => $input['spouse_date_of_birth'] ?? null,
                'mobile_no'        => $input['spouse_mobile_no'] ?? null,
                'employer_name'    => $input['spouse_employer_name'] ?? null,
                'position_name'    => $input['spouse_position_name'] ?? null,
                'monthly_income'   => $input['monthly_income'] ?? null,
                'home_address'     => $input['spouse_home_address'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (count($spouseData) > 1) {
                $db->table('borrower_spouses')->insert($spouseData);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Transaction failed.'
                ]);
            }

            $db->transCommit();

            $this->createAuditLog(
                'borrower',
                $borrowerId,
                'CREATE',
                null,
                $borrowerData,
                'Borrower created'
            );

            return $this->getResponse([
                'isError' => false,
                'borrower_id' => $borrowerId,
                'message' => 'Borrower successfully created.'
            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
        }


    }

    public function update(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();


        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'borrower_id'   => 'required|numeric',
                'last_name'     => 'required',
                'first_name'    => 'required',
                'date_of_birth' => 'required',
                'civil_status'  => 'required',
                'gender'        => 'required',
                'mobile_no'     => 'required',
                'home_address'  => 'required'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors'  => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $borrowerId = (int)$input['borrower_id'];

            $oldBorrower = $db->table('borrowers')
                ->where('borrower_id', $borrowerId)
                ->get()
                ->getRowArray();

            if (!$oldBorrower) {
                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Borrower not found.'
                ]);
            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | BORROWER
            |--------------------------------------------------------------------------
            */

            $borrowerData = [
                'last_name'     => strtoupper(trim((string)($input['last_name'] ?? ''))),
                'first_name'    => strtoupper(trim((string)($input['first_name'] ?? ''))),
                'middle_name'   => strtoupper(trim((string)($input['middle_name'] ?? ''))),
                'date_of_birth' => $input['date_of_birth'] ?? null,
                'civil_status'  => strtoupper(trim((string)($input['civil_status'] ?? ''))),
                'gender'        => strtoupper(trim((string)($input['gender'] ?? ''))),
                'mobile_no'     => $input['mobile_no'] ?? '',
                'email_address' => $input['email_address'] ?? '',
                'home_address'  => strtoupper(trim((string)($input['home_address'] ?? '')))
            ];

            $db->table('borrowers')
                ->where('borrower_id', $borrowerId)
                ->update($borrowerData);

            /*
            |--------------------------------------------------------------------------
            | CREDENTIALS
            |--------------------------------------------------------------------------
            */

            $credentialsData = array_filter([
                'tin_no'       => $input['tin_no'] ?? null,
                'id_presented' => $input['id_presented'] ?? null,
                'id_no'        => $input['id_no'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (!empty($credentialsData)) {

                $credentials = $db->table('borrower_credentials')
                    ->where('borrower_id', $borrowerId)
                    ->get()
                    ->getRowArray();

                if ($credentials) {

                    $db->table('borrower_credentials')
                        ->where('borrower_id', $borrowerId)
                        ->update($credentialsData);

                } else {

                    $credentialsData['borrower_id'] = $borrowerId;

                    $db->table('borrower_credentials')
                        ->insert($credentialsData);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | EMPLOYMENT
            |--------------------------------------------------------------------------
            */

            $employmentData = array_filter([
                'company_school'  => $input['company_school'] ?? null,
                'employer_name'   => $input['employer_name'] ?? null,
                'company_address' => $input['company_address'] ?? null,
                'employment_date' => $input['employment_date'] ?? null,
                'position_name'   => $input['position_name'] ?? null,
                'basic_salary'    => $input['basic_salary'] ?? null,
                'annual_income'   => $input['annual_income'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (!empty($employmentData)) {

                $employment = $db->table('borrower_employment')
                    ->where('borrower_id', $borrowerId)
                    ->get()
                    ->getRowArray();

                if ($employment) {

                    $db->table('borrower_employment')
                        ->where('borrower_id', $borrowerId)
                        ->update($employmentData);

                } else {

                    $employmentData['borrower_id'] = $borrowerId;

                    $db->table('borrower_employment')
                        ->insert($employmentData);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SPOUSE
            |--------------------------------------------------------------------------
            */

            $spouseData = array_filter([
                'last_name'      => $input['spouse_last_name'] ?? null,
                'first_name'     => $input['spouse_first_name'] ?? null,
                'middle_name'    => $input['spouse_middle_name'] ?? null,
                'date_of_birth'  => $input['spouse_date_of_birth'] ?? null,
                'mobile_no'      => $input['spouse_mobile_no'] ?? null,
                'employer_name'  => $input['spouse_employer_name'] ?? null,
                'position_name'  => $input['spouse_position_name'] ?? null,
                'monthly_income' => $input['monthly_income'] ?? null,
                'home_address'   => $input['spouse_home_address'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (!empty($spouseData)) {

                $spouse = $db->table('borrower_spouses')
                    ->where('borrower_id', $borrowerId)
                    ->get()
                    ->getRowArray();

                if ($spouse) {

                    $db->table('borrower_spouses')
                        ->where('borrower_id', $borrowerId)
                        ->update($spouseData);

                } else {

                    $spouseData['borrower_id'] = $borrowerId;

                    $db->table('borrower_spouses')
                        ->insert($spouseData);
                }
            }

            if ($db->transStatus() === false) {

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Transaction failed.'
                ]);
            }

            $db->transCommit();

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(
                'BORROWER',
                $borrowerId,
                'UPDATE',
                $oldBorrower,
                $borrowerData,
                'Borrower updated'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Borrower successfully updated.'
            ]);

        } catch (Exception $ex) {

            if ($db->transStatus()) {
                $db->transRollback();
            }

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
        }


    }


    public function void(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'borrower_id' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors' => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $borrowerId = $input['borrower_id'];

            $borrower = $this->borrowerModel
                ->where('borrower_id', $borrowerId)
                ->first();

            if (!$borrower) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Borrower not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );
            }

            if ((int)($borrower['isActive'] ?? 0) === 0) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Borrower is already voided.'
                    ]
                );
            }
            $oldBorrower = $this->borrowerModel
            ->where('borrower_id', $borrowerId)
            ->first();
            $db->transBegin();
            $this->createAuditLog(
            'borrower',
            $borrowerId,
            'VOID',
            $oldBorrower,
            ['isActive' => 0],
            'Borrower voided'
        );

            $this->borrowerModel
                ->where('borrower_id', $borrowerId)
                ->set([
                    'isActive' => 0
                ])
                ->update();

            if ($db->transStatus() === false) {

                $db->transRollback();

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Failed to void borrower.'
                    ]
                );
            }

            $db->transCommit();

            return $this->getResponse(
                [
                    'isError' => false,
                    'borrower_id' => $borrowerId,
                    'message' => 'Borrower successfully voided.'
                ]
            );

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
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

            $logModel = new \App\Models\AuditLogModel();

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
    public function importDraft(
        int $responseCode = ResponseInterface::HTTP_OK
    )
    {

        $db = \Config\Database::connect();

        try{

            /*
            |--------------------------------------------------------------------------
            | LOAD DRAFT
            |--------------------------------------------------------------------------
            */

            $draftLoanData = $db
                ->table('loan_applications')
                ->get()
                ->getResultArray();

            if(!$draftLoanData){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>'Loan application not found.'

                ],404);

            }

            /*
            |--------------------------------------------------------------------------
            | CHECK IF ALREADY IMPORTED
            |--------------------------------------------------------------------------
            */

            if(
                isset($draftLoanData['isActive']) &&
                $draftLoanData['isActive']==0
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>'Draft has already been imported.'

                ]);

            }

           

            $db->transBegin();

            foreach ($draftLoanData as $key => $draft) {
                /*
                |--------------------------------------------------------------------------
                | FIND EXISTING BORROWER
                |--------------------------------------------------------------------------
                |
                | Priority:
                | 1. Mobile Number
                | 2. Name + Birth Date
                |--------------------------------------------------------------------------
                */

                $borrower =
                    $db->table('borrowers')
                    
                    ->groupStart()
                        ->where(
                            'mobile_no',
                            trim($draft['mobile_no'])
                        )
                        ->orGroupStart()
                            ->where(
                                'first_name',
                                strtoupper(
                                    trim($draft['first_name'])
                                )
                            )
                            ->where(
                                'last_name',
                                strtoupper(
                                    trim($draft['last_name'])
                                )
                            )
                            ->where(
                                'date_of_birth',
                                $draft['date_of_birth']
                            )
                        ->groupEnd()
                    ->groupEnd()
                    ->get()
                    ->getResultArray();


                /*
                |--------------------------------------------------------------------------
                | CREATE BORROWER
                |--------------------------------------------------------------------------
                */

                if(!$borrower){

                    $borrowerData=[

                        'last_name'=>

                            strtoupper(
                                trim(
                                    $draft['last_name']
                                )
                            ),

                        'first_name'=>

                            strtoupper(
                                trim(
                                    $draft['first_name']
                                )
                            ),

                        'middle_name'=>

                            strtoupper(
                                trim(
                                    $draft['middle_name']
                                )
                            ),

                        'date_of_birth'=>

                            $draft['date_of_birth'],

                        'civil_status'=>

                            strtoupper(
                                trim(
                                    $draft['civil_status']
                                )
                            ),

                        'gender'=>

                            strtoupper(
                                trim(
                                    $draft['gender']
                                )
                            ),

                        'home_address'=>

                            strtoupper(
                                trim(
                                    $draft['place_of_birth']
                                )
                            ),
                        'place_of_birth'=>

                            strtoupper(
                                trim(
                                    $draft['place_of_birth']
                                )
                            ),

                        'citizenship'=>

                            strtoupper(
                                trim(
                                    $draft['citizenship']
                                )
                            ),

                        'telephone_number'=>

                            trim(
                                $draft['tel_no']
                            ),

                        'mobile_no'=>

                            trim(
                                $draft['mobile_no']
                            ),

                        'email_address'=>

                            trim(
                                $draft['email_address']
                            ),

                        'tin_number'=>

                            trim(
                                $draft['tin_no']
                            ),

                        'id_presented'=>

                            strtoupper(
                                trim(
                                    $draft['id_presented']
                                )
                            ),

                        'id_number'=>

                            trim(
                                $draft['id_no']
                            ),

                        'created_at'=>

                            date('Y-m-d H:i:s')

                    ];

                    $db
                        ->table('borrowers')
                        ->insert(
                            $borrowerData
                        );

                    $borrowerId =
                        $db->insertID();

                }else{
                    $borrowerId =
                        $borrower[0]['borrower_id'];
                        
          

                }


                /*
                |--------------------------------------------------------------------------
                | BORROWER EMPLOYMENT
                |--------------------------------------------------------------------------
                */

                $employment = [

                    'borrower_id'        => $borrowerId,

                    'company_school'     => strtoupper(
                        trim($draft['company_school'])
                    ),

                    'employer_name'      => strtoupper(
                        trim($draft['employer_name'])
                    ),

                    'company_address'    => strtoupper(
                        trim($draft['company_address'])
                    ),

                    'employment_date'    => $draft['employment_date'],

                    'position_name'      => strtoupper(
                        trim($draft['position_name'])
                    ),

                    'basic_salary'       => $draft['basic_salary'] ?? 0,

                    'annual_income'      => $draft['annual_income'] ?? 0

                ];

                $existingEmployment =
                    $db->table('borrower_employment')
                    ->where(
                        'borrower_id',
                        $borrowerId
                    )
                    ->get()
                    ->getRowArray();

                if($existingEmployment){

                    $db->table('borrower_employment')
                        ->where(
                            'borrower_id',
                            $borrowerId
                        )
                        ->update(
                            $employment
                        );

                }else{

                    $db->table('borrower_employment')
                        ->insert(
                            $employment
                        );

                }

                /*
                |--------------------------------------------------------------------------
                | SPOUSE
                |--------------------------------------------------------------------------
                */

                if(
                    !empty($draft['spouse_name'])
                ){

                    $existingSpouse =
                        $db->table('borrower_spouses')
                        ->where(
                            'borrower_id',
                            $borrowerId
                        )
                        ->get()
                        ->getRowArray();

                    $spouseName = strtoupper(trim($draft['spouse_name']));

                    $parts = preg_split('/\s+/', $spouseName);

                    $lastname = '';
                    $firstname = '';
                    $middlename = '';

                    if(count($parts) == 1){

                        $firstname = $parts[0];

                    }elseif(count($parts) == 2){

                        $firstname = $parts[0];
                        $lastname  = $parts[1];

                    }else{

                        $lastname = array_pop($parts);

                        $firstname = array_shift($parts);

                        $middlename = implode(' ', $parts);

                    }

                    $spouse = [

                        'borrower_id' => $borrowerId,

                        'last_name' =>strtoupper(
                                    trim(
                                        $lastname
                                    )
                                ),
                        'first_name' =>strtoupper(
                                    trim(
                                        $firstname
                                    )
                                ),

                        'middle_name' =>strtoupper(
                                    trim(
                                        $middlename
                                    )
                                ),

                    ];

                    if($existingSpouse){

                        $db->table(
                            'borrower_spouses'
                        )
                        ->where(
                            'borrower_id',
                            $borrowerId
                        )
                        ->update(
                            $spouse
                        );

                    }else{

                        $db->table(
                            'borrower_spouses'
                        )
                        ->insert(
                            $spouse
                        );

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | GET LOAN PRODUCT
                |--------------------------------------------------------------------------
                */

                $loanType = strtoupper(
                    trim(
                        $draft['loan_type']
                    )
                );

                if(
                    $loanType ==
                    'SALARY INTEREST ONLY'
                ){

                    $loanType =
                        'INTEREST ONLY';

                }

                $loanProduct =
                    $db->table(
                        'loan_products'
                    )
                    ->where(
                        'product_name',
                        $loanType
                    )
                    ->where(
                        'is_active',
                        1
                    )
                    ->get()
                    ->getRowArray();

                if(
                    !$loanProduct
                ){

                    throw new Exception(

                        'Loan Product not found : '
                        .$loanType

                    );

                }

                $loanProductId =
                    $loanProduct[
                        'loan_product_id'
                    ];

                /*
                |--------------------------------------------------------------------------
                | COMPUTE LOAN
                |--------------------------------------------------------------------------
                */

                $loanAmount =

                    (float)
                    $draft['loan_amount'];

                $loanTerms =

                    (int)
                    $draft['loan_terms'];

                $approvedInterestRate =

                    (float)
                    $loanProduct[
                        'interest_rate'
                    ];

                $approvedProcessingFee =

                    (float)
                    $loanProduct[
                        'processing_fee_percent'
                    ];

                /*
                |--------------------------------------------------------------------------
                | INTEREST AMOUNT
                |--------------------------------------------------------------------------
                */

                $interestAmount =

                    (
                        $loanAmount
                        *
                        (
                            $approvedInterestRate
                            /100
                        )
                    )
                    *
                    $loanTerms;

                /*
                |--------------------------------------------------------------------------
                | PROCESSING FEE
                |--------------------------------------------------------------------------
                */

                $processingFeeAmount =

                    (
                        $loanAmount
                        *
                        (
                            $approvedProcessingFee
                            /100
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | NET PROCEEDS
                |--------------------------------------------------------------------------
                */

                $netProceeds =

                    $loanAmount
                    -
                    $interestAmount
                    -
                    $processingFeeAmount;

                        /*
                |--------------------------------------------------------------------------
                | CREATE LOAN
                |--------------------------------------------------------------------------
                */

                $loanData = [

                    'borrower_id' =>

                        $borrowerId,

                    'loan_product_id' =>

                        $loanProductId,

                    'loan_amount' =>

                        $loanAmount,

                    'loan_purpose' =>

                        strtoupper(
                            trim(
                                $draft['loan_purpose']
                            )
                        ),

                    'loan_terms' =>

                        $loanTerms,

                    'approved_interest_rate' =>

                        $approvedInterestRate,

                    'approved_processing_fee' =>

                        $approvedProcessingFee,

                    'interest_amount' =>

                        $interestAmount,

                    'processingfee_amount' =>

                        $processingFeeAmount,

                    'net_proceeds' =>

                        $netProceeds,

                    'monthly_interest_deduction' =>

                        4000,

                    'status' =>

                        'RELEASED',

                    'created_at' =>

                        date('Y-m-d H:i:s')

                ];

                $db
                    ->table('loans')
                    ->insert(
                        $loanData
                    );

                $loanId =
                    $db->insertID();

                /*
                |--------------------------------------------------------------------------
                | CREATE COLLATERAL
                |--------------------------------------------------------------------------
                */

                if(

                    !empty(
                        $draft['primary_card_name']
                    )

                    ||

                    !empty(
                        $draft['primary_card_number']
                    )

                    ||

                    !empty(
                        $draft['secondary_card_name']
                    )

                    ||

                    !empty(
                        $draft['secondary_card_number']
                    )

                ){

                    $db
                        ->table(
                            'loan_collaterals'
                        )
                        ->insert([

                            'loan_id'=>

                                $loanId,

                            'primary_card_name'=>

                                strtoupper(
                                    trim(
                                        $draft[
                                            'primary_card_name'
                                        ]
                                    )
                                ),

                            'primary_card_number'=>

                                trim(
                                    $draft[
                                        'primary_card_number'
                                    ]
                                ),

                            'secondary_card_name'=>

                                strtoupper(
                                    trim(
                                        $draft[
                                            'secondary_card_name'
                                        ]
                                    )
                                ),

                            'secondary_card_number'=>

                                trim(
                                    $draft[
                                        'secondary_card_number'
                                    ]
                                )

                        ]);

                }

                /*
                |--------------------------------------------------------------------------
                | IMPORT CO-MAKERS
                |--------------------------------------------------------------------------
                */

                $draftComakers =

                    $db
                        ->table('co_makers')
                        ->where(
                            'loan_application_id',
                            $draft['id']
                        )
                        ->get()
                        ->getResultArray();

                foreach($draftComakers as $comaker){

                    $db
                        ->table('loan_comakers')
                        ->insert([

                            'loan_id' =>

                                $loanId,

                            'name' =>

                                strtoupper(
                                    trim(
                                        $comaker['name']
                                    )
                                ),

                            'phone' =>

                                trim(
                                    $comaker['phone']
                                ),

                            'address' =>

                                strtoupper(
                                    trim(
                                        $comaker['address']
                                    )
                                )

                        ]);

                }

                /*
                |--------------------------------------------------------------------------
                | GENERATE LOAN SCHEDULE
                |--------------------------------------------------------------------------
                */

                $createdAt = date(
                    'Y-m-d H:i:s',
                    strtotime($draft['created_at'] . ' +1 month')
                );

                $this->generateLoanSchedule(

                    $db,

                    $loanId,

                    (int)$loanProductId,

                    (float)$loanAmount,

                    (int)$loanTerms,

                    $createdAt

                );

                /*
                |--------------------------------------------------------------------------
                | MARK DRAFT AS IMPORTED
                |--------------------------------------------------------------------------
                */

                $db
                    ->table('loan_applications')
                    ->where(
                        'id',
                        $draft['id']
                    )
                    ->update([

                        'isActive' => 0,


                    ]);

                /*
                |--------------------------------------------------------------------------
                | CHECK TRANSACTION
                |--------------------------------------------------------------------------
                */

                if(
                    $db->transStatus() === false
                ){

                    $db->transRollback();

                    return $this->getResponse([

                        'isError' => true,

                        'message' => 'Import failed.'

                    ]);

                }

               

                /*
                |--------------------------------------------------------------------------
                | AUDIT LOG
                |--------------------------------------------------------------------------
                */

                $this->createAuditLog(

                    'LOAN',

                    $loanId,

                    'IMPORT',

                    null,

                    $loanData,

                    'Loan imported from Draft Application'

                );

                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

               
            }

            $db->transCommit();
            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan application successfully imported.'

            ]);

        }catch(Exception $ex){

            $db->transRollback();

            return $this->getResponse(

                [

                    'isError' => true,

                    'message' => $ex->getMessage()

                ],

                $responseCode

            );

        }

    }
    private function generateLoanSchedule(
        $db,
        int $loanId,
        int $loanProductId,
        float $loanAmount,
        int $loanTerms,
        string $releaseDate = null
    )
    {
        $releaseDate = $releaseDate ?? date('Y-m-d');

        $db->table('loan_schedule')
            ->where('loan_id', $loanId)
            ->delete();

        switch ($loanProductId) {

            case 1:

            $totalYears = 10;

            $monthlyInterest = $loanAmount * 0.01;

            $totalInterest = $monthlyInterest * ($totalYears * 12);

            $yearlyInterest = $totalInterest / 7;

            $yearlyPrincipal = $loanAmount / $totalYears;

            /*
            |--------------------------------------------------------------------------
            | Compute Adjustment
            |--------------------------------------------------------------------------
            */

            $yearlyInterestEquivalent = $totalInterest / $totalYears;

            $interestDifference =
                $yearlyInterest - $yearlyInterestEquivalent;

            $firstSevenYearPrincipal =
                $yearlyPrincipal - $interestDifference;

            /*
            |--------------------------------------------------------------------------
            | Remaining Principal
            |--------------------------------------------------------------------------
            */

            $remainingPrincipal =
                $loanAmount -
                ($firstSevenYearPrincipal * 7);

            $lastThreeYearPrincipal =
                $remainingPrincipal / 3;

            $balance = $loanAmount;

            /*
            |--------------------------------------------------------------------------
            | YEARS 1-7
            |--------------------------------------------------------------------------
            */

            for ($year = 1; $year <= 7; $year++) {

                $principalDue = round(
                    $firstSevenYearPrincipal,
                    2
                );

                $interestDue = round(
                    $yearlyInterest,
                    2
                );

                $balance -= $principalDue;
                $additionalYear = $year - 1;
                $result = $db->table('loan_schedule')->insert([
                    'loan_id' => $loanId,
                    'due_date' => date(
                        'Y-m-d',
                        strtotime("+{$additionalYear} year", strtotime($releaseDate))
                    ),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'penalty_due' => 0,
                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'penalty_paid' => 0,
                    'balance' => max(round($balance, 2), 0),
                    'status' => 'UNPAID',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$result) {
                    throw new \Exception(
                        'Failed generating Product 1 schedule.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | YEARS 8-10
            |--------------------------------------------------------------------------
            */

            for ($year = 8; $year <= 10; $year++) {

                $principalDue = round(
                    $lastThreeYearPrincipal,
                    2
                );

                $interestDue = 0;

                $balance -= $principalDue;

                if ($year == 10) {
                    $balance = 0;
                }

                $result = $db->table('loan_schedule')->insert([
                    'loan_id' => $loanId,
                    'due_date' => date(
                        'Y-m-d',
                        strtotime("+{$year} year", strtotime($releaseDate))
                    ),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'penalty_due' => 0,
                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'penalty_paid' => 0,
                    'balance' => max(round($balance, 2), 0),
                    'status' => 'UNPAID',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$result) {
                    throw new \Exception(
                        'Failed generating Product 1 schedule.'
                    );
                }
            }

            break;

            case 2:
                if($loanTerms > 0){
                
                    $monthlyPrincipal = $loanAmount / $loanTerms;
                    $balance = $loanAmount;

                    for ($i = 1; $i <= $loanTerms; $i++) {

                        $balance -= $monthlyPrincipal;

                        $result = $db->table('loan_schedule')->insert([
                            'loan_id' => $loanId,
                            'due_date' => date(
                                'Y-m-d',
                                strtotime("+{$i} month", strtotime($releaseDate))
                            ),
                            'principal_due' => round($monthlyPrincipal, 2),
                            'interest_due' => 0,
                            'balance' => max(round($balance, 2), 0),
                            'status' => 'UNPAID'
                        ]);

                        if (!$result) {
                            throw new \Exception(
                                'Failed generating schedule.'
                            );
                        }
                    }
                }

                break;

            case 3:

                $monthlyInterest = $loanAmount * 0.03;

                for ($i = 1; $i <= $loanTerms; $i++) {

                    $principalDue = 0;

                    if ($i == $loanTerms) {
                        $principalDue = $loanAmount;
                    }

                    $result = $db->table('loan_schedule')->insert([
                        'loan_id' => $loanId,
                        'due_date' => date(
                            'Y-m-d',
                            strtotime("+{$i} month", strtotime($releaseDate))
                        ),
                        'principal_due' => $principalDue,
                        'interest_due' => round($monthlyInterest, 2),
                        'balance' => ($i == $loanTerms)
                            ? 0
                            : $loanAmount,
                        'status' => 'UNPAID'
                    ]);

                    if (!$result) {
                        throw new \Exception(
                            'Failed generating schedule.'
                        );
                    }
                }

                break;
        }

        return true;
    }

}