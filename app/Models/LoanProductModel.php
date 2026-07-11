<?php

namespace App\Models;

use CodeIgniter\Model;

class LoanProductModel extends Model
{
    protected $table      = 'loan_products';
    protected $primaryKey = 'loan_product_id';

    protected $allowedFields = [

        'product_name',
        'description',

        'interest_rate',
        'processing_fee_percent',
        'penalty_rate',

        'max_term',

        'min_amount',
        'max_amount',

        'is_active',
        'created_at'

    ];

    protected $returnType = 'array';

    /**
     * Get Loan Products
     */
    public function getLoanProducts(
        $search = '',
        $loan_product_id = null
    )
    {

        $builder = $this->db
            ->table('loan_products');

        if (!empty($loan_product_id)) {

            $builder->where(
                'loan_product_id',
                $loan_product_id
            );
        }

        if (!empty($search)) {

            $builder->groupStart()

                ->like(
                    'product_name',
                    $search
                )

                ->orLike(
                    'description',
                    $search
                )

                ->groupEnd();
        }

        return $builder
            ->orderBy(
                'loan_product_id',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Get Loan Product Details
     */
    public function getLoanProductDetails(
        $loanProductId
    )
    {

        return $this->db
            ->table('loan_products')
            ->where(
                'loan_product_id',
                $loanProductId
            )
            ->get()
            ->getRowArray();
    }

    /**
     * Get Active Loan Products
     */
    public function getActiveLoanProducts()
    {

        return $this->db
            ->table('loan_products')
            ->where(
                'is_active',
                1
            )
            ->orderBy(
                'product_name',
                'ASC'
            )
            ->get()
            ->getResultArray();
    }

    /**
     * Check Existing Product Name
     */
    public function checkProductName(
        $productName,
        $excludeId = null
    )
    {

        $builder = $this->db
            ->table('loan_products')
            ->where(
                'UPPER(product_name)',
                strtoupper($productName)
            );

        if (!empty($excludeId)) {

            $builder->where(
                'loan_product_id !=',
                $excludeId
            );
        }

        return $builder
            ->countAllResults();
    }

    /**
     * Get Product Count
     */
    public function getTotalProducts()
    {

        return $this->db
            ->table('loan_products')
            ->countAllResults();
    }

    /**
     * Get Active Product Count
     */
    public function getActiveProductCount()
    {

        return $this->db
            ->table('loan_products')
            ->where(
                'is_active',
                1
            )
            ->countAllResults();
    }
}