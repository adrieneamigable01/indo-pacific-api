<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\IncentiveTypeModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class IncentiveType extends BaseController
{
    protected $incentiveTypeModel;

    public function __construct()
    {
        $this->incentiveTypeModel = new IncentiveTypeModel();
    }

    public function get()
    {
        try {

            $draw   = (int) $this->request->getGet('draw');
            $start  = (int) $this->request->getGet('start');
            $length = (int) $this->request->getGet('length');

            // Support both DataTables search[value] and custom search
            $search = '';

            $searchParam = $this->request->getGet('search');

            if (is_array($searchParam)) {
                $search = $searchParam['value'] ?? '';
            } else {
                $search = $searchParam ?? '';
            }

            $orderColumn = $this->request->getGet('orderColumn') ?? 'id';
            $orderDir    = strtoupper($this->request->getGet('orderDir') ?? 'ASC');

            if (!in_array($orderDir, ['ASC', 'DESC'])) {
                $orderDir = 'ASC';
            }

            $data = $this->incentiveTypeModel->getIncentiveTypeList(
                $search,
                $start,
                $length,
                $orderColumn,
                $orderDir
            );

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $this->incentiveTypeModel->countAll(),
                'recordsFiltered' => $this->incentiveTypeModel->countFiltered($search),
                'data'            => $data
            ]);

        } catch (\Throwable $e) {

            return $this->response->setJSON([
                'draw'            => 0,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage()
            ]);

        }
    }

    public function details($incentiveTypeId)
    {
        try {

            $incentiveType = $this->incentiveTypeModel->find($incentiveTypeId);

            if (empty($incentiveType)) {
                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Incentive type not found.'
                ]);
            }

            return $this->getResponse([
                'isError' => false,
                'data'    => $incentiveType
            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);

        }
    }

    public function save()
    {
        try {

            $input = $this->getRequestInput($this->request);

            $rules = [
                'name' => 'required|max_length[255]'
            ];

            if (!$this->validateRequest($input, $rules)) {

                return $this->getResponse([
                    'isError' => true,
                    'message' => current($this->validator->getErrors())
                ]);

            }

            $data = [
                'name'              => $this->request->getPost('name'),
                'full_texts'        => $this->request->getPost('full_texts') ?? null,
                'loan_product_id'   => !empty($this->request->getPost('loan_product_id')) 
                    ? (int) $this->request->getPost('loan_product_id') 
                    : null,
                'showInSelection'   => (int) ($this->request->getPost('showInSelection') ?? 1)
            ];

            $incentiveTypeId = $this->request->getPost('id');

            if (empty($incentiveTypeId)) {

                // Check for duplicate name
                $existing = $this->incentiveTypeModel
                    ->where('name', $data['name'])
                    ->first();

                if ($existing) {
                    return $this->getResponse([
                        'isError' => true,
                        'message' => 'Incentive type name already exists.'
                    ]);
                }

                $this->incentiveTypeModel->insert($data);

            } else {

                // Check for duplicate name (excluding current record)
                $existing = $this->incentiveTypeModel
                    ->where('name', $data['name'])
                    ->where('id !=', $incentiveTypeId)
                    ->first();

                if ($existing) {
                    return $this->getResponse([
                        'isError' => true,
                        'message' => 'Incentive type name already exists.'
                    ]);
                }

                $this->incentiveTypeModel->update($incentiveTypeId, $data);

            }

            return $this->getResponse([
                'isError' => false,
                'message' => 'Incentive type saved successfully.'
            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);

        }
    }

    public function delete($incentiveTypeId)
    {
        try {

            // Check if incentive type exists
            $incentiveType = $this->incentiveTypeModel->find($incentiveTypeId);

            if (empty($incentiveType)) {
                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Incentive type not found.'
                ]);
            }

            // Check if incentive type is being used
            $db = \Config\Database::connect();
            $usageCount = $db->table('borrower_incentive')
                ->where('incentive_type', $incentiveType['name'])
                ->countAllResults();

            if ($usageCount > 0) {
                return $this->getResponse([
                    'isError' => true,
                    'message' => 'Cannot delete incentive type. It is being used in ' . $usageCount . ' incentive record(s).'
                ]);
            }

            $this->incentiveTypeModel->delete($incentiveTypeId);

            return $this->getResponse([
                'isError' => false,
                'message' => 'Incentive type successfully deleted.'
            ]);

        } catch (\Throwable $e) {

            return $this->getResponse([
                'isError' => true,
                'message' => $e->getMessage()
            ]);

        }
    }

    public function getForDropdown()
    {
        try {
            $types = $this->incentiveTypeModel->getForDropdown();
            
            return $this->response->setJSON([
                'isError' => false,
                'data'    => $types
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

}

