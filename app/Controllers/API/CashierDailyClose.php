<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\CashierDailyCloseModel;
use App\Models\CashierDailyCloseDenominationModel;
use App\Models\CashierDailyCloseLogModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class CashierDailyClose extends BaseController
{
    protected $dailyCloseModel;
    protected $denominationModel;
    protected $logModel;
    protected $db;

    public function __construct()
    {
        $this->dailyCloseModel   = new CashierDailyCloseModel();
        $this->denominationModel = new CashierDailyCloseDenominationModel();
        $this->logModel          = new CashierDailyCloseLogModel();

        $this->db = \Config\Database::connect();
    }
        /**
     * Create Daily Close
     */
    public function create()
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [

                'cashier_id'      => 'required|integer',
                'business_date'   => 'required',
                'expected_cash'   => 'required|decimal',
                'actual_cash'     => 'required|decimal',
                'returned_amount' => 'required|decimal'

            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(

                    [
                        'isError' => true,
                        'message' => array_values(
                            $this->validator->getErrors()
                        )[0]
                    ],

                    ResponseInterface::HTTP_BAD_REQUEST

                );

            }

            if (
                $this->dailyCloseModel->hasExistingDailyClose(
                    $input['cashier_id'],
                    $input['business_date']
                )
            ) {

                return $this->getResponse(

                    [
                        'isError' => true,
                        'message' => 'Daily return already exists for this business date.'
                    ],

                    ResponseInterface::HTTP_BAD_REQUEST

                );

            }

            $expectedCash = (float) $input['expected_cash'];
            $actualCash   = (float) $input['actual_cash'];

            $variance = $actualCash - $expectedCash;

            $this->db->transBegin();

            $dailyClose = [

                'cashier_id'      => $input['cashier_id'],

                'business_date'   => $input['business_date'],

                'expected_cash'   => $expectedCash,

                'actual_cash'     => $actualCash,

                'variance'        => $variance,

                'returned_amount' => $input['returned_amount'],

                'remarks'         => $input['remarks'] ?? null,

                'status'          => 'PENDING',

                'closed_by'       => $input['closed_by'] ?? $input['cashier_id'],

                'closed_at'       => date('Y-m-d H:i:s')

            ];

            $this->dailyCloseModel->insert($dailyClose);

            $cashierDailyCloseId =
                $this->dailyCloseModel->getInsertID();
                        /*
            |--------------------------------------------------------------------------
            | Save Denominations
            |--------------------------------------------------------------------------
            */

            if (
                isset($input['denominations']) &&
                is_array($input['denominations'])
            ) {

                foreach ($input['denominations'] as $row) {

                    $denomination = (float) ($row['denomination'] ?? 0);
                    $quantity     = (int) ($row['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        continue;
                    }

                    $this->denominationModel->insert([

                        'cashier_daily_close_id' => $cashierDailyCloseId,

                        'denomination' => $denomination,

                        'quantity' => $quantity,

                        'total' => $denomination * $quantity

                    ]);

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Save Audit Log
            |--------------------------------------------------------------------------
            */

            $this->logModel->addLog(

                $cashierDailyCloseId,

                'CREATED',

                $dailyClose['closed_by'],

                'Daily return submitted.'

            );

            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            if ($this->db->transStatus() === false) {

                $this->db->transRollback();

                return $this->getResponse(

                    [

                        'isError' => true,

                        'message' => 'Unable to save daily return.'

                    ],

                    ResponseInterface::HTTP_BAD_REQUEST

                );

            }

            $this->db->transCommit();

            return $this->getResponse(

                [

                    'isError' => false,

                    'message' => 'Daily return submitted successfully.',

                    'data' => [

                        'cashier_daily_close_id' => $cashierDailyCloseId,

                        'status' => 'PENDING'

                    ]

                ],

                ResponseInterface::HTTP_OK

            );

        } catch (Exception $e) {

            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }

            return $this->getResponse(

                [

                    'isError' => true,

                    'message' => $e->getMessage()

                ],

                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR

            );

        }
    }
        /**
     * Daily Close List
     */
    // public function lists()
    // {
    //     try {

    //         $cashierId =
    //             $this->request->getGet('cashier_id');

    //         $status =
    //             $this->request->getGet('status');

    //         $businessDate =
    //             $this->request->getGet('business_date');

    //         $data =
    //             $this->dailyCloseModel->getList(
    //                 $cashierId,
    //                 $status,
    //                 $businessDate
    //             );

    //         return $this->getResponse(
    //             [
    //                 'isError' => false,
    //                 'message' => 'Success',
    //                 'data' => $data
    //             ],
    //             ResponseInterface::HTTP_OK
    //         );

    //     } catch (Exception $e) {

    //         return $this->getResponse(
    //             [
    //                 'isError' => true,
    //                 'message' => $e->getMessage()
    //             ],
    //             ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
    //         );

    //     }
    // }
    public function index()
    {
        try {

            $draw =
                $this->request->getGet('draw') ?? 1;

            $start =
                $this->request->getGet('start') ?? 0;

            $length =
                $this->request->getGet('length') ?? 10;

            $orderColumn =
                $this->request->getGet('orderColumn')
                ?? 'cashier_daily_close_id';

            $orderDir =
                $this->request->getGet('orderDir')
                ?? 'DESC';

            $cashierId =
                $this->request->getGet('cashier_id');

            $status =
                $this->request->getGet('status');

            $businessDate =
                $this->request->getGet('business_date');

            $search =
                $this->request->getGet('search');

            $data =
                $this->dailyCloseModel->getList(

                    $start,

                    $length,

                    $orderColumn,

                    $orderDir,

                    $search,

                    $cashierId,

                    $status,

                    $businessDate

                );

            $total =
                $this->dailyCloseModel->countList(

                    $search,

                    $cashierId,

                    $status,

                    $businessDate

                );

            return $this->response->setJSON([

                "draw" => intval($draw),

                "recordsTotal" => $total,

                "recordsFiltered" => $total,

                "data" => $data

            ]);

        } catch (Exception $e) {

            return $this->response->setJSON([

                "draw" => 0,

                "recordsTotal" => 0,

                "recordsFiltered" => 0,

                "data" => [],

                "isError" => true,

                "message" => $e->getMessage()

            ]);

        }

    }

    /**
     * Daily Close Details
     */
    public function details($id)
    {
        try {

            $header =
                $this->dailyCloseModel
                    ->getDetails($id);

            if (!$header) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );

            }

            $denominations =
                $this->denominationModel
                    ->getByDailyCloseId($id);

            $logs =
                $this->logModel
                    ->getByDailyCloseId($id);

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Success',
                    'data' => [
                        'header' => $header,
                        'denominations' => $denominations,
                        'logs' => $logs
                    ]
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }
        /**
     * Update Daily Close
     */
    public function update($id)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $dailyClose =
                $this->dailyCloseModel
                    ->find($id);

            if (!$dailyClose) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );

            }

            if ($dailyClose['status'] != 'PENDING') {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Only pending daily returns can be updated.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $rules = [

                'expected_cash'   => 'required|decimal',

                'actual_cash'     => 'required|decimal',

                'returned_amount' => 'required|decimal'

            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => array_values(
                            $this->validator->getErrors()
                        )[0]
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $expectedCash =
                (float) $input['expected_cash'];

            $actualCash =
                (float) $input['actual_cash'];

            $variance =
                $actualCash - $expectedCash;

            $this->db->transBegin();

            $update = [

                'expected_cash'   => $expectedCash,

                'actual_cash'     => $actualCash,

                'variance'        => $variance,

                'returned_amount' => $input['returned_amount'],

                'remarks'         => $input['remarks'] ?? null

            ];

            $this->dailyCloseModel->update(
                $id,
                $update
            );
                        /*
            |--------------------------------------------------------------------------
            | Replace Denominations
            |--------------------------------------------------------------------------
            */

            $this->denominationModel->deleteByDailyCloseId($id);

            if (
                isset($input['denominations']) &&
                is_array($input['denominations'])
            ) {

                foreach ($input['denominations'] as $row) {

                    $denomination = (float) ($row['denomination'] ?? 0);
                    $quantity     = (int) ($row['quantity'] ?? 0);

                    if ($quantity <= 0) {
                        continue;
                    }

                    $this->denominationModel->insert([

                        'cashier_daily_close_id' => $id,

                        'denomination' => $denomination,

                        'quantity' => $quantity,

                        'total' => $denomination * $quantity

                    ]);

                }

            }

            /*
            |--------------------------------------------------------------------------
            | Save Audit Log
            |--------------------------------------------------------------------------
            */

            $actionBy =
                $input['updated_by']
                ?? $dailyClose['cashier_id'];

            $this->logModel->addLog(

                $id,

                'UPDATED',

                $actionBy,

                'Daily return updated.'

            );

            /*
            |--------------------------------------------------------------------------
            | Commit / Rollback
            |--------------------------------------------------------------------------
            */

            if ($this->db->transStatus() === false) {

                $this->db->transRollback();

                return $this->getResponse(

                    [

                        'isError' => true,

                        'message' => 'Unable to update daily return.'

                    ],

                    ResponseInterface::HTTP_BAD_REQUEST

                );

            }

            $this->db->transCommit();

            return $this->getResponse(

                [

                    'isError' => false,

                    'message' => 'Daily return updated successfully.',

                    'data' => $this->dailyCloseModel->getDetails($id)

                ],

                ResponseInterface::HTTP_OK

            );

        } catch (Exception $e) {

            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }

            return $this->getResponse(

                [

                    'isError' => true,

                    'message' => $e->getMessage()

                ],

                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR

            );

        }
    }
        /**
     * Approve Daily Close
     */
    public function approve($id)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $dailyClose =
                $this->dailyCloseModel->find($id);

            if (!$dailyClose) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );

            }

            if ($dailyClose['status'] != 'PENDING') {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Only pending daily returns can be approved.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            if (empty($input['approved_by'])) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'approved_by is required.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transBegin();

            $this->dailyCloseModel->update(
                $id,
                [

                    'status'      => 'APPROVED',

                    'approved_by' => $input['approved_by'],

                    'approved_at' => date('Y-m-d H:i:s'),

                    'remarks'     => $input['remarks']
                                        ?? $dailyClose['remarks']

                ]
            );

            $this->logModel->addLog(

                $id,

                'APPROVED',

                $input['approved_by'],

                $input['remarks']
                    ?? 'Daily return approved.'

            );

            if ($this->db->transStatus() === false) {

                $this->db->transRollback();

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Unable to approve daily return.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transCommit();

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Daily return approved successfully.',
                    'data' => $this->dailyCloseModel->getDetails($id)
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }
        /**
     * Reject Daily Close
     */
    public function reject($id)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $dailyClose =
                $this->dailyCloseModel->find($id);

            if (!$dailyClose) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );

            }

            if ($dailyClose['status'] != 'PENDING') {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Only pending daily returns can be rejected.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            if (empty($input['rejected_by'])) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'rejected_by is required.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            if (empty($input['remarks'])) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'remarks is required.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transBegin();

            $this->dailyCloseModel->update(
                $id,
                [

                    'status'        => 'REJECTED',

                    'rejected_by'   => $input['rejected_by'],

                    'rejected_at'   => date('Y-m-d H:i:s'),

                    'remarks'       => $input['remarks']

                ]
            );

            $this->logModel->addLog(

                $id,

                'REJECTED',

                $input['rejected_by'],

                $input['remarks']

            );

            if ($this->db->transStatus() === false) {

                $this->db->transRollback();

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Unable to reject daily return.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transCommit();

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Daily return rejected successfully.',
                    'data' => $this->dailyCloseModel->getDetails($id)
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }
        /**
     * Cancel Daily Close
     */
    public function cancel($id)
    {
        try {

            $input = $this->getRequestInput($this->request);

            $dailyClose =
                $this->dailyCloseModel->find($id);

            if (!$dailyClose) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return not found.'
                    ],
                    ResponseInterface::HTTP_NOT_FOUND
                );

            }

            if ($dailyClose['status'] == 'CANCELLED') {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Daily return is already cancelled.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            if (empty($input['cancelled_by'])) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'cancelled_by is required.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            if (empty($input['cancel_reason'])) {

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'cancel_reason is required.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transBegin();

            $this->dailyCloseModel->update(
                $id,
                [

                    'status'          => 'CANCELLED',

                    'cancelled_by'    => $input['cancelled_by'],

                    'cancelled_at'    => date('Y-m-d H:i:s'),

                    'cancel_reason'   => $input['cancel_reason']

                ]
            );

            $this->logModel->addLog(

                $id,

                'CANCELLED',

                $input['cancelled_by'],

                $input['cancel_reason']

            );

            if ($this->db->transStatus() === false) {

                $this->db->transRollback();

                return $this->getResponse(
                    [
                        'isError' => true,
                        'message' => 'Unable to cancel daily return.'
                    ],
                    ResponseInterface::HTTP_BAD_REQUEST
                );

            }

            $this->db->transCommit();

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Daily return cancelled successfully.',
                    'data' => $this->dailyCloseModel->getDetails($id)
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            if ($this->db->transStatus()) {
                $this->db->transRollback();
            }

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }

    public function getSummary()
    {
        try {

            $cashierId =
                $this->request->getGet('cashier_id');

            $businessDate =
                $this->request->getGet('business_date');

            $status =
                $this->request->getGet('status');

            $data =
                $this->dailyCloseModel->getSummary(

                    $cashierId,

                    $businessDate,

                    $status

                );

            return $this->getResponse(
                [
                    'isError' => false,
                    'message' => 'Success',
                    'data' => $data
                ],
                ResponseInterface::HTTP_OK
            );

        } catch (Exception $e) {

            return $this->getResponse(
                [
                    'isError' => true,
                    'message' => $e->getMessage()
                ],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );

        }
    }

}