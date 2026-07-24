<?php

namespace App\Models;

use CodeIgniter\Model;

class BorrowerSalaryModel extends Model
{
    protected $table = 'borrower_salary';
    protected $primaryKey = 'salary_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'borrower_id',
        'salary_month',
        'gross_salary',
        'remarks',
        'atm_withdrawal_amount',
        'auto_debit_amount',
        'status'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';


    public function getBorrowerSalaryList(
        $search = '',
        $salaryMonth = null,
        $start = 0,
        $length = 10,
        $orderColumn = 'borrower_name',
        $orderDir = 'ASC'
    )
    {
        $builder = $this->db->table('borrowers b');

        $builder->select("
            b.borrower_id,

            CONCAT(
                b.last_name,
                ', ',
                b.first_name,
                ' ',
                IFNULL(b.middle_name, '')
            ) AS borrower_name,

            s.salary_id,

            s.salary_month,

            IFNULL(s.gross_salary, 0) AS gross_salary,

            IFNULL(s.atm_withdrawal_amount,0) AS atm_withdrawal_amount,

            IFNULL(s.auto_debit_amount,0) AS auto_debit_amount,

            IFNULL(s.remarks, '') AS remarks,

            IFNULL(s.status, 'ACTIVE') AS status
        ");

        if (!empty($salaryMonth)) {

           $builder->join(
                'borrower_salary s',
                "s.borrower_id = b.borrower_id
                AND DATE_FORMAT(s.salary_month, '%Y-%m') = " . $this->db->escape($salaryMonth),
                'left'
            );

        } else {

            $builder->join(
                'borrower_salary s',
                's.borrower_id = b.borrower_id',
                'left'
            );

        }

        if (!empty($search)) {

            $builder->groupStart()
                ->like('b.first_name', $search)
                ->orLike('b.middle_name', $search)
                ->orLike('b.last_name', $search)
                ->groupEnd();

        }

        $columns = [
            'salary_id'     => 's.salary_id',
            'borrower_name' => 'b.last_name',
            'salary_month'  => 's.salary_month',
            'gross_salary'  => 's.gross_salary',
            'status'        => 's.status',
            'remarks'       => 's.remarks'
        ];

        $builder->orderBy(
            $columns[$orderColumn] ?? 'b.last_name',
            $orderDir
        );

        if ((int)$length !== -1) {
            $builder->limit((int)$length, (int)$start);
        }

        return $builder->get()->getResultArray();
    }

    public function getSalaries(
        $search = '',
        $borrowerId = null,
        $start = 0,
        $length = 10,
        $orderColumn = 'salary_id',
        $orderDir = 'DESC'
    ){

        $columns = [

            'salary_id'=>'bs.salary_id',
            'salary_month'=>'bs.salary_month',
            'gross_salary'=>'bs.gross_salary',
            'status'=>'bs.status',
            'created_at'=>'bs.created_at'

        ];

        $orderBy = $columns[$orderColumn] ?? 'bs.salary_id';

        $builder =

            $this->db

            ->table('borrower_salary bs')

            ->select("

                bs.*,

                CONCAT(

                    b.last_name,

                    ', ',

                    b.first_name

                ) borrower_name

            ")

            ->join(

                'borrowers b',

                'b.borrower_id=bs.borrower_id'

            );

        if(!empty($borrowerId)){

            $builder->where(
                'bs.borrower_id',
                $borrowerId
            );

        }

        if(!empty($search)){

            $builder

            ->groupStart()

            ->like(
                'b.first_name',
                $search
            )

            ->orLike(
                'b.last_name',
                $search
            )

            ->orLike(
                'bs.salary_month',
                $search
            )

            ->orLike(
                'bs.status',
                $search
            )

            ->groupEnd();

        }

        $builder

            ->orderBy(
                $orderBy,
                $orderDir
            )

            ->limit(
                $length,
                $start
            );

        return

            $builder

            ->get()

            ->getResultArray();

    }

    public function countSalaries()
    {
        return $this->db
            ->table('borrowers')
            ->countAllResults();
    }

    public function countBorrowers()
    {
        return $this->db
            ->table('borrowers')
            ->countAllResults();
    }

    public function countFilteredBorrowers(
        $search = '',
        $salaryMonth = null
    )
    {
        $builder = $this->db->table('borrowers b');

        if (!empty($salaryMonth)) {

            $builder->join(
                'borrower_salary s',
                's.borrower_id = b.borrower_id
                AND s.salary_month = '.$this->db->escape($salaryMonth),
                'left'
            );

        } else {

            $builder->join(
                'borrower_salary s',
                's.borrower_id = b.borrower_id',
                'left'
            );

        }

        if (!empty($search)) {

            $builder->groupStart();

            $builder->like('b.first_name', $search);

            $builder->orLike('b.middle_name', $search);

            $builder->orLike('b.last_name', $search);

            $builder->groupEnd();

        }

        return $builder->countAllResults();
    }

    public function countFilteredSalaries(
        $search = '',
        $salaryMonth = null
    ) {

        $builder = $this->db->table('borrowers b');

        if (!empty($salaryMonth)) {

            $builder->join(
                'borrower_salary s',
                "s.borrower_id = b.borrower_id
                AND s.salary_month = ".$this->db->escape($salaryMonth),
                'left'
            );

        } else {

            $builder->join(
                'borrower_salary s',
                's.borrower_id = b.borrower_id',
                'left'
            );

        }

        if (!empty($search)) {

            $builder->groupStart();

            $builder->like('b.first_name', $search);

            $builder->orLike('b.last_name', $search);

            $builder->orLike('b.middle_name', $search);

            $builder->groupEnd();

        }

        return $builder->countAllResults();

    }

    public function getSalary($salaryId)
    {

        return

            $this

            ->where(
                'salary_id',
                $salaryId
            )

            ->first();

    }

    

}