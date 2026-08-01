<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\BankTransactionModel;
use App\Models\BankModel;
use App\Models\BankAccountModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Bank extends BaseController
{
    protected $bankTransactionModel;
    protected $bankAccountModel;
    protected $bankModel;

    public function __construct()
    {
        helper('jwt');

        $this->bankTransactionModel =
            new BankTransactionModel();

        $this->bankAccountModel =
            new BankAccountModel();
        $this->bankModel = new BankModel();
    }

    public function index()
    {
        return $this->getAccounts();
    }

    /**
     * ============================================================
     * GET TRANSACTIONS (SERVER SIDE)
     * ============================================================
     */

    public function getBankAccountsAll()
    {
        return $this->getAccounts();
    }
    public function getAccounts()
    {
        try {

            $draw =
                (int)$this->request->getGet('draw');

            $start =
                (int)$this->request->getGet('start');

            $length =
                (int)$this->request->getGet('length');

            $orderColumn =
                $this->request->getGet('orderColumn')
                ?? 'bank_transaction_id';

            $orderDir =
                $this->request->getGet('orderDir')
                ?? 'DESC';

            $search =
                $this->request->getGet('search');

            $bankAccountId =
                $this->request->getGet(
                    'bank_account_id'
                );

            $transactionType =
                $this->request->getGet(
                    'transaction_type'
                );

            $dateFrom =
                $this->request->getGet(
                    'date_from'
                );

            $dateTo =
                $this->request->getGet(
                    'date_to'
                );

            $where = [

                'bank_account_id' =>
                    $bankAccountId,

                'transaction_type' =>
                    $transactionType,

                'date_from' =>
                    $dateFrom,

                'date_to' =>
                    $dateTo

            ];

            $data =
                $this->bankTransactionModel
                ->getServerSide(

                    $search,
                    $where,
                    $start,
                    $length,
                    $orderColumn,
                    $orderDir

                );

            return $this->response->setJSON([

                "draw" => $draw,

                "recordsTotal" =>
                    $this->bankTransactionModel
                    ->countTransactions(
                        $where
                    ),

                "recordsFiltered" =>
                    $this->bankTransactionModel
                    ->countFilteredTransactions(

                        $search,

                        $where

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

    public function getBanks()
    {
        try {

            $banks =
                $this->bankModel
                ->getActiveBanks();

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $banks

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }
    /**
     * ============================================================
     * TRANSACTION DETAILS
     * ============================================================
     */
    public function details($bankAccountId)
    {
        try {

            $account = $this->bankAccountModel
                ->getAccountDetails($bankAccountId);

            if (!$account) {

                return $this->response->setJSON([

                    'isError' => true,

                    'message' => 'Bank account not found.'

                ]);

            }

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $account

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }
    
    public function addTransaction(
        int $responseCode = ResponseInterface::HTTP_OK
    )
    {
            $db = \Config\Database::connect();

            helper('jwt');

            $userId = null;

            try {

                $input = $this->getRequestInput($this->request);

                /*
                |--------------------------------------------------------------------------
                | VALIDATION
                |--------------------------------------------------------------------------
                */

                $rules = [

                    'bank_account_id' =>
                        'required|numeric',

                    'transaction_date' =>
                        'required',

                    'transaction_type' =>
                        'required|in_list[DEPOSIT,WITHDRAWAL,TRANSFER]',

                    'amount' =>
                        'required|decimal'

                ];

                if (
                    !$this->validateRequest(
                        $input,
                        $rules
                    )
                ) {

                    return $this->getResponse([

                        'isError' => true,

                        'message' =>
                            current(
                                $this->validator
                                    ->getErrors()
                            ),

                        'errors' =>
                            $this->validator
                                ->getErrors()

                    ], ResponseInterface::HTTP_BAD_REQUEST);

                }

                /*
                |--------------------------------------------------------------------------
                | START DATABASE TRANSACTION
                |--------------------------------------------------------------------------
                */

                $db->transBegin();

                /*
                |--------------------------------------------------------------------------
                | VERIFY SOURCE ACCOUNT
                |--------------------------------------------------------------------------
                */

                $account =
                    $this->bankAccountModel
                        ->find(
                            $input['bank_account_id']
                        );

                if (!$account) {

                    throw new Exception(
                        'Source bank account not found.'
                    );

                }

                    

                /*
                |--------------------------------------------------------------------------
                | DETERMINE TRANSACTION TYPE
                |--------------------------------------------------------------------------
                */

                $transactionType =
                    $input['transaction_type'];

                if (
                    $transactionType == 'TRANSFER'
                ) {

                    $transactionType =
                        'TRANSFER_OUT';

                }

                $transactionRef = $this->bankTransactionModel->generateTransactionRef($transactionType);

                /*
                |--------------------------------------------------------------------------
                | GET CURRENT USER
                |--------------------------------------------------------------------------
                */

                $authHeader =
                    $this->request
                        ->getHeaderLine(
                            'Authorization'
                        );

                if (!empty($authHeader)) {

                    $token =
                        str_replace(
                            'Bearer ',
                            '',
                            $authHeader
                        );

                    $decoded =
                        decodeJWT($token);

                    if (
                        isset($decoded->data)
                    ) {

                        $jwtData =
                            (array)$decoded->data;

                        $userId =
                            $jwtData['userid']
                            ?? null;

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | PREPARE SOURCE TRANSACTION
                |--------------------------------------------------------------------------
                */

                $transaction = [

                    'bank_account_id' =>
                        $input['bank_account_id'],

                    'transaction_date' => date(
                        'Y-m-d H:i:s',
                        strtotime($input['transaction_date'])
                    ),

                    'transaction_type' =>
                        $transactionType,

                    'transfer_type' =>
                        $input['transfer_type']
                        ?? null,

                    'destination_bank_account_id' =>
                        $input['destination_bank_account_id']
                        ?? null,

                    'destination_bank_name' =>
                        $input['destination_bank_name']
                        ?? null,

                    'destination_account_name' =>
                        $input['destination_account_name']
                        ?? null,

                    'destination_account_number' =>
                        $input['destination_account_number']
                        ?? null,

                    'amount' =>
                        $input['amount'],

                    /*
                    |--------------------------------------------------------------------------
                    | Will be updated by recalculateAccountBalance()
                    |--------------------------------------------------------------------------
                    */

                    'balance_before' => 0,

                    'balance_after' => 0,

                    'reference_no' => $transactionRef,

                    'check_no' =>
                        $input['check_no']
                        ?? null,

                    'description' =>
                        strtoupper(
                            trim(
                                $input['description']
                                ?? ''
                            )
                        ),

                    'source' =>
                        $input['source']
                        ?? 'MANUAL',

                    'created_by' =>
                        $userId,

                    'created_at' =>
                        date('Y-m-d H:i:s')

                ];
                        /*
            |--------------------------------------------------------------------------
            | VALIDATE AVAILABLE BALANCE
            |--------------------------------------------------------------------------
            */

            if (

                in_array(

                    $transactionType,

                    [

                        'WITHDRAWAL',

                        'TRANSFER_OUT'

                    ]

                )

            ) {

                $currentBalance =
                    (float)$account['current_balance'];

                if (

                    $currentBalance <

                    (float)$input['amount']

                ) {

                    throw new Exception(
                        'Insufficient bank balance.'
                    );

                }

            }

            /*
            |--------------------------------------------------------------------------
            | INSERT SOURCE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $db->table('bank_transactions')
                ->insert($transaction);

            $transactionId =
                $db->insertID();

            /*
            |--------------------------------------------------------------------------
            | INTERNAL TRANSFER
            |--------------------------------------------------------------------------
            */

            if (

                $input['transaction_type'] == 'TRANSFER'

                &&

                ($input['transfer_type'] ?? '') == 'INTERNAL'

            ) {

                /*
                |--------------------------------------------------------------------------
                | VERIFY DESTINATION ACCOUNT
                |--------------------------------------------------------------------------
                */

                $destination =
                    $this->bankAccountModel
                        ->find(
                            $input['destination_bank_account_id']
                        );

                if (!$destination) {

                    throw new Exception(
                        'Destination bank account not found.'
                    );

                }

                if (

                    $destination['bank_account_id']

                    ==

                    $input['bank_account_id']

                ) {

                    throw new Exception(
                        'Source and destination account cannot be the same.'
                    );

                }

                /*
                |--------------------------------------------------------------------------
                | CREATE TRANSFER IN TRANSACTION
                |--------------------------------------------------------------------------
                */  
                $destinationRef = $this->bankTransactionModel->generateTransactionRef('TRANSFER_IN');
                
                
                
                $destinationTransaction = [

                    'bank_account_id' =>

                        $input['destination_bank_account_id'],
                    'transaction_date' => date(
                        'Y-m-d H:i:s',
                        strtotime($input['transaction_date'])
                    ),

                    'transaction_type' =>

                        'TRANSFER_IN',

                    'transfer_type' =>

                        'INTERNAL',

                    'destination_bank_account_id' =>

                        $input['bank_account_id'],

                    'amount' =>

                        $input['amount'],

                    /*
                    |--------------------------------------------------------------------------
                    | Will be recalculated
                    |--------------------------------------------------------------------------
                    */

                    'balance_before' => 0,

                    'balance_after' => 0,

                    'reference_no' => $destinationRef,
                    'transaction_ref' => $transactionRef,

                    'check_no' =>

                        $input['check_no'] ?? null,

                    'description' =>

                        strtoupper(

                            trim(

                                $input['description']

                                ?? ''

                            )

                        ),

                    'source' =>

                        'TRANSFER',

                    'created_by' =>

                        $userId,

                    'created_at' =>

                        date('Y-m-d H:i:s')

                ];

                $db->table('bank_transactions')
                    ->insert(
                        $destinationTransaction
                    );

            }
                    /*
            |--------------------------------------------------------------------------
            | RECALCULATE SOURCE ACCOUNT
            |--------------------------------------------------------------------------
            */

            $this->bankTransactionModel
                ->recalculateAccountBalance(
                    $input['bank_account_id'],
                );

            /*
            |--------------------------------------------------------------------------
            | RECALCULATE DESTINATION ACCOUNT
            |--------------------------------------------------------------------------
            */

            if (

                $input['transaction_type'] == 'TRANSFER'

                &&

                ($input['transfer_type'] ?? '') == 'INTERNAL'

            ) {

                $this->bankTransactionModel
                    ->recalculateAccountBalance(
                    $input['bank_account_id'],
                );

            }

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                'BANK_TRANSACTION',

                $transactionId,

                $transactionType,

                null,

                $transaction,

                'Bank transaction successfully created.'

            );

            /*
            |--------------------------------------------------------------------------
            | COMMIT TRANSACTION
            |--------------------------------------------------------------------------
            */

            if (

                $db->transStatus() === false

            ) {

                throw new Exception(
                    'Transaction failed.'
                );

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'transaction_id' => $transactionId,

                'message' => 'Transaction successfully saved.'

            ]);

        } catch (Exception $e) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ], $responseCode);

        }

    }
    
    public function editTransaction(
        int $transactionId,
        int $responseCode = ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        helper('jwt');

        $userId = null;

        try {

            $input = $this->getRequestInput($this->request);

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            $rules = [

                'transaction_date' =>
                    'required',

                'amount' =>
                    'required|decimal'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        current(
                            $this->validator
                                ->getErrors()
                        ),

                    'errors' =>
                        $this->validator
                            ->getErrors()

                ], ResponseInterface::HTTP_BAD_REQUEST);

            }

            /*
            |--------------------------------------------------------------------------
            | START DATABASE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | FIND TRANSACTION
            |--------------------------------------------------------------------------
            */

            $transaction =
                $this->bankTransactionModel
                    ->find($transactionId);

            if (!$transaction) {

                throw new Exception(
                    'Transaction not found.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | TRANSFER_IN CANNOT BE EDITED
            |--------------------------------------------------------------------------
            */

            if (
                $transaction['transaction_type']
                == 'TRANSFER_IN'
            ) {

                throw new Exception(
                    'TRANSFER_IN transactions cannot be edited.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | GET RELATED TRANSACTIONS
            |--------------------------------------------------------------------------
            |
            | Only transfer transactions should update both TRANSFER_OUT and
            | TRANSFER_IN. Deposits and withdrawals should update only themselves.
            |
            */

            if ($transaction['transaction_type'] == 'TRANSFER_OUT') {

                $relatedTransactions = $this->bankTransactionModel

                    ->groupStart()

                        ->where(
                            'bank_transaction_id',
                            $transaction['bank_transaction_id']
                        )

                        ->orWhere(
                            'transaction_ref',
                            $transaction['reference_no']
                        )

                    ->groupEnd()

                    ->findAll();

            } else {

                $relatedTransactions = [
                    $transaction
                ];

            }

           
            /*
            |--------------------------------------------------------------------------
            | GET CURRENT USER
            |--------------------------------------------------------------------------
            */

            $authHeader =
                $this->request
                    ->getHeaderLine(
                        'Authorization'
                    );

            if (!empty($authHeader)) {

                $token =
                    str_replace(
                        'Bearer ',
                        '',
                        $authHeader
                    );

                $decoded =
                    decodeJWT($token);

                if (
                    isset($decoded->data)
                ) {

                    $jwtData =
                        (array)$decoded->data;

                    $userId =
                        $jwtData['userid']
                        ?? null;

                }

            }

                /*
                |--------------------------------------------------------------------------
                | VERIFY SOURCE ACCOUNT
                |--------------------------------------------------------------------------
                */

                $account =
                    $this->bankAccountModel
                        ->find(
                            $transaction['bank_account_id']
                        );

                if (!$account) {

                    throw new Exception(
                        'Source bank account not found.'
                    );

                }
                        /*
                |--------------------------------------------------------------------------
                | VALIDATE AVAILABLE BALANCE
                |--------------------------------------------------------------------------
                */

                if (

                    in_array(

                        $transaction['transaction_type'],

                        [

                            'WITHDRAWAL',

                            'TRANSFER_OUT'

                        ]

                    )

                ) {

                    $currentBalance =
                        (float)$account['current_balance'];

                    /*
                    |--------------------------------------------------------------------------
                    | Add back the old transaction amount since we're editing it
                    |--------------------------------------------------------------------------
                    */

                    $availableBalance =
                        $currentBalance +
                        (float)$transaction['amount'];

                    if (

                        $availableBalance <

                        (float)$input['amount']

                    ) {

                        throw new Exception(
                            'Insufficient bank balance.'
                        );

                    }

                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE RELATED TRANSACTIONS
                |--------------------------------------------------------------------------
                */

                foreach ($relatedTransactions as $row) {

                    $update = [

                        'transaction_date' => date(
                            'Y-m-d H:i:s',
                            strtotime($input['transaction_date'])
                        ),

                        'amount' =>

                            $input['amount'],

                        'check_no' =>

                            $input['check_no']
                            ?? null,

                        'description' =>

                            strtoupper(

                                trim(

                                    $input['description']
                                    ?? ''

                                )

                            )

                    ];

                    if (

                        $row['transaction_type']
                        == 'TRANSFER_OUT'

                    ) {

                        $update['transfer_type'] =
                            $input['transfer_type'];

                        $update['destination_bank_account_id'] =
                            $input['destination_bank_account_id']
                            ?? null;

                        $update['destination_bank_name'] =
                            $input['destination_bank_name']
                            ?? null;

                        $update['destination_account_name'] =
                            $input['destination_account_name']
                            ?? null;

                        $update['destination_account_number'] =
                            $input['destination_account_number']
                            ?? null;

                    }

                    $this->bankTransactionModel->update(

                        $row['bank_transaction_id'],

                        $update

                    );

                }

                /*
                |--------------------------------------------------------------------------
                | RECALCULATE ALL AFFECTED ACCOUNTS
                |--------------------------------------------------------------------------
                */

                $accounts = [];

                foreach ($relatedTransactions as $row) {

                    $accounts[] =
                        $row['bank_account_id'];

                }

                $accounts = array_unique($accounts);

                foreach ($accounts as $accountId) {

                    $this->bankTransactionModel
                        ->recalculateAccountBalance(

                            $accountId,

                        );

                }

                /*
                |--------------------------------------------------------------------------
                | AUDIT LOG
                |--------------------------------------------------------------------------
                */

                $this->createAuditLog(

                    'BANK_TRANSACTION',

                    $transactionId,

                    'UPDATE',

                    $transaction,

                    $input,

                    'Bank transaction successfully updated.'

                );

                /*
                |--------------------------------------------------------------------------
                | COMMIT
                |--------------------------------------------------------------------------
                */

                if (

                    $db->transStatus() === false

                ) {

                    throw new Exception(
                        'Transaction update failed.'
                    );

                }

                $db->transCommit();

                return $this->getResponse([

                    'isError' => false,

                    'message' =>
                        'Transaction successfully updated.'

                ]);

            } catch (Exception $e) {

                $db->transRollback();

                return $this->getResponse([

                    'isError' => true,

                    'message' => $e->getMessage()

                ], $responseCode);

            }

    }
    
    // public function addTransaction(
    //     int $responseCode = ResponseInterface::HTTP_OK
    // )
    // {
    //     $db = \Config\Database::connect();
    //     helper('jwt');
    //     $userId = null;
    //     try {

    //         $input = $this->getRequestInput($this->request);

    //         $rules = [

    //             'bank_account_id' => 'required|numeric',

    //             'transaction_date' => 'required',

    //             'transaction_type'=>'required|in_list[DEPOSIT,WITHDRAWAL,TRANSFER]',

    //             'amount' => 'required|decimal',
                

    //         ];

    //         if (!$this->validateRequest($input, $rules)) {

    //             return $this->getResponse([
    //                 'isError' => true,
    //                 'message' => current($this->validator->getErrors()),
    //                 'errors' => $this->validator->getErrors()
    //             ], ResponseInterface::HTTP_BAD_REQUEST);

    //         }

    //         $db->transBegin();

    //         $account = $this->bankAccountModel
    //             ->find($input['bank_account_id']);

    //         if (!$account) {

    //             throw new Exception('Bank account not found.');

    //         }

    //         $balanceBefore = (float)$account['current_balance'];

    //         switch ($input['transaction_type']) {

    //             case 'DEPOSIT':

    //                 $balanceAfter = $balanceBefore + (float)$input['amount'];

    //                 break;

    //             case 'WITHDRAWAL':

    //                 if ($balanceBefore < (float)$input['amount']) {

    //                     throw new Exception('Insufficient bank balance.');

    //                 }

    //                 $balanceAfter = $balanceBefore - (float)$input['amount'];

    //                 break;

    //             case 'TRANSFER':

    //                 if($balanceBefore < (float)$input['amount']){

    //                     throw new Exception(
    //                         'Insufficient bank balance.'
    //                     );

    //                 }

    //                 $balanceAfter =
    //                     $balanceBefore -
    //                     (float)$input['amount'];

    //                 break;

    //             default:

    //                 throw new Exception('Invalid transaction type.');

    //         }


    //         $authHeader = $this->request->getHeaderLine('Authorization');

    //         if (!empty($authHeader)) {

    //             $token = str_replace(
    //                 'Bearer ',
    //                 '',
    //                 $this->request->getHeaderLine('Authorization')
    //             );

    //                     $encodedToken = decodeJWT($token);
                
    //             if (isset($encodedToken->data)) {

    //                 $jwtData = (array)$encodedToken->data;
                    
    //                 $userId = $jwtData['userid'] ?? null;
    //             }
    //         }

    //         $transaction = [

    //             'bank_account_id' => $input['bank_account_id'],

    //             'transaction_date' => $input['transaction_date'],

    //             'transfer_type'=>$input['transfer_type'] ?? null,

    //             'destination_bank_account_id'=>
    //                 $input['destination_bank_account_id'] ?? null,

    //             'destination_bank_name'=>
    //                 $input['destination_bank_name'] ?? null,

    //             'destination_account_name'=>
    //                 $input['destination_account_name'] ?? null,

    //             'destination_account_number'=>
    //                 $input['destination_account_number'] ?? null,

    //             'amount' => $input['amount'],

    //             'reference_no' => $input['reference_no'] ?? null,

    //             'check_no' => $input['check_no'] ?? null,

    //             'description' => strtoupper(trim($input['description'] ?? '')),

    //             'source' => $input['source'] ?? 'MANUAL',

    //             'created_by' => $userId,

    //             'created_at' => date('Y-m-d H:i:s')

    //         ];

    //         $db->table('bank_transactions')->insert($transaction);

    //         $transactionId = $db->insertID();

    //         $db->table('bank_accounts')
    //             ->where('bank_account_id', $input['bank_account_id'])
    //             ->update([
    //                 'current_balance' => $balanceAfter
    //             ]);

            
    //         if(
    //             $input['transaction_type']=='TRANSFER'
    //             &&
    //             $input['transfer_type']=='INTERNAL'
    //         ){

    //             $destination =
    //             $this->bankAccountModel
    //             ->find(
    //                 $input['destination_bank_account_id']
    //             );

    //         if(!$destination){

    //             throw new Exception(
    //                 'Destination account not found.'
    //             );

    //         }

    //         $destinationBalanceBefore =
    //             (float)$destination['current_balance'];

    //         $destinationBalanceAfter =
    //             $destinationBalanceBefore +
    //             (float)$input['amount'];

    //         /*
    //         |--------------------------------------------------------------------------
    //         | UPDATE DESTINATION BALANCE
    //         |--------------------------------------------------------------------------
    //         */

    //         $db->table('bank_accounts')

    //         ->where(
    //             'bank_account_id',
    //             $input['destination_bank_account_id']
    //         )

    //         ->update([

    //             'current_balance'=>
    //                 $destinationBalanceAfter

    //         ]);


    //             /*
    //             |--------------------------------------------------------------------------
    //             | INSERT DEPOSIT TRANSACTION
    //             |--------------------------------------------------------------------------
    //             */

    //             $db->table('bank_transactions')

    //                 ->insert([

    //                     'bank_account_id'=>
    //                         $input['destination_bank_account_id'],

    //                     'transaction_date'=>
    //                         $input['transaction_date'],

    //                     'transaction_type'=>
    //                         'DEPOSIT',

    //                     'transfer_type'=>
    //                         'INTERNAL',

    //                     'destination_bank_account_id'=>
    //                         $input['bank_account_id'],

    //                     'amount'=>
    //                         $input['amount'],


    //                     'balance_after'=>
    //                         $destinationBalanceAfter,

    //                     'reference_no'=>
    //                         $input['reference_no'] ?? null,

    //                     'check_no'=>
    //                         $input['check_no'] ?? null,

    //                     'description'=>
    //                         'TRANSFER FROM ACCOUNT #' .
    //                         $input['bank_account_id'],

    //                     'source'=>'TRANSFER',

    //                     'created_by'=>
    //                         $input['created_by'] ?? null,

    //                     'created_at'=>
    //                         date('Y-m-d H:i:s')

    //                 ]);

    //         }

    //         $this->createAuditLog(

    //             'BANK_TRANSACTION',

    //             $transactionId,

    //             $input['transaction_type'],

    //             null,

    //             $transaction,

    //             'Bank ' . strtolower($input['transaction_type']) . ' created.'

    //         );

    //         if ($db->transStatus() === false) {

    //             throw new Exception('Transaction failed.');

    //         }

    //         $db->transCommit();

    //         return $this->getResponse([

    //             'isError' => false,

    //             'transaction_id' => $transactionId,

    //             'message' => ucfirst(strtolower($input['transaction_type'])) . ' successfully saved.'

    //         ]);

    //     } catch (Exception $e) {

    //         $db->transRollback();

    //         return $this->getResponse([

    //             'isError' => true,

    //             'message' => $e->getMessage()

    //         ], $responseCode);

    //     }
    // }
    public function updateBank(
        int $responseCode = ResponseInterface::HTTP_OK
    )
    {

        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'bank_account_id' =>
                    'required|numeric',

                'bank_id' =>
                    'required|numeric',

                'account_name' =>
                    'required',

                'account_number' =>
                    'required',

                'account_type' =>
                    'required',

                'currency' =>
                    'required',

                'opening_balance' =>
                    'required|decimal'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        current(
                            $this->validator
                                ->getErrors()
                        ),

                    'errors' =>
                        $this->validator
                            ->getErrors()

                ], ResponseInterface::HTTP_BAD_REQUEST);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | GET ACCOUNT
            |--------------------------------------------------------------------------
            */

            $account =
                $db->table(
                    'bank_accounts'
                )
                ->where(
                    'bank_account_id',
                    $input['bank_account_id']
                )
                ->get()
                ->getRowArray();

            if (!$account) {

                throw new Exception(
                    'Bank account not found.'
                );

            }

            $oldData = $account;

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            $updateData = [

                'bank_id' =>
                    $input['bank_id'],

                'card_theme' =>
                    trim(
                        $input['card_theme']
                    ),

                'account_name' =>
                    strtoupper(
                        trim(
                            $input['account_name']
                        )
                    ),

                'account_number' =>
                    trim(
                        $input['account_number']
                    ),

                'account_type' =>
                    strtoupper(
                        trim(
                            $input['account_type']
                        )
                    ),

                'currency' =>
                    strtoupper(
                        trim(
                            $input['currency']
                        )
                    ),

                'opening_balance' =>
                    (float)$input['opening_balance'],

                'description' =>
                    strtoupper(
                        trim(
                            $input['description']
                            ?? ''
                        )
                    )

            ];

            $db->table(
                'bank_accounts'
            )
            ->where(
                'bank_account_id',
                $input['bank_account_id']
            )
            ->update(
                $updateData
            );

            /*
            |--------------------------------------------------------------------------
            | GET UPDATED RECORD
            |--------------------------------------------------------------------------
            */

            $newData =
                $db->table(
                    'bank_accounts'
                )
                ->where(
                    'bank_account_id',
                    $input['bank_account_id']
                )
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                'BANK_ACCOUNT',

                $input['bank_account_id'],

                'UPDATE',

                $oldData,

                $newData,

                'Bank account updated.'

            );

            if (
                $db->transStatus() === false
            ) {

                $db->transRollback();

                throw new Exception(
                    'Failed updating bank account.'
                );

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Bank account successfully updated.'

            ]);

        }
        catch (Exception $e) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ], $responseCode);

        }

    }

        /**
     * ============================================================
     * UPDATE TRANSACTION
     * ============================================================
     */
    public function update(
        int $responseCode = ResponseInterface::HTTP_OK
    )
    {

        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'bank_transaction_id' =>
                    'required|numeric',

                'transaction_date' =>
                    'required',

                'amount' =>
                    'required|decimal',

                'description' =>
                    'permit_empty'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        current(
                            $this->validator
                                ->getErrors()
                        ),

                    'errors' =>
                        $this->validator
                            ->getErrors()

                ], ResponseInterface::HTTP_BAD_REQUEST);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | GET TRANSACTION
            |--------------------------------------------------------------------------
            */

            $transaction =
                $db->table(
                    'bank_transactions'
                )
                ->where(
                    'bank_transaction_id',
                    $input['bank_transaction_id']
                )
                ->get()
                ->getRowArray();

            if (!$transaction) {

                throw new Exception(
                    'Transaction not found.'
                );

            }

            $oldData = $transaction;

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            */

            $updateData = [

                'transaction_date' =>
                    $input['transaction_date'],

                'reference_no' =>
                    $input['reference_no']
                    ?? null,

                'check_no' =>
                    $input['check_no']
                    ?? null,

                'description' =>
                    strtoupper(
                        trim(
                            $input['description']
                            ?? ''
                        )
                    ),

                'amount' =>
                    (float)$input['amount']

            ];

            $db->table(
                'bank_transactions'
            )
            ->where(
                'bank_transaction_id',
                $input['bank_transaction_id']
            )
            ->update(
                $updateData
            );

           
            /*
            |--------------------------------------------------------------------------
            | GET UPDATED RECORD
            |--------------------------------------------------------------------------
            */

            $newData =
                $db->table(
                    'bank_transactions'
                )
                ->where(
                    'bank_transaction_id',
                    $input['bank_transaction_id']
                )
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | AUDIT
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                'BANK_TRANSACTION',

                $input['bank_transaction_id'],

                'UPDATE',

                $oldData,

                $newData,

                'Bank transaction updated.'

            );

            if (
                $db->transStatus() === false
            ) {

                $db->transRollback();

                throw new Exception(
                    'Failed updating transaction.'
                );

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Transaction successfully updated.'

            ]);

        }
        catch (Exception $e) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ], $responseCode);

        }

    }

    
        /**
     * ============================================================
     * DELETE TRANSACTION
     * ============================================================
     */
    public function delete($transactionId)
    {
        $db = \Config\Database::connect();

        try {

            $db->transBegin();

            $transaction = $db->table('bank_transactions')
                ->where('bank_transaction_id', $transactionId)
                ->get()
                ->getRowArray();

            if (!$transaction) {
                throw new Exception('Transaction not found.');
            }

            $db->table('bank_transactions')
                ->where('bank_transaction_id', $transactionId)
                ->delete();

           
            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                'BANK_TRANSACTION',

                $transactionId,

                'DELETE',

                $transaction,

                null,

                'Bank transaction deleted.'

            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                throw new Exception(
                    'Failed deleting transaction.'
                );

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Transaction successfully deleted.'

            ]);

        } catch (Exception $e) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ]);

        }
    }

    /**
     * ============================================================
     * ACCOUNT LEDGER
     * ============================================================
     */
    public function ledger($bankAccountId)
    {

        try {

            $ledger =
                $this->bankTransactionModel
                    ->getLedger(
                        $bankAccountId
                    );

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $ledger

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }

    }

    /**
     * ============================================================
     * ACCOUNT SUMMARY
     * ============================================================
     */
    public function summary($bankAccountId)
    {

        try {

            $db = \Config\Database::connect();

            /*
            |--------------------------------------------------------------------------
            | ACCOUNT
            |--------------------------------------------------------------------------
            */

            $account = $db->table('bank_accounts')
                ->where(
                    'bank_account_id',
                    $bankAccountId
                )
                ->get()
                ->getRowArray();

            if (!$account) {

                throw new Exception(
                    'Bank account not found.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | TOTAL DEPOSITS
            |--------------------------------------------------------------------------
            */

            $deposit = $db->table('bank_transactions')
                ->selectSum('amount')
                ->where(
                    'bank_account_id',
                    $bankAccountId
                )
                ->where(
                    'transaction_type',
                    'DEPOSIT'
                )
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | TOTAL WITHDRAWALS
            |--------------------------------------------------------------------------
            */

            $withdraw = $db->table('bank_transactions')
                ->selectSum('amount')
                ->where(
                    'bank_account_id',
                    $bankAccountId
                )
                ->where(
                    'transaction_type',
                    'WITHDRAWAL'
                )
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION COUNT
            |--------------------------------------------------------------------------
            */

            $transactionCount =
                $db->table('bank_transactions')
                ->where(
                    'bank_account_id',
                    $bankAccountId
                )
                ->countAllResults();

            $summary = [

                'bank_account_id' =>
                    $bankAccountId,

                'account_name' =>
                    $account['account_name'],

                'account_number' =>
                    $account['account_number'],

                'opening_balance' =>
                    (float)$account['opening_balance'],

                'current_balance' =>
                    (float)$account['current_balance'],

                'total_deposits' =>
                    (float)(
                        $deposit['amount']
                        ?? 0
                    ),

                'total_withdrawals' =>
                    (float)(
                        $withdraw['amount']
                        ?? 0
                    ),

                'transaction_count' =>
                    $transactionCount

            ];

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $summary

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ]);

        }

    }

     public function addBank()
    {
        try {
            helper('jwt');
            $userId = null;
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
                }
            }
            $data = [

                'bank_id'         => $this->request->getPost('bank_id'),
                'account_name'    => $this->request->getPost('account_name'),
                'account_number'  => $this->request->getPost('account_number'),
                'account_type'    => $this->request->getPost('account_type'),
                'currency'        => $this->request->getPost('currency'),
                'opening_balance' => $this->request->getPost('opening_balance'),
                'current_balance' => $this->request->getPost('opening_balance'),
                'description'     => $this->request->getPost('description'),
                'card_theme'     => $this->request->getPost('card_theme'),
                'created_by'      => $userId,
                'is_active'       => 1

            ];

            $result = $this->bankAccountModel->saveBankAccount($data);

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Bank account saved successfully.',

                'result'  => $result,

                'data'    => $data

            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage(),

                'file'    => $e->getFile(),

                'line'    => $e->getLine()

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

    public function transactions()
    {
        try {

            $request = service('request');

            $draw   = (int)$request->getGet('draw');
            $start  = (int)$request->getGet('start');
            $length = (int)$request->getGet('length');

            $search = trim($request->getGet('search') ?? '');

            $order = $request->getGet('order');

            $orderColumn = 0;
            $orderDir    = 'DESC';

            if (!empty($order)) {

                $orderColumn = $order[0]['column'];

                $orderDir = strtoupper($order[0]['dir']);

            }

            $filters = [

                'bank_account_id' => $request->getGet('bank_account_id'),
                'transaction_type' => $request->getGet('transaction_type'),
                'date_from' => $request->getGet('date_from'),
                'date_to' => $request->getGet('date_to')

            ];

            $result = $this->bankTransactionModel
                ->getTransactions(

                    $start,
                    $length,
                    $search,
                    $orderColumn,
                    $orderDir,
                    $filters

                );

            return $this->response->setJSON([

                'draw' => $draw,

                'recordsTotal' => $result['total'],

                'recordsFiltered' => $result['filtered'],

                'data' => $result['data']

            ]);

        } catch (\Exception $e) {

            return $this->response->setJSON([

                'draw' => 0,

                'recordsTotal' => 0,

                'recordsFiltered' => 0,

                'data' => [],

                'message' => $e->getMessage()

            ]);

        }
    }

    public function transactionDetails($transactionId)
    {
        try {

            $transaction = $this->bankTransactionModel
                ->getTransactionDetails($transactionId);

            if (!$transaction) {

                return $this->response->setJSON([

                    'isError' => true,

                    'message' => 'Transaction not found.'

                ]);

            }

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'data' => $transaction

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }

    public function dashboardAccountSummary($bankAccountId)
    {
         try {

            $data = $this->bankTransactionModel->getDashboardTransactionSummary($bankAccountId);

            return $this->response->setJSON([
                'isError' => false,
                'message' => 'Dashboard summary retrieved successfully.',
                'data'    => $data
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function closeBankAccount(
        int $bankAccountId
    )
    {
        helper('jwt');

        try {

            $userId = null;

            $authHeader = $this->request->getHeaderLine('Authorization');

            if (!empty($authHeader)) {

                $token = str_replace(
                    'Bearer ',
                    '',
                    $authHeader
                );

                $decoded = decodeJWT($token);

                if (isset($decoded->data)) {

                    $jwtData = (array)$decoded->data;

                    $userId = $jwtData['userid'] ?? null;

                }

            }

            $reason = $this->request->getVar('reason');

            $result = $this->bankAccountModel->closeBankAccount(

                $bankAccountId,

                $userId,

                $reason

            );

            if ($result['isError']) {

                return $this->getResponse(

                    $result,

                    ResponseInterface::HTTP_BAD_REQUEST

                );

            }

            return $this->getResponse($result);

        } catch (\Exception $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ], ResponseInterface::HTTP_BAD_REQUEST);

        }

    }

    public function voidTransaction($bankTransactionId)
    {

        helper('jwt');

        try {

            $userId = null;

            $authHeader = $this->request->getHeaderLine('Authorization');

            if (!empty($authHeader)) {

                $token = str_replace(
                    'Bearer ',
                    '',
                    $authHeader
                );

                $decoded = decodeJWT($token);

                if (isset($decoded->data)) {

                    $jwtData = (array)$decoded->data;

                    $userId = $jwtData['userid'] ?? null;

                }

            }

            $reason = $this->request->getVar('reason');

            $result = $this->bankTransactionModel->voidTransaction(

                $bankTransactionId,

                $userId,

                $reason

            );

            return $this->getResponse(

                $result,

                $result['isError']
                    ? ResponseInterface::HTTP_BAD_REQUEST
                    : ResponseInterface::HTTP_OK

            );

        } catch (\Exception $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ], ResponseInterface::HTTP_BAD_REQUEST);

        }
    }
 
}
