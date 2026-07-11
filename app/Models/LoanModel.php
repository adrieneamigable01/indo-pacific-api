<?php

namespace App\Models;

use CodeIgniter\Model;

class LoanModel extends Model
{
    protected $table            = 'loans';
    protected $primaryKey       = 'loan_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'borrower_id',
        'loan_product_id',
        'loan_amount',
        'loan_purpose',
        'loan_terms',
        'approved_interest_rate',
        'approved_processing_fee',
        'status',
        'interest_amount',
        'processingfee_amount',
        'net_proceeds',
        'created_at',
        'approveRemarks',
        'disapproveReason',
    ];

    /**
     * GET ALL LOANS
     */
    public function getLoans($search = '', $where = [])
    {
        $builder = $this->db
            ->table('loans l')
            ->select("
                l.*,

                CONCAT(
                    COALESCE(b.last_name,''), ', ',
                    COALESCE(b.first_name,''), ' ',
                    COALESCE(b.middle_name,'')
                ) AS borrower_name,

                lp.product_name,
                lp.is_salary_deducted
            ")
            ->join(
                'borrowers b',
                'b.borrower_id = l.borrower_id',
                'left'
            )
            ->join(
                'loan_products lp',
                'lp.loan_product_id = l.loan_product_id',
                'left'
            );
      
        if (!empty($where['l.status'])) {
            $builder->where('l.status',$where['l.status']);
        }
        if (!empty($where['borrower_id'])) {
             $builder->where('l.borrower_id',$where['borrower_id']);
        }

        if (!empty($search)) {

            $builder->groupStart()
                ->like('b.first_name', $search)
                ->orLike('b.last_name', $search)
                ->orLike('lp.product_name', $search)
                ->orLike('l.loan_amount', $search)
                ->orLike('l.loan_id', $search)
                ->groupEnd();
        }

        $loans = $builder
            ->orderBy('l.loan_id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($loans as &$loan) {

            /*
            |--------------------------------------------------------------------------
            | SCHEDULES
            |--------------------------------------------------------------------------
            */

            $schedules = $this->db
                ->table('loan_schedule')
                ->where('loan_id', $loan['loan_id'])
                ->orderBy('due_date', 'ASC')
                ->get()
                ->getResultArray();

            foreach($schedules as &$schedule){

                $paymentTotals = $this->db
                    ->table('loan_payments')
                    ->select("
                        COALESCE(SUM(principal_amount),0) as principal_paid,
                        COALESCE(SUM(interest_amount),0) as interest_paid,
                        COALESCE(SUM(penalty_amount),0) as penalty_paid
                    ")
                    ->where(
                        'schedule_id',
                        $schedule['schedule_id']
                    )
                    ->get()
                    ->getRowArray();

                $schedule['principal_paid'] =
                    (float)$paymentTotals['principal_paid'];

                $schedule['interest_paid'] =
                    (float)$paymentTotals['interest_paid'];

                $schedule['penalty_paid'] =
                    (float)$paymentTotals['penalty_paid'];

                $schedule['balance'] =

                    max(
                        0,
                        $schedule['principal_due']
                        -
                        $schedule['principal_paid']
                    )

                    +

                    max(
                        0,
                        $schedule['interest_due']
                        -
                        $schedule['interest_paid']
                    )

                    +

                    max(
                        0,
                        $schedule['penalty_due']
                        -
                        $schedule['penalty_paid']
                    );

                if(
                    $schedule['balance'] <= 0
                ){

                    $schedule['status'] =
                        'PAID';

                }
                else if(

                    $schedule['principal_paid'] > 0 ||

                    $schedule['interest_paid'] > 0 ||

                    $schedule['penalty_paid'] > 0

                ){

                    $schedule['status'] =
                        'PARTIAL';

                }
                else{

                    $schedule['status'] =
                        'UNPAID';

                }

            }

            $loan['schedules'] =
                $schedules;

            /*
            |--------------------------------------------------------------------------
            | PAYMENTS
            |--------------------------------------------------------------------------
            */

            $loan['payments'] = $this->db
                ->table('loan_payments')
                ->where('loan_id', $loan['loan_id'])
                ->orderBy('payment_date', 'DESC')
                ->get()
                ->getResultArray();

            /*
            |--------------------------------------------------------------------------
            | TOTAL DUE FROM SCHEDULE
            |--------------------------------------------------------------------------
            */

            $scheduleTotals = $this->db
                ->table('loan_schedule')
                ->select("
                    COALESCE(SUM(principal_due),0) as total_principal_due,
                    COALESCE(SUM(interest_due),0) as total_interest_due,
                    COALESCE(SUM(penalty_due),0) as total_penalty_due
                ")
                ->where('loan_id', $loan['loan_id'])
                ->get()
                ->getRowArray();

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL PAID FROM PAYMENTS
                    |--------------------------------------------------------------------------
                    */

                    $paymentTotals = $this->db
                        ->table('loan_payments')
                        ->select("
                            COALESCE(SUM(principal_amount),0) as total_principal_paid,
                            COALESCE(SUM(interest_amount),0) as total_interest_paid,
                            COALESCE(SUM(penalty_amount),0) as total_penalty_paid
                        ")
                        ->where('loan_id', $loan['loan_id'])
                        ->get()
                        ->getRowArray();

                    $loan['total_principal_due'] =
                        (float)($scheduleTotals['total_principal_due'] ?? 0);

                    $loan['total_interest_due'] =
                        (float)($scheduleTotals['total_interest_due'] ?? 0);

                    $loan['total_penalty_due'] =
                        (float)($scheduleTotals['total_penalty_due'] ?? 0);

                    $loan['total_principal_paid'] =
                        (float)($paymentTotals['total_principal_paid'] ?? 0);

                    $loan['total_interest_paid'] =
                        (float)($paymentTotals['total_interest_paid'] ?? 0);

                    $loan['total_penalty_paid'] =
                        (float)($paymentTotals['total_penalty_paid'] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | BALANCES
            |--------------------------------------------------------------------------
            */

            $loan['principal_balance'] =
                $loan['total_principal_due']
                - $loan['total_principal_paid'];

            $loan['interest_balance'] =
                $loan['total_interest_due']
                - $loan['total_interest_paid'];

            $loan['penalty_balance'] =
                $loan['total_penalty_due']
                - $loan['total_penalty_paid'];

            $loan['total_balance'] =
                $loan['principal_balance']
                + $loan['interest_balance']
                + $loan['penalty_balance'];

            /*
            |--------------------------------------------------------------------------
            | NEXT DUE
            |--------------------------------------------------------------------------
            */

            $nextDue = $this->db
                ->table('loan_schedule')
                ->where('loan_id', $loan['loan_id'])
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->orderBy('due_date', 'ASC')
                ->get()
                ->getRowArray();

            $loan['next_due_date'] =
                $nextDue['due_date'] ?? null;

            $loan['next_due_amount'] =
                ($nextDue['principal_due'] ?? 0)
                +
                ($nextDue['interest_due'] ?? 0)
                +
                ($nextDue['penalty_due'] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | PAYMENT PERCENTAGE
            |--------------------------------------------------------------------------
            */

            $totalDue =
                $loan['total_principal_due']
                +
                $loan['total_interest_due']
                +
                $loan['total_penalty_due'];

            $totalPaid =
                $loan['total_principal_paid']
                +
                $loan['total_interest_paid']
                +
                $loan['total_penalty_paid'];

            $loan['payment_percentage'] =
                $totalDue > 0
                ? round(($totalPaid / $totalDue) * 100, 2)
                : 0;
        }

        return $loans;
    }

    /**
     * GET SINGLE LOAN
     */
    public function getLoanDetails($loanId)
    {
        return $this->db->table('loans l')
            ->select("
                l.*,
                b.*,
                CONCAT(
                    COALESCE(b.last_name,''), ', ',
                    COALESCE(b.first_name,''), ' ',
                    COALESCE(b.middle_name,'')
                ) AS borrower_name,
                CONCAT(
                    COALESCE(lsp.last_name,''), ', ',
                    COALESCE(lsp.first_name,''), ' ',
                    COALESCE(lsp.middle_name,'')
                ) AS spouse_name,
                lp.product_name
            ")
            ->join(
                'borrowers b',
                'b.borrower_id = l.borrower_id',
                'left'
            )
            ->join(
                'loan_products lp',
                'lp.loan_product_id = l.loan_product_id',
                'left'
            )
            ->join(
                'borrower_spouses lsp',
                'lsp.borrower_id = b.borrower_id',
                'left'
            )
            ->where('l.loan_id', $loanId)
            ->get()
            ->getRowArray();
    }

    /**
     * GET COLLATERAL
     */
    public function getCollateral($loanId)
    {
        return $this->db->table('loan_collaterals')
            ->where('loan_id', $loanId)
            ->get()
            ->getRowArray();
    }

    /**
     * GET COMAKERS
     */
    public function getComakers($loanId)
    {
        return $this->db->table('loan_comakers')
            ->where('loan_id', $loanId)
            ->get()
            ->getResultArray();
    }

    /**
     * SAVE COLLATERAL
     */
    public function saveCollateral(
        $loanId,
        $primaryCardName,
        $primaryCardNumber,
        $secondaryCardName,
        $secondaryCardNumber
    ) {

        $existing = $this->db->table('loan_collaterals')
            ->where('loan_id', $loanId)
            ->get()
            ->getRowArray();

        $data = [
            'loan_id' => $loanId,
            'primary_card_name' => $primaryCardName,
            'primary_card_number' => $primaryCardNumber,
            'secondary_card_name' => $secondaryCardName,
            'secondary_card_number' => $secondaryCardNumber
        ];

        if ($existing) {

            return $this->db->table('loan_collaterals')
                ->where('loan_id', $loanId)
                ->update($data);
        }

        return $this->db->table('loan_collaterals')
            ->insert($data);
    }

    /**
     * DELETE COMAKERS
     */
    public function deleteComakers($loanId)
    {
        return $this->db->table('loan_comakers')
            ->where('loan_id', $loanId)
            ->delete();
    }

    /**
     * ADD COMAKER
     */
    public function addComaker(
        $loanId,
        $name,
        $phone,
        $address
    ) {

        return $this->db->table('loan_comakers')
            ->insert([
                'loan_id' => $loanId,
                'name' => strtoupper(trim((string)$name)),
                'phone' => $phone,
                'address' => strtoupper(trim((string)$address))
            ]);
    }

    /**
     * VOID LOAN
     */
    public function voidLoan($loanId)
    {
        return $this->where('loan_id', $loanId)
            ->set([
                'status' => 'VOID'
            ])
            ->update();
    }

    /**
     * APPROVE LOAN
     */
    public function approveLoan(
        $loanId,
        $interestRate,
        $processingFee
    ) {

        return $this->where('loan_id', $loanId)
            ->set([
                'approved_interest_rate' => $interestRate,
                'approved_processing_fee' => $processingFee,
                'status' => 'APPROVED'
            ])
            ->update();
    }

    /**
     * RELEASE LOAN
     */
    public function releaseLoan($loanId)
    {
        return $this->where('loan_id', $loanId)
            ->set([
                'status' => 'RELEASED'
            ])
            ->update();
    }

    /**
     * GET LOANS BY BORROWER
     */
    public function getBorrowerLoans($borrowerId)
    {
        return $this->where('borrower_id', $borrowerId)
            ->orderBy('loan_id', 'DESC')
            ->findAll();
    }
}