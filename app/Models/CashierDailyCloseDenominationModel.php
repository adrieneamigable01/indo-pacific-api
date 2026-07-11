<?php

namespace App\Models;

use CodeIgniter\Model;

class CashierDailyCloseDenominationModel extends Model
{
    protected $table            = 'cashier_daily_close_denominations';
    protected $primaryKey       = 'denomination_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [

        'cashier_daily_close_id',
        'denomination',
        'quantity',
        'total',

        'created_at',
        'updated_at'

    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;

    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * Get all denominations for a Daily Close
     */
    public function getByDailyCloseId($cashierDailyCloseId)
    {
        return $this
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->orderBy(
                'denomination',
                'DESC'
            )
            ->findAll();
    }

    /**
     * Save denomination list
     */
    public function saveDenominations(
        $cashierDailyCloseId,
        array $denominations
    ) {

        foreach ($denominations as $row) {

            $this->insert([

                'cashier_daily_close_id' => $cashierDailyCloseId,

                'denomination' => $row['denomination'],

                'quantity' => $row['quantity'],

                'total' => $row['total']

            ]);

        }

        return true;
    }

    /**
     * Replace denomination list
     */
    public function replaceDenominations(
        $cashierDailyCloseId,
        array $denominations
    ) {

        $this->where(
            'cashier_daily_close_id',
            $cashierDailyCloseId
        )->delete();

        return $this->saveDenominations(
            $cashierDailyCloseId,
            $denominations
        );
    }

    /**
     * Delete all denominations
     */
    public function deleteByDailyCloseId(
        $cashierDailyCloseId
    ) {

        return $this
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->delete();
    }

    /**
     * Compute total cash from denominations
     */
    public function computeTotal(
        $cashierDailyCloseId
    ) {

        $builder = $this->db
            ->table($this->table);

        $builder->selectSum(
            'total',
            'grand_total'
        );

        $builder->where(
            'cashier_daily_close_id',
            $cashierDailyCloseId
        );

        $result = $builder
            ->get()
            ->getRowArray();

        return (float) ($result['grand_total'] ?? 0);
    }

    /**
     * Get denomination summary
     */
    public function getSummary(
        $cashierDailyCloseId
    ) {

        return $this->db
            ->table($this->table)
            ->select("
                denomination,
                quantity,
                total
            ")
            ->where(
                'cashier_daily_close_id',
                $cashierDailyCloseId
            )
            ->orderBy(
                'denomination',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }
    
}