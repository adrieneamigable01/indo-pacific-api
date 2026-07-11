<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\BorrowerModel;
use App\Models\CashierVaultModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class CashierVault extends BaseController
{
    protected $cashierVaultModel;
    protected $borrowerModel;
    public function __construct()
    {
        $this->cashierVaultModel =
            new CashierVaultModel();

        $this->borrowerModel = new BorrowerModel();
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return $this->get();
    }

    /*
    |--------------------------------------------------------------------------
    | GET TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    public function get()
    {
        try {

            $draw = (int)$this->request->getGet('draw');

            $start = (int)$this->request->getGet('start');

            $length = (int)$this->request->getGet('length');

            $orderColumn = $this->request->getGet('orderColumn') ?? 'cashier_transaction_id';

            $orderDir = $this->request->getGet('orderDir') ?? 'DESC';

            $search = $this->request->getGet('search');

            $cashierTransactionId = $this->request->getGet('cashier_transaction_id');

            $cashierId = $this->request->getGet('cashier_id');

            $transactionType = $this->request->getGet('transaction_type');

            $businessDate = $this->request->getGet('business_date');

            $dateFrom = $businessDate;

            $dateTo = $businessDate;

            $data = $this->cashierVaultModel->getTransactions(

                $search,

                $cashierTransactionId,

                $cashierId,

                $transactionType,

                $dateFrom,

                $dateTo,

                $start,

                $length,

                $orderColumn,

                $orderDir

            );

            return $this->response->setJSON([

                "draw"=>$draw,

                "recordsTotal"=>$this->cashierVaultModel->countTransactions(

                    $cashierId,

                    $dateFrom,

                    $dateTo

                ),

                "recordsFiltered"=>$this->cashierVaultModel->countFilteredTransactions(

                    $search,

                    $cashierTransactionId,

                    $cashierId,

                    $transactionType,

                    $dateFrom,

                    $dateTo

                ),

                "data"=>$data

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                "draw"=>0,

                "recordsTotal"=>0,

                "recordsFiltered"=>0,

                "data"=>[],

                "error"=>$e->getMessage()

            ]);

        }
    }

    
    public function getTransactionDetails()
    {
        try{

            $cashierTransactionId =

                $this->request

                ->getGet(

                    'cashier_transaction_id'

                );

            $data =

                $this->cashierVaultModel

                ->getTransactionDetails(

                    $cashierTransactionId

                );

            return $this->response->setJSON([

                'isError'=>false,

                'message'=>'Success',

                'data'=>$data

            ]);

        }catch(Exception $e){

            return $this->response->setJSON([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }
    }
    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    public function summary()
    {
        try {

            $cashierId =
                $this->request->getGet(
                    'cashier_id'
                );

            $data =
                $this->cashierVaultModel
                ->getSummary(
                    $cashierId
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

    public function getSummary()
    {
        try {

            $cashierId =

                $this->request->getGet(
                    'cashier_id'
                );

            if(empty($cashierId)){

                return $this->response->setJSON([

                    'isError'=>true,

                    'message'=>'cashier_id is required.'

                ]);

            }

            $businessDate =

                $this->request->getGet(
                    'business_date'
                );

            $isClosing =
            $this->cashierVaultModel
                ->isBusinessDateClosed(
                    $cashierId,
                    $businessDate
                );

            $summary =

                $this->cashierVaultModel

                ->getSummary(

                    $cashierId,

                    $businessDate

                );

            return $this->response->setJSON([

                'isError'=>false,

                'message'=>'Success',

                'isClosing'=> $isClosing,

                'data'=>$summary

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }

    }

    public function isBusinessDateClosed(
        int $cashierId,
        string $businessDate
    ): bool
    {
        return $this->db
            ->table('cashier_daily_close')
            ->where('cashier_id', $cashierId)
            ->where('business_date', $businessDate)
            ->whereIn('status', [
                'PENDING',
                'APPROVED'
            ])
            ->countAllResults() > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAILS
    |--------------------------------------------------------------------------
    */

    public function details(
        $cashierTransactionId
    )
    {
        try {

            $data =
                $this->cashierVaultModel
                ->getTransactionDetails(
                    $cashierTransactionId
                );

            if(
                empty($data)
            ){

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Transaction not found.'

                ]);

            }

            return $this->getResponse([

                'isError' => false,

                'message' => 'Success',

                'data' => $data

            ]);

        } catch (Exception $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | RECEIVE TRANSFER
    |--------------------------------------------------------------------------
    */

    public function receiveTransfer(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db =
            \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
                    'required|numeric',

                'amount' =>
                    'required|decimal',

                'business_date' =>
                    'permit_empty|valid_date[Y-m-d]'

            ];

            if(
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $db->transBegin();

            $businessDate =
                $input['business_date']
                ?? date('Y-m-d');

            $currentBalance =
            $this->cashierVaultModel
                ->getLatestBalance(

                    $input['cashier_id'],

                    $businessDate

                );

            $balanceAfter =
                $currentBalance +
                (float)$input['amount'];

            $transactionId =
                $this->cashierVaultModel
                    ->receiveTransfer([

                        'cashier_id' =>
                            $input['cashier_id'],

                        'transaction_type' =>
                            'TRANSFER_IN',

                        'amount' =>
                            $input['amount'],

                        'balance_before' =>
                            $currentBalance,

                        'balance_after' =>
                            $balanceAfter,

                        'reference_no' =>
                            $input['reference_no']
                            ?? '',

                        'remarks' =>
                            $input['remarks']
                            ?? '',

                        'created_by' =>
                            $input['created_by']
                            ?? 1

                    ]);

            $db->transCommit();

            return $this->getResponse([

                'isError'=>false,

                'cashier_transaction_id'=>
                    $transactionId,

                'current_balance'=>
                    $balanceAfter,

                'message'=>
                    'Transfer received successfully.'

            ]);

        }catch(Exception $ex){

            $db->transRollback();

            return $this->getResponse([

                'isError'=>true,

                'message'=>$ex->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | LOAN RELEASE
    |--------------------------------------------------------------------------
    */

    public function loanRelease(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db =
            \Config\Database::connect();

        try{

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules=[

                'cashier_id' =>
                    'required|numeric',

                'loan_id' =>
                    'required|numeric',

                'amount' =>
                    'required|decimal'

            ];

            if(
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $db->transBegin();

            $currentBalance =
                $this->cashierVaultModel
                    ->getLatestBalance(
                        $input['cashier_id']
                    );

            if(
                $currentBalance <
                $input['amount']
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>'Insufficient cashier balance.'

                ]);

            }

            $balanceAfter =
                $currentBalance -
                (float)$input['amount'];

            $transactionId =
                $this->cashierVaultModel
                    ->loanRelease([

                        'cashier_id' =>
                            $input['cashier_id'],

                        'loan_id' =>
                            $input['loan_id'],

                        'transaction_type' =>
                            'LOAN_RELEASE',

                        'amount' =>
                            $input['amount'],

                        'balance_before' =>
                            $currentBalance,

                        'balance_after' =>
                            $balanceAfter,

                        'reference_no' =>
                            $input['reference_no']
                            ?? '',

                        'remarks' =>
                            $input['remarks']
                            ?? '',

                        'created_by' =>
                            $input['created_by']
                            ?? 1

                    ]);

            $db->transCommit();

            return $this->getResponse([

                'isError'=>false,

                'cashier_transaction_id'=>
                    $transactionId,

                'current_balance'=>
                    $balanceAfter,

                'message'=>
                    'Loan released successfully.'

            ]);

        }catch(Exception $ex){

            $db->transRollback();

            return $this->getResponse([

                'isError'=>true,

                'message'=>$ex->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | COLLECTION
    |--------------------------------------------------------------------------
    */

    public function collection(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
                    'required|numeric',

                'collection_id' =>
                    'required|numeric',

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

                    'message' => current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $db->transBegin();

            $currentBalance =
                $this->cashierVaultModel
                    ->getLatestBalance(
                        $input['cashier_id']
                    );

            $balanceAfter =
                $currentBalance +
                (float)$input['amount'];

            $transactionId =
                $this->cashierVaultModel
                    ->collection([

                        'cashier_id' =>
                            $input['cashier_id'],

                        'collection_id' =>
                            $input['collection_id'],

                        'transaction_type' =>
                            'COLLECTION',

                        'amount' =>
                            $input['amount'],

                        'balance_before' =>
                            $currentBalance,

                        'balance_after' =>
                            $balanceAfter,

                        'reference_no' =>
                            $input['reference_no']
                            ?? '',

                        'remarks' =>
                            $input['remarks']
                            ?? '',

                        'created_by' =>
                            $input['created_by']
                            ?? 1

                    ]);

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'cashier_transaction_id' =>
                    $transactionId,

                'current_balance' =>
                    $balanceAfter,

                'message' =>
                    'Collection successfully posted.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' => $ex->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | EXPENSE
    |--------------------------------------------------------------------------
    */

    public function expense(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
                    'required|numeric',

                'expense_id' =>
                    'permit_empty|numeric',

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

                    'message' => current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $db->transBegin();

            $currentBalance =
                $this->cashierVaultModel
                    ->getLatestBalance(
                        $input['cashier_id']
                    );

            if (
                $currentBalance <
                $input['amount']
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Insufficient cashier balance.'

                ]);

            }

            $balanceAfter =
                $currentBalance -
                (float)$input['amount'];

            $transactionId =
                $this->cashierVaultModel
                    ->expense([

                        'cashier_id' =>
                            $input['cashier_id'],

                        'expense_id' =>
                            $input['expense_id']
                            ?? null,

                        'transaction_type' =>
                            'EXPENSE',

                        'amount' =>
                            $input['amount'],

                        'balance_before' =>
                            $currentBalance,

                        'balance_after' =>
                            $balanceAfter,

                        'reference_no' =>
                            $input['reference_no']
                            ?? '',

                        'remarks' =>
                            $input['remarks']
                            ?? '',

                        'created_by' =>
                            $input['created_by']
                            ?? 1

                    ]);

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'cashier_transaction_id' =>
                    $transactionId,

                'current_balance' =>
                    $balanceAfter,

                'message' =>
                    'Expense successfully posted.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' => $ex->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | RETURN TO MANAGER
    |--------------------------------------------------------------------------
    */

    // public function returnToManager(
    //     int $responseCode =
    //     ResponseInterface::HTTP_OK
    // )
    // {
    //     $db = \Config\Database::connect();

    //     try {

    //         $input =
    //             $this->getRequestInput(
    //                 $this->request
    //             );

    //         $rules = [

    //             'cashier_id' =>
    //                 'required|numeric',

    //             'amount' =>
    //                 'required|decimal'

    //         ];

    //         if(
    //             !$this->validateRequest(
    //                 $input,
    //                 $rules
    //             )
    //         ){

    //             return $this->getResponse([

    //                 'isError'=>true,

    //                 'message'=>current(
    //                     $this->validator
    //                         ->getErrors()
    //                 )

    //             ]);

    //         }

    //         $db->transBegin();

    //         $currentBalance =
    //             $this->cashierVaultModel
    //                 ->getLatestBalance(
    //                     $input['cashier_id']
    //                 );

    //         if(
    //             $currentBalance <
    //             $input['amount']
    //         ){

    //             return $this->getResponse([

    //                 'isError'=>true,

    //                 'message'=>'Insufficient cashier balance.'

    //             ]);

    //         }

    //         $balanceAfter =
    //             $currentBalance -
    //             (float)$input['amount'];

    //         /*
    //         |--------------------------------------------------------------------------
    //         | CASHIER LEDGER
    //         |--------------------------------------------------------------------------
    //         */

    //         $cashierTransactionId =
    //             $this->cashierVaultModel
    //                 ->returnToManager([

    //                     'cashier_id' =>
    //                         $input['cashier_id'],

    //                     'transaction_type' =>
    //                         'RETURN_TO_MANAGER',

    //                     'amount' =>
    //                         $input['amount'],

    //                     'balance_before' =>
    //                         $currentBalance,

    //                     'balance_after' =>
    //                         $balanceAfter,

    //                     'reference_no' =>
    //                         $input['reference_no']
    //                         ?? '',

    //                     'remarks' =>
    //                         $input['remarks']
    //                         ?? '',

    //                     'created_by' =>
    //                         $input['created_by']
    //                         ?? 1

    //                 ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | MANAGER LEDGER
    //         |--------------------------------------------------------------------------
    //         */

    //         $managerModel =
    //             new \App\Models\ManagerVaultModel();

    //         $managerBalance =
    //             $managerModel
    //                 ->getLatestManagerBalance();

    //         $managerAfter =
    //             $managerBalance +
    //             (float)$input['amount'];

    //         $managerModel
    //             ->addTransaction([

    //                 'transaction_type' =>
    //                     'RETURN_FROM_CASHIER',

    //                 'cashier_id' =>
    //                     $input['cashier_id'],

    //                 'amount' =>
    //                     $input['amount'],

    //                 'balance_before' =>
    //                     $managerBalance,

    //                 'balance_after' =>
    //                     $managerAfter,

    //                 'reference_no' =>
    //                     $input['reference_no']
    //                     ?? '',

    //                 'remarks' =>
    //                     $input['remarks']
    //                     ?? '',

    //                 'created_by' =>
    //                     $input['created_by']
    //                     ?? 1

    //             ]);

    //         /*
    //             |--------------------------------------------------------------------------
    //             | DAILY CLOSE
    //             |--------------------------------------------------------------------------
    //             */

    //             $businessDate =
    //                 $input['business_date']
    //                 ?? date('Y-m-d');

    //             $db->table(
    //                 'cashier_daily_close'
    //             )->insert([

    //                 'cashier_id' =>
    //                     $input['cashier_id'],

    //                 'business_date' =>
    //                     $businessDate,

    //                 'expected_cash' =>
    //                     $currentBalance,

    //                 'actual_cash' =>
    //                     $input['amount'],

    //                 'returned_amount' =>
    //                     $input['amount'],

    //                 'variance' =>
    //                     $currentBalance -
    //                     $input['amount'],

    //                 'remarks' =>
    //                     $input['remarks']
    //                     ?? '',

    //                 'closed_by' =>
    //                     $input['created_by']
    //                     ?? 1,

    //                 'closed_at' =>
    //                     date('Y-m-d H:i:s')

    //             ]);

    //         $db->transCommit();

    //         return $this->getResponse([

    //             'isError'=>false,

    //             'cashier_transaction_id'=>
    //                 $cashierTransactionId,

    //             'current_balance'=>
    //                 $balanceAfter,

    //             'message'=>
    //                 'Cash successfully returned to manager.'

    //         ]);

    //     }catch(Exception $ex){

    //         $db->transRollback();

    //         return $this->getResponse([

    //             'isError'=>true,

    //             'message'=>$ex->getMessage()

    //         ]);

    //     }

    // }


    public function returnToManager(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();
        
        try {

            helper('jwt');


            $userId = null;

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
                    }
                }

            } catch (\Exception $e) {
                log_message('error', 'Audit JWT Error: ' . $e->getMessage());
            }

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
                    'required|numeric',

                'actual_cash' =>
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

                    'message' => current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $businessDate =
                $input['business_date']
                ?? date('Y-m-d');

            /*
            |--------------------------------------------------------------------------
            | CHECK IF ALREADY PENDING / APPROVED
            |--------------------------------------------------------------------------
            */

            $existing =

                $db->table(
                    'cashier_daily_close'
                )

                ->where(
                    'cashier_id',
                    $input['cashier_id']
                )

                ->where(
                    'business_date',
                    $businessDate
                )

                ->whereIn(
                    'status',
                    [
                        'PENDING',
                        'APPROVED'
                    ]
                )

                ->countAllResults();

            if ($existing > 0) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Daily Close already exists for this business date.'

                ]);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | CURRENT BALANCE
            |--------------------------------------------------------------------------
            */

            $currentBalance =

                $this->cashierVaultModel
                    ->getLatestBalance(

                        $input['cashier_id'],

                        $businessDate

                    );

            if (
                $currentBalance <
                $input['actual_cash']
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Insufficient cashier balance.'

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | CREATE DAILY CLOSE REQUEST
            |--------------------------------------------------------------------------
            */

            $db->table(
                'cashier_daily_close'
            )->insert([

                'cashier_id' =>
                    $input['cashier_id'],

                'business_date' =>
                    $businessDate,

                'expected_cash' =>
                    $currentBalance,

                'actual_cash' =>
                    $input['actual_cash'],

                'returned_amount' =>
                    $input['actual_cash'],

                'variance' =>
                    $currentBalance -
                    $input['actual_cash'],

                'remarks' =>
                    $input['remarks']
                    ?? '',

                'closed_by' => $userId,

                'closed_at' =>
                    date('Y-m-d H:i:s'),

                'status' =>
                    'PENDING'

            ]);

            $dailyCloseId =
                $db->insertID();


            /*
            |--------------------------------------------------------------------------
            | SAVE DENOMINATIONS
            |--------------------------------------------------------------------------
            */

            if (

                !empty($input['denominations']) &&

                is_array($input['denominations'])

            ) {

                foreach (

                    $input['denominations']

                    as $row

                ) {

                    $denomination =

                        (float)($row['denomination'] ?? 0);

                    $quantity =

                        (int)($row['quantity'] ?? 0);

                    if (

                        $denomination <= 0 ||

                        $quantity <= 0

                    ) {

                        continue;

                    }

                    $db

                        ->table(

                            'cashier_daily_close_denominations'

                        )

                        ->insert([

                            'cashier_daily_close_id' =>

                                $dailyCloseId,

                            'denomination' =>

                                $denomination,

                            'quantity' =>

                                $quantity,

                            'total' =>

                                $denomination * $quantity

                        ]);

                }

            }

            /*
            |--------------------------------------------------------------------------
            | APPROVAL LOG
            |--------------------------------------------------------------------------
            */

            $db->table(
                'cashier_daily_close_logs'
            )->insert([

                'cashier_daily_close_id' =>
                    $dailyCloseId,

                'action' =>
                    'PENDING',

                'remarks' =>
                    'Waiting for manager approval.',

                'action_by' =>
                    $userId
                    ?? 1

            ]);

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'cashier_daily_close_id' =>
                    $dailyCloseId,

                'status' =>
                    'PENDING',

                'message' =>
                    'Daily Close submitted for manager approval.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $ex->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | VOID TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function voidTransaction(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        try{

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules=[

                'cashier_transaction_id'=>
                    'required|numeric',
                'cashier_id'=>
                    'required|numeric',
                'business_date' =>
                    'required|valid_date[Y-m-d]'

            ];

            if(
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $this->cashierVaultModel
                ->update(

                    $input['cashier_transaction_id'],

                    [

                        'is_active'=>0,

                        'deleted_at'=>
                            date(
                                'Y-m-d H:i:s'
                            )

                    ]

                );


            $this->cashierVaultModel->rebuildCashierBalances(

                $input['cashier_id'],

                $input['business_date']

            );

            return $this->getResponse([

                'isError'=>false,

                'message'=>
                    'Transaction successfully voided.'

            ]);

        }catch(Exception $ex){

            return $this->getResponse([

                'isError'=>true,

                'message'=>$ex->getMessage()

            ]);

        }

    }

    public function cashTransaction(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
                    'required|numeric',

                'transaction_type' =>
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

                    'message' => current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $transactionTime =
                $input['transaction_time']
                ?? date('H:i');

            $businessDate =
                $input['business_date']
                ?? date('Y-m-d');

            $currentDateTime =
                $businessDate . ' ' .
                $transactionTime . ':00';

            $borrowerId =
                !empty($input['borrower_id'])
                    ? (int)$input['borrower_id']
                    : null;
            /*
            |--------------------------------------------------------------------------
            | CHECK DAILY CLOSE
            |--------------------------------------------------------------------------
            */

            $isClosed =
                $this->cashierVaultModel
                    ->isBusinessDateClosed(

                        $input['cashier_id'],

                        $businessDate

                    );

            if ($isClosed) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Business date is already closed.'

                ]);

            }

            if ($borrowerId !== null) {

                $borrower =
                    $this->borrowerModel
                        ->find($borrowerId);

                if (!$borrower) {

                    return $this->getResponse([

                        'isError' => true,

                        'message' => 'Invalid borrower selected.'

                    ]);

                }

            }

            $db->transBegin();

            $currentBalance =
                $this->cashierVaultModel
                    ->getLatestBalance(

                        $input['cashier_id'],

                        $businessDate

                    );

            $amount =
                (float)$input['amount'];

            $transactionType =
                strtoupper(
                    $input['transaction_type']
                );

            /*
            |--------------------------------------------------------------------------
            | COMPUTE BALANCE
            |--------------------------------------------------------------------------
            */

            switch ($transactionType) {

                case 'CASH IN':

                    $balanceAfter =
                        $currentBalance +
                        $amount;

                    break;

                case 'CASH OUT':

                    if (
                        $amount >
                        $currentBalance
                    ) {

                        return $this->getResponse([

                            'isError' => true,

                            'message' =>
                                'Insufficient cashier balance.'

                        ]);

                    }

                    $balanceAfter =
                        $currentBalance -
                        $amount;

                    break;

                default:

                    return $this->getResponse([

                        'isError' => true,

                        'message' =>
                            'Invalid transaction type.'

                    ]);

            }

            /*
            |--------------------------------------------------------------------------
            | SAVE HEADER
            |--------------------------------------------------------------------------
            */

            $transactionId =
                $this->cashierVaultModel
                    ->insertTransaction([

                        'cashier_id' =>
                            $input['cashier_id'],

                        'business_date' =>
                            $currentDateTime,

                        'transaction_type' =>
                            $transactionType,

                        'amount' =>
                            $amount,

                        'balance_before' =>
                            $currentBalance,

                        'balance_after' =>
                            $balanceAfter,

                        'reference_no' =>
                           $this
                            ->cashierVaultModel
                            ->generateReferenceNo(
                                 $transactionType
                            ),
                        'borrower_id' => $borrowerId,
                        'remarks' =>
                            $input['remarks']
                            ?? '',
                        'created_at' => $businessDate,
                        'created_by' =>
                            $input['created_by']
                            ?? 1

                    ]);

            /*
            |--------------------------------------------------------------------------
            | SAVE DETAILS
            |--------------------------------------------------------------------------
            */

            if (
                !empty($input['details'])
            ) {

                foreach (
                    $input['details']
                    as $detail
                ) {

                    $this->cashierVaultModel
                    ->insertDetail([

                        'cashier_transaction_id' =>
                            $transactionId,

                        'transaction_type' =>
                            strtoupper(
                                $detail['transaction_type']
                                ?? 'CASH_OUT'
                            ),

                        'reference_type' =>
                            $detail['reference_type']
                            ?? '',

                        'reference_id' =>
                            $detail['reference_id']
                            ?? null,

                        'description' =>
                            $detail['description']
                            ?? '',

                        'amount' =>
                            (float)$detail['amount']

                    ]);

                }

            }


            $this->cashierVaultModel->rebuildCashierBalances(

                $input['cashier_id'],

                $businessDate

            );

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'cashier_transaction_id' =>
                    $transactionId,

                'current_balance' =>
                    $balanceAfter,

                'message' =>
                    'Transaction successfully saved.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' => $ex->getMessage()

            ]);

        }

    }

    // public function approveDailyClose(
    //     int $responseCode =
    //     ResponseInterface::HTTP_OK
    // )
    // {
    //     $db = \Config\Database::connect();

    //     try {

    //         $input =
    //             $this->getRequestInput(
    //                 $this->request
    //             );

    //         $rules = [

    //             'cashier_daily_close_id' =>
    //                 'required|numeric',

    //             'approved_by' =>
    //                 'required|numeric'

    //         ];

    //         if (
    //             !$this->validateRequest(
    //                 $input,
    //                 $rules
    //             )
    //         ) {

    //             return $this->getResponse([

    //                 'isError' => true,

    //                 'message' => current(
    //                     $this->validator->getErrors()
    //                 )

    //             ]);

    //         }

    //         $db->transBegin();

    //         /*
    //         |--------------------------------------------------------------------------
    //         | GET DAILY CLOSE
    //         |--------------------------------------------------------------------------
    //         */

    //         $dailyClose =

    //             $db->table(
    //                 'cashier_daily_close'
    //             )

    //             ->where(
    //                 'cashier_daily_close_id',
    //                 $input['cashier_daily_close_id']
    //             )

    //             ->get()

    //             ->getRowArray();

    //         if (!$dailyClose) {

    //             return $this->getResponse([

    //                 'isError' => true,

    //                 'message' =>
    //                     'Daily Close not found.'

    //             ]);

    //         }

    //         if ($dailyClose['status'] != 'PENDING') {

    //             return $this->getResponse([

    //                 'isError' => true,

    //                 'message' =>
    //                     'Daily Close is already processed.'

    //             ]);

    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | MANAGER BALANCE
    //         |--------------------------------------------------------------------------
    //         */

    //         $managerModel =
    //             new \App\Models\ManagerVaultModel();

    //         $managerBalance =
    //             $managerModel
    //                 ->getLatestManagerBalance();

    //         $managerAfter =
    //             $managerBalance +
    //             $dailyClose['returned_amount'];

    //         /*
    //         |--------------------------------------------------------------------------
    //         | INSERT MANAGER LEDGER
    //         |--------------------------------------------------------------------------
    //         */

    //         $managerModel
    //             ->addTransaction([

    //                 'transaction_type' =>
    //                     'RETURN_FROM_CASHIER',

    //                 'cashier_id' =>
    //                     $dailyClose['cashier_id'],

    //                 'business_date' =>
    //                     $dailyClose['business_date'],

    //                 'amount' =>
    //                     $dailyClose['returned_amount'],

    //                 'balance_before' =>
    //                     $managerBalance,

    //                 'balance_after' =>
    //                     $managerAfter,

    //                 'reference_no' =>
    //                     'DC-' .
    //                     $dailyClose['cashier_daily_close_id'],

    //                 'remarks' =>
    //                     $dailyClose['remarks'],

    //                 'created_by' =>
    //                     $input['approved_by']

    //             ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | INSERT CASHIER LEDGER
    //         |--------------------------------------------------------------------------
    //         */

    //         $cashierModel =
    //             new \App\Models\CashierVaultModel();

    //         $cashierModel
    //             ->returnToManager([

    //                 'cashier_id' =>
    //                     $dailyClose['cashier_id'],

    //                 'transaction_type' =>
    //                     'RETURN_TO_MANAGER',

    //                 'amount' =>
    //                     $dailyClose['returned_amount'],

    //                 'balance_before' =>
    //                     $dailyClose['expected_cash'],

    //                 'balance_after' =>
    //                     $dailyClose['expected_cash']
    //                     -
    //                     $dailyClose['returned_amount'],

    //                 'reference_no' =>
    //                     'DC-' .
    //                     $dailyClose['cashier_daily_close_id'],

    //                 'remarks' =>
    //                     'Approved Daily Close',

    //                 'created_by' =>
    //                     $input['approved_by']

    //             ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | UPDATE DAILY CLOSE
    //         |--------------------------------------------------------------------------
    //         */

    //         $db->table(
    //             'cashier_daily_close'
    //         )

    //         ->where(
    //             'cashier_daily_close_id',
    //             $dailyClose['cashier_daily_close_id']
    //         )

    //         ->update([

    //             'status' =>
    //                 'APPROVED',

    //             'approved_by' =>
    //                 $input['approved_by'],

    //             'approved_at' =>
    //                 date('Y-m-d H:i:s')

    //         ]);

    //         /*
    //         |--------------------------------------------------------------------------
    //         | LOG
    //         |--------------------------------------------------------------------------
    //         */

    //         $db->table(
    //             'cashier_daily_close_logs'
    //         )

    //         ->insert([

    //             'cashier_daily_close_id' =>
    //                 $dailyClose['cashier_daily_close_id'],

    //             'action' =>
    //                 'APPROVED',

    //             'remarks' =>
    //                 'Manager approved daily close.',

    //             'action_by' =>
    //                 $input['approved_by']

    //         ]);

    //         $db->transCommit();

    //         return $this->getResponse([

    //             'isError' => false,

    //             'message' =>
    //                 'Daily Close successfully approved.'

    //         ]);

    //     } catch (Exception $ex) {

    //         $db->transRollback();

    //         return $this->getResponse([

    //             'isError' => true,

    //             'message' =>
    //                 $ex->getMessage()

    //         ]);

    //     }

    // }

    public function approveDailyClose(
    int $responseCode =
    ResponseInterface::HTTP_OK
)
{
    $db =
        \Config\Database::connect();

    try {

        $input =
            $this->getRequestInput(
                $this->request
            );

        $rules = [

            'cashier_daily_close_id' =>
                'required|numeric',

            'approved_by' =>
                'required|numeric'

        ];

        if (
            !$this->validateRequest(
                $input,
                $rules
            )
        ) {

            return $this->getResponse([

                'isError' => true,

                'message' => current(
                    $this->validator
                        ->getErrors()
                )

            ]);

        }

        $db->transBegin();

        /*
        |--------------------------------------------------------------------------
        | GET DAILY CLOSE
        |--------------------------------------------------------------------------
        */

        $dailyClose =

            $db
            ->table(
                'cashier_daily_close'
            )
            ->where(
                'cashier_daily_close_id',
                $input[
                    'cashier_daily_close_id'
                ]
            )
            ->get()
            ->getRowArray();

        if (!$dailyClose) {

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    'Daily Close not found.'

            ]);

        }

        if (
            $dailyClose['status']
            != 'PENDING'
        ) {

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    'Daily Close already processed.'

            ]);

        }

        /*
        |--------------------------------------------------------------------------
        | MANAGER MODEL
        |--------------------------------------------------------------------------
        */

        $managerModel =
            new \App\Models\ManagerVaultModel();

        /*
        |--------------------------------------------------------------------------
        | MANAGER CURRENT BALANCE
        |--------------------------------------------------------------------------
        */

        $managerBalance =
            $managerModel
                ->getLatestManagerBalance();

        $managerAfter =
            $managerBalance +
            (float)
            $dailyClose[
                'returned_amount'
            ];

        /*
        |--------------------------------------------------------------------------
        | INSERT MANAGER LEDGER
        |--------------------------------------------------------------------------
        */

        $managerTransactionId =

            $managerModel
                ->addTransaction([

                    'transaction_type' =>
                        'RETURN TO VAULT',

                    'cashier_id' =>
                        $dailyClose[
                            'cashier_id'
                        ],
                    'cashier_daily_close_id' =>
                        $input[
                            'cashier_daily_close_id'
                        ],

                    'business_date' =>
                        $dailyClose[
                            'business_date'
                        ],

                    'amount' =>
                        $dailyClose[
                            'returned_amount'
                        ],

                    'balance_before' =>
                        $managerBalance,

                    'balance_after' =>
                        $managerAfter,

                    'reference_no' =>
                        'DC-' .
                        $dailyClose[
                            'cashier_daily_close_id'
                        ],

                    'remarks' =>
                        $dailyClose[
                            'remarks'
                        ],

                    'created_by' =>
                        $input[
                            'approved_by'
                        ]

                ]);

            /*
            |--------------------------------------------------------------------------
            | CASHIER MODEL
            |--------------------------------------------------------------------------
            */

            $cashierModel =
                new \App\Models\CashierVaultModel();

            /*
            |--------------------------------------------------------------------------
            | GET CASHIER CURRENT BALANCE
            |--------------------------------------------------------------------------
            */

            $cashierCurrentBalance =

                $cashierModel
                    ->getLatestBalance(

                        $dailyClose[
                            'cashier_id'
                        ],

                        $dailyClose[
                            'business_date'
                        ]

                    );

            $cashierAfter =

                $cashierCurrentBalance -

                (float)
                $dailyClose[
                    'returned_amount'
                ];

            /*
            |--------------------------------------------------------------------------
            | INSERT CASHIER RETURN
            |--------------------------------------------------------------------------
            */

            $cashierTransactionId =

                $cashierModel
                    ->returnToManager([

                        'cashier_id' =>
                            $dailyClose[
                                'cashier_id'
                            ],

                        'transaction_type' =>
                            'RETURN_TO_MANAGER',

                        'business_date' =>
                            $dailyClose[
                                'business_date'
                            ],

                        'amount' =>
                            $dailyClose[
                                'returned_amount'
                            ],

                        'balance_before' =>
                            $cashierCurrentBalance,

                        'balance_after' =>
                            $cashierAfter,
                            
                        'business_date' =>
                             $dailyClose[
                            'business_date'
                        ],

                        'reference_no' =>
                            'DC-' .
                            $dailyClose[
                                'cashier_daily_close_id'
                            ],

                        'remarks' =>
                            'Approved Daily Close',

                        'created_by' =>
                            $input[
                                'approved_by'
                            ]

                    ]);
                            /*
            |--------------------------------------------------------------------------
            | UPDATE DAILY CLOSE
            |--------------------------------------------------------------------------
            */

            $db
                ->table(
                    'cashier_daily_close'
                )
                ->where(
                    'cashier_daily_close_id',
                    $dailyClose[
                        'cashier_daily_close_id'
                    ]
                )
                ->update([

                    'status' =>
                        'APPROVED',

                    'approved_by' =>
                        $input[
                            'approved_by'
                        ],

                    'approved_at' =>
                        date(
                            'Y-m-d H:i:s'
                        ),

                    'cashier_transaction_id' =>
                        $cashierTransactionId,

                    'manager_transaction_id' =>
                        $managerTransactionId

                ]);

            /*
            |--------------------------------------------------------------------------
            | APPROVAL LOG
            |--------------------------------------------------------------------------
            */

            $db
                ->table(
                    'cashier_daily_close_logs'
                )
                ->insert([

                    'cashier_daily_close_id' =>
                        $dailyClose[
                            'cashier_daily_close_id'
                        ],

                    'action' =>
                        'APPROVED',

                    'remarks' =>
                        'Manager approved daily close.',

                    'action_by' =>
                        $input[
                            'approved_by'
                        ],

                    'created_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]);
            
            $managerModel->rebuildCashBalances();   

            $this->cashierVaultModel->rebuildCashierBalances(

                $dailyClose['cashier_id'],

                $dailyClose['business_date']

            );

            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'cashier_daily_close_id' =>
                    $dailyClose[
                        'cashier_daily_close_id'
                    ],

                'cashier_transaction_id' =>
                    $cashierTransactionId,

                'manager_transaction_id' =>
                    $managerTransactionId,

                'manager_balance' =>
                    $managerAfter,

                'cashier_balance' =>
                    $cashierAfter,

                'message' =>
                    'Daily Close successfully approved.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $ex->getMessage()

            ]);

        }

    }

    public function rejectDailyClose(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_daily_close_id' =>
                    'required|numeric',

                'rejected_by' =>
                    'required|numeric',

                'remarks' =>
                    'required'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' => current(
                        $this->validator
                            ->getErrors()
                    )

                ]);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | GET DAILY CLOSE
            |--------------------------------------------------------------------------
            */

            $dailyClose =

                $db->table(
                    'cashier_daily_close'
                )

                ->where(
                    'cashier_daily_close_id',
                    $input['cashier_daily_close_id']
                )

                ->get()

                ->getRowArray();

            if (!$dailyClose) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Daily Close not found.'

                ]);

            }

            if (
                $dailyClose['status']
                != 'PENDING'
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Daily Close is already processed.'

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE DAILY CLOSE
            |--------------------------------------------------------------------------
            */

            $db->table(
                'cashier_daily_close'
            )

            ->where(
                'cashier_daily_close_id',
                $input['cashier_daily_close_id']
            )

            ->update([

                'status' =>
                    'REJECTED',

                'rejected_by' =>
                    $input['rejected_by'],

                'rejected_at' =>
                    date('Y-m-d H:i:s'),

                'remarks' =>
                    $input['remarks']

            ]);

            /*
            |--------------------------------------------------------------------------
            | APPROVAL LOG
            |--------------------------------------------------------------------------
            */

            $db->table(
                'cashier_daily_close_logs'
            )

            ->insert([

                'cashier_daily_close_id' =>
                    $dailyClose[
                        'cashier_daily_close_id'
                    ],

                'action' =>
                    'REJECTED',

                'remarks' =>
                    $input['remarks'],

                'action_by' =>
                    $input['rejected_by']

            ]);

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Daily Close rejected successfully.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $ex->getMessage()

            ]);

        }

    }

    

}