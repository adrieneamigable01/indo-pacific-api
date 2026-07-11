<?php

namespace App\Models;

use CodeIgniter\Model;

class LoanAdjustmentModel extends Model
{
    protected $table = 'loan_adjustments';

    protected $primaryKey = 'loan_adjustment_id';

    protected $allowedFields = [

        'loan_id',
        'adjustment_type',
        'amount',
        'interest_rate',
        'penalty_rate',
        'term_months',
        'remarks',
        'created_by',
        'created_at',
        'is_active'

    ];

    protected $returnType = 'array';

    public function getLoanAdjustments(
        $search = '',
        $loanAdjustmentId = null,
        $loanId = null
    )
    {

        $builder = $this->db
            ->table('loan_adjustments la')
            ->select("
                la.*,
                lp.product_name,
                l.loan_amount
            ")
            ->join(
                'loans l',
                'l.loan_id = la.loan_id',
                'left'
            )
            ->join(
                'loan_products lp',
                'lp.loan_product_id = l.loan_product_id',
                'left'
            );

        if (!empty($loanAdjustmentId)) {

            $builder->where(
                'la.loan_adjustment_id',
                $loanAdjustmentId
            );
        }

        if (!empty($loanId)) {

            $builder->where(
                'la.loan_id',
                $loanId
            );
        }

        if (!empty($search)) {

            $builder->groupStart()

                ->like(
                    'lp.product_name',
                    $search
                )

                ->orLike(
                    'la.adjustment_type',
                    $search
                )

                ->orLike(
                    'la.remarks',
                    $search
                )

                ->groupEnd();
        }

        return $builder
            ->orderBy(
                'la.loan_adjustment_id',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    public function getLoanAdjustmentDetails(
        $loanAdjustmentId
    )
    {

        return $this->db
            ->table('loan_adjustments la')
            ->select("
                la.*,
                lp.product_name,
                l.loan_amount
            ")
            ->join(
                'loans l',
                'l.loan_id = la.loan_id',
                'left'
            )
            ->join(
                'loan_products lp',
                'lp.loan_product_id = l.loan_product_id',
                'left'
            )
            ->where(
                'la.loan_adjustment_id',
                $loanAdjustmentId
            )
            ->get()
            ->getRowArray();
    }
}