<?php

namespace App\Models;

use CodeIgniter\Model;

use CodeIgniter\Database\RawSql;

class CashierVaultModel extends Model
{
    protected $table            = 'cashier_vault';
    protected $primaryKey       = 'cashier_transaction_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [

        'cashier_id',
        'transaction_type',
        'loan_id',
        'collection_id',
        'expense_id',
        'amount',
        'balance_before',
        'balance_after',
        'reference_no',
        'remarks',
        'created_by',
        'created_at',
        'deleted_at',
        'is_active'

    ];

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    // public function getSummary(
    //     int $cashierId
    // )
    // {

    //     $transfer = $this->db
    //         ->table('cashier_vault')
    //         ->selectSum('amount')
    //         ->where('cashier_id',$cashierId)
    //         ->where('transaction_type','TRANSFER_IN')
    //         ->where('DATE(created_at)',date('Y-m-d'),false)
    //         ->where('is_active',1)
    //         ->get()
    //         ->getRowArray();

    //     $loanRelease = $this->db
    //         ->table('cashier_vault')
    //         ->selectSum('amount')
    //         ->where('cashier_id',$cashierId)
    //         ->where('transaction_type','LOAN_RELEASE')
    //         ->where('DATE(created_at)',date('Y-m-d'),false)
    //         ->where('is_active',1)
    //         ->get()
    //         ->getRowArray();

    //     $collection = $this->db
    //         ->table('cashier_vault')
    //         ->selectSum('amount')
    //         ->where('cashier_id',$cashierId)
    //         ->where('transaction_type','COLLECTION')
    //         ->where('DATE(created_at)',date('Y-m-d'),false)
    //         ->where('is_active',1)
    //         ->get()
    //         ->getRowArray();

    //     $expense = $this->db
    //         ->table('cashier_vault')
    //         ->selectSum('amount')
    //         ->where('cashier_id',$cashierId)
    //         ->where('transaction_type','EXPENSE')
    //         ->where('DATE(created_at)',date('Y-m-d'),false)
    //         ->where('is_active',1)
    //         ->get()
    //         ->getRowArray();

    //     $return = $this->db
    //         ->table('cashier_vault')
    //         ->selectSum('amount')
    //         ->where('cashier_id',$cashierId)
    //         ->where('transaction_type','RETURN_TO_MANAGER')
    //         ->where('DATE(created_at)',date('Y-m-d'),false)
    //         ->where('is_active',1)
    //         ->get()
    //         ->getRowArray();

    //     return [

    //         'current_balance' =>
    //             $this->getLatestBalance(
    //                 $cashierId
    //             ),

    //         'transfer_today' =>
    //             (float)($transfer['amount'] ?? 0),

    //         'loan_release_today' =>
    //             (float)($loanRelease['amount'] ?? 0),

    //         'collection_today' =>
    //             (float)($collection['amount'] ?? 0),

    //         'expense_today' =>
    //             (float)($expense['amount'] ?? 0),

    //         'return_today' =>
    //             (float)($return['amount'] ?? 0)

    //     ];

    // }

    /*
    |--------------------------------------------------------------------------
    | GET TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    public function getTransactions(

        $search = '',

        $cashierTransactionId = null,

        $cashierId = null,

        $transactionType = '',

        $dateFrom = '',

        $dateTo = '',

        $start = 0,

        $length = 10,

        $orderColumn = 'cashier_transaction_id',

        $orderDir = 'DESC'

    )
    {

        /*
        |--------------------------------------------------------------------------
        | ORDERABLE COLUMNS
        |--------------------------------------------------------------------------
        */

        $columns = [

            'cashier_transaction_id' => 'cv.cashier_transaction_id',

            'business_date'             => 'cv.business_date',

            'reference_no'           => 'cv.reference_no',

            'transaction_type'       => 'cv.transaction_type',

            'amount'                 => 'cv.amount',

            'balance_before'         => 'cv.balance_before',

            'balance_after'          => 'cv.balance_after',

            'created_by_name'        => 'u.lastname',

            'remarks'                => 'cv.remarks',

        ];

        $orderBy =

            $columns[$orderColumn]

            ??

            'cv.cashier_transaction_id';

        /*
        |--------------------------------------------------------------------------
        | DEFAULT DATES
        |--------------------------------------------------------------------------
        */

        if (empty($dateFrom) && empty($dateTo)) {

            $dateFrom = date('Y-m-d');

            $dateTo = date('Y-m-d');

        } elseif (!empty($dateFrom) && empty($dateTo)) {

            $dateTo = $dateFrom;

        } elseif (empty($dateFrom) && !empty($dateTo)) {

            $dateFrom = $dateTo;

        }

        /*
        |--------------------------------------------------------------------------
        | QUERY
        |--------------------------------------------------------------------------
        */

        $builder =

            $this->db

            ->table('cashier_vault cv')

            ->select("

                cv.*,

                CONCAT(

                    IFNULL(c.firstname,''),

                    ' ',

                    IFNULL(c.lastname,'')

                ) AS cashier_name,

                CONCAT(

                    IFNULL(u.firstname,''),

                    ' ',

                    IFNULL(u.lastname,'')

                ) AS created_by_name,
                
                CONCAT(
                    cv.amount,
                    ' (',
                    cv.balance_after,
                    ' )'

                ) AS amount_with_balance,
    
                CASE

                WHEN cv.borrower_id IS NOT NULL THEN

                    CONCAT(

                        cv.remarks,

                        ' - ',

                        IFNULL(b.last_name,''),

                        ', ',

                        IFNULL(b.first_name,''),

                        CASE

                            WHEN b.middle_name IS NOT NULL
                            AND b.middle_name <> ''

                            THEN CONCAT(' ', b.middle_name)

                            ELSE ''

                        END

                    )

                ELSE

                    cv.remarks

            END AS remarks_display

            ")

            ->join(

                'borrowers b',

                'b.borrower_id = cv.borrower_id',

                'left'

            )

            ->join(

                'users c',

                'c.userid = cv.cashier_id',

                'left'

            )

            ->join(

                'users u',

                'u.userid = cv.created_by',

                'left'

            )

            ->where(

                'cv.is_active',

                1

            );

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if (!empty($cashierId)) {

            $builder->where(

                'cv.cashier_id',

                $cashierId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (!empty($search)) {

            $search = trim($search);

            $numericSearch = str_replace(

                ['₱', ',', ' '],

                '',

                $search

            );

            $builder

                ->groupStart()

                ->like(

                    'cv.reference_no',

                    $search

                )

                ->orLike(

                    'cv.transaction_type',

                    $search

                )

                ->orLike(

                    'cv.remarks',

                    $search

                )

                ->orLike(

                    'u.firstname',

                    $search

                )

                ->orLike(

                    'u.lastname',

                    $search

                );

            

            if (is_numeric($numericSearch)) {

                $builder

                    ->orWhere(

                        'cv.cashier_transaction_id',

                        (int)$numericSearch

                    )

                    ->orWhere(

                        'cv.amount',

                        (float)$numericSearch

                    )

                    ->orWhere(

                        'cv.balance_before',

                        (float)$numericSearch

                    )

                    ->orWhere(

                        'cv.balance_after',

                        (float)$numericSearch

                    );

            }

            if (strtotime($search) !== false) {

                $builder

                    ->orWhere(

                        'DATE(cv.created_at)',

                        date(

                            'Y-m-d',

                            strtotime($search)

                        )

                    );

            }

            $builder->groupEnd();

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION ID
        |--------------------------------------------------------------------------
        */

        if (!empty($cashierTransactionId)) {

            $builder->where(

                'cv.cashier_transaction_id',

                $cashierTransactionId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION TYPE
        |--------------------------------------------------------------------------
        */

        if (!empty($transactionType)) {

            $builder->where(

                'cv.transaction_type',

                strtoupper($transactionType)

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if (!empty($dateFrom)) {

            $builder->where(

                'DATE(cv.created_at) >=',

                $dateFrom

            );

        }

        if (!empty($dateTo)) {

            $builder->where(

                'DATE(cv.created_at) <=',

                $dateTo

            );

        }

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        $builder->orderBy(

            $orderBy,

            $orderDir

        );

        /*
        |--------------------------------------------------------------------------
        | LIMIT
        |--------------------------------------------------------------------------
        */

        if ($length != -1) {

            $builder->limit(

                $length,

                $start

            );

        }

        $transactions =

            $builder

            ->get()

            ->getResultArray();
            /*
        |--------------------------------------------------------------------------
        | LOAD DETAILS
        |--------------------------------------------------------------------------
        */

        foreach ($transactions as &$transaction) {

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION DETAILS
            |--------------------------------------------------------------------------
            */

            $transaction['details'] =

                $this->db

                ->table('cashier_vault_details')

                ->where(

                    'cashier_transaction_id',

                    $transaction['cashier_transaction_id']

                )

                ->orderBy(

                    'cashier_vault_detail_id',

                    'ASC'

                )

                ->get()

                ->getResultArray();

            /*
            |--------------------------------------------------------------------------
            | DAILY CLOSE
            |--------------------------------------------------------------------------
            */

            $dailyClose =

                $this->db

                ->table('cashier_daily_close')

                ->where(

                    'cashier_daily_close_id',

                    $transaction['cashier_daily_close_id']

                    ?? 0

                )

                ->get()

                ->getRowArray();

                if(!empty($dailyClose)){

                    $transaction['daily_close'] =

                        $dailyClose;

                    /*
                    |--------------------------------------------------------------------------
                    | DENOMINATIONS
                    |--------------------------------------------------------------------------
                    */

                    $transaction['denominations'] =

                        $this->db

                        ->table(

                            'cashier_daily_close_denominations'

                        )

                        ->where(

                            'cashier_daily_close_id',

                            $dailyClose['cashier_daily_close_id']

                        )

                        ->orderBy(

                            'denomination',

                            'DESC'

                        )

                        ->get()

                        ->getResultArray();

                }else{

                    $transaction['daily_close'] = [];

                    $transaction['denominations'] = [];

                }

                /*
                |--------------------------------------------------------------------------
                | MANAGER APPROVAL
                |--------------------------------------------------------------------------
                */

                if(

                    !empty(

                        $transaction['manager_transaction_id']

                    )

                ){

                    $transaction['manager_transaction'] =

                        $this->db

                        ->table('manager_vault')

                        ->select("

                            manager_transaction_id,

                            transaction_type,

                            reference_no,

                            amount,

                            status,

                            approved_by,

                            approved_at

                        ")

                        ->where(

                            'manager_transaction_id',

                            $transaction['manager_transaction_id']

                        )

                        ->get()

                        ->getRowArray();

                }else{

                    $transaction['manager_transaction'] = [];

                }

        }

        unset($transaction);

        return $transactions;

    }

    public function countTransactions(

        $cashierId = null,

        $dateFrom = '',

        $dateTo = ''

    )
    {

        $builder =

            $this->db

            ->table('cashier_vault cv')

            ->where(

                'cv.is_active',

                1

            );

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(

                'cv.cashier_id',

                $cashierId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        if(!empty($dateFrom)){

            $builder->where(

                'DATE(cv.created_at) >=',

                $dateFrom

            );

        }

        if(!empty($dateTo)){

            $builder->where(

                'DATE(cv.created_at) <=',

                $dateTo

            );

        }

        return

            $builder

            ->countAllResults();

    }

    public function countFilteredTransactions(

        $search = '',

        $cashierTransactionId = null,

        $cashierId = null,

        $transactionType = '',

        $dateFrom = '',

        $dateTo = ''

    )
    {

        $builder =

            $this->db

            ->table('cashier_vault cv')

            ->join(

                'users c',

                'c.userid = cv.cashier_id',

                'left'

            )

            ->join(

                'users u',

                'u.userid = cv.created_by',

                'left'

            )

            ->where(

                'cv.is_active',

                1

            );

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(

                'cv.cashier_id',

                $cashierId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if(!empty($search)){

            $search = trim($search);

            $numericSearch = str_replace(

                ['₱', ',', ' '],

                '',

                $search

            );

            $builder

                ->groupStart()

                ->like(

                    'cv.reference_no',

                    $search

                )

                ->orLike(

                    'cv.transaction_type',

                    $search

                )

                ->orLike(

                    'cv.remarks',

                    $search

                )

                ->orLike(

                    'c.firstname',

                    $search

                )

                ->orLike(

                    'c.lastname',

                    $search

                )

                ->orLike(

                    'u.firstname',

                    $search

                )

                ->orLike(

                    'u.lastname',

                    $search

                );

            if(is_numeric($numericSearch)){

                $builder

                    ->orWhere(

                        'cv.cashier_transaction_id',

                        (int)$numericSearch

                    )

                    ->orWhere(

                        'cv.amount',

                        (float)$numericSearch

                    )

                    ->orWhere(

                        'cv.balance_before',

                        (float)$numericSearch

                    )

                    ->orWhere(

                        'cv.balance_after',

                        (float)$numericSearch

                    );

            }

            $builder->groupEnd();

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION ID
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierTransactionId)){

            $builder->where(

                'cv.cashier_transaction_id',

                $cashierTransactionId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | TYPE
        |--------------------------------------------------------------------------
        */

        if(!empty($transactionType)){

            $builder->where(

                'cv.transaction_type',

                strtoupper(

                    $transactionType

                )

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        if(!empty($dateFrom)){

            $builder->where(

                'DATE(cv.created_at) >=',

                $dateFrom

            );

        }

        if(!empty($dateTo)){

            $builder->where(

                'DATE(cv.created_at) <=',

                $dateTo

            );

        }

        return

            $builder

            ->countAllResults();

    }

    public function getTransactionDetails($cashierTransactionId)
    {

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        $transaction =

            $this->db

            ->table('cashier_vault cv')

            ->select("

                cv.*,

                CONCAT(

                    IFNULL(c.firstname,''),

                    ' ',

                    IFNULL(c.lastname,'')

                ) AS cashier_name,

                CONCAT(

                    IFNULL(u.firstname,''),

                    ' ',

                    IFNULL(u.lastname,'')

                ) AS created_by_name,
                  CONCAT(

                    IFNULL(b.last_name,''),

                    ', ',

                    IFNULL(b.first_name,'')
                ) as borrower

            ")

             ->join(

                'borrowers b',

                'b.borrower_id = cv.borrower_id',

                'left'

            )

            ->join(

                'users c',

                'c.userid=cv.cashier_id',

                'left'

            )

            ->join(

                'users u',

                'u.userid=cv.created_by',

                'left'

            )

            ->where(

                'cv.cashier_transaction_id',

                $cashierTransactionId

            )

            ->where(

                'cv.is_active',

                1

            )

            ->get()

            ->getRowArray();

        if(empty($transaction)){

            return null;

        }

        /*
        |--------------------------------------------------------------------------
        | DETAILS
        |--------------------------------------------------------------------------
        */

        $transaction['details'] =

            $this->db

            ->table('cashier_vault_details')

            ->where(

                'cashier_transaction_id',

                $cashierTransactionId

            )

            ->orderBy(

                'cashier_vault_detail_id',

                'ASC'

            )

            ->get()

            ->getResultArray();

        /*
        |--------------------------------------------------------------------------
        | DAILY CLOSE
        |--------------------------------------------------------------------------
        */

        if(!empty($transaction['cashier_daily_close_id'])){

            $dailyClose =

            $this->db

            ->table('cashier_daily_close cdc')

            ->select("

                cdc.*,

                CONCAT(
                    IFNULL(u.firstname,''),
                    ' ',
                    IFNULL(u.lastname,'')
                ) AS closed_by_name,

                CONCAT(
                    IFNULL(a.firstname,''),
                    ' ',
                    IFNULL(a.lastname,'')
                ) AS approved_by_name

            ")

            ->join(

                'users u',

                'u.userid=cdc.closed_by',

                'left'

            )

            ->join(

                'users a',

                'a.userid=cdc.approved_by',

                'left'

            )

            ->where(

                'cdc.cashier_daily_close_id',

                $transaction['cashier_daily_close_id']

            )

            ->get()

            ->getRowArray();

            $transaction['daily_close'] =

                $dailyClose;

            /*
            |--------------------------------------------------------------------------
            | DENOMINATIONS
            |--------------------------------------------------------------------------
            */

            $transaction['denominations'] =

                $this->db

                ->table(

                    'cashier_daily_close_denominations'

                )

                ->where(

                    'cashier_daily_close_id',

                    $transaction['cashier_daily_close_id']

                )

                ->orderBy(

                    'denomination',

                    'DESC'

                )

                ->get()

                ->getResultArray();

        }else{

            $transaction['daily_close'] = [];

            $transaction['denominations'] = [];

            if(

                !empty(

                    $transaction['cashier_daily_close_id']

                )

            ){

                $dailyClose =

                    $this->db

                    ->table('cashier_daily_close cdc')

                    ->select("

                        cdc.*,

                        CONCAT(

                            IFNULL(c.firstname,''),

                            ' ',

                            IFNULL(c.lastname,'')

                        ) AS closed_by_name,

                        CONCAT(

                            IFNULL(a.firstname,''),

                            ' ',

                            IFNULL(a.lastname,'')

                        ) AS approved_by_name,

                        CONCAT(

                            IFNULL(r.firstname,''),

                            ' ',

                            IFNULL(r.lastname,'')

                        ) AS rejected_by_name

                    ")

                    ->join(

                        'users c',

                        'c.userid=cdc.closed_by',

                        'left'

                    )

                    ->join(

                        'users a',

                        'a.userid=cdc.approved_by',

                        'left'

                    )

                    ->join(

                        'users r',

                        'r.userid=cdc.rejected_by',

                        'left'

                    )

                    ->where(

                        'cdc.cashier_daily_close_id',

                        $transaction['cashier_daily_close_id']

                    )

                    ->get()

                    ->getRowArray();

                $transaction['daily_close'] =

                    $dailyClose;

                $transaction['denominations'] =

                    $this->db

                    ->table(

                        'cashier_daily_close_denominations'

                    )

                    ->where(

                        'cashier_daily_close_id',

                        $transaction['cashier_daily_close_id']

                    )

                    ->orderBy(

                        'denomination',

                        'DESC'

                    )

                    ->get()

                    ->getResultArray();

            }

        }

        /*
        |--------------------------------------------------------------------------
        | MANAGER TRANSACTION
        |--------------------------------------------------------------------------
        */

        if(!empty($transaction['manager_transaction_id'])){

            $transaction['manager_transaction'] =

                $this->db

                ->table('manager_vault mv')

                ->select("

                    mv.*,

                    CONCAT(

                        IFNULL(u.firstname,''),

                        ' ',

                        IFNULL(u.lastname,'')

                    ) AS approved_by_name

                ")

                ->join(

                    'users u',

                    'u.userid=mv.approved_by',

                    'left'

                )

                ->where(

                    'mv.manager_transaction_id',

                    $transaction['manager_transaction_id']

                )

                ->get()

                ->getRowArray();

        }else{

            $transaction['manager_transaction'] = [];

        }

        return $transaction;

    }
    /*
    |--------------------------------------------------------------------------
    | GET TRANSACTION DETAILS
    |--------------------------------------------------------------------------
    */

    // public function getTransactionDetails(
    //     int $cashierTransactionId
    // )
    // {

    //     return

    //         $this->db

    //         ->table('cashier_vault cv')

    //         ->select("

    //             cv.*,

    //             CONCAT(

    //                 IFNULL(c.firstname,''),

    //                 ' ',

    //                 IFNULL(c.lastname,'')

    //             ) AS cashier_name,

    //             CONCAT(

    //                 IFNULL(u.firstname,''),

    //                 ' ',

    //                 IFNULL(u.lastname,'')

    //             ) AS created_by_name

    //         ")

    //         ->join(

    //             'users c',

    //             'c.userid=cv.cashier_id',

    //             'left'

    //         )

    //         ->join(

    //             'users u',

    //             'u.userid=cv.created_by',

    //             'left'

    //         )

    //         ->where(

    //             'cv.cashier_transaction_id',

    //             $cashierTransactionId

    //         )

    //         ->where(

    //             'cv.is_active',

    //             1

    //         )

    //         ->get()

    //         ->getRowArray();

    // }
    public function getSummary(

        $cashierId,

        $businessDate=''

    )
    {

        if(empty($businessDate)){

            $businessDate =

                date('Y-m-d');

        }

        $builder =

            $this->db

            ->table('cashier_vault')

            ->where(

                'cashier_id',

                $cashierId

            )

            ->where(

                'is_active',

                1

            )

            ->where(

                'DATE(business_date)',

                $businessDate

            )
            ->orderBy('business_date','ASC')

            ->orderBy('cashier_transaction_id','ASC');

        $transactions =

            $builder

            ->get()

            ->getResultArray();

        $summary = [

            'current_balance'=>0,

            'received'=>0,

            'loan_release'=>0,

            'collections'=>0,

            'expenses'=>0,

            'cash_out'=>0,

            'cash_in'=>0,

            'return_to_vault'=>0,

            'adjustment_plus'=>0,

            'adjustment_minus'=>0,

            'transaction_count'=>count($transactions)

        ];

        foreach($transactions as $row){

            $amount =

                (float)$row['amount'];

            $summary['current_balance'] =

                (float)$row['balance_after'];

            switch(

                strtoupper(

                    $row['transaction_type']

                )

            ){

                case 'TRANSFER_IN':

                    $summary['cash_in'] +=

                        $amount;

                break;

                case 'CASH OUT':

                    $summary['cash_out'] +=

                        $amount;

                break;

                case 'CASH IN':

                    $summary['cash_in'] +=

                        $amount;

                break;

                case 'EXPENSE':

                    $summary['expenses'] +=

                        $amount;

                break;

                case 'RETURN TO VAULT':

                    $summary['return_to_vault'] +=

                        $amount;

                break;

            }

        }

        return $summary;

    }

    /*
    |--------------------------------------------------------------------------
    | RECEIVE TRANSFER
    |--------------------------------------------------------------------------
    */

    public function receiveTransfer(
        array $data
    )
    {

        $this->db

            ->table(
                'cashier_vault'
            )

            ->insert([

                'cashier_id' =>

                    $data['cashier_id'],

                'transaction_type' =>

                    'TRANSFER_IN',

                'amount' =>

                    $data['amount'],

                'balance_before' =>

                    $data['balance_before'],

                'balance_after' =>

                    $data['balance_after'],

                'reference_no' =>

                    $data['reference_no']
                    ?? '',

                'remarks' =>

                    $data['remarks']
                    ?? '',

                'created_by' =>

                    $data['created_by'],

                'created_at' =>

                    date(
                        'Y-m-d H:i:s'
                    ),

                'is_active' =>

                    1

            ]);

        return

            $this->db

            ->insertID();

    }

    /*
    |--------------------------------------------------------------------------
    | LOAN RELEASE
    |--------------------------------------------------------------------------
    */

    public function loanRelease(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault')
            ->insert([

                'cashier_id' =>
                    $data['cashier_id'],

                'transaction_type' =>
                    'LOAN_RELEASE',

                'loan_id' =>
                    $data['loan_id'],

                'amount' =>
                    $data['amount'],

                'balance_before' =>
                    $data['balance_before'],

                'balance_after' =>
                    $data['balance_after'],

                'reference_no' =>
                    $data['reference_no']
                    ?? '',

                'remarks' =>
                    $data['remarks']
                    ?? '',

                'created_by' =>
                    $data['created_by'],

                'created_at' =>
                    date('Y-m-d H:i:s'),

                'is_active' =>
                    1

            ]);

        return $this->db->insertID();

    }

    /*
    |--------------------------------------------------------------------------
    | COLLECTION
    |--------------------------------------------------------------------------
    */

    public function collection(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault')
            ->insert([

                'cashier_id' =>
                    $data['cashier_id'],

                'transaction_type' =>
                    'COLLECTION',

                'collection_id' =>
                    $data['collection_id'],

                'loan_id' =>
                    $data['loan_id']
                    ?? null,

                'amount' =>
                    $data['amount'],

                'balance_before' =>
                    $data['balance_before'],

                'balance_after' =>
                    $data['balance_after'],

                'reference_no' =>
                    $data['reference_no']
                    ?? '',

                'remarks' =>
                    $data['remarks']
                    ?? '',

                'created_by' =>
                    $data['created_by'],

                'created_at' =>
                    date('Y-m-d H:i:s'),

                'is_active' =>
                    1

            ]);

        return $this->db->insertID();

    }

    /*
    |--------------------------------------------------------------------------
    | EXPENSE
    |--------------------------------------------------------------------------
    */

    public function expense(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault')
            ->insert([

                'cashier_id' =>
                    $data['cashier_id'],

                'transaction_type' =>
                    'EXPENSE',

                'expense_id' =>
                    $data['expense_id']
                    ?? null,

                'amount' =>
                    $data['amount'],

                'balance_before' =>
                    $data['balance_before'],

                'balance_after' =>
                    $data['balance_after'],

                'reference_no' =>
                    $data['reference_no']
                    ?? '',

                'remarks' =>
                    $data['remarks']
                    ?? '',

                'created_by' =>
                    $data['created_by'],

                'created_at' =>
                    date('Y-m-d H:i:s'),

                'is_active' =>
                    1

            ]);

        return $this->db->insertID();

    }

    /*
    |--------------------------------------------------------------------------
    | RETURN TO MANAGER
    |--------------------------------------------------------------------------
    */

    public function returnToManager(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault')
            ->insert([

                'cashier_id' =>
                    $data['cashier_id'],

                'transaction_type' =>
                    'RETURN_TO_MANAGER',

                'amount' =>
                    $data['amount'],

                'balance_before' =>
                    $data['balance_before'],

                'balance_after' =>
                    $data['balance_after'],

                'reference_no' =>
                    $data['reference_no']
                    ?? '',

                'remarks' =>
                    $data['remarks']
                    ?? '',

                'created_by' =>
                    $data['created_by'],

                'created_at' =>
                    $data['business_date'],

                'is_active' =>
                    1

            ]);

        return
            $this->db
            ->insertID();

    }

     public function generateReferenceNo(
        string $transactionType
    )
    {
        switch($transactionType){

            case 'CASH IN':
                $prefix = 'CI';
            break;

            case 'CASH OUT':
                $prefix = 'CO';
            break;

            case 'EXPENSE':
                $prefix = 'EXP';
            break;

            default:
                $prefix = 'CV';
            break;

        }

        $last =

            $this->db

            ->table('cashier_vault')

            ->select('reference_no')

            ->like(
                'reference_no',
                $prefix . '-',
                'after'
            )

            ->orderBy(
                'cashier_transaction_id',
                'DESC'
            )

            ->limit(1)

            ->get()

            ->getRowArray();

        $next = 1;

        if(!empty($last)){

            $parts = explode(
                '-',
                $last['reference_no']
            );

            if(isset($parts[1])){

                $next =
                    ((int)$parts[1]) + 1;

            }

        }

        return sprintf(

            '%s-%06d',

            $prefix,

            $next

        );

    }

    /*
    |--------------------------------------------------------------------------
    | GET LATEST BALANCE
    |--------------------------------------------------------------------------
    */

    public function getLatestBalance(
        int $cashierId,
        ?string $businessDate = null
    )
    {
        if (empty($businessDate)) {
            $businessDate = date('Y-m-d');
        }

        $row = $this->db
            ->table('cashier_vault')
            ->select('balance_after')
            ->where('cashier_id', $cashierId)
            ->where('DATE(business_date)', $businessDate)
            ->where('is_active', 1)
            ->orderBy('business_date', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return (float)($row['balance_after'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | VOID TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function voidTransaction(
        int $cashierTransactionId
    )
    {

        return

            $this->db

            ->table(
                'cashier_vault'
            )

            ->where(
                'cashier_transaction_id',
                $cashierTransactionId
            )

            ->update([

                'is_active' =>
                    0,

                'deleted_at' =>
                    date(
                        'Y-m-d H:i:s'
                    )

            ]);

    }

    public function isBusinessDateClosed(
        int $cashierId,
        ?string $businessDate = null
    )
    {
        if (empty($businessDate)) {
            $businessDate = date('Y-m-d');
        }

        $count = $this->db
            ->table('cashier_daily_close')
            ->where('cashier_id', $cashierId)
            ->where('business_date', $businessDate)
             ->whereIn(
                'status',
                [
                    'PENDING',
                    'APPROVED'
                ]
            )
            ->countAllResults();

        return $count > 0;
    }

    public function insertTransaction(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault')
            ->insert([

                'cashier_id' =>
                    $data['cashier_id'],

                'transaction_type' =>
                    strtoupper(
                        $data['transaction_type']
                    ),

                'amount' =>
                    $data['amount'],
                    
                'borrower_id' =>
                    $data['borrower_id'],

                'balance_before' =>
                    $data['balance_before'],

                'balance_after' =>
                    $data['balance_after'],

                'reference_no' =>
                    $data['reference_no']
                    ?? '',

                'remarks' =>
                    $data['remarks']
                    ?? '',

                'created_by' =>
                    $data['created_by'],

                'created_at' =>
                    $data['created_at'],
                'business_date' =>
                    $data['business_date'],

                'is_active' =>
                    1

            ]);

        return $this->db->insertID();
    }

    public function insertDetail(
        array $data
    )
    {
        $this->db
            ->table('cashier_vault_details')
            ->insert([

                'cashier_transaction_id' =>
                    $data['cashier_transaction_id'],

                'transaction_type' =>
                    strtoupper(
                        $data['transaction_type']
                        ?? 'CASH_OUT'
                    ),

                'reference_type' =>
                    $data['reference_type']
                    ?? '',

                'reference_id' =>
                    $data['reference_id']
                    ?? null,

                'description' =>
                    $data['description']
                    ?? '',

                'amount' =>
                    (float)$data['amount']

            ]);

        return $this->db->insertID();
    }

    public function rebuildCashierBalances(
        int $cashierId,
        string $businessDate
    )
    {
        $transactions =

            $this->db

            ->table('cashier_vault')

            ->where('cashier_id',$cashierId)

            ->where('DATE(business_date)',$businessDate)

            ->where('is_active',1)

            ->orderBy('business_date','ASC')

            ->orderBy('cashier_transaction_id','ASC')

            ->get()

            ->getResultArray();

        $balance = 0;

        foreach($transactions as $row){

            $before = $balance;

            if($row['transaction_type']=="CASH IN" || $row['transaction_type']=="TRANSFER_IN"){

                $balance += $row['amount'];

            }else{

                $balance -= $row['amount'];

            }

            $this->db

            ->table('cashier_vault')

            ->where(
                'cashier_transaction_id',
                $row['cashier_transaction_id']
            )

            ->update([

                'balance_before'=>$before,

                'balance_after'=>$balance

            ]);

        }

    }
}