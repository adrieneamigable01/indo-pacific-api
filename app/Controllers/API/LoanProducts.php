<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\LoanProductModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class LoanProducts extends BaseController
{
    protected $loanProductModel;

    public function __construct()
    {
        $this->loanProductModel =
            new LoanProductModel();
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

            $loan_product_id =
                $this->request->getGet('loan_product_id');

            $data =
                $this->loanProductModel
                ->getLoanProducts(
                    $search,
                    $loan_product_id
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

    public function details($loanProductId)
    {
        try {

            $data =
                $this->loanProductModel
                ->getLoanProductDetails(
                    $loanProductId
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

                'product_name' =>
                    'required',

                'interest_rate' =>
                    'required',

                'max_term' =>
                    'required',

                'min_amount' =>
                    'required',

                'max_amount' =>
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
                        'message' =>
                            current(
                                $this->validator
                                ->getErrors()
                            ),
                        'errors' =>
                            $this->validator
                            ->getErrors()
                    ],
                    ResponseInterface::
                    HTTP_BAD_REQUEST
                );

            }

            $db->transBegin();

            $data = [

                'product_name' =>
                    strtoupper(
                        trim(
                            $input['product_name']
                        )
                    ),

                'description' =>
                    $input['description']
                    ?? '',

                'interest_rate' =>
                    $input['interest_rate'],

                'processing_fee_percent' =>
                    $input['processing_fee_percent']
                    ?? 0,

                'penalty_rate' =>
                    $input['penalty_rate']
                    ?? 0,

                'max_term' =>
                    $input['max_term'],

                'min_amount' =>
                    $input['min_amount'],

                'max_amount' =>
                    $input['max_amount'],

                'is_active' =>
                    $input['is_active']
                    ?? 1,

                'created_at' =>
                    date(
                        'Y-m-d H:i:s'
                    )

            ];

            $db->table(
                'loan_products'
            )->insert(
                $data
            );

            $loanProductId =
                $db->insertID();

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

                'LOAN PRODUCT',

                $loanProductId,

                'CREATE',

                null,

                $data,

                'Loan product created'

            );

            return $this->getResponse([

                'isError' => false,

                'loan_product_id' =>
                    $loanProductId,

                'message' =>
                    'Loan Product successfully created.'

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

                'loan_product_id' =>
                    'required|numeric',

                'product_name' =>
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
                        'message' =>
                            current(
                                $this->validator
                                ->getErrors()
                            )
                    ]
                );

            }

            $loanProductId =
                $input['loan_product_id'];

            $oldData =
                $db->table(
                    'loan_products'
                )
                ->where(
                    'loan_product_id',
                    $loanProductId
                )
                ->get()
                ->getRowArray();

            if (!$oldData) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Loan Product not found.'

                ]);

            }

            $db->transBegin();

            $data = [

                'product_name' =>
                    strtoupper(
                        trim(
                            $input['product_name']
                        )
                    ),

                'description' =>
                    $input['description']
                    ?? '',

                'interest_rate' =>
                    $input['interest_rate'],

                'processing_fee_percent' =>
                    $input['processing_fee_percent']
                    ?? 0,

                'penalty_rate' =>
                    $input['penalty_rate']
                    ?? 0,

                'max_term' =>
                    $input['max_term'],

                'min_amount' =>
                    $input['min_amount'],

                'max_amount' =>
                    $input['max_amount'],

                'is_active' =>
                    $input['is_active']

            ];

            $db->table(
                'loan_products'
            )
            ->where(
                'loan_product_id',
                $loanProductId
            )
            ->update(
                $data
            );

            $db->transCommit();

            $this->createAuditLog(

                'LOAN PRODUCT',

                $loanProductId,

                'UPDATE',

                $oldData,

                $data,

                'Loan product updated'

            );

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Loan Product successfully updated.'

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

            $loanProductId =
                $input['loan_product_id'];

            $oldData =
                $this->loanProductModel
                ->where(
                    'loan_product_id',
                    $loanProductId
                )
                ->first();

            if (!$oldData) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Loan Product not found.'

                ]);

            }

            $db->transBegin();

            $this->loanProductModel
                ->where(
                    'loan_product_id',
                    $loanProductId
                )
                ->set([
                    'is_active' => 0
                ])
                ->update();

            $db->transCommit();

            $this->createAuditLog(

                'LOAN PRODUCT',

                $loanProductId,

                'VOID',

                $oldData,

                ['is_active' => 0],

                'Loan product voided'

            );

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Loan Product successfully voided.'

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
}