<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierDailyCloseLogModel extends Model
{
    protected $table            = 'cashier_daily_close_logs';
    protected $primaryKey       = 'close_log_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [

        'cashier_daily_close_id',
        'action',
        'remarks',
        'action_by',

        'created_at'

    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Get Logs
     */
    public function getByDailyCloseId($cashierDailyCloseId)
    {
        return $this->db
            ->table($this->table . ' l')
            ->select("
                l.*,
                CONCAT(
                    IFNULL(u.firstname,''),
                    ' ',
                    IFNULL(u.lastname,'')
                ) AS action_by_name
            ")
            ->join(
                'users u',
                'u.userid = l.action_by',
                'left'
            )
            ->where(
                'l.cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->orderBy(
                'l.created_at',
                'ASC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Add Log
     */
    public function addLog(
        $cashierDailyCloseId,
        $action,
        $actionBy,
        $remarks = null
    ) {

        return $this->insert([

            'cashier_daily_close_id' => $cashierDailyCloseId,

            'action' => strtoupper($action),

            'remarks' => $remarks,

            'action_by' => $actionBy

        ]);
    }

    /**
     * Get Latest Log
     */
    public function getLatestLog($cashierDailyCloseId)
    {
        return $this->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->first();
    }

    /**
     * Delete Logs
     */
    public function deleteByDailyCloseId($cashierDailyCloseId)
    {
        return $this
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->delete();
    }

    /**
     * Count Logs
     */
    public function countLogs($cashierDailyCloseId)
    {
        return $this
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->countAllResults();
    }

    /**
     * Get Logs By Action
     */
    public function getByAction(
        $cashierDailyCloseId,
        $action
    ) {

        return $this
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->where(
                'action',
                strtoupper($action)
            )
            ->orderBy(
                'created_at',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Get Recent Logs
     */
    public function getRecentLogs($limit = 20)
    {
        return $this->db
            ->table($this->table . ' l')
            ->select("
                l.*,
                CONCAT(
                    IFNULL(u.firstname,''),
                    ' ',
                    IFNULL(u.lastname,'')
                ) AS action_by_name
            ")
            ->join(
                'users u',
                'u.userid = l.action_by',
                'left'
            )
            ->orderBy(
                'l.created_at',
                'DESC'
            )
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
    public function getSummary(
        $cashierId = null,
        $businessDate = null
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
        | Summary
        |--------------------------------------------------------------------------
        */

        $builder->select("
            COUNT(*) AS total,
            SUM(CASE WHEN d.status='PENDING' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN d.status='APPROVED' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN d.status='REJECTED' THEN 1 ELSE 0 END) AS rejected,
            SUM(CASE WHEN d.status='CANCELLED' THEN 1 ELSE 0 END) AS cancelled,
            SUM(IFNULL(d.expected_cash,0)) AS expected_cash,
            SUM(IFNULL(d.actual_cash,0)) AS actual_cash,
            SUM(IFNULL(d.variance,0)) AS variance,
            SUM(IFNULL(d.returned_amount,0)) AS returned_amount
        ");

        return $builder
            ->get()
            ->getRowArray();

    }
}