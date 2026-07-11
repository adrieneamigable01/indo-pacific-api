<?php

namespace App\Models;

use CodeIgniter\Model;

class ManagerVaultModel extends Model
{
    protected $table            = 'manager_vault';
    protected $primaryKey       = 'manager_transaction_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [

        'transaction_type',
        'cashier_id',
        'amount',
        'balance_before',
        'balance_after',
        'reference_no',
        'remarks',
        'created_by',
        'created_at',
        'is_active'

    ];

    /*
    |--------------------------------------------------------------------------
    | SUMMARY
    |--------------------------------------------------------------------------
    */

    // public function getSummary()
    // {
    //     $currentBalance =
    //         $this->getLatestManagerBalance();

    //     $cashIn = $this->db
    //         ->table('manager_vault')
    //         ->selectSum('amount')
    //         ->where('transaction_type', 'CASH_IN')
    //         ->where('DATE(created_at)', date('Y-m-d'), false)
    //         ->where('is_active', 1)
    //         ->get()
    //         ->getRowArray();

    //     $cashOut = $this->db
    //         ->table('manager_vault')
    //         ->selectSum('amount')
    //         ->where('transaction_type', 'CASH_OUT')
    //         ->where('DATE(created_at)', date('Y-m-d'), false)
    //         ->where('is_active', 1)
    //         ->get()
    //         ->getRowArray();

    //     $transfer = $this->db
    //         ->table('manager_vault')
    //         ->selectSum('amount')
    //         ->where('transaction_type', 'TRANSFER_TO_CASHIER')
    //         ->where('DATE(created_at)', date('Y-m-d'), false)
    //         ->where('is_active', 1)
    //         ->get()
    //         ->getRowArray();

    //     return [

    //         'current_balance' =>
    //             $currentBalance,

    //         'cash_in_today' =>
    //             (float)($cashIn['amount'] ?? 0),

    //         'cash_out_today' =>
    //             (float)($cashOut['amount'] ?? 0),

    //         'transfer_today' =>
    //             (float)($transfer['amount'] ?? 0)

    //     ];
    // }

    public function getSummary(

        $search = '',

        $transactionType = '',

        $dateFrom = '',

        $dateTo = '',

        $cashierId = null

    )
    {

        $builder =

            $this->db

            ->table('manager_vault mv')

            ->where(

                'mv.is_active',

                1

            );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if(!empty($search)){

            $builder

                ->groupStart()

                ->like(

                    'mv.reference_no',

                    $search

                )

                ->orLike(

                    'mv.remarks',

                    $search

                )

                ->orLike(

                    'mv.transaction_type',

                    $search

                )

                ->groupEnd();

        }

        /*
        |--------------------------------------------------------------------------
        | TYPE
        |--------------------------------------------------------------------------
        */

        if(!empty($transactionType)){

            $builder->where(

                'mv.transaction_type',

                strtoupper(
                    $transactionType
                )

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(

                'mv.cashier_id',

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

                'DATE(mv.created_at) >=',

                $dateFrom

            );

        }

        if(!empty($dateTo)){

            $builder->where(

                'DATE(mv.created_at) <=',

                $dateTo

            );

        }

        $rows =

            $builder

            ->orderBy(

                'mv.manager_transaction_id',

                'DESC'

            )

            ->get()

            ->getResultArray();

        $currentBalance = 0;

        $cashIn = 0;

        $cashOut = 0;

        $transfer = 0;

        $returnToVault = 0;

        if(!empty($rows)){

            $currentBalance =

                (float)

                $rows[0]['balance_after'];

        }

        foreach($rows as $row){

            $amount =

                (float)

                $row['amount'];

            switch(

                strtoupper(

                    $row['transaction_type']

                )

            ){

                case 'CASH_IN':

                    $cashIn +=

                        $amount;

                break;

                case 'CASH_OUT':

                    $cashOut +=

                        $amount;

                break;

                case 'TRANSFER':

                    $transfer +=

                        $amount;

                    $cashOut +=

                        $amount;

                break;

                case 'RETURN TO VAULT':

                    $returnToVault +=

                        $amount;

                    $cashIn +=

                        $amount;

                break;

            }

        }

        return [

            'current_balance' =>

                $currentBalance,

            'cash_in' =>

                $cashIn,

            'cash_out' =>

                $cashOut,

            'transfer' =>

                $transfer - $returnToVault,

            'return_to_vault' =>

                $returnToVault 

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | GET TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    // public function getTransactions(
    //     $search = '',
    //     $transactionId = null,
    //     $transactionType = '',
    //     $dateFrom = '',
    //     $dateTo = '',
    //     $cashierId = null
    // )
    // {
    //     $builder =
    //         $this->db
    //         ->table(
    //             'manager_vault mv'
    //         )
    //         ->select("

    //             mv.*,

    //             CONCAT(

    //                 COALESCE(u.firstname,''),

    //                 ' ',

    //                 COALESCE(u.lastname,'')

    //             ) AS cashier_name,

    //             CONCAT(

    //                 COALESCE(c.firstname,''),

    //                 ' ',

    //                 COALESCE(c.lastname,'')

    //             ) AS created_by_name

    //         ")

    //         ->join(

    //             'users u',

    //             'u.userid = mv.cashier_id',

    //             'left'

    //         )

    //         ->join(

    //             'users c',

    //             'c.userid = mv.created_by',

    //             'left'

    //         )

    //         ->where(

    //             'mv.is_active',

    //             1

    //         );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | SEARCH
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($search)
    //     ){

    //         $builder
    //         ->groupStart()

    //         ->like(
    //             'mv.reference_no',
    //             $search
    //         )

    //         ->orLike(
    //             'mv.remarks',
    //             $search
    //         )

    //         ->orLike(
    //             'mv.transaction_type',
    //             $search
    //         )

    //         ->orLike(
    //             "CONCAT(u.first_name,' ',u.last_name)",
    //             $search,
    //             false
    //         )

    //         ->groupEnd();

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | TRANSACTION ID
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($transactionId)
    //     ){

    //         $builder->where(

    //             'mv.manager_transaction_id',

    //             $transactionId

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | TYPE
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($transactionType)
    //     ){

    //         $builder->where(

    //             'mv.transaction_type',

    //             strtoupper(
    //                 $transactionType
    //             )

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | CASHIER
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($cashierId)
    //     ){

    //         $builder->where(

    //             'mv.cashier_id',

    //             $cashierId

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | DATE FROM
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($dateFrom)
    //     ){

    //         $builder->where(

    //             'DATE(mv.created_at) >=',

    //             $dateFrom

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | DATE TO
    //     |--------------------------------------------------------------------------
    //     */

    //     if(
    //         !empty($dateTo)
    //     ){

    //         $builder->where(

    //             'DATE(mv.created_at) <=',

    //             $dateTo

    //         );

    //     }

    //     return
    //         $builder

    //         ->orderBy(

    //             'mv.manager_transaction_id',

    //             'DESC'

    //         )

    //         ->get()

    //         ->getResultArray();

    // }

    // public function getTransactions(

    //     $search = '',

    //     $transactionId = null,

    //     $transactionType = '',

    //     $dateFrom = '',

    //     $dateTo = '',

    //     $cashierId = null,

    //     $start = 0,

    //     $length = 10,

    //     $orderColumn = 'manager_transaction_id',

    //     $orderDir = 'DESC'

    // )
    // {

    //     /*
    //     |--------------------------------------------------------------------------
    //     | ALLOWED ORDER COLUMNS
    //     |--------------------------------------------------------------------------
    //     */

    //     $columns = [

    //         'manager_transaction_id' => 'mv.manager_transaction_id',

    //         'created_at' => 'mv.created_at',

    //         'reference_no' => 'mv.reference_no',

    //         'cashier_name' => 'u.lastname',

    //         'transaction_type' => 'mv.transaction_type',

    //         'amount' => 'mv.amount',

    //         'balance_before' => 'mv.balance_before',

    //         'balance_after' => 'mv.balance_after',

    //         'created_by_name' => 'c.lastname'

    //     ];

    //     $orderBy =

    //         $columns[$orderColumn]

    //         ??

    //         'mv.manager_transaction_id';

    //     $builder =

    //         $this->db

    //         ->table('manager_vault mv')

    //         ->select("

    //             mv.*,

    //             CONCAT(

    //                 IFNULL(u.firstname,''),

    //                 ' ',

    //                 IFNULL(u.lastname,'')

    //             ) AS cashier_name,

    //             CONCAT(

    //                 IFNULL(c.firstname,''),

    //                 ' ',

    //                 IFNULL(c.lastname,'')

    //             ) AS created_by_name

    //         ")

    //         ->join(

    //             'users u',

    //             'u.userid = mv.cashier_id',

    //             'left'

    //         )

    //         ->join(

    //             'users c',

    //             'c.userid = mv.created_by',

    //             'left'

    //         )

    //         ->where(

    //             'mv.is_active',

    //             1

    //         );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | SEARCH
    //     |--------------------------------------------------------------------------
    //     */


    //     if (!empty($search)) {

    //         $search = trim($search);

    //         // Convert currency input like ₱100,000.00 to 100000.00
    //         $numericSearch = str_replace(
    //             ['₱', ',', ' '],
    //             '',
    //             $search
    //         );

    //         $builder->groupStart()

    //             // Reference
    //             ->like(
    //                 'mv.reference_no',
    //                 $search
    //             )

    //             // Transaction Type
    //             ->orLike(
    //                 'mv.transaction_type',
    //                 $search
    //             )

    //             // Remarks
    //             ->orLike(
    //                 'mv.remarks',
    //                 $search
    //             )

    //             // Cashier
    //             ->orLike(
    //                 'u.firstname',
    //                 $search
    //             )

    //             ->orLike(
    //                 'u.lastname',
    //                 $search
    //             )

    //             // Created By
    //             ->orLike(
    //                 'c.firstname',
    //                 $search
    //             )

    //             ->orLike(
    //                 'c.lastname',
    //                 $search
    //             );

    //         /*
    //         |--------------------------------------------------------------------------
    //         | NUMERIC SEARCH
    //         |--------------------------------------------------------------------------
    //         */

    //         if (is_numeric($numericSearch)) {

    //             $builder

    //                 ->orWhere(
    //                     'mv.manager_transaction_id',
    //                     (int)$numericSearch
    //                 )

    //                 ->orWhere(
    //                     'mv.cashier_id',
    //                     (int)$numericSearch
    //                 )

    //                 ->orWhere(
    //                     'mv.amount',
    //                     (float)$numericSearch
    //                 )

    //                 ->orWhere(
    //                     'mv.balance_before',
    //                     (float)$numericSearch
    //                 )

    //                 ->orWhere(
    //                     'mv.balance_after',
    //                     (float)$numericSearch
    //                 );

    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | DATE SEARCH
    //         |--------------------------------------------------------------------------
    //         */

    //         if (
    //             strtotime($search) !== false
    //         ) {

    //             $builder->orWhere(

    //                 'DATE(mv.created_at)',

    //                 date(
    //                     'Y-m-d',
    //                     strtotime($search)
    //                 )

    //             );

    //         }

    //         $builder->groupEnd();

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | TRANSACTION ID
    //     |--------------------------------------------------------------------------
    //     */

    //     if(!empty($transactionId)){

    //         $builder->where(

    //             'mv.manager_transaction_id',

    //             $transactionId

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | TRANSACTION TYPE
    //     |--------------------------------------------------------------------------
    //     */

    //     if(!empty($transactionType)){

    //         $builder->where(

    //             'mv.transaction_type',

    //             strtoupper(

    //                 $transactionType

    //             )

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | CASHIER
    //     |--------------------------------------------------------------------------
    //     */

    //     if(!empty($cashierId)){

    //         $builder->where(

    //             'mv.cashier_id',

    //             $cashierId

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | DATE FROM
    //     |--------------------------------------------------------------------------
    //     */

    //     if(!empty($dateFrom)){

    //         $builder->where(

    //             'DATE(mv.created_at) >=',

    //             $dateFrom

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | DATE TO
    //     |--------------------------------------------------------------------------
    //     */

    //     if(!empty($dateTo)){

    //         $builder->where(

    //             'DATE(mv.created_at) <=',

    //             $dateTo

    //         );

    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | ORDER
    //     |--------------------------------------------------------------------------
    //     */

    //     $builder->orderBy(

    //         $orderBy,

    //         $orderDir

    //     );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | LIMIT
    //     |--------------------------------------------------------------------------
    //     */

    //     if($length != -1){

    //         $builder->limit(

    //             $length,

    //             $start

    //         );

    //     }

    //     return

    //         $builder

    //         ->get()

    //         ->getResultArray();

    // }

    public function getTransactions(

        $search = '',

        $transactionId = null,

        $transactionType = '',

        $dateFrom = '',

        $dateTo = '',

        $cashierId = null,

        $start = 0,

        $length = 10,

        $orderColumn = 'manager_transaction_id',

        $orderDir = 'DESC'

    )
    {

        $columns = [

            'manager_transaction_id' => 'mv.manager_transaction_id',

            'created_at' => 'mv.created_at',

            'reference_no' => 'mv.reference_no',

            'cashier_name' => 'cashier.lastname',

            'transaction_type' => 'mv.transaction_type',

            'amount' => 'mv.amount',

            'balance_before' => 'mv.balance_before',

            'balance_after' => 'mv.balance_after',

            'created_by_name' => 'creator.lastname',

            'business_date' => 'cdc.business_date',

            'status' => 'cdc.status'

        ];

        $orderBy =

            $columns[$orderColumn]

            ??

            'mv.manager_transaction_id';

        $builder =

            $this->db

            ->table('manager_vault mv')

            ->select("

                mv.*,

                cdc.business_date,

                cdc.status,

                cdc.expected_cash,

                cdc.actual_cash,

                cdc.returned_amount,

                cdc.variance,

                cdc.closed_at,

                cdc.approved_at,

                cdc.approved_by,

                cdc.remarks AS daily_close_remarks,

                CONCAT(

                    IFNULL(cashier.firstname,''),

                    ' ',

                    IFNULL(cashier.lastname,'')

                ) AS cashier_name,

                CONCAT(

                    IFNULL(creator.firstname,''),

                    ' ',

                    IFNULL(creator.lastname,'')

                ) AS created_by_name,

                CONCAT(

                    IFNULL(approver.firstname,''),

                    ' ',

                    IFNULL(approver.lastname,'')

                ) AS approved_by_name

            ")

            ->join(

                'cashier_daily_close cdc',

                'cdc.cashier_daily_close_id = mv.cashier_daily_close_id',

                'left'

            )

            ->join(

                'users cashier',

                'cashier.userid = mv.cashier_id',

                'left'

            )

            ->join(

                'users creator',

                'creator.userid = mv.created_by',

                'left'

            )

            ->join(

                'users approver',

                'approver.userid = cdc.approved_by',

                'left'

            )

            ->where(

                'mv.is_active',

                1

            );

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

            $builder->groupStart()

                ->like(
                    'mv.reference_no',
                    $search
                )

                ->orLike(
                    'mv.transaction_type',
                    $search
                )

                ->orLike(
                    'mv.remarks',
                    $search
                )

                ->orLike(
                    'cashier.firstname',
                    $search
                )

                ->orLike(
                    'cashier.lastname',
                    $search
                )

                ->orLike(
                    'creator.firstname',
                    $search
                )

                ->orLike(
                    'creator.lastname',
                    $search
                )

                ->orLike(
                    'cdc.status',
                    $search
                )

                ->orLike(
                    'cdc.business_date',
                    $search
                );

            if(is_numeric($numericSearch)){

                $builder

                    ->orWhere(
                        'mv.manager_transaction_id',
                        (int)$numericSearch
                    )

                    ->orWhere(
                        'mv.cashier_id',
                        (int)$numericSearch
                    )

                    ->orWhere(
                        'mv.amount',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'mv.balance_before',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'mv.balance_after',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'cdc.expected_cash',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'cdc.actual_cash',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'cdc.returned_amount',
                        (float)$numericSearch
                    )

                    ->orWhere(
                        'cdc.variance',
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

        if(!empty($transactionId)){

            $builder->where(

                'mv.manager_transaction_id',

                $transactionId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION TYPE
        |--------------------------------------------------------------------------
        */

        if(!empty($transactionType)){

            $builder->where(

                'mv.transaction_type',

                strtoupper($transactionType)

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(

                'mv.cashier_id',

                $cashierId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE FILTER
        |--------------------------------------------------------------------------
        */

        if(!empty($dateFrom)){

            $builder->where(

                'DATE(mv.created_at) >=',

                $dateFrom

            );

        }

        if(!empty($dateTo)){

            $builder->where(

                'DATE(mv.created_at) <=',

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

        if($length != -1){

            $builder->limit(

                $length,

                $start

            );

        }

        return

            $builder

            ->get()

            ->getResultArray();

    }


    public function countTransactions(

        $search = '',

        $transactionType = '',

        $dateFrom = '',

        $dateTo = '',

        $cashierId = null

    )
    {

        $builder =

            $this->db

            ->table('manager_vault mv')

            ->join(

                'users u',

                'u.userid = mv.cashier_id',

                'left'

            )

            ->join(

                'users c',

                'c.userid = mv.created_by',

                'left'

            )

            ->where(

                'mv.is_active',

                1

            );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if(!empty($search)){

            $builder

                ->groupStart()

                ->like(
                    'mv.reference_no',
                    $search
                )

                ->orLike(
                    'mv.remarks',
                    $search
                )

                ->orLike(
                    'mv.transaction_type',
                    $search
                )

                ->orLike(
                    'u.firstname',
                    $search
                )

                ->orLike(
                    'u.lastname',
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

                ->groupEnd();

        }

        /*
        |--------------------------------------------------------------------------
        | TRANSACTION TYPE
        |--------------------------------------------------------------------------
        */

        if(!empty($transactionType)){

            $builder->where(

                'mv.transaction_type',

                strtoupper($transactionType)

            );

        }

        /*
        |--------------------------------------------------------------------------
        | CASHIER
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(

                'mv.cashier_id',

                $cashierId

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE FROM
        |--------------------------------------------------------------------------
        */

        if(!empty($dateFrom)){

            $builder->where(

                'DATE(mv.created_at) >=',

                $dateFrom

            );

        }

        /*
        |--------------------------------------------------------------------------
        | DATE TO
        |--------------------------------------------------------------------------
        */

        if(!empty($dateTo)){

            $builder->where(

                'DATE(mv.created_at) <=',

                $dateTo

            );

        }

        return

            $builder

            ->countAllResults();

    }

    /*
    |--------------------------------------------------------------------------
    | GET TRANSACTION DETAILS
    |--------------------------------------------------------------------------
    */

    // public function getTransactionDetails(
    //     int $transactionId
    // )
    // {
    //     return $this->db
    //         ->table('manager_vault mv')
    //         ->select("

    //             mv.*,

    //             u.userid,

    //             u.firstname,

    //             u.lastname,


    //             CONCAT(

    //                 IFNULL(u.firstname,''),

    //                 ' ',

    //                 IFNULL(u.lastname,'')

    //             ) AS cashier_name,


    //             CONCAT(

    //                 IFNULL(c.firstname,''),

    //                 ' ',

    //                 IFNULL(c.lastname,'')

    //             ) AS created_by_name

    //         ")

    //         ->join(
    //             'users u',
    //             'u.user_id = mv.cashier_id',
    //             'left'
    //         )

    //         ->join(
    //             'users c',
    //             'c.user_id = mv.created_by',
    //             'left'
    //         )

    //         ->where(
    //             'mv.manager_transaction_id',
    //             $transactionId
    //         )

    //         ->where(
    //             'mv.is_active',
    //             1
    //         )

    //         ->get()

    //         ->getRowArray();

    // }

    /*
    |--------------------------------------------------------------------------
    | ADD TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function addTransaction(
        array $data
    )
    {
        $this->db
            ->table(
                'manager_vault'
            )
            ->insert([

                'transaction_type' =>
                    strtoupper(
                        $data['transaction_type']
                    ),

                'cashier_id' =>
                    $data['cashier_id']
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
                    $data['created_at']
                    ?? date(
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
    | UPDATE MANAGER BALANCE
    |--------------------------------------------------------------------------
    */

    public function updateBalance(
        float $balance
    )
    {
        $row =
            $this->db
            ->table(
                'manager_balance'
            )
            ->where(
                'id',
                1
            )
            ->get()
            ->getRowArray();

        if($row){

            $this->db
                ->table(
                    'manager_balance'
                )
                ->where(
                    'id',
                    1
                )
                ->update([

                    'current_balance' =>
                        $balance,

                    'updated_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]);

        }else{

            $this->db
                ->table(
                    'manager_balance'
                )
                ->insert([

                    'id' => 1,

                    'current_balance' =>
                        $balance,

                    'updated_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]);

        }

        return true;

    }

    /*
    |--------------------------------------------------------------------------
    | GET CASHIER BALANCE
    |--------------------------------------------------------------------------
    */

    public function getCashierBalance(
        int $cashierId
    )
    {
        $balance =
            $this->db
            ->table(
                'cashier_balance'
            )
            ->where(
                'cashier_id',
                $cashierId
            )
            ->get()
            ->getRowArray();

        if(!$balance){

            return [

                'cashier_id' =>
                    $cashierId,

                'current_balance' =>
                    0

            ];

        }

        return $balance;

    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE CASHIER BALANCE
    |--------------------------------------------------------------------------
    */

    public function getLatestBalance(
        int $cashierId
    )
    {
        $row = $this->db
            ->table('cashier_vault')
            ->select('balance_after')
            ->where('cashier_id', $cashierId)
            ->where('is_active', 1)
            ->orderBy('cashier_transaction_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return (float)($row['balance_after'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | ADD CASHIER TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function addCashierTransaction(
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
                    strtoupper(
                        $data['transaction_type']
                    ),

                'loan_id' =>
                    $data['loan_id']
                    ?? null,

                'collection_id' =>
                    $data['collection_id']
                    ?? null,

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
                    $data['created_at']
                    ?? date(
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
    | DELETE CASHIER TRANSACTION
    |--------------------------------------------------------------------------
    */

    public function deleteCashierTransaction(
        string $referenceNo
    )
    {
        return
            $this->db
            ->table(
                'cashier_vault'
            )
            ->where(
                'reference_no',
                $referenceNo
            )
            ->update([

                'is_active' =>
                    0

            ]);

    }

    /*
    |--------------------------------------------------------------------------
    | LATEST MANAGER BALANCE
    |--------------------------------------------------------------------------
    */

    public function getLatestManagerBalance()
    {
        $row = $this->db
            ->table('manager_vault')
            ->select('balance_after')
            ->where('is_active', 1)
            ->orderBy(
                'manager_transaction_id',
                'DESC'
            )
            ->limit(1)
            ->get()
            ->getRowArray();

        return (float)(
            $row['balance_after'] ?? 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LATEST CASHIER BALANCE
    |--------------------------------------------------------------------------
    */

    public function getLatestCashierBalance(
        int $cashierId
    )
    {
        $row =
            $this->db
            ->table(
                'cashier_vault'
            )
            ->select(
                'balance_after'
            )
            ->where(
                'cashier_id',
                $cashierId
            )
            ->where(
                'is_active',
                1
            )
            ->orderBy(
                'cashier_transaction_id',
                'DESC'
            )
            ->get()
            ->getRowArray();

        return
            (float)(
                $row['balance_after']
                ?? 0
            );

    }

    public function generateReferenceNo(
            string $transactionType
        )
        {
            switch($transactionType){

                case 'CASH_IN':
                    $prefix = 'CI';
                break;

                case 'CASH_OUT':
                    $prefix = 'CO';
                break;

                case 'TRANSFER':
                    $prefix = 'TR';
                break;

                case 'RETURN TO VAULT':
                    $prefix = 'RV';
                break;

                default:
                    $prefix = 'MV';
                break;

            }

            $last =

                $this->db

                ->table('manager_vault')

                ->select('reference_no')

                ->like(
                    'reference_no',
                    $prefix . '-',
                    'after'
                )

                ->orderBy(
                    'manager_transaction_id',
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

        public function getTransactionDetails(
                int $managerTransactionId
            )
            {

                /*
                |--------------------------------------------------------------------------
                | TRANSACTION
                |--------------------------------------------------------------------------
                */

                $transaction =

                    $this->db

                    ->table('manager_vault mv')

                    ->select("

                        mv.*,

                        cdc.business_date,

                        cdc.status,

                        cdc.expected_cash,

                        cdc.actual_cash,

                        cdc.returned_amount,

                        cdc.variance,

                        cdc.closed_at,

                        cdc.approved_at,

                        cdc.approved_by,

                        cdc.remarks AS daily_close_remarks,

                        CONCAT(

                            IFNULL(cashier.firstname,''),

                            ' ',

                            IFNULL(cashier.lastname,'')

                        ) AS cashier_name,

                        CONCAT(

                            IFNULL(creator.firstname,''),

                            ' ',

                            IFNULL(creator.lastname,'')

                        ) AS created_by_name,

                        CONCAT(

                            IFNULL(approver.firstname,''),

                            ' ',

                            IFNULL(approver.lastname,'')

                        ) AS approved_by_name

                    ")

                    ->join(

                        'cashier_daily_close cdc',

                        'cdc.cashier_daily_close_id = mv.cashier_daily_close_id',

                        'left'

                    )

                    ->join(

                        'users cashier',

                        'cashier.userid = mv.cashier_id',

                        'left'

                    )

                    ->join(

                        'users creator',

                        'creator.userid = mv.created_by',

                        'left'

                    )

                    ->join(

                        'users approver',

                        'approver.userid = cdc.approved_by',

                        'left'

                    )

                    ->where(

                        'mv.manager_transaction_id',

                        $managerTransactionId

                    )

                    ->where(

                        'mv.is_active',

                        1

                    )

                    ->get()

                    ->getRowArray();

                if(
                    !$transaction
                ){

                    return null;

                }

                /*
                |--------------------------------------------------------------------------
                | DENOMINATIONS
                |--------------------------------------------------------------------------
                */

                $denominations = [];

                if(
                    !empty(
                        $transaction['cashier_daily_close_id']
                    )
                ){

                    $denominations =

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

                /*
                |--------------------------------------------------------------------------
                | RETURN
                |--------------------------------------------------------------------------
                */

                return [

                    'transaction' =>

                        $transaction,

                    'denominations' =>

                        $denominations

                ];

            }

    public function rebuildCashBalances()
    {
        $transactions =

            $this->db

            ->table('manager_vault')

            ->where('is_active',1)

            ->orderBy('manager_transaction_id','ASC')

            ->get()

            ->getResultArray();

        $balance = 0;

        foreach($transactions as $row){

            $before = $balance;

            if($row['transaction_type']=="CASH_IN" || $row['transaction_type']=="RETURN TO VAULT"){

                $balance += $row['amount'];

            }else{

                $balance -= $row['amount'];

            }

            $this->db

            ->table('manager_vault')

            ->where(
                'manager_transaction_id',
                $row['manager_transaction_id']
            )

            ->update([

                'balance_before'=>$before,

                'balance_after'=>$balance

            ]);

        }

    }    

}