<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\LoanAdjustmentModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class LoanAdjustment extends BaseController
{
    protected $loanAdjustmentModel;

    public function __construct()
    {
        $this->loanAdjustmentModel =
            new LoanAdjustmentModel();
    }

    public function index()
    {
        return $this->get();
    }

    public function get()
    {
        try {

            $search =
                $this->request->getGet('search');

            $loanAdjustmentId =
                $this->request->getGet(
                    'loan_adjustment_id'
                );

            $loanId =
                $this->request->getGet(
                    'loan_id'
                );

            $data =
                $this->loanAdjustmentModel
                ->getLoanAdjustments(
                    $search,
                    $loanAdjustmentId,
                    $loanId
                );

            return $this->response->setJSON([
                'isError' => false,
                'message' => 'Success',
                'data' => $data
            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function details(
        $loanAdjustmentId
    )
    {
        try {

            $data =
                $this->loanAdjustmentModel
                ->getLoanAdjustmentDetails(
                    $loanAdjustmentId
                );

            return $this->response->setJSON([
                'isError' => false,
                'data' => $data
            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function add(
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

                'loan_id' =>
                    'required|numeric',

                'adjustment_type' =>
                    'required',

                'amount' =>
                    'required|decimal'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => current(
                        $this->validator
                        ->getErrors()
                    )
                ]);
            }

            $db->transBegin();

            $data = [

                'loan_id' =>
                    $input['loan_id'],

                'adjustment_type' =>
                    strtoupper(
                        $input['adjustment_type']
                    ),

                'amount' =>
                    $input['amount'],

                'interest_rate' =>
                    $input['interest_rate']
                    ?? 0,

                'penalty_rate' =>
                    $input['penalty_rate']
                    ?? 0,

                'term_months' =>
                    $input['term_months']
                    ?? 0,

                'remarks' =>
                    $input['remarks']
                    ?? '',

                'created_at' =>
                    date('Y-m-d H:i:s'),

                'is_active' => 1

            ];

            $db->table(
                'loan_adjustments'
            )->insert(
                $data
            );

            $adjustmentId =
                $db->insertID();

            $db->transCommit();

            return $this->getResponse([

                'isError' => false,

                'loan_adjustment_id' =>
                    $adjustmentId,

                'message' =>
                    'Loan Adjustment successfully created.'

            ]);

        } catch (Exception $ex) {

            $db->transRollback();

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function update(
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

                'loan_adjustment_id' =>
                    'required|numeric'

            ];

            if (
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => current(
                        $this->validator
                        ->getErrors()
                    )
                ]);
            }

            $db->table(
                'loan_adjustments'
            )
            ->where(
                'loan_adjustment_id',
                $input['loan_adjustment_id']
            )
            ->update([

                'amount' =>
                    $input['amount'],

                'interest_rate' =>
                    $input['interest_rate']
                    ?? 0,

                'penalty_rate' =>
                    $input['penalty_rate']
                    ?? 0,

                'term_months' =>
                    $input['term_months']
                    ?? 0,

                'remarks' =>
                    $input['remarks']
                    ?? ''

            ]);

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Loan Adjustment successfully updated.'

            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function void(
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

            $db->table(
                'loan_adjustments'
            )
            ->where(
                'loan_adjustment_id',
                $input['loan_adjustment_id']
            )
            ->update([
                'is_active' => 0
            ]);

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Loan Adjustment successfully voided.'

            ]);

        } catch (Exception $ex) {

            return $this->getResponse([
                'isError' => true,
                'message' => $ex->getMessage()
            ]);
        }
    }

    public function approve()
    {
        $input = $this->getRequestInput(
            $this->request
        );

        $adjustmentId =
            $input['loan_adjustment_id'];

        $adjustment =
            $this->loanAdjustmentModel
            ->find($adjustmentId);

        if(!$adjustment){

            return $this->getResponse([
                'isError'=>true,
                'message'=>'Adjustment not found'
            ]);

        }

        $this->loanAdjustmentModel
            ->update(
                $adjustmentId,
                [

                    'status' =>
                        'APPROVED',

                    'approved_at' =>
                        date(
                            'Y-m-d H:i:s'
                        )

                ]
            );

        /*
        |--------------------------------------------------------------------------
        | GENERATE SCHEDULE HERE
        |--------------------------------------------------------------------------
        */

        return $this->getResponse([
            'isError'=>false,
            'message'=>'Adjustment approved.'
        ]);
    }
}