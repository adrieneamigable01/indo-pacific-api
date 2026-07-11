<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\ManagerVaultModel;
use App\Models\CashierVaultModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class ManagerVault extends BaseController
{
    protected $managerVaultModel;
    protected $cashierVaultModel;

    public function __construct()
    {
        $this->managerVaultModel =
            new ManagerVaultModel();

        $this->cashierVaultModel =
        new CashierVaultModel();

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

    // public function get()
    // {
    //     try {

    //         $search =
    //             $this->request->getGet('search');

    //         $transactionId =
    //             $this->request->getGet(
    //                 'manager_transaction_id'
    //             );

    //         $transactionType =
    //             $this->request->getGet(
    //                 'transaction_type'
    //             );

    //         $dateFrom =
    //             $this->request->getGet(
    //                 'date_from'
    //             );

    //         $dateTo =
    //             $this->request->getGet(
    //                 'date_to'
    //             );

    //         $cashierId =
    //             $this->request->getGet(
    //                 'cashier_id'
    //             );

    //         $data =
    //             $this->managerVaultModel
    //             ->getTransactions(

    //                 $search,

    //                 $transactionId,

    //                 $transactionType,

    //                 $dateFrom,

    //                 $dateTo,

    //                 $cashierId

    //             );

    //         return $this->response->setJSON([

    //             'isError' => false,

    //             'message' => 'Success',

    //             'data' => $data

    //         ]);

    //     } catch (Exception $e) {

    //         return $this->response->setJSON([

    //             'isError' => true,

    //             'message' => $e->getMessage()

    //         ]);

    //     }
    // }


    public function get()
    {
        try {

            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            $search =
                $this->request->getGet('search');

            $transactionId =
                $this->request->getGet(
                    'manager_transaction_id'
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

            $cashierId =
                $this->request->getGet(
                    'cashier_id'
                );

            /*
            |--------------------------------------------------------------------------
            | DATATABLES
            |--------------------------------------------------------------------------
            */

            $draw =
                (int)$this->request
                ->getGet('draw');

            $start =
                (int)$this->request
                ->getGet('start');

            $length =
                (int)$this->request
                ->getGet('length');

            $orderColumn =
                $this->request
                ->getGet('orderColumn')
                ?? 'manager_transaction_id';

            $orderDir =
                strtoupper(

                    $this->request
                    ->getGet('orderDir')

                ) == 'ASC'

                ? 'ASC'

                : 'DESC';

            /*
            |--------------------------------------------------------------------------
            | DATA
            |--------------------------------------------------------------------------
            */

            $data =

                $this->managerVaultModel
                ->getTransactions(

                    $search,

                    $transactionId,

                    $transactionType,

                    $dateFrom,

                    $dateTo,

                    $cashierId,

                    $start,

                    $length,

                    $orderColumn,

                    $orderDir

                );

            /*
            |--------------------------------------------------------------------------
            | TOTAL RECORDS
            |--------------------------------------------------------------------------
            */

            $recordsTotal =

                $this->managerVaultModel
                ->countTransactions();

            /*
            |--------------------------------------------------------------------------
            | FILTERED RECORDS
            |--------------------------------------------------------------------------
            */

            $recordsFiltered =

                $this->managerVaultModel
                ->countTransactions(

                    $search,

                    $transactionType,

                    $dateFrom,

                    $dateTo,

                    $cashierId

                );

            return $this->response->setJSON([

                "draw" =>

                    $draw,

                "recordsTotal" =>

                    $recordsTotal,

                "recordsFiltered" =>

                    $recordsFiltered,

                "data" =>

                    $data

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                "draw" => 0,

                "recordsTotal" => 0,

                "recordsFiltered" => 0,

                "data" => [],

                "error" =>

                    $e->getMessage()

            ]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | GET SUMMARY
    |--------------------------------------------------------------------------
    */

    public function summary()
    {
        try {

            $data =
                $this->managerVaultModel
                ->getSummary();

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

    /*
    |--------------------------------------------------------------------------
    | DETAILS
    |--------------------------------------------------------------------------
    */

    public function details(
        $transactionId
    )
    {
        try {

            $data =
                $this->managerVaultModel
                ->getTransactionDetails(
                    $transactionId
                );

            if (
                empty($data)
            ) {

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
    | ADD TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function addTransaction(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db =
            \Config\Database::connect();

        try {

            helper('jwt');
            $userId = null;

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

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

            $transactionType =
                strtoupper(
                    trim(
                        $input['transaction_type']
                    )
                );

            if (
                !in_array(
                    $transactionType,
                    [
                        'CASH_IN',
                        'CASH_OUT'
                    ]
                )
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Invalid transaction type.'

                ]);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | CURRENT BALANCE
            |--------------------------------------------------------------------------
            */

            $currentBalance =
            $this->managerVaultModel
            ->getLatestManagerBalance();

            $amount =
                (float)
                $input['amount'];

            /*
            |--------------------------------------------------------------------------
            | CASH IN
            |--------------------------------------------------------------------------
            */

            if (
                $transactionType ==
                'CASH_IN'
            ) {

                $balanceAfter =
                    $currentBalance +
                    $amount;

            }

            /*
            |--------------------------------------------------------------------------
            | CASH OUT
            |--------------------------------------------------------------------------
            */

            else {

                if (
                    $amount >
                    $currentBalance
                ) {

                    return $this->getResponse([

                        'isError' => true,

                        'message' =>
                            'Insufficient vault balance.'

                    ]);

                }

                $balanceAfter =
                    $currentBalance -
                    $amount;

            }

            /*
            |--------------------------------------------------------------------------
            | SAVE
            |--------------------------------------------------------------------------
            */

            $data = [

                'transaction_type' =>
                    $transactionType,

                'cashier_id' =>
                    $input['cashier_id']
                    ?? null,

                'amount' =>
                    $amount,

                'balance_before' =>
                    $currentBalance,

                'balance_after' =>
                    $balanceAfter,

                'reference_no' =>

                $this
                ->managerVaultModel
                ->generateReferenceNo(

                    $transactionType

                ),

                'remarks' =>
                    $input['remarks']
                    ?? '',

                'created_by' => $userId,
                // 'created_by' =>
                //     $input['created_by']
                //     ?? $userId,

                'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    )

            ];

            $managerTransactionId =
                $this->managerVaultModel
                ->addTransaction(
                    $data
                );

            $this
            ->managerVaultModel
            ->rebuildCashBalances();

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'manager_transaction_id' =>
                    $managerTransactionId,

                'current_balance' =>
                    $balanceAfter,

                'message' =>
                    'Transaction successfully saved.'

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
    | TRANSFER TO CASHIER
    |--------------------------------------------------------------------------
    */

    public function transfer(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db =
            \Config\Database::connect();
        helper('jwt');
        try {



            $userId = null;
            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'cashier_id' =>
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

            $db->transBegin();



            /*
            |--------------------------------------------------------------------------
            | MANAGER CURRENT BALANCE
            |--------------------------------------------------------------------------
            */

            $managerBalance =
            $this->managerVaultModel
            ->getLatestManagerBalance();

            $amount =
                (float)
                $input['amount'];

            if (
                $amount >
                $managerBalance
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Insufficient manager vault balance.'

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | CASHIER BALANCE
            |--------------------------------------------------------------------------
            */

            $cashierCurrent =
            $this->cashierVaultModel
            ->getLatestBalance(
                $input['cashier_id']
            );

            /*
            |--------------------------------------------------------------------------
            | NEW BALANCES
            |--------------------------------------------------------------------------
            */

            $managerAfter =
                $managerBalance -
                $amount;

            $cashierAfter =
                $cashierCurrent +
                $amount;

            /*
            |--------------------------------------------------------------------------
            | SAVE MANAGER TRANSACTION
            |--------------------------------------------------------------------------
            */

            $this->managerVaultModel
                ->addTransaction([

                    'transaction_type' => 'TRANSFER',

                    'cashier_id' =>
                        $input['cashier_id'],

                    'amount' =>
                        $amount,

                    'balance_before' =>
                        $managerBalance,

                    'balance_after' =>
                        $managerAfter,

                    'reference_no' =>

                        $this
                        ->managerVaultModel
                        ->generateReferenceNo('TRANSFER'),

                    'remarks' =>
                        $input['remarks']
                        ?? '',

                    'created_by' => $userId,

                    'created_at' =>
                        date('Y-m-d H:i:s')

                ]);

            /*
            |--------------------------------------------------------------------------
            | SAVE CASHIER TRANSACTION
            |--------------------------------------------------------------------------
            */

            $this->cashierVaultModel
            ->receiveTransfer([
                

                'cashier_id' =>
                    $input['cashier_id'],

                'amount' =>
                    $amount,

                'balance_before' =>
                    $cashierCurrent,

                'balance_after' =>
                    $cashierAfter,

                'reference_no' =>$this
                        ->managerVaultModel
                        ->generateReferenceNo('TRANSFER'),

                'remarks' =>
                    $input['remarks']
                    ?? '',

                'created_by' => $userId

            ]);

             $this
            ->managerVaultModel
            ->rebuildCashBalances();

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'current_balance' =>
                    $managerAfter,

                'message' =>
                    'Funds successfully transferred.'

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
    | DELETE TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function deleteTransaction(
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

                'manager_transaction_id' =>
                    'required|numeric'

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

            /*
            |--------------------------------------------------------------------------
            | GET TRANSACTION
            |--------------------------------------------------------------------------
            */

            $transaction =
                $this->managerVaultModel
                ->find(
                    $input['manager_transaction_id']
                );

            if(
                empty($transaction)
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>'Transaction not found.'

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | CURRENT MANAGER BALANCE
            |--------------------------------------------------------------------------
            */

            $managerBalance =
            $this->managerVaultModel
            ->getLatestManagerBalance();

            /*
            |--------------------------------------------------------------------------
            | REVERSE BALANCE
            |--------------------------------------------------------------------------
            */

            switch(
                $transaction['transaction_type']
            ){

                /*
                |----------------------------------------------------------
                | CASH IN
                |----------------------------------------------------------
                */

                case 'CASH_IN':

                    $managerBalance -=
                        $transaction['amount'];

                break;

                /*
                |----------------------------------------------------------
                | CASH OUT
                |----------------------------------------------------------
                */

                case 'CASH_OUT':

                    $managerBalance +=
                        $transaction['amount'];

                break;

                /*
                |----------------------------------------------------------
                | TRANSFER
                |----------------------------------------------------------
                */

                case 'TRANSFER':

                    $managerBalance +=
                        $transaction['amount'];

                    /*
                    |--------------------------------------
                    | CASHIER BALANCE
                    |--------------------------------------
                    */

                    $cashier =
                        $this->managerVaultModel
                        ->getCashierBalance(
                            $transaction['cashier_id']
                        );

                    $cashierBalance =
                        (float)(
                            $cashier['current_balance']
                            ?? 0
                        );

                    $cashierBalance -=
                        $transaction['amount'];

                    $this->managerVaultModel
                        ->updateCashierBalance(

                            $transaction['cashier_id'],

                            $cashierBalance

                        );

                    /*
                    |--------------------------------------
                    | DELETE CASHIER RECORD
                    |--------------------------------------
                    */

                    $this->cashierVaultModel
                    ->voidTransfer(
                        $transaction['reference_no']
                    );

                break;

            }

                 
            /*
            |--------------------------------------------------------------------------
            | DELETE TRANSACTION
            |--------------------------------------------------------------------------
            */

            $this->managerVaultModel
            ->update(

                $transaction['manager_transaction_id'],

                [

                    'is_active' => 0,

                    'deleted_at' => date('Y-m-d H:i:s')

                ]

            );

            /*
            |--------------------------------------------------------------------------
            | UPDATE MANAGER BALANCE
            |--------------------------------------------------------------------------
            */
            $this
            ->managerVaultModel
            ->rebuildCashBalances();   

            $db->transCommit();

            return $this->getResponse([

                'isError'=>false,

                'current_balance'=>$managerBalance,

                'message'=>'Transaction deleted successfully.'

            ]);

        } catch(Exception $ex){

            $db->transRollback();

            return $this->getResponse([

                'isError'=>true,

                'message'=>$ex->getMessage()

            ]);

        }

    }

    public function getSummary()
    {

        try{

            $search =

                $this->request

                ->getGet(

                    'search'

                );

            $transactionType =

                $this->request

                ->getGet(

                    'transaction_type'

                );

            $dateFrom =

                $this->request

                ->getGet(

                    'date_from'

                );

            $dateTo =

                $this->request

                ->getGet(

                    'date_to'

                );

            $cashierId =

                $this->request

                ->getGet(

                    'cashier_id'

                );

            $summary =

                $this->managerVaultModel

                ->getSummary(

                    $search,

                    $transactionType,

                    $dateFrom,

                    $dateTo,

                    $cashierId

                );

            return $this->response->setJSON([

                'isError'=>false,

                'message'=>'Success',

                'data'=>$summary

            ]);

        }catch(Exception $e){

            return $this->response->setJSON([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }

    }

    public function getTransactionDetails()
    {

        try{

            $managerTransactionId =

                $this->request

                ->getGet(
                    'manager_transaction_id'
                );

            if(
                empty(
                    $managerTransactionId
                )
            ){

                return $this->response->setJSON([

                    'isError'=>true,

                    'message'=>

                        'Manager Transaction ID is required.'

                ]);

            }

            $data =

                $this->managerVaultModel

                ->getTransactionDetails(

                    (int)
                    $managerTransactionId

                );

            if(
                !$data
            ){

                return $this->response->setJSON([

                    'isError'=>true,

                    'message'=>

                        'Transaction not found.'

                ]);

            }

            return $this->response->setJSON([

                'isError'=>false,

                'message'=>'Success',

                'data'=>$data

            ]);

        }

        catch(Exception $e){

            return $this->response->setJSON([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }

    }
}