<?php

namespace App\Models;

use CodeIgniter\Model;

class BorrowerIncentiveModel extends Model
{
    protected $table            = 'borrower_incentive'; // ✅ Singular, not plural
    protected $primaryKey       = 'incentive_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'borrower_id',
        'incentive_month',
        'incentive_type',        // ✅ String field (e.g., "SALARY INTEREST ONLY")
        'incentive_type_id',     // ✅ OR int field referencing incentive_types.id
        'incentive_amount',
        'status',
        'remarks'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getBorrowerIncentiveList(
        $search = '',
        $incentiveMonth = '',
        $borrowerId = '',
        $incentiveTypeId = '',
        $status = '',
        $start = 0,
        $length = 10,
        $orderColumn = 'borrower_name',
        $orderDir = 'ASC'
    ) {
        $incentiveDate = null;

        if (!empty($incentiveMonth)) {
            $incentiveDate = date('Y-m-20', strtotime($incentiveMonth . '-01'));
        }

        // ✅ CORRECT TABLE NAME: 'borrowers' with alias 'b'
        $builder = $this->db->table('borrowers b');

        $builder->select('
            bi.incentive_id,
            b.borrower_id,
            CONCAT(b.last_name, ", ", b.first_name) AS borrower_name,
            bi.incentive_type_id,
            it.name AS incentive_type_name,
            bi.incentive_type,
            bi.incentive_month,
            COALESCE(bi.incentive_amount, 0) AS incentive_amount,
            bi.remarks,
            COALESCE(bi.status, "PENDING") AS status
        ');

        /*
        |--------------------------------------------------------------------------
        | JOIN borrower_incentive (CORRECT TABLE NAME)
        |--------------------------------------------------------------------------
        */
        $joinCondition = 'bi.borrower_id = b.borrower_id';

        if (!empty($incentiveDate)) {
            $joinCondition .= ' AND bi.incentive_month = ' . $this->db->escape($incentiveDate);
        }

        if (!empty($incentiveTypeId)) {
            $joinCondition .= ' AND bi.incentive_type_id = ' . (int) $incentiveTypeId;
        }

        // ✅ CORRECT TABLE NAME: 'borrower_incentive' (singular)
        $builder->join('borrower_incentive bi', $joinCondition, 'left');

        /*
        |--------------------------------------------------------------------------
        | JOIN incentive_types
        |--------------------------------------------------------------------------
        */
        // ✅ CORRECT: incentive_types.id (not incentive_type_id)
        $builder->join('incentive_types it', 'it.id = bi.incentive_type_id', 'left');

        /*
        |--------------------------------------------------------------------------
        | Active borrowers only
        |--------------------------------------------------------------------------
        */
        $builder->where('b.isActive', 1);

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */
        if (!empty($borrowerId)) {
            $builder->where('b.borrower_id', (int) $borrowerId);
        }

        if (!empty($status)) {
            $builder->where('bi.status', strtoupper($status));
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if (!empty($search)) {
            $builder->groupStart()
                ->like('b.first_name', $search)
                ->orLike('b.last_name', $search)
                ->orLike('it.name', $search)
                ->orLike('bi.incentive_type', $search)
                ->groupEnd();
        }

        /*
        |--------------------------------------------------------------------------
        | Ordering
        |--------------------------------------------------------------------------
        */
        $allowedOrderColumns = [
            'incentive_id'        => 'bi.incentive_id',
            'borrower_name'       => 'b.last_name',
            'incentive_type_name' => 'it.name',
            'incentive_amount'    => 'bi.incentive_amount',
            'status'              => 'bi.status',
            'incentive_month'     => 'bi.incentive_month'
        ];

        $orderBy = $allowedOrderColumns[$orderColumn] ?? 'b.last_name';
        $orderDir = strtoupper($orderDir);

        if (!in_array($orderDir, ['ASC', 'DESC'])) {
            $orderDir = 'ASC';
        }

        $builder->orderBy($orderBy, $orderDir);
        $builder->orderBy('b.first_name', 'ASC');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
        if ((int) $length !== -1) {
            $builder->limit((int) $length, (int) $start);
        }

        // ✅ DEBUG: Print query before executing (remove in production)
        // echo $builder->getCompiledSelect(); die();

        $result = $builder->get();

        // ✅ CHECK IF QUERY FAILED
        if ($result === false) {
            log_message('error', 'Query failed: ' . $this->db->getError());
            return [];
        }

        return $result->getResultArray();
    }

    public function countBorrowers($incentiveTypeId = '')
    {
        $builder = $this->db->table('borrowers');
        $builder->where('isActive', 1);
        return $builder->countAllResults();
    }

    public function countFilteredBorrowers(
        $search = '',
        $incentiveMonth = '',
        $borrowerId = '',
        $incentiveTypeId = '',
        $status = ''
    ) {
        $incentiveDate = null;

        if (!empty($incentiveMonth)) {
            $incentiveDate = date('Y-m-20', strtotime($incentiveMonth . '-01'));
        }

        $builder = $this->db->table('borrowers b');

        $joinCondition = 'bi.borrower_id = b.borrower_id';

        if (!empty($incentiveDate)) {
            $joinCondition .= ' AND bi.incentive_month = ' . $this->db->escape($incentiveDate);
        }

        if (!empty($incentiveTypeId)) {
            $joinCondition .= ' AND bi.incentive_type_id = ' . (int) $incentiveTypeId;
        }

        $builder->join('borrower_incentive bi', $joinCondition, 'left');

        if (!empty($status)) {
            $builder->where('bi.status', strtoupper($status));
        }

        if (!empty($borrowerId)) {
            $builder->where('b.borrower_id', (int) $borrowerId);
        }

        if (!empty($search)) {
            $builder->groupStart()
                ->like('b.first_name', $search)
                ->orLike('b.last_name', $search)
                ->groupEnd();
        }

        $builder->where('b.isActive', 1);

        return $builder->countAllResults();
    }

    public function getIncentive($incentiveId)
    {
        $builder = $this->db->table('borrower_incentive bi');

        $builder->select('
            bi.*,
            b.first_name,
            b.last_name,
            CONCAT(b.last_name, ", ", b.first_name) as borrower_name,
            it.name as incentive_type_name
        ');

        $builder->join('borrowers b', 'b.borrower_id = bi.borrower_id', 'left');
        $builder->join('incentive_types it', 'it.id = bi.incentive_type_id', 'left');
        $builder->where('bi.incentive_id', $incentiveId);

        $result = $builder->get();

        if ($result === false) {
            log_message('error', 'Query failed in getIncentive: ' . $this->db->getError());
            return null;
        }

        return $result->getRowArray();
    }

    public function getSummary($incentiveMonth)
    {
        $incentiveDate = date('Y-m-20', strtotime($incentiveMonth . '-01'));

        $builder = $this->db->table('borrower_incentive bi');

        $builder->select('
            COUNT(*) as withIncentive,
            SUM(bi.incentive_amount) as totalAmount,
            SUM(CASE WHEN bi.status = "PAID" THEN bi.incentive_amount ELSE 0 END) as totalPaid,
            SUM(CASE WHEN bi.status = "PENDING" THEN bi.incentive_amount ELSE 0 END) as totalPending
        ');

        $builder->where('bi.incentive_month', $incentiveDate);

        $result = $builder->get();

        if ($result === false) {
            log_message('error', 'Query failed in getSummary: ' . $this->db->getError());
            return ['withIncentive' => 0, 'totalAmount' => 0, 'totalPaid' => 0, 'totalPending' => 0];
        }

        return $result->getRowArray();
    }
}