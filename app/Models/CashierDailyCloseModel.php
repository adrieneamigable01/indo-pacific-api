<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierDailyCloseModel extends Model
{
    protected $table            = 'cashier_daily_close';
    protected $primaryKey       = 'cashier_daily_close_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $useSoftDeletes   = false;

    protected $protectFields    = true;

    protected $allowedFields = [

        'cashier_id',
        'business_date',

        'expected_cash',
        'actual_cash',
        'variance',
        'returned_amount',

        'status',
        'remarks',

        'closed_by',
        'closed_at',

        'approved_by',
        'approved_at',

        'rejected_by',
        'rejected_at',

        'cancelled_by',
        'cancelled_at',
        'cancel_reason',

        'created_at',
        'updated_at'

    ];

    protected bool $allowEmptyInserts = false;

    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;

    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [];

    protected $validationMessages = [];

    protected $skipValidation = false;

    /**
     * Get Daily Close List
     */

    public function getList(
        $start,
        $length,
        $orderColumn,
        $orderDir,
        $search = null,
        $cashierId = null,
        $status = null,
        $businessDate = null
    ) {

        $builder = $this->db
            ->table($this->table . ' d');

        $builder->select("
            d.*,
            CONCAT(
                IFNULL(u.firstname,''),
                ' ',
                IFNULL(u.lastname,'')
            ) AS cashier_name
        ");

        $builder->join(
            'users u',
            'u.userid = d.cashier_id',
            'left'
        );

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($cashierId)) {

            $builder->where(
                'd.cashier_id',
                $cashierId
            );

        }

        if (!empty($status)) {

            $builder->where(
                'd.status',
                $status
            );

        }

        if (!empty($businessDate)) {

            $builder->where(
                'd.business_date',
                $businessDate
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($search)) {

            $builder->groupStart();

            $builder->like(
                'u.firstname',
                $search
            );

            $builder->orLike(
                'u.lastname',
                $search
            );

            $builder->orLike(
                'd.business_date',
                $search
            );

            $builder->orLike(
                'd.status',
                $search
            );

            $builder->groupEnd();

        }

        /*
        |--------------------------------------------------------------------------
        | Column Mapping
        |--------------------------------------------------------------------------
        */

        $columns = [

            'cashier_daily_close_id' => 'd.cashier_daily_close_id',

            'cashier_name' => 'u.firstname',

            'business_date' => 'd.business_date',

            'expected_cash' => 'd.expected_cash',

            'actual_cash' => 'd.actual_cash',

            'variance' => 'd.variance',

            'returned_amount' => 'd.returned_amount',

            'status' => 'd.status'

        ];

        if (!isset($columns[$orderColumn])) {

            $orderColumn = 'cashier_daily_close_id';

        }

        $builder->orderBy(

            $columns[$orderColumn],

            strtoupper($orderDir) == 'ASC'
                ? 'ASC'
                : 'DESC'

        );

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $builder->limit(

            $length,

            $start

        );

        return $builder
            ->get()
            ->getResultArray();

    }
    

    public function countList(

        $search,

        $cashierId,

        $status,

        $businessDate

    )
    {

        $builder =
            $this->db
            ->table($this->table.' d');

        $builder->join(
            'users u',
            'u.userid=d.cashier_id',
            'left'
        );

        if(!empty($cashierId)){

            $builder->where(
                'd.cashier_id',
                $cashierId
            );

        }

        if(!empty($status)){

            $builder->where(
                'd.status',
                $status
            );

        }

        if(!empty($businessDate)){

            $builder->where(
                'd.business_date',
                $businessDate
            );

        }

        if(!empty($search)){

            $builder->groupStart();

            $builder->like(
                'u.firstname',
                $search
            );

            $builder->orLike(
                'u.lastname',
                $search
            );

            $builder->orLike(
                'd.business_date',
                $search
            );

            $builder->groupEnd();

        }

        return $builder->countAllResults();

    }

    /**
     * Get Single Daily Close
     */
    public function getDetails($id)
    {
        $builder = $this->db
            ->table($this->table . ' d');

        $builder->select("
            d.*,
            CONCAT(
                IFNULL(u.firstname,''),
                ' ',
                IFNULL(u.lastname,'')
            ) AS cashier_name
        ");

        $builder->join(
            'users u',
            'u.userid = d.cashier_id',
            'left'
        );

        $builder->where(
            'd.cashier_daily_close_id',
            $id
        );

        return $builder
            ->get()
            ->getRowArray();
    }

    /**
     * Check Existing Daily Close
     */
    public function hasExistingDailyClose(
        $cashierId,
        $businessDate
    ) {

        return $this
            ->where('cashier_id', $cashierId)
            ->where('business_date', $businessDate)
            ->countAllResults() > 0;
    }

    /**
     * Update Status
     */
    public function updateStatus(
        $id,
        $status,
        $data = []
    ) {

        $payload = array_merge(
            [
                'status' => $status
            ],
            $data
        );

        return $this
            ->update(
                $id,
                $payload
            );
    }

    /**
     * Get Pending Returns
     */
    public function getPending()
    {
        return $this
            ->where(
                'status',
                'PENDING'
            )
            ->orderBy(
                'business_date',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Get Approved Returns
     */
    public function getApproved()
    {
        return $this
            ->where(
                'status',
                'APPROVED'
            )
            ->orderBy(
                'business_date',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Get Rejected Returns
     */
    public function getRejected()
    {
        return $this
            ->where(
                'status',
                'REJECTED'
            )
            ->orderBy(
                'business_date',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Get Cancelled Returns
     */
    public function getCancelled()
    {
        return $this
            ->where(
                'status',
                'CANCELLED'
            )
            ->orderBy(
                'business_date',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Get Cashier Latest Daily Close
     */
    public function getLatestByCashier($cashierId)
    {
        return $this
            ->where(
                'cashier_id',
                $cashierId
            )
            ->orderBy(
                'business_date',
                'DESC'
            )
            ->first();
    }
    public function getSummary(
        $cashierId = null,
        $businessDate = null,
        $status = null
    ){

        $builder = $this->db
            ->table($this->table . ' d');

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if(!empty($cashierId)){

            $builder->where(
                'd.cashier_id',
                $cashierId
            );

        }

        if(!empty($businessDate)){

            $builder->where(
                'd.business_date',
                $businessDate
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        | Optional. Usually not needed because the cards should show
        | all statuses, but included if you want to support it.
        |--------------------------------------------------------------------------
        */

        if(!empty($status)){

            $builder->where(
                'd.status',
                $status
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $builder->select("
            SUM(CASE WHEN d.status='PENDING' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status='APPROVED' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN d.status='REJECTED' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN d.status='CANCELLED' THEN 1 ELSE 0 END) AS cancelled
        ");

        return $builder
            ->get()
            ->getRowArray();

    }
}