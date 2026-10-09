<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\BorrowerIncentiveModel;
use Exception;

class BorrowerIncentive extends BaseController
{
    protected $incentiveModel;

    public function __construct()
    {
        $this->incentiveModel =
            new BorrowerIncentiveModel();
    }

    /*
    |--------------------------------------------------------------------------
    | GET INCENTIVE LIST
    |--------------------------------------------------------------------------
    */

    public function get()
    {
        try {

            $draw   = (int) $this->request->getGet('draw');
            $start  = (int) $this->request->getGet('start');
            $length = (int) $this->request->getGet('length');

            /*
            |--------------------------------------------------------------------------
            | SEARCH
            |--------------------------------------------------------------------------
            */

            $search = '';

            $searchParam = $this->request->getGet('search');

            if (is_array($searchParam)) {

                $search = $searchParam['value'] ?? '';

            } else {

                $search = $searchParam ?? '';

            }

            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            $incentiveMonth = $this->request->getGet('incentive_month');

            $borrowerId = $this->request->getGet('borrower_id');

            $incentiveTypeId =
                $this->request->getGet('incentive_type_id');

            $status =
                $this->request->getGet('status');

            /*
            |--------------------------------------------------------------------------
            | ORDERING
            |--------------------------------------------------------------------------
            */

            $orderColumn =
                $this->request->getGet('orderColumn')
                ?? 'borrower_name';

            $orderDir = strtoupper(
                $this->request->getGet('orderDir')
                ?? 'ASC'
            );

            if (!in_array($orderDir, ['ASC', 'DESC'])) {
                $orderDir = 'ASC';
            }

            /*
            |--------------------------------------------------------------------------
            | GET DATA
            |--------------------------------------------------------------------------
            */

            $data = $this->incentiveModel
                ->getBorrowerIncentiveList(
                    $search,
                    $incentiveMonth,
                    $borrowerId,
                    $incentiveTypeId,
                    $status,
                    $start,
                    $length,
                    $orderColumn,
                    $orderDir
                );

            return $this->response->setJSON([

                'draw' => $draw,

                'recordsTotal' => $this->incentiveModel
                    ->countBorrowers(
                        $incentiveTypeId
                    ),

                'recordsFiltered' => $this->incentiveModel
                    ->countFilteredBorrowers(
                        $search,
                        $incentiveMonth,
                        $borrowerId,
                        $incentiveTypeId,
                        $status
                    ),

                'data' => $data

            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([

                'draw' => 0,

                'recordsTotal' => 0,

                'recordsFiltered' => 0,

                'data' => [],

                'error' => $e->getMessage()

            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET INCENTIVE DETAILS
    |--------------------------------------------------------------------------
    */

    public function details($incentiveId)
    {
        try {

            $data = $this->incentiveModel
                ->getIncentive($incentiveId);

            if (!$data) {

                return $this->getResponse([

                    'isError' => true,

                    'message' => 'Incentive record not found.'

                ]);

            }

            return $this->getResponse([

                'isError' => false,

                'data' => $data

            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE SINGLE INCENTIVE
    |--------------------------------------------------------------------------
    */

    public function save()
    {
        try {

            $input = $this->getRequestInput(
                $this->request
            );

            $rules = [

                'borrower_id' =>
                    'required|numeric',

                'incentive_type_id' =>
                    'required|numeric',

                'incentive_month' =>
                    'required',

                'incentive_amount' =>
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
                        $this->validator->getErrors()
                    )

                ]);

            }

            $incentiveId =
                $input['incentive_id'] ?? null;

            $borrowerId =
                (int) ($input['borrower_id'] ?? 0);

            $incentiveTypeId =
                (int) ($input['incentive_type_id'] ?? 0);

            /*
            |--------------------------------------------------------------------------
            | Convert 2026-03 into 2026-03-20
            |--------------------------------------------------------------------------
            */

            $incentiveMonth = date(
                'Y-m-20',
                strtotime(
                    ($input['incentive_month'] ?? '') . '-01'
                )
            );

            $incentiveAmount =
                (float) ($input['incentive_amount'] ?? 0);

            if ($incentiveAmount < 0) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Incentive amount cannot be negative.'

                ]);

            }

            $data = [

                'borrower_id' =>
                    $borrowerId,

                'incentive_type_id' =>
                    $incentiveTypeId,

                'incentive_month' =>
                    $incentiveMonth,

                'incentive_amount' =>
                    $incentiveAmount,

                'remarks' =>
                    trim($input['remarks'] ?? ''),

                'status' =>
                    $input['status'] ?? 'ACTIVE'

            ];

            /*
            |--------------------------------------------------------------------------
            | INSERT / UPDATE
            |--------------------------------------------------------------------------
            */

            if (empty($incentiveId)) {

                $existing = $this->incentiveModel
                    ->where('borrower_id', $borrowerId)
                    ->where('incentive_type_id', $incentiveTypeId)
                    ->where('incentive_month', $incentiveMonth)
                    ->first();

                if ($existing) {

                    $this->incentiveModel->update(
                        $existing['incentive_id'],
                        $data
                    );

                } else {

                    $this->incentiveModel->insert($data);

                }

            } else {

                $this->incentiveModel->update(
                    $incentiveId,
                    $data
                );

            }

            if (!empty($this->incentiveModel->errors())) {

                return $this->getResponse([

                    'isError' => true,

                    'message' => implode(
                        ', ',
                        $this->incentiveModel->errors()
                    )

                ]);

            }

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Incentive saved successfully.'

            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE INCENTIVE
    |--------------------------------------------------------------------------
    */

    public function delete($incentiveId)
    {
        try {

            $incentive = $this->incentiveModel
                ->find($incentiveId);

            if (!$incentive) {

                return $this->getResponse([

                    'isError' => true,

                    'message' =>
                        'Incentive record not found.'

                ]);

            }

            $this->incentiveModel->delete(
                $incentiveId
            );

            return $this->getResponse([

                'isError' => false,

                'message' =>
                    'Incentive successfully deleted.'

            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | BULK SAVE INCENTIVES
    |--------------------------------------------------------------------------
    */

    public function bulkSave()
    {
        $db = null;

        try {

            /*
            |--------------------------------------------------------------------------
            | DEBUG: Log received data
            |--------------------------------------------------------------------------
            */
            log_message('debug', '=== BULK SAVE DEBUG START ===');
            log_message('debug', 'Content-Type: ' . $this->request->getHeaderLine('Content-Type'));
            log_message('debug', 'Raw Body: ' . $this->request->getBody());
            log_message('debug', 'POST Data: ' . print_r($_POST, true));
            
            if (isset($_POST['incentives']) && is_array($_POST['incentives'])) {
                log_message('debug', 'Incentives count: ' . count($_POST['incentives']));
                if (count($_POST['incentives']) > 0) {
                    log_message('debug', 'First incentive: ' . print_r($_POST['incentives'][0], true));
                }
            }
            
            log_message('debug', '=== BULK SAVE DEBUG END ===');

            /*
            |--------------------------------------------------------------------------
            | Get input - supports both JSON and form-encoded POST
            |--------------------------------------------------------------------------
            */
            
            $input = null;
            
            $contentType = $this->request->getHeaderLine('Content-Type');
            
            if (strpos($contentType, 'application/json') !== false) {
                $rawBody = $this->request->getBody();
                $input = json_decode($rawBody, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response->setJSON([
                        'isError' => true,
                        'message' => 'Invalid JSON: ' . json_last_error_msg()
                    ]);
                }
            }
            
            if (empty($input)) {
                $input = $this->request->getPost();
            }

            if (empty($input)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Invalid request. No data received.'
                ]);
            }

            $incentiveMonthInput = $input['incentive_month'] ?? '';
            $incentives = $input['incentives'] ?? [];

            if (empty($incentiveMonthInput)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Incentive month is required.'
                ]);
            }

            if (empty($incentives)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'No incentive records found.'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Convert 2026-03 to 2026-03-20
            |--------------------------------------------------------------------------
            */
            $incentiveMonth = date('Y-m-20', strtotime($incentiveMonthInput . '-01'));

            if (!$incentiveMonth) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Invalid incentive month format.'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            $updatedCount = 0;
            $insertedCount = 0;
            $skippedCount = 0;

            foreach ($incentives as $index => $incentive) {

                $incentiveId = $incentive['incentive_id'] ?? null;
                $borrowerId = (int) ($incentive['borrower_id'] ?? 0);
                $incentiveTypeId = (int) ($incentive['incentive_type_id'] ?? 0);
                $incentiveAmount = (float) ($incentive['incentive_amount'] ?? 0);

                // Handle empty incentive_id
                if ($incentiveId === '' || $incentiveId === 'null' || $incentiveId === null) {
                    $incentiveId = null;
                }

                if ($borrowerId <= 0) {
                    throw new Exception('Invalid borrower ID at record #' . ($index + 1));
                }

                if ($incentiveAmount < 0) {
                    throw new Exception('Incentive amount cannot be negative at record #' . ($index + 1));
                }

                /*
                |--------------------------------------------------------------------------
                | CASE 1: UPDATE existing record (incentive_id is provided)
                |--------------------------------------------------------------------------
                */
                if (!empty($incentiveId)) {

                    $incentiveId = (int) $incentiveId;

                    // Check if record exists
                    $existingRecord = $this->incentiveModel->find($incentiveId);
                    
                    if (!$existingRecord) {
                        throw new Exception('Record #' . ($index + 1) . ': incentive_id ' . $incentiveId . ' not found');
                    }

                    $data = [
                        'borrower_id' => $borrowerId,
                        'incentive_month' => $incentiveMonth,
                        'incentive_amount' => $incentiveAmount,
                        'remarks' => trim($incentive['remarks'] ?? ''),
                        'status' => $incentive['status'] ?? 'PENDING'
                        // ❌ DO NOT change incentive_type_id on UPDATE
                    ];

                    $this->incentiveModel->update($incentiveId, $data);

                    if (!empty($this->incentiveModel->errors())) {
                        throw new Exception('Record #' . ($index + 1) . ': ' . implode(', ', $this->incentiveModel->errors()));
                    }

                    $updatedCount++;
                    log_message('debug', 'Updated record #' . $index . ' with incentive_id=' . $incentiveId);

                } 
                /*
                |--------------------------------------------------------------------------
                | CASE 2: INSERT new record (no incentive_id, but has incentive_type_id)
                |--------------------------------------------------------------------------
                */
                else {

                    // ✅ incentive_type_id is REQUIRED for INSERT
                    if ($incentiveTypeId <= 0) {
                        // Try to get existing incentive_type_id from database for this borrower+month
                        $existingForBorrower = $this->incentiveModel
                            ->where('borrower_id', $borrowerId)
                            ->where('incentive_month', $incentiveMonth)
                            ->first();

                        if ($existingForBorrower) {
                            // Use existing incentive_type_id
                            $incentiveTypeId = (int) $existingForBorrower['incentive_type_id'];
                            log_message('debug', 'Using existing incentive_type_id=' . $incentiveTypeId . ' for borrower ' . $borrowerId);
                        } else {
                            throw new Exception('Record #' . ($index + 1) . ': incentive_type_id is required for new records');
                        }
                    }

                    // Check for duplicate: borrower + type + month
                    $existingDuplicate = $this->incentiveModel
                        ->where('borrower_id', $borrowerId)
                        ->where('incentive_type_id', $incentiveTypeId)
                        ->where('incentive_month', $incentiveMonth)
                        ->first();

                    if ($existingDuplicate) {
                        // ✅ UPDATE existing instead of INSERT
                        $data = [
                            'borrower_id' => $borrowerId,
                            'incentive_month' => $incentiveMonth,
                            'incentive_type_id' => $incentiveTypeId,
                            'incentive_amount' => $incentiveAmount,
                            'remarks' => trim($incentive['remarks'] ?? ''),
                            'status' => $incentive['status'] ?? 'PENDING'
                        ];

                        $this->incentiveModel->update($existingDuplicate['incentive_id'], $data);

                        if (!empty($this->incentiveModel->errors())) {
                            throw new Exception('Record #' . ($index + 1) . ': ' . implode(', ', $this->incentiveModel->errors()));
                        }

                        $updatedCount++;
                        log_message('debug', 'Updated existing duplicate record #' . $index . ' with incentive_id=' . $existingDuplicate['incentive_id']);

                    } else {
                        // ✅ INSERT new record
                        $data = [
                            'borrower_id' => $borrowerId,
                            'incentive_month' => $incentiveMonth,
                            'incentive_type_id' => $incentiveTypeId,
                            'incentive_amount' => $incentiveAmount,
                            'remarks' => trim($incentive['remarks'] ?? ''),
                            'status' => $incentive['status'] ?? 'PENDING'
                        ];

                        $this->incentiveModel->insert($data);

                        if (!empty($this->incentiveModel->errors())) {
                            throw new Exception('Record #' . ($index + 1) . ': ' . implode(', ', $this->incentiveModel->errors()));
                        }

                        $insertedCount++;
                        log_message('debug', 'Inserted new record #' . $index . ' for borrower ' . $borrowerId);
                    }

                }

            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Unable to save incentive records. Database transaction failed.'
                ]);
            }

            $db->transCommit();

            $message = 'Successfully processed ' . ($updatedCount + $insertedCount) . ' record(s).';
            $details = [];
            
            if ($updatedCount > 0) {
                $details[] = $updatedCount . ' updated';
            }
            if ($insertedCount > 0) {
                $details[] = $insertedCount . ' inserted';
            }
            if ($skippedCount > 0) {
                $details[] = $skippedCount . ' skipped';
            }
            
            if (!empty($details)) {
                $message .= ' (' . implode(', ', $details) . ')';
            }

            return $this->response->setJSON([
                'isError' => false,
                'message' => $message,
                'updated' => $updatedCount,
                'inserted' => $insertedCount,
                'skipped' => $skippedCount
            ]);

        } catch (\Throwable $e) {

            if ($db !== null && $db->transStatus() === false) {
                $db->transRollback();
            }

            log_message('error', 'BulkSave Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | INCENTIVE SUMMARY
    |--------------------------------------------------------------------------
    */

    public function summary()
    {
        try {

            $incentiveMonth =
                $this->request->getGet(
                    'incentive_month'
                );

            $incentiveTypeId =
                $this->request->getGet(
                    'incentive_type_id'
                );

            if (empty($incentiveMonth)) {

                return $this->response->setJSON([

                    'isError' => true,

                    'message' =>
                        'Incentive month is required.'

                ]);

            }

            /*
            |--------------------------------------------------------------------------
            | Must match your saved date format
            |--------------------------------------------------------------------------
            */

            $incentiveMonth = date(
                'Y-m-20',
                strtotime($incentiveMonth . '-01')
            );

            $db = db_connect();

            /*
            |--------------------------------------------------------------------------
            | TOTAL ACTIVE BORROWERS
            |--------------------------------------------------------------------------
            */

            $totalBorrowers = $db
                ->table('borrowers')
                ->where('isActive', 1)
                ->countAllResults();

            /*
            |--------------------------------------------------------------------------
            | INCENTIVE RECORD SUMMARY
            |--------------------------------------------------------------------------
            */

            $builder = $db
                ->table('borrower_incentives bi');

            $builder->select('
                COUNT(DISTINCT bi.borrower_id) AS withIncentive,
                COALESCE(
                    SUM(bi.incentive_amount),
                    0
                ) AS totalIncentive
            ');

            $builder->where(
                'bi.incentive_month',
                $incentiveMonth
            );

            $builder->where(
                'bi.status',
                'ACTIVE'
            );

            if (!empty($incentiveTypeId)) {

                $builder->where(
                    'bi.incentive_type_id',
                    $incentiveTypeId
                );

            }

            $summary = $builder
                ->get()
                ->getRowArray();

            $withIncentive = (int) (
                $summary['withIncentive'] ?? 0
            );

            return $this->response->setJSON([

                'isError' => false,

                'data' => [

                    'totalBorrowers' =>
                        $totalBorrowers,

                    'withIncentive' =>
                        $withIncentive,

                    'withoutIncentive' =>
                        max(
                            0,
                            $totalBorrowers - $withIncentive
                        ),

                    'totalIncentive' =>
                        (float) (
                            $summary['totalIncentive']
                            ?? 0
                        )

                ]

            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([

                'isError' => true,

                'message' => $e->getMessage()

            ]);

        }
    }
}