<?php

namespace App\Controllers\API;

use App\Models\LoanModel;
use App\Controllers\BaseController;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Exception;
use ReflectionException;
use App\Libraries\Pdf;

class Loan extends BaseController
{
    protected $loanModel;

    public function __construct()
    {
        $this->loanModel = new LoanModel();
    }

    public function index()
    {
        return $this->get();
    }
    

    /**
     * GET LOANS
     */
    public function get()
    {
        try {

            $search = $this->request->getGet('search'); 
            $status = $this->request->getGet('status');
            $borrower_id = $this->request->getGet('borrower_id');
           
            $where = array(
                'status' => $status,
                'borrower_id' => $borrower_id
            );

            $data = $this->loanModel->getLoans($search,$where);

            $res = [
                'isError' => false,
                'message' => 'Success',
                'data' => $data,
            ];
        
            if(!empty($borrower_id)){
                $res['salary'] = $this->getBorrowerSalary($borrower_id);
                $res['payments'] = $this->getLoanPaymentsByYear($borrower_id);
                $res['activeLoanCount'] =  $this->loanModel->getLoanCount(
                        'RELEASED',
                        $borrower_id
                    );
                 $res['pendingLoanCount'] =  $this->loanModel->getLoanCount(
                        'PENDING',
                        $borrower_id
                    );
            }

            return $this->response->setJSON($res);

           

        } catch (Exception $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function get_payment_report()
    {
        try {

            $borrower_id =
                $this->request->getGet(
                    'borrower_id'
                );

            $year =
                $this->request->getGet(
                    'year'
                );

            return $this->response->setJSON([

                'isError' => false,

                'message' => 'Success',

                'salary' =>
                    $this->getSalaryByYear(
                        $borrower_id,
                        $year
                    ),

                'settlements' =>
                    $this->getSettlementByYear(
                        $borrower_id,
                        $year
                    ),
                'bonusCollection' =>
                    $this->getPaymentReportBonusCollections(
                        $borrower_id,
                        $year
                    ),
                'payments' =>
                $this->getLoanPaymentsByYear(
                    $borrower_id,
                    $year
                ),


            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);
        }
    }

    private function getPaymentReportBonusCollections(
        int $borrowerId,
        int $year
    )
    {
        try {

            $db =
                \Config\Database::connect();

            if (
                empty($borrowerId)
            ) {
                return [];
            }

            if (
                empty($year)
            ) {
                $year = date('Y');
            }

            // rest of code...
            $result = [];

            /*
            |--------------------------------------------------------------------------
            | GET LOANS
            |--------------------------------------------------------------------------
            */

            $loans =
                $db->table('loans')
                ->where(
                    'borrower_id',
                    $borrowerId
                )
                ->where(
                    'loan_product_id',
                    1
                )
                ->get()
                ->getResultArray();

            if (empty($loans)) {

                return [
                    'debug' => 'NO LOANS FOUND',
                    'borrower_id' => $borrowerId
                ];

            }

            foreach ($loans as $loan) {

                /*
                |--------------------------------------------------------------------------
                | GET DEDUCTIONS
                |--------------------------------------------------------------------------
                */

                $deductions =
                    $db->table(
                        'loan_bonus_deductions'
                    )
                    ->where(
                        'loan_id',
                        $loan['loan_id']
                    )
                    ->get()
                    ->getResultArray();

                /*
                |--------------------------------------------------------------------------
                | DEBUG
                |--------------------------------------------------------------------------
                */

                if (empty($deductions)) {

                    $result[] = [

                        'loan_id' =>
                            $loan['loan_id'],

                        'debug' =>
                            'NO BONUS DEDUCTIONS'

                    ];

                    continue;
                }

                foreach (
                    $deductions
                    as $deduction
                ) {

                    $collection =
                        $db->table(
                            'borrower_bonus_collections'
                        )
                        ->where(
                            'borrower_id',
                            $borrowerId
                        )
                        ->where(
                            'loan_id',
                            $loan['loan_id']
                        )
                        ->where(
                            'collection_type',
                            $deduction['deduction_type']
                        )
                        ->where(
                            "YEAR(created_at) = {$year}",
                            null,
                            false
                        )
                        ->orderBy(
                            'collection_id',
                            'DESC'
                        )
                        ->get()
                        ->getRowArray();

                    $status =
                        'NO_CREDIT';

                    $paymentId =
                        null;

                    $totalPaid =
                        0;

                    if ($collection) {

                        $status =
                            'CREDITED';

                        $payment =
                            $db->table(
                                'loan_payments'
                            )
                            ->where(
                                'bonus_collection_id',
                                $collection['collection_id']
                            )
                            ->orderBy(
                                'payment_id',
                                'DESC'
                            )
                            ->get()
                            ->getRowArray();

                        if ($payment) {

                            $status =
                                'PAID';

                            $paymentId =
                                $payment['payment_id'];

                            $totalPaidRow =
                                $db->table(
                                    'loan_payments'
                                )
                                ->selectSum(
                                    'total_amount'
                                )
                                ->where(
                                    'bonus_collection_id',
                                    $collection['collection_id']
                                )
                                ->get()
                                ->getRowArray();

                            $totalPaid =
                                (float)(
                                    $totalPaidRow['total_amount']
                                    ?? 0
                                );

                        }

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CHECK BONUS SETTLEMENT
                    |--------------------------------------------------------------------------
                    */

                    $settlement =
                        $db->table(
                            'borrower_settlement_details d'
                        )
                        ->select("
                            d.settlement_id,
                            s.status AS settlement_status,
                            s.settlement_month
                        ")
                        ->join(
                            'borrower_settlements s',
                            's.settlement_id = d.settlement_id'
                        )
                        ->where(
                            'd.bonus_deduction_id',
                            $deduction['bonus_deduction_id']
                        )
                        ->like(
                            's.settlement_month',
                            $year,
                            'after'
                        )
                        ->get()
                        ->getRowArray();

                    $result[] = [

                    'loan_id' =>
                        $loan['loan_id'],

                    'bonus_deduction_id' =>
                        $deduction['bonus_deduction_id'],

                    'deduction_type' =>
                        $deduction['deduction_type'],

                    'expected_amount' =>
                        (float)(
                            $deduction['amount']
                            ?? 0
                        ),

                    'bonus_collection_id' =>
                        $collection['collection_id']
                        ?? null,

                    'credited_amount' =>
                        (float)(
                            $collection['default_amount']
                            ?? 0
                        ),

                    'payment_id' =>
                        $paymentId,

                    'total_paid' =>
                        $totalPaid,

                    'status' =>
                        $status,

                    'settlement_id' =>
                        $settlement['settlement_id']
                        ?? null,

                    'settlement_status' =>
                        $settlement['settlement_status']
                        ?? null,

                    'settlement_month' =>
                        $settlement['settlement_month']
                        ?? null

                ];

                }

            }

            return $result;

        } catch (\Exception $ex) {

            return [

                'debug' =>
                    'ERROR',

                'message' =>
                    $ex->getMessage()

            ];

        }
    }

    private function getSalaryByYear(
        int $borrowerId,
        ?string $year = null
    )
    {
        $db = \Config\Database::connect();
        $builder = $db
            ->table('borrower_salary')
            ->where(
                'borrower_id',
                $borrowerId
            );

        if (!empty($year)) {

            $builder->like(
                'salary_month',
                $year,
                'after'
            );
        }

        return $builder
            ->orderBy(
                'salary_month',
                'ASC'
            )
            ->get()
            ->getResultArray();
    }

    private function getSettlementByYear(
        int $borrowerId,
        ?string $year = null
    )
    {
        $db = \Config\Database::connect();

        $builder = $db
            ->table('borrower_settlements')
            ->where(
                'borrower_id',
                $borrowerId
            );

        if (!empty($year)) {

            $builder->like(
                'settlement_month',
                $year,
                'after'
            );

        }

        $settlements =
            $builder
            ->get()
            ->getResultArray();

        foreach(
            $settlements as &$settlement
        ){

            $settlement['details'] =
                $db->table(
                    'borrower_settlement_details bsd'
                )
                ->select('
                    bsd.*,
                    lp.product_name
                ')
                ->join(
                    'loans l',
                    'l.loan_id = bsd.loan_id'
                )
                ->join(
                    'loan_products lp',
                    'lp.loan_product_id = l.loan_product_id'
                )
                ->where(
                    'bsd.settlement_id',
                    $settlement['settlement_id']
                )
                ->get()
                ->getResultArray();

        }

        return $settlements;
    }

    private function getLoanPaymentsByYear(
        int $borrowerId,
        ?string $year = null
    )
    {
        $db =
            \Config\Database::connect();

        $builder =
            $db->table(
                'loan_payments lp'
            )
            ->select('

                lp.*,

                l.borrower_id,

                l.loan_product_id,

                p.product_name

            ')
            ->join(
                'loans l',
                'l.loan_id = lp.loan_id'
            )
            ->join(
                'loan_products p',
                'p.loan_product_id = l.loan_product_id'
            )
            ->where(
                'l.borrower_id',
                $borrowerId
            );

        if(
            !empty($year)
        ){

            $builder->like(
                'lp.payment_month',
                $year,
                'after'
            );

        }

        return $builder
            ->orderBy(
                'lp.payment_date',
                'ASC'
            )
            ->get()
            ->getResultArray();
    }


    public function getBorrowerSalary($borrowerId)
    {
         $db = \Config\Database::connect();
        return $db->table('borrower_salary')
            ->where('borrower_id', $borrowerId)
            ->where('status', 'ACTIVE')
            ->get()
            ->getResultArray();
    }

    /**
     * LOAN DETAILS
     */
    public function details($loanId)
    {
        try {

            $loan = $this->loanModel->getLoanDetails($loanId);

            if (!$loan) {

                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Loan not found.'
                ]);
            }

            $loan['collateral'] = $this->loanModel->getCollateral($loanId);
            $loan['comakers'] = $this->loanModel->getComakers($loanId);

            return $this->response->setJSON([
                'isError' => false,
                'data' => $loan
            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function generateLoanSchedule(
        $db,
        int $loanId,
        int $loanProductId,
        float $loanAmount,
        int $loanTerms,
        string $releaseDate = null
    )
    {
        $releaseDate = $releaseDate ?? date('Y-m-d');

        $db->table('loan_schedule')
            ->where('loan_id', $loanId)
            ->delete();

        switch ($loanProductId) {

            case 1:

            $totalYears = 10;

            $monthlyInterest = $loanAmount * 0.01;

            $totalInterest = $monthlyInterest * ($totalYears * 12);

            $yearlyInterest = $totalInterest / 7;

            $yearlyPrincipal = $loanAmount / $totalYears;

            /*
            |--------------------------------------------------------------------------
            | Compute Adjustment
            |--------------------------------------------------------------------------
            */

            $yearlyInterestEquivalent = $totalInterest / $totalYears;

            $interestDifference =
                $yearlyInterest - $yearlyInterestEquivalent;

            $firstSevenYearPrincipal =
                $yearlyPrincipal - $interestDifference;

            /*
            |--------------------------------------------------------------------------
            | Remaining Principal
            |--------------------------------------------------------------------------
            */

            $remainingPrincipal =
                $loanAmount -
                ($firstSevenYearPrincipal * 7);

            $lastThreeYearPrincipal =
                $remainingPrincipal / 3;

            $balance = $loanAmount;

            /*
            |--------------------------------------------------------------------------
            | YEARS 1-7
            |--------------------------------------------------------------------------
            */

            for ($year = 1; $year <= 7; $year++) {

                $principalDue = round(
                    $firstSevenYearPrincipal,
                    2
                );

                $interestDue = round(
                    $yearlyInterest,
                    2
                );

                $balance -= $principalDue;
                $additionalYear = $year - 1;
                $result = $db->table('loan_schedule')->insert([
                    'loan_id' => $loanId,
                    'due_date' => date(
                        'Y-m-d',
                        strtotime("+{$additionalYear} year", strtotime($releaseDate))
                    ),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'penalty_due' => 0,
                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'penalty_paid' => 0,
                    'balance' => max(round($balance, 2), 0),
                    'status' => 'UNPAID',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$result) {
                    throw new \Exception(
                        'Failed generating Product 1 schedule.'
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | YEARS 8-10
            |--------------------------------------------------------------------------
            */

            for ($year = 8; $year <= 10; $year++) {

                $principalDue = round(
                    $lastThreeYearPrincipal,
                    2
                );

                $interestDue = 0;

                $balance -= $principalDue;

                if ($year == 10) {
                    $balance = 0;
                }

                $result = $db->table('loan_schedule')->insert([
                    'loan_id' => $loanId,
                    'due_date' => date(
                        'Y-m-d',
                        strtotime("+{$year} year", strtotime($releaseDate))
                    ),
                    'principal_due' => $principalDue,
                    'interest_due' => $interestDue,
                    'penalty_due' => 0,
                    'principal_paid' => 0,
                    'interest_paid' => 0,
                    'penalty_paid' => 0,
                    'balance' => max(round($balance, 2), 0),
                    'status' => 'UNPAID',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                if (!$result) {
                    throw new \Exception(
                        'Failed generating Product 1 schedule.'
                    );
                }
            }

            break;

            case 2:

                $monthlyPrincipal = $loanAmount / $loanTerms;

                $balance = $loanAmount;

                for ($i = 1; $i <= $loanTerms; $i++) {

                    $balance -= $monthlyPrincipal;

                    $result = $db->table('loan_schedule')->insert([
                        'loan_id' => $loanId,
                        'due_date' => date(
                            'Y-m-d',
                            strtotime("+{$i} month", strtotime($releaseDate))
                        ),
                        'principal_due' => round($monthlyPrincipal, 2),
                        'interest_due' => 0,
                        'balance' => max(round($balance, 2), 0),
                        'status' => 'UNPAID'
                    ]);

                    if (!$result) {
                        throw new \Exception(
                            'Failed generating schedule.'
                        );
                    }
                }

                break;

            case 3:

                $monthlyInterest = $loanAmount * 0.03;

                for ($i = 1; $i <= $loanTerms; $i++) {

                    $principalDue = 0;

                    if ($i == $loanTerms) {
                        $principalDue = $loanAmount;
                    }

                    $result = $db->table('loan_schedule')->insert([
                        'loan_id' => $loanId,
                        'due_date' => date(
                            'Y-m-d',
                            strtotime("+{$i} month", strtotime($releaseDate))
                        ),
                        'principal_due' => $principalDue,
                        'interest_due' => round($monthlyInterest, 2),
                        'balance' => ($i == $loanTerms)
                            ? 0
                            : $loanAmount,
                        'status' => 'UNPAID'
                    ]);

                    if (!$result) {
                        throw new \Exception(
                            'Failed generating schedule.'
                        );
                    }
                }

                break;
        }

        return true;
    }

    public function updateLoanSchedule()
    {
        try {

            $input = $this->getRequestInput($this->request);

            $validation = \Config\Services::validation();

            $validation->setRules([

                'loan_id'                    => 'required|numeric',
                'loan_product_id'            => 'required|numeric',
                'loan_amount'                => 'required|decimal',
                'loan_terms'                 => 'required|numeric',
                'approved_interest_rate'     => 'required|decimal',
                'approved_processing_fee'    => 'permit_empty|decimal',
                'release_date'               => 'required',
                'void_reason'                => 'required',
                'delete_by'                  => 'required|numeric',
            ]);

            if (!$validation->run($input)) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => $validation->getErrors()
                ]);

            }

            $db = \Config\Database::connect();

            $db->transBegin();

            $loanId = (int)$input['loan_id'];

            /*
            |--------------------------------------------------------------------------
            | GET LOAN
            |--------------------------------------------------------------------------
            */

            $loan = $db->table('loans')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            if (!$loan) {

                throw new \Exception(
                    'Loan not found.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | CHECK IF LOAN HAS PAYMENTS
            |--------------------------------------------------------------------------
            */

            $payment = $db->table('loan_payments')
                ->select('
                    COALESCE(SUM(principal_amount),0) AS principal_paid,
                    COALESCE(SUM(interest_amount),0) AS interest_paid,
                    COALESCE(SUM(penalty_amount),0) AS penalty_paid,
                    COALESCE(SUM(total_amount),0) AS total_paid
                ')
                ->where('loan_id', $loanId)
                ->where('status', 'ACTIVE') // or your completed status
                ->get()
                ->getRow();

            if ($payment->total_paid > 0) {
                throw new \Exception(
                    'Loan cannot be updated because payments already exist.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE LOAN
            |--------------------------------------------------------------------------
            */

            $updateData = [

                'loan_product_id' =>
                    $input['loan_product_id'],

                'loan_amount' =>
                    $input['loan_amount'],

                'loan_terms' =>
                    $input['loan_terms'],

                'approved_interest_rate' =>
                    $input['approved_interest_rate'],

                'approved_processing_fee' =>
                    $input['approved_processing_fee'],

                'release_date' =>
                    $input['release_date']

            ];

            $db->table('loans')
                ->where('loan_id', $loanId)
                ->update($updateData);

                
            /*
            |--------------------------------------------------------------------------
            | VOID EXISTING SCHEDULE
            |--------------------------------------------------------------------------
            */

            $db->table('loan_schedule')
                ->where('loan_id', $loanId)
                ->update([

                    'is_void' => 1,

                    'status' => 'VOID',

                    'void_reason' => $input['void_reason'],

                    'void_by' => $input['delete_by'],

                    'void_at' => date('Y-m-d H:i:s')

                ]);

            /*
            |--------------------------------------------------------------------------
            | REGENERATE SCHEDULE
            |--------------------------------------------------------------------------
            */

            $this->generateLoanSchedule(

                $db,

                $loanId,

                (int)$input['loan_product_id'],

                (float)$input['loan_amount'],

                (int)$input['loan_terms'],

                $input['release_date']

            );

            /*
            |--------------------------------------------------------------------------
            | GET UPDATED LOAN
            |--------------------------------------------------------------------------
            */

            $newLoan = $db->table('loans')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(

                'LOAN',

                $loanId,

                'UPDATE',

                $loan,

                $newLoan,

                'Loan updated and schedule regenerated.'

            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                throw new \Exception(
                    'Failed updating loan.'
                );

            }

            $db->transCommit();

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully updated.'
            ]);

        } catch (\Exception $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);

        }
    }

    public function getLoanSummary($loanId)
    {
        try {

            $db = \Config\Database::connect();

            /*
            |--------------------------------------------------------------------------
            | SCHEDULE TOTALS
            |--------------------------------------------------------------------------
            */

            $schedule = $db->table('loan_schedule')
                ->select('
                    SUM(principal_due) as total_principal_due,
                    SUM(interest_due) as total_interest_due,
                    SUM(principal_paid) as total_principal_paid,
                    SUM(interest_paid) as total_interest_paid
                ')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            /*
            |--------------------------------------------------------------------------
            | PAYMENT SOURCE BREAKDOWN
            |--------------------------------------------------------------------------
            */

            $sources = $db->table('loan_payments')
                ->select('
                    payment_source,
                    SUM(total_amount) as total_amount
                ')
                ->where('loan_id', $loanId)
                ->groupBy('payment_source')
                ->get()
                ->getResultArray();

            /*
            |--------------------------------------------------------------------------
            | LOAN INFO
            |--------------------------------------------------------------------------
            */

            $loan = $db->table('loans')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            $summary = [

                'loan_amount' =>
                    (float)$loan['loan_amount'],

                'principal_due' =>
                    (float)$schedule['total_principal_due'],

                'principal_paid' =>
                    (float)$schedule['total_principal_paid'],

                'principal_remaining' =>
                    max(
                        0,
                        (float)$schedule['total_principal_due']
                        -
                        (float)$schedule['total_principal_paid']
                    ),

                'interest_due' =>
                    (float)$schedule['total_interest_due'],

                'interest_paid' =>
                    (float)$schedule['total_interest_paid'],

                'interest_remaining' =>
                    max(
                        0,
                        (float)$schedule['total_interest_due']
                        -
                        (float)$schedule['total_interest_paid']
                    ),

                'status' =>
                    $loan['status'],

                'payment_sources' =>
                    $sources

            ];

            return $this->response->setJSON([

                'isError' => false,

                'data' => $summary

            ]);

        } catch (\Exception $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ]);

        }
    }

    public function payment()
    {
        try {

            $input = $this->getRequestInput($this->request);

            $validation = \Config\Services::validation();

            $validation->setRules([
                'loan_id'      => 'required|numeric',
                'amount'       => 'required|decimal',
                'payment_date' => 'required'
            ]);

            if (!$validation->run($input)) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => $validation->getErrors()
                ]);
            }

            $db = \Config\Database::connect();

            $db->transBegin();

            $loanId = (int)$input['loan_id'];
            $amount = (float)$input['amount'];

            $remainingPayment = $amount;

            $schedules = $db->table('loan_schedule')
                ->where('loan_id', $loanId)
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->orderBy('due_date', 'ASC')
                ->get()
                ->getResultArray();

            if (empty($schedules)) {
                throw new Exception('No unpaid schedules found.');
            }

            foreach ($schedules as $schedule) {

                if ($remainingPayment <= 0) {
                    break;
                }

                $scheduleId = $schedule['schedule_id'];

                $penaltyRemaining =
                    max(
                        0,
                        $schedule['penalty_due']
                        - $schedule['penalty_paid']
                    );

                $interestRemaining =
                    max(
                        0,
                        $schedule['interest_due']
                        - $schedule['interest_paid']
                    );

                $principalRemaining =
                    max(
                        0,
                        $schedule['principal_due']
                        - $schedule['principal_paid']
                    );

                $penaltyPaid = 0;
                $interestPaid = 0;
                $principalPaid = 0;

                /*
                |--------------------------------------------------------------------------
                | PENALTY FIRST
                |--------------------------------------------------------------------------
                */

                if (
                    $remainingPayment > 0 &&
                    $penaltyRemaining > 0
                ) {

                    $penaltyPaid = min(
                        $remainingPayment,
                        $penaltyRemaining
                    );

                    $remainingPayment -= $penaltyPaid;
                }

                /*
                |--------------------------------------------------------------------------
                | INTEREST SECOND
                |--------------------------------------------------------------------------
                */

                if (
                    $remainingPayment > 0 &&
                    $interestRemaining > 0
                ) {

                    $interestPaid = min(
                        $remainingPayment,
                        $interestRemaining
                    );

                    $remainingPayment -= $interestPaid;
                }

                /*
                |--------------------------------------------------------------------------
                | PRINCIPAL LAST
                |--------------------------------------------------------------------------
                */

                if (
                    $remainingPayment > 0 &&
                    $principalRemaining > 0
                ) {

                    $principalPaid = min(
                        $remainingPayment,
                        $principalRemaining
                    );

                    $remainingPayment -= $principalPaid;
                }

                $updatedPrincipalPaid =
                    $schedule['principal_paid']
                    + $principalPaid;

                $updatedInterestPaid =
                    $schedule['interest_paid']
                    + $interestPaid;

                $updatedPenaltyPaid =
                    $schedule['penalty_paid']
                    + $penaltyPaid;

                /*
                |--------------------------------------------------------------------------
                | REMAINING BALANCES
                |--------------------------------------------------------------------------
                */

                $principalRemaining =
                    max(
                        0,
                        $schedule['principal_due']
                        - $updatedPrincipalPaid
                    );

                $interestRemaining =
                    max(
                        0,
                        $schedule['interest_due']
                        - $updatedInterestPaid
                    );

                $penaltyRemaining =
                    max(
                        0,
                        $schedule['penalty_due']
                        - $updatedPenaltyPaid
                    );

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                if (
                    $principalRemaining <= 0 &&
                    $interestRemaining <= 0 &&
                    $penaltyRemaining <= 0
                ) {

                    $status = 'PAID';

                } elseif (
                    $updatedPrincipalPaid > 0 ||
                    $updatedInterestPaid > 0 ||
                    $updatedPenaltyPaid > 0
                ) {

                    $status = 'PARTIAL';

                } else {

                    $status = 'UNPAID';
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE SCHEDULE
                |--------------------------------------------------------------------------
                */

                $db->table('loan_schedule')
                    ->where('schedule_id', $scheduleId)
                    ->update([
                        'principal_paid' => $updatedPrincipalPaid,
                        'interest_paid'  => $updatedInterestPaid,
                        'penalty_paid'   => $updatedPenaltyPaid,
                        'status'         => $status
                    ]);

                /*
                |--------------------------------------------------------------------------
                | INSERT PAYMENT RECORD
                |--------------------------------------------------------------------------
                */

                $db->table('loan_payments')
                    ->insert([
                        'loan_id'          => $loanId,
                        'schedule_id'      => $scheduleId,
                        'payment_date'     => $input['payment_date'],
                        'principal_amount' => $principalPaid,
                        'interest_amount'  => $interestPaid,
                        'penalty_amount'   => $penaltyPaid,
                        'total_amount'     =>
                            $principalPaid +
                            $interestPaid +
                            $penaltyPaid,
                        'or_number'        =>
                            $input['or_number'] ?? null,
                        'remarks'          =>
                            $input['remarks'] ?? null,
                        'created_at'       =>
                            date('Y-m-d H:i:s')
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | CHECK IF LOAN IS FULLY PAID
            |--------------------------------------------------------------------------
            */

            $remainingSchedules = $db->table('loan_schedule')
                ->where('loan_id', $loanId)
                ->whereIn('status', ['UNPAID', 'PARTIAL'])
                ->countAllResults();

            if ($remainingSchedules == 0) {

                $db->table('loans')
                    ->where('loan_id', $loanId)
                    ->update([
                        'status' => 'PAID'
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(
                'LOAN_PAYMENT',
                $loanId,
                'PAYMENT',
                null,
                [
                    'payment_amount' => $amount,
                    'payment_date' => $input['payment_date']
                ],
                'Loan payment posted.'
            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                throw new Exception(
                    'Payment transaction failed.'
                );
            }

            $db->transCommit();

            return $this->getResponse([
                'isError' => false,
                'message' => 'Payment posted successfully.',
                'change' => round($remainingPayment, 2)
            ]);

        } catch (Exception $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function voidPayment()
    {
        try {

            $input = $this->getRequestInput($this->request);

            $validation = \Config\Services::validation();

            $validation->setRules([
                'payment_id' => 'required|numeric'
            ]);

            if (!$validation->run($input)) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => $validation->getErrors()
                ]);
            }

            $db = \Config\Database::connect();

            $db->transBegin();

            $paymentId = (int)$input['payment_id'];

            $payment = $db->table('loan_payments')
                ->where('payment_id', $paymentId)
                ->where('status', 'ACTIVE')
                ->get()
                ->getRowArray();

            if (!$payment) {
                throw new \Exception(
                    'Payment not found or already voided.'
                );
            }

            $schedule = $db->table('loan_schedule')
                ->where(
                    'schedule_id',
                    $payment['schedule_id']
                )
                ->get()
                ->getRowArray();

            if (!$schedule) {
                throw new \Exception(
                    'Schedule not found.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | REVERSE PAYMENT
            |--------------------------------------------------------------------------
            */

            $newPrincipalPaid =
                max(
                    0,
                    $schedule['principal_paid']
                    - $payment['principal_amount']
                );

            $newInterestPaid =
                max(
                    0,
                    $schedule['interest_paid']
                    - $payment['interest_amount']
                );

            $newPenaltyPaid =
                max(
                    0,
                    $schedule['penalty_paid']
                    - $payment['penalty_amount']
                );

            /*
            |--------------------------------------------------------------------------
            | RECALCULATE STATUS
            |--------------------------------------------------------------------------
            */

            $status = 'UNPAID';

            if (
                $newPrincipalPaid > 0 ||
                $newInterestPaid > 0 ||
                $newPenaltyPaid > 0
            ) {
                $status = 'PARTIAL';
            }

            if (
                $newPrincipalPaid >= $schedule['principal_due']
                &&
                $newInterestPaid >= $schedule['interest_due']
                &&
                $newPenaltyPaid >= $schedule['penalty_due']
            ) {
                $status = 'PAID';
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE SCHEDULE
            |--------------------------------------------------------------------------
            */

            $db->table('loan_schedule')
                ->where(
                    'schedule_id',
                    $schedule['schedule_id']
                )
                ->update([
                    'principal_paid' => $newPrincipalPaid,
                    'interest_paid'  => $newInterestPaid,
                    'penalty_paid'   => $newPenaltyPaid,
                    'status'         => $status
                ]);

            /*
            |--------------------------------------------------------------------------
            | VOID PAYMENT
            |--------------------------------------------------------------------------
            */

            $db->table('loan_payments')
                ->where('payment_id', $paymentId)
                ->update([
                    'status' => 'VOID'
                ]);

            /*
            |--------------------------------------------------------------------------
            | REOPEN LOAN IF NEEDED
            |--------------------------------------------------------------------------
            */

            $remainingSchedules = $db->table('loan_schedule')
                ->where('loan_id', $payment['loan_id'])
                ->whereIn(
                    'status',
                    ['UNPAID', 'PARTIAL']
                )
                ->countAllResults();

            if ($remainingSchedules > 0) {

                $db->table('loans')
                    ->where(
                        'loan_id',
                        $payment['loan_id']
                    )
                    ->update([
                        'status' => 'ACTIVE'
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | AUDIT LOG
            |--------------------------------------------------------------------------
            */

            $this->createAuditLog(
                'LOAN_PAYMENT',
                $payment['loan_id'],
                'VOID',
                $payment,
                null,
                'Payment voided.'
            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                throw new \Exception(
                    'Void payment failed.'
                );
            }

            $db->transCommit();

            return $this->getResponse([
                'isError' => false,
                'message' => 'Payment successfully voided.'
            ]);

        } catch (\Exception $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * ADD LOAN
     */
    public function add(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();

        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'borrower_id' => 'required|numeric',
                'loan_product_id' => 'required|numeric',
                'loan_amount' => 'required',
                'loan_terms' => 'required',
                'interest_amount' => 'required|numeric',
                'processingfee_amount' => 'required|numeric',
                'net_proceeds' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors' => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $db->transBegin();

            $loanData = [
                'borrower_id' => $input['borrower_id'],
                'loan_product_id' => $input['loan_product_id'],
                'loan_amount' => $input['loan_amount'],
                'loan_purpose' => strtoupper(trim((string)($input['loan_purpose'] ?? ''))),
                'loan_terms' => $input['loan_terms'],
                'approved_interest_rate' => $input['approved_interest_rate'] ?? 0,
                'approved_processing_fee' => $input['approved_processing_fee'] ?? 0,
                'interest_amount' => $input['interest_amount'] ?? 0,
                'processingfee_amount' => $input['processingfee_amount'] ?? 0,
                'net_proceeds' => $input['net_proceeds'] ?? 0,
                'status' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'monthly_interest_deduction' =>
                    $input['monthly_interest_deduction']
                    ?? 4000,
            ];

            $db->table('loans')->insert($loanData);

            $loanId = $db->insertID();

            /*
            |--------------------------------------------------------------------------
            | COLLATERAL
            |--------------------------------------------------------------------------
            */

            $collateralData = array_filter([
                'loan_id' => $loanId,
                'primary_card_name' => $input['primary_card_name'] ?? null,
                'primary_card_number' => $input['primary_card_number'] ?? null,
                'secondary_card_name' => $input['secondary_card_name'] ?? null,
                'secondary_card_number' => $input['secondary_card_number'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (count($collateralData) > 1) {

                $db->table('loan_collaterals')
                    ->insert($collateralData);
            }

            /*
            |--------------------------------------------------------------------------
            | COMAKERS
            |--------------------------------------------------------------------------
            */

            if (!empty($input['comakers']) && is_array($input['comakers'])) {

                foreach ($input['comakers'] as $comaker) {

                    $db->table('loan_comakers')->insert([
                        'loan_id' => $loanId,
                        'name' => strtoupper(trim((string)($comaker['name'] ?? ''))),
                        'phone' => $comaker['phone'] ?? '',
                        'address' => strtoupper(trim((string)($comaker['address'] ?? '')))
                    ]);
                }
            }

            
            /*
            |--------------------------------------------------------------------------
            | INCENTIVES DEDUCTION
            |--------------------------------------------------------------------------
            */

            if(
                !empty(
                    $input['bonus_deductions']
                )
            ){

                foreach(
                    $input['bonus_deductions']
                    as $bonus
                ){

                    $db->table(
                        'loan_bonus_deductions'
                    )->insert([

                        'loan_id' =>
                            $loanId,

                        'deduction_type' =>
                            $bonus['deduction_type'],

                        'amount' =>
                            $bonus['amount'],

                        'is_paid' =>
                            0,

                        'created_at' =>
                            date('Y-m-d H:i:s')

                    ]);

                }

            }

            $this->generateLoanSchedule(
                $db,
                $loanId,
                (int)$input['loan_product_id'],
                (float)$input['loan_amount'],
                (int)$input['loan_terms']
            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Transaction failed.'
                ]);
            }

            $db->transCommit();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'CREATE',
                null,
                $loanData,
                'Loan created'
            );

            return $this->getResponse([
                'isError' => false,
                'loan_id' => $loanId,
                'message' => 'Loan successfully created.'
            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
        }
    }
    public function addLoanYearlySettlement(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();

        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'borrower_id' => 'required|numeric',
                'loan_product_id' => 'required|numeric',
                'loan_amount' => 'required',
                'loan_terms' => 'required',
                'interest_amount' => 'required|numeric',
                'processingfee_amount' => 'required|numeric',
                'net_proceeds' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors' => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $db->transBegin();

            $loanData = [
                'borrower_id' => $input['borrower_id'],
                'loan_product_id' => $input['loan_product_id'],
                'loan_amount' => $input['loan_amount'],
                'loan_purpose' => strtoupper(trim((string)($input['loan_purpose'] ?? ''))),
                'loan_terms' => $input['loan_terms'],
                'approved_interest_rate' => $input['approved_interest_rate'] ?? 0,
                'approved_processing_fee' => $input['approved_processing_fee'] ?? 0,
                'interest_amount' => $input['interest_amount'] ?? 0,
                'processingfee_amount' => $input['processingfee_amount'] ?? 0,
                'net_proceeds' => $input['net_proceeds'] ?? 0,
                'status' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s'),
                'monthly_interest_deduction' =>
                    $input['monthly_interest_deduction']
                    ?? 4000,
            ];

            $db->table('loans')->insert($loanData);

            $loanId = $db->insertID();

            if(
                !empty(
                    $input['settlement_ids']
                )
            ){

                $settlements =
                    $db->table(
                        'borrower_settlements'
                    )
                    ->whereIn(
                        'settlement_id',
                        $input['settlement_ids']
                    )
                    ->get()
                    ->getResultArray();

                foreach(
                    $settlements
                    as $settlement
                ){

                    $remainingPayment =
                        (float)$settlement['deficit_amount'];

                    $details =
                        $db->table(
                            'borrower_settlement_details'
                        )
                        ->where(
                            'settlement_id',
                            $settlement['settlement_id']
                        )
                        ->get()
                        ->getResultArray();

                    foreach(
                        $details
                        as $detail
                    ){

                        if(
                            $remainingPayment <= 0
                        ){
                            break;
                        }

                        $loanToSettle =
                            $detail['loan_id'];

                        $schedules =
                            $db->table(
                                'loan_schedule'
                            )
                            ->where(
                                'loan_id',
                                $loanToSettle
                            )
                            ->whereIn(
                                'status',
                                [
                                    'UNPAID',
                                    'PARTIAL'
                                ]
                            )
                            ->orderBy(
                                'due_date',
                                'ASC'
                            )
                            ->get()
                            ->getResultArray();

                        foreach(
                            $schedules
                            as $schedule
                        ){

                            if(
                                $remainingPayment <= 0
                            ){
                                break;
                            }

                            $scheduleId =
                                $schedule['schedule_id'];

                            /*
                            |--------------------------------------------------------------------------
                            | GET PAID TOTALS
                            |--------------------------------------------------------------------------
                            */

                            $totals =
                                $db->table(
                                    'loan_payments'
                                )
                                ->select("
                                    COALESCE(SUM(principal_amount),0) principal_paid,
                                    COALESCE(SUM(interest_amount),0) interest_paid,
                                    COALESCE(SUM(penalty_amount),0) penalty_paid
                                ")
                                ->where(
                                    'schedule_id',
                                    $scheduleId
                                )
                                ->get()
                                ->getRowArray();

                            $penaltyRemaining =
                                max(
                                    0,
                                    $schedule['penalty_due']
                                    -
                                    $totals['penalty_paid']
                                );

                            $interestRemaining =
                                max(
                                    0,
                                    $schedule['interest_due']
                                    -
                                    $totals['interest_paid']
                                );

                            $principalRemaining =
                                max(
                                    0,
                                    $schedule['principal_due']
                                    -
                                    $totals['principal_paid']
                                );

                            $penaltyPaid = 0;
                            $interestPaid = 0;
                            $principalPaid = 0;

                            /*
                            |--------------------------------------------------------------------------
                            | PENALTY
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $penaltyRemaining > 0
                            ){

                                $penaltyPaid =
                                    min(
                                        $remainingPayment,
                                        $penaltyRemaining
                                    );

                                $remainingPayment -=
                                    $penaltyPaid;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | INTEREST
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $interestRemaining > 0
                            ){

                                $interestPaid =
                                    min(
                                        $remainingPayment,
                                        $interestRemaining
                                    );

                                $remainingPayment -=
                                    $interestPaid;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | PRINCIPAL
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $principalRemaining > 0
                            ){

                                $principalPaid =
                                    min(
                                        $remainingPayment,
                                        $principalRemaining
                                    );

                                $remainingPayment -=
                                    $principalPaid;
                            }

                            $db->table(
                                'loan_payments'
                            )
                            ->insert([

                                'loan_id' =>
                                    $loanToSettle,

                                'schedule_id' =>
                                    $scheduleId,

                                'settlement_id' =>
                                    $settlement['settlement_id'],

                                'payment_date' =>
                                    date('Y-m-d'),

                                'payment_source' =>
                                    'SETTLEMENT_LOAN',

                                'principal_amount' =>
                                    $principalPaid,

                                'interest_amount' =>
                                    $interestPaid,

                                'penalty_amount' =>
                                    $penaltyPaid,

                                'total_amount' =>
                                    (
                                        $principalPaid +
                                        $interestPaid +
                                        $penaltyPaid
                                    ),

                                'remarks' =>
                                    'SETTLEMENT LOAN PAYMENT',

                                'created_at' =>
                                    date(
                                        'Y-m-d H:i:s'
                                    )

                            ]);

                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE SCHEDULE STATUS
                            |--------------------------------------------------------------------------
                            */

                            $updatedTotals =
                                $db->table(
                                    'loan_payments'
                                )
                                ->select("
                                    COALESCE(SUM(principal_amount),0) principal_paid,
                                    COALESCE(SUM(interest_amount),0) interest_paid,
                                    COALESCE(SUM(penalty_amount),0) penalty_paid
                                ")
                                ->where(
                                    'schedule_id',
                                    $scheduleId
                                )
                                ->get()
                                ->getRowArray();

                            $remainingPrincipal =
                                max(
                                    0,
                                    $schedule['principal_due']
                                    -
                                    $updatedTotals['principal_paid']
                                );

                            $remainingInterest =
                                max(
                                    0,
                                    $schedule['interest_due']
                                    -
                                    $updatedTotals['interest_paid']
                                );

                            $remainingPenalty =
                                max(
                                    0,
                                    $schedule['penalty_due']
                                    -
                                    $updatedTotals['penalty_paid']
                                );

                            if(
                                $remainingPrincipal <= 0 &&
                                $remainingInterest <= 0 &&
                                $remainingPenalty <= 0
                            ){

                                $scheduleStatus = 'PAID';

                            }
                            elseif(

                                $updatedTotals['principal_paid'] > 0 ||
                                $updatedTotals['interest_paid'] > 0 ||
                                $updatedTotals['penalty_paid'] > 0

                            ){

                                $scheduleStatus = 'PARTIAL';

                            }
                            else{

                                $scheduleStatus = 'UNPAID';

                            }

                            $db->table(
                                'loan_schedule'
                            )
                            ->where(
                                'schedule_id',
                                $scheduleId
                            )
                            ->update([
                                'status' =>
                                    $scheduleStatus
                            ]);

                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE LOAN STATUS
                            |--------------------------------------------------------------------------
                            */

                            $remainingSchedules =
                                $db->table(
                                    'loan_schedule'
                                )
                                ->where(
                                    'loan_id',
                                    $loanToSettle
                                )
                                ->whereIn(
                                    'status',
                                    [
                                        'UNPAID',
                                        'PARTIAL'
                                    ]
                                )
                                ->countAllResults();

                            if(
                                $remainingSchedules == 0
                            ){

                                $db->table(
                                    'loans'
                                )
                                ->where(
                                    'loan_id',
                                    $loanToSettle
                                )
                                ->update([
                                    'status' => 'PAID'
                                ]);

                            }

                        }

                    }

                    $totalPaidRow =
                        $db->table(
                            'loan_payments'
                        )
                        ->selectSum(
                            'total_amount'
                        )
                        ->where(
                            'settlement_id',
                            $settlement['settlement_id']
                        )
                        ->get()
                        ->getRowArray();

                    $totalPaid =
                        (float)(
                            $totalPaidRow['total_amount']
                            ?? 0
                        );

                    $settlementStatus =
                        $totalPaid >=
                        (float)$settlement['deficit_amount']
                        ? 'SETTLED'
                        : 'PARTIAL';

                    $db->table(
                        'borrower_settlements'
                    )
                    ->where(
                        'settlement_id',
                        $settlement['settlement_id']
                    )
                    ->update([

                        'status' =>
                            $settlementStatus,

                        'settled_at' =>
                            date(
                                'Y-m-d H:i:s'
                            ),

                        'settlement_loan_id' =>
                            $loanId

                    ]);

                }

            }

            /*
            |--------------------------------------------------------------------------
            | COLLATERAL
            |--------------------------------------------------------------------------
            */

            $collateralData = array_filter([
                'loan_id' => $loanId,
                'primary_card_name' => $input['primary_card_name'] ?? null,
                'primary_card_number' => $input['primary_card_number'] ?? null,
                'secondary_card_name' => $input['secondary_card_name'] ?? null,
                'secondary_card_number' => $input['secondary_card_number'] ?? null
            ], fn($value) => $value !== null && $value !== '');

            if (count($collateralData) > 1) {

                $db->table('loan_collaterals')
                    ->insert($collateralData);
            }

            /*
            |--------------------------------------------------------------------------
            | COMAKERS
            |--------------------------------------------------------------------------
            */

            if (!empty($input['comakers']) && is_array($input['comakers'])) {

                foreach ($input['comakers'] as $comaker) {

                    $db->table('loan_comakers')->insert([
                        'loan_id' => $loanId,
                        'name' => strtoupper(trim((string)($comaker['name'] ?? ''))),
                        'phone' => $comaker['phone'] ?? '',
                        'address' => strtoupper(trim((string)($comaker['address'] ?? '')))
                    ]);
                }
            }

            
            /*
            |--------------------------------------------------------------------------
            | INCENTIVES DEDUCTION
            |--------------------------------------------------------------------------
            */

            if(
                !empty(
                    $input['bonus_deductions']
                )
            ){

                foreach(
                    $input['bonus_deductions']
                    as $bonus
                ){

                    $db->table(
                        'loan_bonus_deductions'
                    )->insert([

                        'loan_id' =>
                            $loanId,

                        'deduction_type' =>
                            $bonus['deduction_type'],

                        'amount' =>
                            $bonus['amount'],

                        'is_paid' =>
                            0,

                        'created_at' =>
                            date('Y-m-d H:i:s')

                    ]);

                }

            }

            $this->generateLoanSchedule(
                $db,
                $loanId,
                (int)$input['loan_product_id'],
                (float)$input['loan_amount'],
                (int)$input['loan_terms']
            );

            if ($db->transStatus() === false) {

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Transaction failed.'
                ]);
            }

            $db->transCommit();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'CREATE',
                null,
                $loanData,
                'Loan created'
            );

            return $this->getResponse([
                'isError' => false,
                'loan_id' => $loanId,
                'message' => 'Loan successfully created.'
            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $ex->getMessage(),
                ],
                $responseCode
            );
        }
    }
    // public function addLoanYearlySettlement(int $responseCode = ResponseInterface::HTTP_OK)
    // {
    //     $db = \Config\Database::connect();

    //     try {

    //         $input = $this->getRequestInput($this->request);

    //         $rules = [
    //             'borrower_id' => 'required|numeric',
    //             'loan_product_id' => 'required|numeric',
    //             'loan_amount' => 'required',
    //             'loan_terms' => 'required',
    //             'interest_amount' => 'required|numeric',
    //             'processingfee_amount' => 'required|numeric',
    //             'net_proceeds' => 'required|numeric'
    //         ];

    //         if (!$this->validateRequest($input, $rules)) {

    //             return $this->getResponse(
    //                 [
    //                     'isError' => true,
    //                     'message' => current($this->validator->getErrors()),
    //                     'errors' => $this->validator->getErrors()
    //                 ],
    //                 ResponseInterface::HTTP_BAD_REQUEST
    //             );
    //         }

    //         $db->transBegin();

    //         $loanData = [
    //             'borrower_id' => $input['borrower_id'],
    //             'loan_product_id' => $input['loan_product_id'],
    //             'loan_amount' => $input['loan_amount'],
    //             'loan_purpose' => strtoupper(trim((string)($input['loan_purpose'] ?? ''))),
    //             'loan_terms' => $input['loan_terms'],
    //             'approved_interest_rate' => $input['approved_interest_rate'] ?? 0,
    //             'approved_processing_fee' => $input['approved_processing_fee'] ?? 0,
    //             'interest_amount' => $input['interest_amount'] ?? 0,
    //             'processingfee_amount' => $input['processingfee_amount'] ?? 0,
    //             'net_proceeds' => $input['net_proceeds'] ?? 0,
    //             'status' => 'PENDING',
    //             'created_at' => date('Y-m-d H:i:s'),
    //             'monthly_interest_deduction' =>
    //                 $input['monthly_interest_deduction']
    //                 ?? 4000,
    //         ];

    //         $db->table('loans')->insert($loanData);

    //         $loanId = $db->insertID();

    //         if(
    //             !empty(
    //                 $input['settlement_ids']
    //             )
    //         ){

    //             $settlements =
    //                 $db->table(
    //                     'borrower_settlements'
    //                 )
    //                 ->whereIn(
    //                     'settlement_id',
    //                     $input['settlement_ids']
    //                 )
    //                 ->get()
    //                 ->getResultArray();

    //             foreach(
    //                 $settlements
    //                 as $settlement
    //             ){

    //                 $remainingPayment =
    //                     (float)$settlement['deficit_amount'];

    //                 $details =
    //                     $db->table(
    //                         'borrower_settlement_details'
    //                     )
    //                     ->where(
    //                         'settlement_id',
    //                         $settlement['settlement_id']
    //                     )
    //                     ->get()
    //                     ->getResultArray();

    //                 foreach(
    //                     $details
    //                     as $detail
    //                 ){

    //                     if(
    //                         $remainingPayment <= 0
    //                     ){
    //                         break;
    //                     }

    //                     $loanToSettle =
    //                         $detail['loan_id'];

    //                     $schedules =
    //                         $db->table(
    //                             'loan_schedule'
    //                         )
    //                         ->where(
    //                             'loan_id',
    //                             $loanToSettle
    //                         )
    //                         ->whereIn(
    //                             'status',
    //                             [
    //                                 'UNPAID',
    //                                 'PARTIAL'
    //                             ]
    //                         )
    //                         ->orderBy(
    //                             'due_date',
    //                             'ASC'
    //                         )
    //                         ->get()
    //                         ->getResultArray();

    //                     foreach(
    //                         $schedules
    //                         as $schedule
    //                     ){

    //                         if(
    //                             $remainingPayment <= 0
    //                         ){
    //                             break;
    //                         }

    //                         $scheduleId =
    //                             $schedule['schedule_id'];

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | GET PAID TOTALS
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         $totals =
    //                             $db->table(
    //                                 'loan_payments'
    //                             )
    //                             ->select("
    //                                 COALESCE(SUM(principal_amount),0) principal_paid,
    //                                 COALESCE(SUM(interest_amount),0) interest_paid,
    //                                 COALESCE(SUM(penalty_amount),0) penalty_paid
    //                             ")
    //                             ->where(
    //                                 'schedule_id',
    //                                 $scheduleId
    //                             )
    //                             ->get()
    //                             ->getRowArray();

    //                         $penaltyRemaining =
    //                             max(
    //                                 0,
    //                                 $schedule['penalty_due']
    //                                 -
    //                                 $totals['penalty_paid']
    //                             );

    //                         $interestRemaining =
    //                             max(
    //                                 0,
    //                                 $schedule['interest_due']
    //                                 -
    //                                 $totals['interest_paid']
    //                             );

    //                         $principalRemaining =
    //                             max(
    //                                 0,
    //                                 $schedule['principal_due']
    //                                 -
    //                                 $totals['principal_paid']
    //                             );

    //                         $penaltyPaid = 0;
    //                         $interestPaid = 0;
    //                         $principalPaid = 0;

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | PENALTY
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         if(
    //                             $remainingPayment > 0 &&
    //                             $penaltyRemaining > 0
    //                         ){

    //                             $penaltyPaid =
    //                                 min(
    //                                     $remainingPayment,
    //                                     $penaltyRemaining
    //                                 );

    //                             $remainingPayment -=
    //                                 $penaltyPaid;
    //                         }

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | INTEREST
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         if(
    //                             $remainingPayment > 0 &&
    //                             $interestRemaining > 0
    //                         ){

    //                             $interestPaid =
    //                                 min(
    //                                     $remainingPayment,
    //                                     $interestRemaining
    //                                 );

    //                             $remainingPayment -=
    //                                 $interestPaid;
    //                         }

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | PRINCIPAL
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         if(
    //                             $remainingPayment > 0 &&
    //                             $principalRemaining > 0
    //                         ){

    //                             $principalPaid =
    //                                 min(
    //                                     $remainingPayment,
    //                                     $principalRemaining
    //                                 );

    //                             $remainingPayment -=
    //                                 $principalPaid;
    //                         }

    //                         $db->table(
    //                             'loan_payments'
    //                         )
    //                         ->insert([

    //                             'loan_id' =>
    //                                 $loanToSettle,

    //                             'schedule_id' =>
    //                                 $scheduleId,

    //                             'settlement_id' =>
    //                                 $settlement['settlement_id'],

    //                             'payment_date' =>
    //                                 date('Y-m-d'),

    //                             'payment_source' =>
    //                                 'SETTLEMENT_LOAN',

    //                             'principal_amount' =>
    //                                 $principalPaid,

    //                             'interest_amount' =>
    //                                 $interestPaid,

    //                             'penalty_amount' =>
    //                                 $penaltyPaid,

    //                             'total_amount' =>
    //                                 (
    //                                     $principalPaid +
    //                                     $interestPaid +
    //                                     $penaltyPaid
    //                                 ),

    //                             'remarks' =>
    //                                 'SETTLEMENT LOAN PAYMENT',

    //                             'created_at' =>
    //                                 date(
    //                                     'Y-m-d H:i:s'
    //                                 )

    //                         ]);

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | UPDATE SCHEDULE STATUS
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         $updatedTotals =
    //                             $db->table(
    //                                 'loan_payments'
    //                             )
    //                             ->select("
    //                                 COALESCE(SUM(principal_amount),0) principal_paid,
    //                                 COALESCE(SUM(interest_amount),0) interest_paid,
    //                                 COALESCE(SUM(penalty_amount),0) penalty_paid
    //                             ")
    //                             ->where(
    //                                 'schedule_id',
    //                                 $scheduleId
    //                             )
    //                             ->get()
    //                             ->getRowArray();

    //                         $remainingPrincipal =
    //                             max(
    //                                 0,
    //                                 $schedule['principal_due']
    //                                 -
    //                                 $updatedTotals['principal_paid']
    //                             );

    //                         $remainingInterest =
    //                             max(
    //                                 0,
    //                                 $schedule['interest_due']
    //                                 -
    //                                 $updatedTotals['interest_paid']
    //                             );

    //                         $remainingPenalty =
    //                             max(
    //                                 0,
    //                                 $schedule['penalty_due']
    //                                 -
    //                                 $updatedTotals['penalty_paid']
    //                             );

    //                         if(
    //                             $remainingPrincipal <= 0 &&
    //                             $remainingInterest <= 0 &&
    //                             $remainingPenalty <= 0
    //                         ){

    //                             $scheduleStatus = 'PAID';

    //                         }
    //                         elseif(

    //                             $updatedTotals['principal_paid'] > 0 ||
    //                             $updatedTotals['interest_paid'] > 0 ||
    //                             $updatedTotals['penalty_paid'] > 0

    //                         ){

    //                             $scheduleStatus = 'PARTIAL';

    //                         }
    //                         else{

    //                             $scheduleStatus = 'UNPAID';

    //                         }

    //                         $db->table(
    //                             'loan_schedule'
    //                         )
    //                         ->where(
    //                             'schedule_id',
    //                             $scheduleId
    //                         )
    //                         ->update([
    //                             'status' =>
    //                                 $scheduleStatus
    //                         ]);

    //                         /*
    //                         |--------------------------------------------------------------------------
    //                         | UPDATE LOAN STATUS
    //                         |--------------------------------------------------------------------------
    //                         */

    //                         $remainingSchedules =
    //                             $db->table(
    //                                 'loan_schedule'
    //                             )
    //                             ->where(
    //                                 'loan_id',
    //                                 $loanToSettle
    //                             )
    //                             ->whereIn(
    //                                 'status',
    //                                 [
    //                                     'UNPAID',
    //                                     'PARTIAL'
    //                                 ]
    //                             )
    //                             ->countAllResults();

    //                         if(
    //                             $remainingSchedules == 0
    //                         ){

    //                             $db->table(
    //                                 'loans'
    //                             )
    //                             ->where(
    //                                 'loan_id',
    //                                 $loanToSettle
    //                             )
    //                             ->update([
    //                                 'status' => 'PAID'
    //                             ]);

    //                         }

    //                     }

    //                 }

    //                 $totalPaidRow =
    //                     $db->table(
    //                         'loan_payments'
    //                     )
    //                     ->selectSum(
    //                         'total_amount'
    //                     )
    //                     ->where(
    //                         'settlement_id',
    //                         $settlement['settlement_id']
    //                     )
    //                     ->get()
    //                     ->getRowArray();

    //                 $totalPaid =
    //                     (float)(
    //                         $totalPaidRow['total_amount']
    //                         ?? 0
    //                     );

    //                 $settlementStatus =
    //                     $totalPaid >=
    //                     (float)$settlement['deficit_amount']
    //                     ? 'SETTLED'
    //                     : 'PARTIAL';

    //                 $db->table(
    //                     'borrower_settlements'
    //                 )
    //                 ->where(
    //                     'settlement_id',
    //                     $settlement['settlement_id']
    //                 )
    //                 ->update([

    //                     'status' =>
    //                         $settlementStatus,

    //                     'settled_at' =>
    //                         date(
    //                             'Y-m-d H:i:s'
    //                         ),

    //                     'settlement_loan_id' =>
    //                         $loanId

    //                 ]);

    //             }

    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | COLLATERAL
    //         |--------------------------------------------------------------------------
    //         */

    //         $collateralData = array_filter([
    //             'loan_id' => $loanId,
    //             'primary_card_name' => $input['primary_card_name'] ?? null,
    //             'primary_card_number' => $input['primary_card_number'] ?? null,
    //             'secondary_card_name' => $input['secondary_card_name'] ?? null,
    //             'secondary_card_number' => $input['secondary_card_number'] ?? null
    //         ], fn($value) => $value !== null && $value !== '');

    //         if (count($collateralData) > 1) {

    //             $db->table('loan_collaterals')
    //                 ->insert($collateralData);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | COMAKERS
    //         |--------------------------------------------------------------------------
    //         */

    //         if (!empty($input['comakers']) && is_array($input['comakers'])) {

    //             foreach ($input['comakers'] as $comaker) {

    //                 $db->table('loan_comakers')->insert([
    //                     'loan_id' => $loanId,
    //                     'name' => strtoupper(trim((string)($comaker['name'] ?? ''))),
    //                     'phone' => $comaker['phone'] ?? '',
    //                     'address' => strtoupper(trim((string)($comaker['address'] ?? '')))
    //                 ]);
    //             }
    //         }

            
    //         /*
    //         |--------------------------------------------------------------------------
    //         | INCENTIVES DEDUCTION
    //         |--------------------------------------------------------------------------
    //         */

    //         if(
    //             !empty(
    //                 $input['bonus_deductions']
    //             )
    //         ){

    //             foreach(
    //                 $input['bonus_deductions']
    //                 as $bonus
    //             ){

    //                 $db->table(
    //                     'loan_bonus_deductions'
    //                 )->insert([

    //                     'loan_id' =>
    //                         $loanId,

    //                     'deduction_type' =>
    //                         $bonus['deduction_type'],

    //                     'amount' =>
    //                         $bonus['amount'],

    //                     'is_paid' =>
    //                         0,

    //                     'created_at' =>
    //                         date('Y-m-d H:i:s')

    //                 ]);

    //             }

    //         }

    //         $this->generateLoanSchedule(
    //             $db,
    //             $loanId,
    //             (int)$input['loan_product_id'],
    //             (float)$input['loan_amount'],
    //             (int)$input['loan_terms']
    //         );

    //         if ($db->transStatus() === false) {

    //             $db->transRollback();

    //             return $this->getResponse([
    //                 'isError' => true,
    //                 'message' => 'Transaction failed.'
    //             ]);
    //         }

    //         $db->transCommit();

    //         $this->createAuditLog(
    //             'LOAN',
    //             $loanId,
    //             'CREATE',
    //             null,
    //             $loanData,
    //             'Loan created'
    //         );

    //         return $this->getResponse([
    //             'isError' => false,
    //             'loan_id' => $loanId,
    //             'message' => 'Loan successfully created.'
    //         ]);

    //     } catch (Exception $ex) {

    //         $db->transRollback();

    //         return $this->getResponse(
    //             [
    //                 'isError' => true,
    //                 'message' => $ex->getMessage(),
    //             ],
    //             $responseCode
    //         );
    //     }
    // }

    public function update(int $responseCode = ResponseInterface::HTTP_OK)
    {
        $db = \Config\Database::connect();

        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'loan_id' => 'required|numeric',
                'borrower_id' => 'required|numeric',
                'loan_product_id' => 'required|numeric',
                'interest_amount' => 'required|numeric',
                'processing_fee_amount' => 'required|numeric',
                'net_proceeds' => 'required|numeric',
                'loan_amount' => 'required',
                'loan_terms' => 'required'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors()),
                        'errors' => $this->validator->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $loanId = $input['loan_id'];

            $oldLoan = $db->table('loans')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            if (!$oldLoan) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Loan not found.'
                ]);
            }

            $db->transBegin();

            $loanData = [
                'borrower_id' => $input['borrower_id'],
                'loan_product_id' => $input['loan_product_id'],
                'loan_amount' => $input['loan_amount'],
                'loan_purpose' => strtoupper(trim((string)($input['loan_purpose'] ?? ''))),
                'loan_terms' => $input['loan_terms'],
                'approved_interest_rate' => $input['approved_interest_rate'] ?? 0,
                'approved_processing_fee' => $input['approved_processing_fee'] ?? 0,
                'interest_amount' => $input['interest_amount'] ?? 0,
                'processing_fee_amount' => $input['processing_fee_amount'] ?? 0,
                'net_proceeds' => $input['net_proceeds'] ?? 0
            ];

            $db->table('loans')
                ->where('loan_id', $loanId)
                ->update($loanData);

            // COLLATERAL

            $collateral = $db->table('loan_collaterals')
                ->where('loan_id', $loanId)
                ->get()
                ->getRowArray();

            $collateralData = [
                'primary_card_name' => $input['primary_card_name'] ?? '',
                'primary_card_number' => $input['primary_card_number'] ?? '',
                'secondary_card_name' => $input['secondary_card_name'] ?? '',
                'secondary_card_number' => $input['secondary_card_number'] ?? ''
            ];

            if ($collateral) {

                $db->table('loan_collaterals')
                    ->where('loan_id', $loanId)
                    ->update($collateralData);

            } else {

                $collateralData['loan_id'] = $loanId;

                $db->table('loan_collaterals')
                    ->insert($collateralData);
            }

            // COMAKERS

            $db->table('loan_comakers')
                ->where('loan_id', $loanId)
                ->delete();

            if (!empty($input['comakers']) && is_array($input['comakers'])) {

                foreach ($input['comakers'] as $comaker) {

                    $db->table('loan_comakers')->insert([
                        'loan_id' => $loanId,
                        'name' => strtoupper(trim((string)($comaker['name'] ?? ''))),
                        'phone' => $comaker['phone'] ?? '',
                        'address' => strtoupper(trim((string)($comaker['address'] ?? '')))
                    ]);
                }
            }

            if ($db->transStatus() === false) {

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Transaction failed.'
                ]);
            }

            $db->transCommit();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'UPDATE',
                $oldLoan,
                $loanData,
                'Loan updated'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully updated.'
            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }
    public function void(int $responseCode = ResponseInterface::HTTP_OK)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'loan_id' => 'required|numeric',
                'voidReason' => 'required',
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors())
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $loanId = $input['loan_id'];
            $voidReason = $input['voidReason'];

            $oldLoan = $this->loanModel
                ->where('loan_id', $loanId)
                ->first();

            if (!$oldLoan) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Loan not found.'
                ]);
            }

            $this->loanModel
                ->where('loan_id', $loanId)
                ->set([
                    'status' => 'VOID',
                    'voidReason' => $voidReason,
                ])
                ->update();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'VOID',
                $oldLoan,
                ['status' => 'VOID'],
                'Loan voided'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully voided.'
            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function approve(int $responseCode = ResponseInterface::HTTP_OK)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'loan_id' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors())
                    ]
                );
            }

            $loanId = $input['loan_id'];

            $oldLoan = $this->loanModel
                ->where('loan_id', $loanId)
                ->first();

            $this->loanModel
                ->where('loan_id', $loanId)
                ->set([
                    'approved_interest_rate' => $input['approved_interest_rate'] ?? 0,
                    'approved_processing_fee' => $input['approved_processing_fee'] ?? 0,
                    'approveRemarks' => $input['approveRemarks'],
                    'status' => 'APPROVED'
                ])
                ->update();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'APPROVE',
                $oldLoan,
                ['status' => 'APPROVED'],
                'Loan approved'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully approved.'
            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function release(int $responseCode = ResponseInterface::HTTP_OK)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'loan_id' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors())
                    ]
                );
            }

            $loanId = $input['loan_id'];

            $oldLoan = $this->loanModel
                ->where('loan_id', $loanId)
                ->first();

            $this->loanModel
                ->where('loan_id', $loanId)
                ->set([
                    'status' => 'RELEASED'
                ])
                ->update();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'RELEASE',
                $oldLoan,
                ['status' => 'RELEASED'],
                'Loan released'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully released.'
            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }
    public function reject(int $responseCode = ResponseInterface::HTTP_OK)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'loan_id' => 'required|numeric'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current($this->validator->getErrors())
                    ]
                );
            }

            $loanId = $input['loan_id'];

            $oldLoan = $this->loanModel
                ->where('loan_id', $loanId)
                ->first();

            $this->loanModel
                ->where('loan_id', $loanId)
                ->set([
                    'status' => 'REJECTED',
                    'disapproveReason' => $input['disapproveReason'],
                ])
                ->update();

            $this->createAuditLog(
                'LOAN',
                $loanId,
                'REJECTED',
                $oldLoan,
                ['status' => 'REJECT'],
                'Loan rejected'
            );

            return $this->getResponse([
                'isError' => false,
                'message' => 'Loan successfully reject.'
            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    protected function createAuditLog(
        string $module,
        int $recordId,
        string $action,
        $oldData = null,
        $newData = null,
        string $remarks = ''
    )
    {
        try {

            helper('jwt');

            $logModel = new \App\Models\AuditLogModel();

            $userId = null;
            $username = null;

            try {

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
                        $username = $jwtData['email'] ?? null;
                    }
                }

            } catch (\Exception $e) {
                log_message('error', 'Audit JWT Error: ' . $e->getMessage());
            }

               
            $auditData = [
                'module'      => strtoupper($module),
                'record_id'   => $recordId,
                'action'      => strtoupper($action),
                'user_id'     => $userId,
                'username'    => $username,
                'old_data'    => json_encode($oldData),
                'new_data'    => json_encode($newData),
                'remarks'     => $remarks,
                'ip_address'  => 1,
                'user_agent'  => (string)$this->request->getUserAgent(),
                'created_at'  => date('Y-m-d H:i:s')
            ];
            $logModel->createLog($auditData);

        } catch (\Exception $e) {

            log_message('error', 'Audit Log Error: ' . $e->getMessage());
        }
        
    }

    /**
     * ADD SETTLEMENT
     */
    public function addSettlement(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        helper('jwt');


        $userId = null;
        $username = null;

        try {

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
                    $username = $jwtData['email'] ?? null;
                }
            }

        } catch (\Exception $e) {
            log_message('error', 'Audit JWT Error: ' . $e->getMessage());
        }

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'borrower_id' =>
                    'required|numeric',

                'settlement_month' =>
                    'required',

                'deficit_amount' =>
                    'required|numeric'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current(
                            $this->validator
                                ->getErrors()
                        ),
                        'errors' =>
                            $this->validator
                                ->getErrors()
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE
            |--------------------------------------------------------------------------
            */

            $existing =
                $db->table(
                    'borrower_settlements'
                )
                ->where(
                    'borrower_id',
                    $input['borrower_id']
                )
                ->where(
                    'settlement_month',
                    $input['settlement_month']
                )
                ->get()
                ->getRowArray();

            if ($existing) {

                return $this->getResponse([
                    'isError' => true,
                    'message' =>
                        'Settlement already exists for this month.'
                ]);
            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | HEADER
            |--------------------------------------------------------------------------
            */

            $settlementData = [

                'borrower_id' =>
                    $input['borrower_id'],

                'settlement_month' =>
                    $input['settlement_month'],

                'deficit_amount' =>
                    $input['deficit_amount'],

                'remarks' =>
                    strtoupper(
                        trim(
                            (string)(
                                $input['remarks']
                                ?? ''
                            )
                        )
                    ),

                'status' =>
                    'UNPAID',

                'settled_at' =>
                    null,

                'settlement_loan_id' =>
                    null,

                'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    )

            ];

            $db->table(
                'borrower_settlements'
            )->insert(
                $settlementData
            );

            $settlementId =
                $db->insertID();

            /*
            |--------------------------------------------------------------------------
            | DETAILS
            |--------------------------------------------------------------------------
            */

            if (
                !empty(
                    $input['details']
                ) &&
                is_array(
                    $input['details']
                )
            ) {

                foreach (
                    $input['details']
                    as $detail
                ) {

                    if (
                        empty(
                            $detail['loan_id']
                        )
                    ) {
                        continue;
                    }

                    $dueAmount =
                        (float)(
                            $detail['due_amount']
                            ?? 0
                        );

                    $paidAmount =
                        (float)(
                            $detail['paid_amount']
                            ?? 0
                        );

                    $unpaidAmount =
                        (float)(
                            $detail['unpaid_amount']
                            ?? 0
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | SAVE SETTLEMENT DETAIL
                    |--------------------------------------------------------------------------
                    */

                    $db->table(
                        'borrower_settlement_details'
                    )->insert([

                        'settlement_id' =>
                            $settlementId,

                        'loan_id' =>
                            $detail['loan_id'],

                        'due_amount' =>
                            $dueAmount,

                        'paid_amount' =>
                            $paidAmount,

                        'unpaid_amount' =>
                            $unpaidAmount,

                        'amount' =>
                            $detail['amount'] ?? 0,

                        'created_at' =>
                            date(
                                'Y-m-d H:i:s'
                            )

                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE LOAN PAYMENT
                    |--------------------------------------------------------------------------
                    */

                    if ($paidAmount > 0) {

                        $remainingPayment =
                            $paidAmount;

                        $schedules =
                            $db->table('loan_schedule')
                            ->where(
                                'loan_id',
                                $detail['loan_id']
                            )
                            ->whereIn(
                                'status',
                                ['UNPAID','PARTIAL']
                            )
                            ->orderBy(
                                'due_date',
                                'ASC'
                            )
                            ->get()
                            ->getResultArray();

                        foreach($schedules as $schedule){

                            if(
                                $remainingPayment <= 0
                            ){
                                break;
                            }

                            $scheduleId =
                                $schedule['schedule_id'];

                            $penaltyRemaining =
                                max(
                                    0,
                                    $schedule['penalty_due']
                                    -
                                    $schedule['penalty_paid']
                                );

                            $interestRemaining =
                                max(
                                    0,
                                    $schedule['interest_due']
                                    -
                                    $schedule['interest_paid']
                                );

                            $principalRemaining =
                                max(
                                    0,
                                    $schedule['principal_due']
                                    -
                                    $schedule['principal_paid']
                                );

                            $penaltyPaid = 0;
                            $interestPaid = 0;
                            $principalPaid = 0;

                            /*
                            |--------------------------------------------------------------------------
                            | PENALTY FIRST
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $penaltyRemaining > 0
                            ){

                                $penaltyPaid =
                                    min(
                                        $remainingPayment,
                                        $penaltyRemaining
                                    );

                                $remainingPayment -=
                                    $penaltyPaid;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | INTEREST SECOND
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $interestRemaining > 0
                            ){

                                $interestPaid =
                                    min(
                                        $remainingPayment,
                                        $interestRemaining
                                    );

                                $remainingPayment -=
                                    $interestPaid;
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | PRINCIPAL LAST
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPayment > 0 &&
                                $principalRemaining > 0
                            ){

                                $principalPaid =
                                    min(
                                        $remainingPayment,
                                        $principalRemaining
                                    );

                                $remainingPayment -=
                                    $principalPaid;
                            }

                            $updatedPrincipalPaid =
                                $schedule['principal_paid']
                                + $principalPaid;

                            $updatedInterestPaid =
                                $schedule['interest_paid']
                                + $interestPaid;

                            $updatedPenaltyPaid =
                                $schedule['penalty_paid']
                                + $penaltyPaid;

                            $remainingPrincipal =
                                max(
                                    0,
                                    $schedule['principal_due']
                                    -
                                    $updatedPrincipalPaid
                                );

                            $remainingInterest =
                                max(
                                    0,
                                    $schedule['interest_due']
                                    -
                                    $updatedInterestPaid
                                );

                            $remainingPenalty =
                                max(
                                    0,
                                    $schedule['penalty_due']
                                    -
                                    $updatedPenaltyPaid
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | STATUS
                            |--------------------------------------------------------------------------
                            */

                            if(
                                $remainingPrincipal <= 0 &&
                                $remainingInterest <= 0 &&
                                $remainingPenalty <= 0
                            ){

                                $status = 'PAID';

                            }
                            elseif(

                                $updatedPrincipalPaid > 0 ||
                                $updatedInterestPaid > 0 ||
                                $updatedPenaltyPaid > 0

                            ){

                                $status = 'PARTIAL';

                            }
                            else{

                                $status = 'UNPAID';

                            }

                            /*
                            |--------------------------------------------------------------------------
                            | UPDATE SCHEDULE
                            |--------------------------------------------------------------------------
                            */

                            $db->table(
                                'loan_schedule'
                            )
                            ->where(
                                'schedule_id',
                                $scheduleId
                            )
                            ->update([

                                'principal_paid' =>
                                    $updatedPrincipalPaid,

                                'interest_paid' =>
                                    $updatedInterestPaid,

                                'penalty_paid' =>
                                    $updatedPenaltyPaid,

                                'status' =>
                                    $status

                            ]);

                            /*
                            |--------------------------------------------------------------------------
                            | PAYMENT RECORD
                            |--------------------------------------------------------------------------
                            */

                            $db->table(
                                'loan_payments'
                            )->insert([

                                'loan_id' =>
                                    $detail['loan_id'],

                                'schedule_id' =>
                                    $scheduleId,

                                'payment_date' =>
                                    date('Y-m-d'),

                                'payment_month' =>
                                    $input['settlement_month'],

                                'payment_type' =>
                                    'Interest',

                                'principal_amount' =>
                                    $principalPaid,

                                'interest_amount' =>
                                    $interestPaid,

                                'penalty_amount' =>
                                    $penaltyPaid,

                                'total_amount' =>

                                    $principalPaid +

                                    $interestPaid +

                                    $penaltyPaid,

                                'or_number' =>
                                    'SALARY-' .
                                    str_replace(
                                        '-',
                                        '',
                                        $input['settlement_month']
                                    ),

                                'remarks' =>
                                    'SALARY ALLOCATION',

                                'collected_by' =>
                                    $userId,

                                'status' =>
                                    'POSTED',

                                'created_at' =>
                                    date('Y-m-d H:i:s')

                            ]);

                        }
                    }

                }

            }

            if (
                $db->transStatus()
                === false
            ) {

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' =>
                        'Transaction failed.'
                ]);
            }

            $db->transCommit();

            $this->createAuditLog(
                'BORROWER_SETTLEMENT',
                $settlementId,
                'CREATE',
                null,
                $settlementData,
                'Settlement created'
            );

            return $this->getResponse([

                'isError' => false,

                'settlement_id' =>
                    $settlementId,

                'message' =>
                    'Settlement successfully created.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' =>
                        $ex->getMessage()
                ],
                $responseCode
            );
        }
    }

    public function addBonusSettlement(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'borrower_id' =>
                    'required|numeric',

                'loan_id' =>
                    'required|numeric',

                'bonus_deduction_id' =>
                    'required|numeric',

                'amount' =>
                    'required|numeric',

                'deduction_type' =>
                    'required'

            ];

            if(
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ){

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        current(
                            $this->validator
                                ->getErrors()
                        )

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | CHECK IF ALREADY SETTLED
            |--------------------------------------------------------------------------
            */

            $alreadySettled =
                $db->table(
                    'borrower_settlement_details'
                )
                ->where(
                    'bonus_deduction_id',
                    $input['bonus_deduction_id']
                )
                ->countAllResults();

            if(
                $alreadySettled > 0
            ){

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Bonus deduction is already included in a settlement.'

                ]);

            }

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | HEADER
            |--------------------------------------------------------------------------
            */

            $settlementMonth =
                date('Y') . '-BONUS';

            $settlementData = [

                'borrower_id' =>
                    $input['borrower_id'],

                'settlement_month' =>
                    $settlementMonth,

                'deficit_amount' =>
                    $input['amount'],

                'remarks' =>
                    'BONUS SETTLEMENT - ' .
                    strtoupper(
                        $input['deduction_type']
                    ),

                'status' =>
                    'UNPAID',

                'created_at' =>
                    date('Y-m-d H:i:s')

            ];

            $db->table(
                'borrower_settlements'
            )->insert(
                $settlementData
            );

            $settlementId =
                $db->insertID();

            /*
            |--------------------------------------------------------------------------
            | DETAIL
            |--------------------------------------------------------------------------
            */

            $db->table(
                'borrower_settlement_details'
            )->insert([

                'settlement_id' =>
                    $settlementId,

                'loan_id' =>
                    $input['loan_id'],

                'bonus_deduction_id' =>
                    $input['bonus_deduction_id'],

                'amount' =>
                    $input['amount'],

                'due_amount' =>
                    $input['amount'],

                'paid_amount' =>
                    0,

                'unpaid_amount' =>
                    $input['amount'],

                'created_at' =>
                    date('Y-m-d H:i:s')

            ]);

            if(
                $db->transStatus()
                === false
            ){

                $db->transRollback();

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Failed to create bonus settlement.'

                ]);

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'settlement_id' =>
                    $settlementId,

                'message' =>
                    'Bonus successfully added to settlement.'

            ]);

        }
        catch(Exception $ex){

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $ex->getMessage()

            ], $responseCode);

        }
    }
            
    public function addSalaryPayment(
        int $responseCode =
        ResponseInterface::HTTP_OK
    )
    {
        $db = \Config\Database::connect();

        try {

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules = [

                'borrower_id' =>
                    'required|numeric',

                'payment_month' =>
                    'required'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => current(
                            $this->validator
                                ->getErrors()
                        )
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );
            }

            $db->transBegin();

            foreach(
                $input['details']
                as $detail
            ){

                $loanId =
                    (int)(
                        $detail['loan_id']
                        ?? 0
                    );

                $amount =
                    (float)(
                        $detail['amount']
                        ?? 0
                    );

                if(
                    $loanId <= 0 ||
                    $amount <= 0
                ){
                    continue;
                }

                $remainingPayment =
                    $amount;

                $schedules =
                    $db->table('loan_schedule')
                    ->where(
                        'loan_id',
                        $loanId
                    )
                    ->whereIn(
                        'status',
                        ['UNPAID','PARTIAL']
                    )
                    ->orderBy(
                        'due_date',
                        'ASC'
                    )
                    ->get()
                    ->getResultArray();

                foreach(
                    $schedules
                    as $schedule
                ){

                    if(
                        $remainingPayment <= 0
                    ){
                        break;
                    }

                    $scheduleId =
                        $schedule['schedule_id'];

                    /*
                    |--------------------------------------------------------------------------
                    | GET PAID TOTALS FROM LOAN PAYMENTS
                    |--------------------------------------------------------------------------
                    */

                    $paymentTotals =
                        $db->table(
                            'loan_payments'
                        )
                        ->select("
                            COALESCE(SUM(principal_amount),0) as principal_paid,
                            COALESCE(SUM(interest_amount),0) as interest_paid,
                            COALESCE(SUM(penalty_amount),0) as penalty_paid
                        ")
                        ->where(
                            'schedule_id',
                            $scheduleId
                        )
                        ->get()
                        ->getRowArray();

                    $principalPaidToDate =
                        (float)(
                            $paymentTotals['principal_paid']
                            ?? 0
                        );

                    $interestPaidToDate =
                        (float)(
                            $paymentTotals['interest_paid']
                            ?? 0
                        );

                    $penaltyPaidToDate =
                        (float)(
                            $paymentTotals['penalty_paid']
                            ?? 0
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | REMAINING BALANCES
                    |--------------------------------------------------------------------------
                    */

                    $penaltyRemaining =
                        max(
                            0,
                            (float)$schedule['penalty_due']
                            - $penaltyPaidToDate
                        );

                    $interestRemaining =
                        max(
                            0,
                            (float)$schedule['interest_due']
                            - $interestPaidToDate
                        );

                    $principalRemaining =
                        max(
                            0,
                            (float)$schedule['principal_due']
                            - $principalPaidToDate
                        );

                    if(
                        $penaltyRemaining <= 0 &&
                        $interestRemaining <= 0 &&
                        $principalRemaining <= 0
                    ){
                        continue;
                    }

                    $penaltyPaid = 0;
                    $interestPaid = 0;
                    $principalPaid = 0;

                    /*
                    |--------------------------------------------------------------------------
                    | PENALTY
                    |--------------------------------------------------------------------------
                    */

                    if(
                        $remainingPayment > 0 &&
                        $penaltyRemaining > 0
                    ){

                        $penaltyPaid =
                            min(
                                $remainingPayment,
                                $penaltyRemaining
                            );

                        $remainingPayment -=
                            $penaltyPaid;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | INTEREST
                    |--------------------------------------------------------------------------
                    */

                    if(
                        $remainingPayment > 0 &&
                        $interestRemaining > 0
                    ){

                        $interestPaid =
                            min(
                                $remainingPayment,
                                $interestRemaining
                            );

                        $remainingPayment -=
                            $interestPaid;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PRINCIPAL
                    |--------------------------------------------------------------------------
                    */

                    if(
                        $remainingPayment > 0 &&
                        $principalRemaining > 0
                    ){

                        $principalPaid =
                            min(
                                $remainingPayment,
                                $principalRemaining
                            );

                        $remainingPayment -=
                            $principalPaid;
                    }

                    $newPrincipalPaid =
                        $principalPaidToDate
                        + $principalPaid;

                    $newInterestPaid =
                        $interestPaidToDate
                        + $interestPaid;

                    $newPenaltyPaid =
                        $penaltyPaidToDate
                        + $penaltyPaid;

                    $remainingPrincipal =
                        max(
                            0,
                            (float)$schedule['principal_due']
                            - $newPrincipalPaid
                        );

                    $remainingInterest =
                        max(
                            0,
                            (float)$schedule['interest_due']
                            - $newInterestPaid
                        );

                    $remainingPenalty =
                        max(
                            0,
                            (float)$schedule['penalty_due']
                            - $newPenaltyPaid
                        );

                    if(
                        $remainingPrincipal <= 0 &&
                        $remainingInterest <= 0 &&
                        $remainingPenalty <= 0
                    ){

                        $status = 'PAID';

                    }
                    elseif(

                        $newPrincipalPaid > 0 ||
                        $newInterestPaid > 0 ||
                        $newPenaltyPaid > 0

                    ){

                        $status = 'PARTIAL';

                    }
                    else{

                        $status = 'UNPAID';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE SCHEDULE STATUS ONLY
                    |--------------------------------------------------------------------------
                    */

                    $db->table(
                        'loan_schedule'
                    )
                    ->where(
                        'schedule_id',
                        $scheduleId
                    )
                    ->update([

                        'status' =>
                            $status

                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | PAYMENT RECORD
                    |--------------------------------------------------------------------------
                    */

                    $db->table(
                        'loan_payments'
                    )->insert([

                        'loan_id' =>
                            $loanId,

                        'schedule_id' =>
                            $scheduleId,

                        'payment_date' =>
                            date('Y-m-d'),

                        'payment_month' =>
                            $input['payment_month'],

                        'payment_type' =>
                            'PRINCIPAL',

                        'principal_amount' =>
                            $principalPaid,

                        'interest_amount' =>
                            $interestPaid,

                        'penalty_amount' =>
                            $penaltyPaid,

                        'total_amount' =>

                            $principalPaid +

                            $interestPaid +

                            $penaltyPaid,

                        'remarks' =>
                            'SALARY DEDUCTION',

                        'status' =>
                            'POSTED',

                        'created_at' =>
                            date('Y-m-d H:i:s')

                    ]);

                }

                /*
                |--------------------------------------------------------------------------
                | CHECK LOAN STATUS
                |--------------------------------------------------------------------------
                */

                $remainingSchedules =
                    $db->table(
                        'loan_schedule'
                    )
                    ->where(
                        'loan_id',
                        $loanId
                    )
                    ->whereIn(
                        'status',
                        ['UNPAID','PARTIAL']
                    )
                    ->countAllResults();

                if(
                    $remainingSchedules == 0
                ){

                    $db->table(
                        'loans'
                    )
                    ->where(
                        'loan_id',
                        $loanId
                    )
                    ->update([
                        'status' => 'PAID'
                    ]);

                }

            }

            if(
                $db->transStatus()
                === false
            ){

                $db->transRollback();

                return $this->getResponse([
                    'isError' => true,
                    'message' =>
                        'Transaction failed.'
                ]);

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Salary payment successfully posted.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' =>
                        $ex->getMessage()
                ],
                $responseCode
            );
        }
    }


    /**
     * CREATE YEARLY SETTLEMENT LOAN
     */
    public function createSettlementLoan()
    {
        $db = \Config\Database::connect();

        try {

            $input = $this->getRequestInput(
                $this->request
            );

            $db->transBegin();

            /*
            |--------------------------------------------------------------------------
            | CREATE LOAN
            |--------------------------------------------------------------------------
            */

            $loanData = [

                'borrower_id' =>
                    $input['borrower_id'],

                'loan_product_id' =>
                    $input['loan_product_id'],

                'loan_amount' =>
                    $input['loan_amount'],

                'loan_purpose' =>
                    'YEARLY SETTLEMENT '.$input['year'],

                'loan_terms' =>
                    $input['loan_terms'],

                'approved_interest_rate' =>
                    $input['approved_interest_rate'],

                'approved_processing_fee' => 0,

                'interest_amount' =>
                    $input['interest_amount'],

                'processingfee_amount' =>
                    $input['processingfee_amount'],

                'net_proceeds' =>
                    $input['net_proceeds'],

                'status' => 'PENDING',

                'is_settlement' => 1,

                'created_at' =>
                    date('Y-m-d H:i:s')

            ];

            $db->table('loans')
                ->insert($loanData);

            $loanId = $db->insertID();

            /*
            |--------------------------------------------------------------------------
            | GENERATE SCHEDULE
            |--------------------------------------------------------------------------
            */

            $this->generateLoanSchedule(
                $db,
                $loanId,
                (int)$input['loan_product_id'],
                (float)$input['loan_amount'],
                (int)$input['loan_terms']
            );

            /*
            |--------------------------------------------------------------------------
            | SETTLE ALL UNPAID MONTHS
            |--------------------------------------------------------------------------
            */

            $settlements =
                $db->table(
                    'borrower_settlements'
                )
                ->where(
                    'borrower_id',
                    $input['borrower_id']
                )
                ->where(
                    'status',
                    'UNPAID'
                )
                ->like(
                    'settlement_month',
                    $input['year'],
                    'after'
                )
                ->get()
                ->getResultArray();

            foreach(
                $settlements as $row
            ){

                $db->table(
                    'borrower_settlements'
                )
                ->where(
                    'settlement_id',
                    $row['settlement_id']
                )
                ->update([

                    'status' =>
                        'SETTLED',

                    'loan_id' =>
                        $loanId,

                    'settled_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]);

            }

            if(
                $db->transStatus()
                === false
            ){

                $db->transRollback();

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Transaction failed.'

                ]);
            }

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'loan_id' =>
                    $loanId,

                'message' =>
                    'Settlement loan successfully created.'

            ]);

        } catch (\Exception $e) {

            $db->transRollback();

            return $this->getResponse([

                'isError' => true,

                'message' =>
                    $e->getMessage()

            ]);
        }
    }


    public function contractOfLoan()
    {
        $loanId = $this->request->getGet('id');

        $loanModel = new LoanModel();

        $loan = $loanModel->getLoanDetails($loanId);
   
        $loan['collateral'] = $loanModel->getCollateral($loanId);
        $loan['comakers'] = $loanModel->getComakers($loanId);
        $data['comakers'] = $loanModel->getComakers($loanId);
        $data['representative'] = $loanModel->getRepresentative();
            // print_r($data['representative']);return false;
        $fullname =
            $loan['first_name'] . ' ' .
            $loan['middle_name'] . ' ' .
            $loan['last_name'];

        $name = "Contract of Loan of Mr/Ms {$fullname}";

        $data['loan'] = $loan;
        $data['title'] = $name;

        $pdf = new Pdf();

        $html = view('pdf/contract', $data);
      
        $pdf->load_view2_portrait(
            $name,
            $html
        );
    }
    public function loanAddendum()
    {
        $loanId = $this->request->getGet('id');

        $loanModel = new LoanModel();

        $loan = $loanModel->getLoanDetails($loanId);
   
        $loan['collateral'] = $loanModel->getCollateral($loanId);
        $loan['comakers'] = $loanModel->getComakers($loanId);
        $data['comakers'] = $loanModel->getComakers($loanId);
        $data['representative'] = $loanModel->getRepresentative();
            // print_r($data['representative']);return false;
        $fullname =
            $loan['first_name'] . ' ' .
            $loan['middle_name'] . ' ' .
            $loan['last_name'];

        $name = "Contract of Loan of Mr/Ms {$fullname}";

        $data['loan'] = $loan;
        $data['title'] = $name;

        $pdf = new Pdf();

        $html = view('pdf/loan_aquisition', $data);
      
        $pdf->load_view2_portrait(
            $name,
            $html
        );
    }

    public function getBonusCollections()
    {
        try {

            $db =
                \Config\Database::connect();

            $loanId =
                (int)$this->request
                    ->getGet(
                        'loan_id'
                    );

            $year =
                (int)$this->request
                    ->getGet(
                        'year'
                    );

            if (
                empty($loanId)
            ) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'loan_id is required'

                ]);
            }

            if (
                empty($year)
            ) {

                $year =
                    date('Y');
            }

            $rows =
                $db->table(
                    'loan_bonus_deductions lbd'
                )
                ->select("
                    lbd.bonus_deduction_id,
                    lbd.loan_id,
                    lbd.collection_type,
                    lbd.amount as default_amount,

                    bc.collection_id,
                    bc.amount as credited_amount,
                    bc.collection_date,

                    lp.payment_id,
                    lp.total_amount as paid_amount,
                    lp.payment_date
                ")
                ->join(
                    'borrower_bonus_collections bc',
                    "
                    bc.bonus_deduction_id =
                    lbd.bonus_deduction_id

                    AND YEAR(
                        bc.collection_date
                    ) = {$year}
                    ",
                    'left'
                )
                ->join(
                    'loan_payments lp',
                    '
                    lp.bonus_collection_id =
                    bc.collection_id
                    ',
                    'left'
                )
                ->where(
                    'lbd.loan_id',
                    $loanId
                )
                ->where(
                    'lbd.is_active',
                    1
                )
                ->orderBy(
                    'lbd.collection_type',
                    'ASC'
                )
                ->get()
                ->getResultArray();

            $result = [];

            foreach (
                $rows
                as $row
            ) {

                $status =
                    'NO_CREDIT';

                if (
                    !empty(
                        $row['collection_id']
                    )
                ) {

                    $status =
                        'CREDITED';
                }

                if (
                    !empty(
                        $row['payment_id']
                    )
                ) {

                    $status =
                        'PAID';
                }

                $result[] = [

                    'bonus_deduction_id' =>
                        $row['bonus_deduction_id'],

                    'loan_id' =>
                        $row['loan_id'],

                    'collection_type' =>
                        $row['collection_type'],

                    'default_amount' =>
                        (float)(
                            $row['default_amount']
                            ?? 0
                        ),

                    'collection_id' =>
                        $row['collection_id'],

                    'credited_amount' =>
                        (float)(
                            $row['credited_amount']
                            ?? 0
                        ),

                    'collection_date' =>
                        $row['collection_date'],

                    'payment_id' =>
                        $row['payment_id'],

                    'paid_amount' =>
                        (float)(
                            $row['paid_amount']
                            ?? 0
                        ),

                    'payment_date' =>
                        $row['payment_date'],

                    'status' =>
                        $status

                ];

            }

            return $this->getResponse([

                'isError' =>
                    false,

                'year' =>
                    $year,

                'data' =>
                    $result

            ]);

        } catch (
            Exception $ex
        ) {

            return $this->getResponse([

                'isError' =>
                    true,

                'message' =>
                    $ex->getMessage()

            ]);

        }
    }

    public function payBonusCollection()
    {
        try {

            $db = \Config\Database::connect();

            $collectionId =
                (int)$this->request
                    ->getPost(
                        'collection_id'
                    );

            if(
                empty(
                    $collectionId
                )
            ){

                throw new \Exception(
                    'Collection ID is required.'
                );

            }

            $collection =
                $db->table(
                    'borrower_bonus_collections'
                )
                ->where(
                    'collection_id',
                    $collectionId
                )
                ->get()
                ->getRowArray();

            if(
                !$collection
            ){

                throw new \Exception(
                    'Bonus collection not found.'
                );

            }

            $existingPayment =
                $db->table(
                    'loan_payments'
                )
                ->where(
                    'bonus_collection_id',
                    $collectionId
                )
                ->countAllResults();

            if(
                $existingPayment > 0
            ){

                throw new \Exception(
                    'Bonus already paid.'
                );

            }
            $loanId =
                (int)$collection['loan_id'];

            $deduction =
                $db->table(
                    'loan_bonus_deductions'
                )
                ->where(
                    'loan_id',
                    $loanId
                )
                ->where(
                    'deduction_type',
                    $collection['collection_type']
                )
                ->get()
                ->getRowArray();

            if(
                !$deduction
            ){

                throw new \Exception(
                    'Bonus deduction setup not found.'
                );

            }

            $creditedAmount =
                (float)(
                    $collection['default_amount']
                    ?? 0
                );

            $deductionAmount =
                (float)(
                    $deduction['amount']
                    ?? 0
                );

            $remainingPayment =
                min(
                    $creditedAmount,
                    $deductionAmount
                );

            $sukli =
                max(
                    0,
                    $creditedAmount
                    - $deductionAmount
                );

            $db->transBegin();

            $schedules =
                $db->table(
                    'loan_schedule'
                )
                ->where(
                    'loan_id',
                    $loanId
                )
                ->whereIn(
                    'status',
                    [
                        'UNPAID',
                        'PARTIAL'
                    ]
                )
                ->orderBy(
                    'due_date',
                    'ASC'
                )
                ->get()
                ->getResultArray();

            foreach(
                $schedules
                as $schedule
            ){

                if(
                    $remainingPayment <= 0
                ){
                    break;
                }

                $scheduleId =
                    $schedule['schedule_id'];

                /*
                |--------------------------------------------------------------------------
                | GET ACTUAL PAID AMOUNTS FROM LOAN PAYMENTS
                |--------------------------------------------------------------------------
                */

                $paymentTotals =
                    $db->table(
                        'loan_payments'
                    )
                    ->select("
                        COALESCE(SUM(principal_amount),0) as principal_paid,
                        COALESCE(SUM(interest_amount),0) as interest_paid,
                        COALESCE(SUM(penalty_amount),0) as penalty_paid
                    ")
                    ->where(
                        'schedule_id',
                        $scheduleId
                    )
                    ->get()
                    ->getRowArray();

                $principalPaidToDate =
                    (float)(
                        $paymentTotals['principal_paid']
                        ?? 0
                    );

                $interestPaidToDate =
                    (float)(
                        $paymentTotals['interest_paid']
                        ?? 0
                    );

                $penaltyPaidToDate =
                    (float)(
                        $paymentTotals['penalty_paid']
                        ?? 0
                    );

                /*
                |--------------------------------------------------------------------------
                | REMAINING BALANCES
                |--------------------------------------------------------------------------
                */

                $penaltyRemaining =
                    max(
                        0,
                        (float)$schedule['penalty_due']
                        - $penaltyPaidToDate
                    );

                $interestRemaining =
                    max(
                        0,
                        (float)$schedule['interest_due']
                        - $interestPaidToDate
                    );

                $principalRemaining =
                    max(
                        0,
                        (float)$schedule['principal_due']
                        - $principalPaidToDate
                    );

                /*
                |--------------------------------------------------------------------------
                | ALLOCATE PAYMENT
                |--------------------------------------------------------------------------
                */

                $penaltyPaid = 0;
                $interestPaid = 0;
                $principalPaid = 0;

                /*
                |--------------------------------------------------------------------------
                | PENALTY FIRST
                |--------------------------------------------------------------------------
                */

                if(
                    $remainingPayment > 0 &&
                    $penaltyRemaining > 0
                ){

                    $penaltyPaid =
                        min(
                            $remainingPayment,
                            $penaltyRemaining
                        );

                    $remainingPayment -=
                        $penaltyPaid;

                }

                /*
                |--------------------------------------------------------------------------
                | INTEREST SECOND
                |--------------------------------------------------------------------------
                */

                if(
                    $remainingPayment > 0 &&
                    $interestRemaining > 0
                ){

                    $interestPaid =
                        min(
                            $remainingPayment,
                            $interestRemaining
                        );

                    $remainingPayment -=
                        $interestPaid;

                }

                /*
                |--------------------------------------------------------------------------
                | PRINCIPAL LAST
                |--------------------------------------------------------------------------
                */

                if(
                    $remainingPayment > 0 &&
                    $principalRemaining > 0
                ){

                    $principalPaid =
                        min(
                            $remainingPayment,
                            $principalRemaining
                        );

                    $remainingPayment -=
                        $principalPaid;

                }

                /*
                |--------------------------------------------------------------------------
                | INSERT PAYMENT
                |--------------------------------------------------------------------------
                */

                $db->table(
                    'loan_payments'
                )
                ->insert([

                    'loan_id' =>
                        $loanId,

                    'schedule_id' =>
                        $scheduleId,

                    'bonus_collection_id' =>
                        $collectionId,

                    'payment_date' =>
                        date('Y-m-d'),

                    'payment_source' =>
                        $collection['collection_type'],

                    'principal_amount' =>
                        $principalPaid,

                    'interest_amount' =>
                        $interestPaid,

                    'penalty_amount' =>
                        $penaltyPaid,

                    'total_amount' =>
                        (
                            $principalPaid +
                            $interestPaid +
                            $penaltyPaid
                        ),

                    'remarks' =>
                        'BONUS COLLECTION PAYMENT',

                    'created_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]);

                /*
                |--------------------------------------------------------------------------
                | COMPUTE NEW STATUS
                |--------------------------------------------------------------------------
                */

                $newPrincipalPaid =
                    $principalPaidToDate
                    + $principalPaid;

                $newInterestPaid =
                    $interestPaidToDate
                    + $interestPaid;

                $newPenaltyPaid =
                    $penaltyPaidToDate
                    + $penaltyPaid;

                $principalBalance =
                    max(
                        0,
                        (float)$schedule['principal_due']
                        - $newPrincipalPaid
                    );

                $interestBalance =
                    max(
                        0,
                        (float)$schedule['interest_due']
                        - $newInterestPaid
                    );

                $penaltyBalance =
                    max(
                        0,
                        (float)$schedule['penalty_due']
                        - $newPenaltyPaid
                    );

                if(
                    $principalBalance <= 0 &&
                    $interestBalance <= 0 &&
                    $penaltyBalance <= 0
                ){

                    $status =
                        'PAID';

                }
                else if(
                    $newPrincipalPaid > 0 ||
                    $newInterestPaid > 0 ||
                    $newPenaltyPaid > 0
                ){

                    $status =
                        'PARTIAL';

                }
                else{

                    $status =
                        'UNPAID';

                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE STATUS ONLY
                |--------------------------------------------------------------------------
                */

                $db->table(
                    'loan_schedule'
                )
                ->where(
                    'schedule_id',
                    $scheduleId
                )
                ->update([

                    'status' =>
                        $status

                ]);

            }

            if(
                $db->transStatus()
                === false
            ){

                $db->transRollback();

                throw new \Exception(
                    'Payment failed.'
                );

            }

            $db->transCommit();

            return $this->getResponse([

                'isError' =>
                    false,

                'credited_amount' =>
                    $creditedAmount,

                'deduction_amount' =>
                    $deductionAmount,

                'amount_applied' =>
                    min(
                        $creditedAmount,
                        $deductionAmount
                    ),

                'sukli' =>
                    $sukli,

                'message' =>
                    'Bonus payment successfully applied.'

            ]);

        } catch(
            \Exception $e
        ){

            return $this->getResponse([

                'isError' =>
                    true,

                'message' =>
                    $e->getMessage()

            ]);

        }
    }
    public function getBonusPaymentDetails()
    {
        try {

            $collectionId =
                (int)$this->request
                    ->getGet(
                        'collection_id'
                    );

            if(
                empty(
                    $collectionId
                )
            ){

                throw new \Exception(
                    'Collection ID is required.'
                );

            }

            $db =
                \Config\Database::connect();

            /*
            |--------------------------------------------------------------------------
            | BONUS COLLECTION
            |--------------------------------------------------------------------------
            */

            $collection =
                $db->table(
                    'borrower_bonus_collections'
                )
                ->where(
                    'collection_id',
                    $collectionId
                )
                ->get()
                ->getRowArray();

            if(
                !$collection
            ){

                throw new \Exception(
                    'Bonus collection not found.'
                );

            }

            /*
            |--------------------------------------------------------------------------
            | DEDUCTION SETUP
            |--------------------------------------------------------------------------
            */

            $deduction =
                $db->table(
                    'loan_bonus_deductions'
                )
                ->where(
                    'loan_id',
                    $collection['loan_id']
                )
                ->where(
                    'deduction_type',
                    $collection['collection_type']
                )
                ->get()
                ->getRowArray();

            $creditedAmount =
                (float)(
                    $collection['default_amount']
                    ?? 0
                );

            $deductionAmount =
                (float)(
                    $deduction['amount']
                    ?? 0
                );

            $sukli =
                max(
                    0,
                    $creditedAmount -
                    $deductionAmount
                );

            /*
            |--------------------------------------------------------------------------
            | PAYMENT DETAILS
            |--------------------------------------------------------------------------
            */

            $rows =
                $db->table(
                    'loan_payments lp'
                )
                ->select("
                    lp.*,
                    lp.bonus_collection_id,
                    l.loan_id,
                    lp.payment_date,
                    lp.interest_amount,
                    lp.principal_amount,
                    lp.penalty_amount,
                    lp.total_amount,
                    lp.payment_source,
                    pr.product_name
                ")
                ->join(
                    'loans l',
                    'l.loan_id = lp.loan_id'
                )
                ->join(
                    'loan_products pr',
                    'pr.loan_product_id = l.loan_product_id'
                )
                ->where(
                    'lp.bonus_collection_id',
                    $collectionId
                )
                ->orderBy(
                    'lp.payment_date',
                    'ASC'
                )
                ->get()
                ->getResultArray();

            $totalApplied = 0;

            foreach(
                $rows
                as $row
            ){

                $totalApplied +=
                    (float)(
                        $row['total_amount']
                        ?? 0
                    );

            }

            return $this->getResponse([

                'isError' =>
                    false,

                'collection_type' =>
                    $collection['collection_type'],

                'credited_amount' =>
                    $creditedAmount,

                'deduction_amount' =>
                    $deductionAmount,

                'total_applied' =>
                    round(
                        $totalApplied,
                        2
                    ),

                'sukli' =>
                    round(
                        $sukli,
                        2
                    ),

                'data' =>
                    $rows

            ]);

        } catch(Exception $ex){

            return $this->getResponse([

                'isError' =>
                    true,

                'message' =>
                    $ex->getMessage()

            ]);

        }
    }

    public function getBonusSettlementDetails()
    {
        try{

            $settlementId =
                $this->request
                    ->getGet(
                        'settlement_id'
                    );

            $db =
                \Config\Database::connect();

            $rows =
                $db->table(
                    'borrower_settlement_details d'
                )
                ->select("
                    d.*,
                    s.status,
                    s.settlement_month,
                    lbd.deduction_type
                ")
                ->join(
                    'borrower_settlements s',
                    's.settlement_id = d.settlement_id'
                )
                ->join(
                    'loan_bonus_deductions lbd',
                    'lbd.bonus_deduction_id = d.bonus_deduction_id',
                    'left'
                )
                ->where(
                    'd.settlement_id',
                    $settlementId
                )
                ->get()
                ->getResultArray();

            return $this->getResponse([

                'isError' =>
                    false,

                'data' =>
                    $rows

            ]);

        }
        catch(Exception $ex){

            return $this->getResponse([

                'isError' =>
                    true,

                'message' =>
                    $ex->getMessage()

            ]);

        }
    }
}