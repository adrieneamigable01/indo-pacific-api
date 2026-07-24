<?php

namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\BorrowerSalaryModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class BorrowerSalary extends BaseController
{
    protected $salaryModel;

    public function __construct()
    {
        $this->salaryModel =
            new BorrowerSalaryModel();
    }

    // public function get()
    // {

    //     try{

    //         $draw = (int)$this->request->getGet('draw');
    //         $start = (int)$this->request->getGet('start');
    //         $length = (int)$this->request->getGet('length');

    //         $search = $this->request->getGet('search');

    //         $borrowerId = $this->request->getGet('borrower_id');

    //         $orderColumn = $this->request->getGet('orderColumn') ?? 'salary_id';

    //         $orderDir = $this->request->getGet('orderDir') ?? 'DESC';

    //         $data =

    //             $this->salaryModel

    //             ->getSalaries(

    //                 $search,

    //                 $borrowerId,

    //                 $start,

    //                 $length,

    //                 $orderColumn,

    //                 $orderDir

    //             );

    //         return $this->response->setJSON([

    //             'draw'=>$draw,

    //             'recordsTotal'=>

    //                 $this->salaryModel

    //                 ->countSalaries($borrowerId),

    //             'recordsFiltered'=>

    //                 $this->salaryModel

    //                 ->countFilteredSalaries(

    //                     $search,

    //                     $borrowerId

    //                 ),

    //             'data'=>$data

    //         ]);

    //     }catch(Exception $e){

    //         return $this->response->setJSON([

    //             'draw'=>0,

    //             'recordsTotal'=>0,

    //             'recordsFiltered'=>0,

    //             'data'=>[],

    //             'error'=>$e->getMessage()

    //         ]);

    //     }

    // }

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

            $salaryMonth = $this->request->getGet('salary_month');

            $orderColumn = $this->request->getGet('orderColumn') ?? 'borrower_name';
            $orderDir    = strtoupper($this->request->getGet('orderDir') ?? 'ASC');

            if (!in_array($orderDir, ['ASC', 'DESC'])) {
                $orderDir = 'ASC';
            }

            $data = $this->salaryModel->getBorrowerSalaryList(
                $search,
                $salaryMonth,
                $start,
                $length,
                $orderColumn,
                $orderDir
            );

            return $this->response->setJSON([
                'draw'            => $draw,
                'recordsTotal'    => $this->salaryModel->countBorrowers(),
                'recordsFiltered' => $this->salaryModel->countFilteredBorrowers(
                    $search,
                    $salaryMonth
                ),
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

    public function details($salaryId)
    {

        return $this->getResponse([

            'isError'=>false,

            'data'=>

                $this->salaryModel

                ->getSalary($salaryId)

        ]);

    }

    public function save()
    {

        try{

            $input =
                $this->getRequestInput(
                    $this->request
                );

            $rules=[

                'borrower_id'=>'required|numeric',

                'salary_month'=>'required',

                'gross_salary'=>'required|decimal'

            ];

            if(
                !$this->validateRequest(
                    $input,
                    $rules
                )
            ){

                return $this->getResponse([

                    'isError'=>true,

                    'message'=>current(
                        $this->validator->getErrors()
                    )

                ]);

            }


            $grossSalary = (float)$this->request->getPost('gross_salary');
            $atmAmount   = (float)$this->request->getPost('atm_withdrawal_amount');
            $autoDebit   = (float)$this->request->getPost('auto_debit_amount');

            if (($atmAmount + $autoDebit) != $grossSalary) {

                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'ATM Withdrawal + Auto Debit must equal Gross Salary.'
                ]);

            }

            $data = [

                'borrower_id' => $this->request->getPost('borrower_id'),

                'salary_month' => $this->request->getPost('salary_month'),

                'gross_salary' => $grossSalary,

                'atm_withdrawal_amount' => $atmAmount,

                'auto_debit_amount' => $autoDebit,

                'remarks' => $this->request->getPost('remarks'),

                'status' => $this->request->getPost('status')

            ];

            if (empty($this->request->getPost('salary_id'))) {

                $this->salaryModel->insert($data);

            } else {

                $this->salaryModel->update(
                    $this->request->getPost('salary_id'),
                    $data
                );

            }

            return $this->response->setJSON([
                'isError' => false,
                'message' => 'Salary saved successfully.'
            ]);


        }catch(Exception $e){

            return $this->getResponse([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }

    }

    public function delete($salaryId)
    {

        try{

            $this->salaryModel->delete($salaryId);

            return $this->getResponse([

                'isError'=>false,

                'message'=>'Salary successfully deleted.'

            ]);

        }catch(Exception $e){

            return $this->getResponse([

                'isError'=>true,

                'message'=>$e->getMessage()

            ]);

        }

    }

    public function bulkSave()
    {
        try {

        

           $input = $this->request->getPost();

           

            if (empty($input)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Invalid request.'
                ]);
            }

            $salaryMonth = !empty($input['salary_month'])
            ? date('Y-m-20', strtotime($input['salary_month'] . '-01'))
            : null;
            $salaries = $input['salaries'] ?? [];

            if (empty($salaryMonth)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Salary month is required.'
                ]);
            }

            if (empty($salaries)) {
                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'No salary records found.'
                ]);
            }

            $db = \Config\Database::connect();
            $db->transBegin();

            foreach ($salaries as $salary) {

                $borrowerId = (int)($salary['borrower_id'] ?? 0);
                $salaryId   = $salary['salary_id'] ?? null;

                $atmAmount  = (float)($salary['atm_withdrawal_amount'] ?? 0);
                $autoDebit  = (float)($salary['auto_debit_amount'] ?? 0);

                $grossSalary = $atmAmount + $autoDebit;

                $data = [
                    'borrower_id'            => $borrowerId,
                    'salary_month'           => $salaryMonth,
                    'gross_salary'           => $grossSalary,
                    'atm_withdrawal_amount'  => $atmAmount,
                    'auto_debit_amount'      => $autoDebit,
                    'remarks'                => trim($salary['remarks'] ?? ''),
                    'status'                 => $salary['status'] ?? 'ACTIVE'
                ];

                if (!empty($salaryId)) {

                    $this->salaryModel->update($salaryId, $data);

                } else {

                    // Prevent duplicate borrower/month
                    $existing = $this->salaryModel
                        ->where('borrower_id', $borrowerId)
                        ->where('salary_month', $salaryMonth)
                        ->first();

                    if ($existing) {

                        $this->salaryModel->update(
                            $existing['salary_id'],
                            $data
                        );

                    } else {

                        $this->salaryModel->insert($data);

                    }
                }

                if (!empty($this->salaryModel->errors())) {

                    $db->transRollback();

                    return $this->response->setJSON([
                        'isError' => true,
                        'message' => implode(', ', $this->salaryModel->errors())
                    ]);
                }
            }

            $db->transCommit();

            return $this->response->setJSON([
                'isError' => false,
                'message' => 'All salary records have been saved successfully.'
            ]);

        } catch (\Throwable $e) {

            if (isset($db) && $db->transStatus()) {
                $db->transRollback();
            }

            return $this->response->setJSON([
                'isError' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function summary()
    {
        try {

            $salaryMonth = $this->request->getGet('salary_month');

            if (empty($salaryMonth)) {

                return $this->response->setJSON([
                    'isError' => true,
                    'message' => 'Salary month is required.'
                ]);

            }

            $salaryMonth = $salaryMonth . '-20';

            $db = db_connect();

            // Total Borrowers
            $totalBorrowers = $db->table('borrowers')
                ->where('isActive', 1)
                ->countAllResults();

            // Salary records for selected month
            $salaryBuilder = $db->table('borrower_salary');

            $salaryBuilder->select('
                COUNT(*) AS withSalary,
                SUM(gross_salary) AS totalGrossSalary,
                SUM(atm_withdrawal_amount) AS totalATM,
                SUM(auto_debit_amount) AS totalAutoDebit
            ');

            $salaryBuilder->where('salary_month', $salaryMonth);

            $summary = $salaryBuilder->get()->getRowArray();

            $withSalary = (int)($summary['withSalary'] ?? 0);

            return $this->response->setJSON([

                'isError' => false,

                'data' => [

                    'totalBorrowers' => $totalBorrowers,

                    'withSalary' => $withSalary,

                    'withoutSalary' => $totalBorrowers - $withSalary,

                    'totalGrossSalary' => (float)($summary['totalGrossSalary'] ?? 0),

                    'totalATM' => (float)($summary['totalATM'] ?? 0),

                    'totalAutoDebit' => (float)($summary['totalAutoDebit'] ?? 0)

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