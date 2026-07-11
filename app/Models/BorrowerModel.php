<?php

namespace App\Models;

use CodeIgniter\Model;

class BorrowerModel extends Model
{
    protected $table = 'borrowers';
    protected $primaryKey = 'borrower_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'last_name',
        'first_name',
        'middle_name',
        'date_of_birth',
        'civil_status',
        'gender',
        'mobile_no',
        'email_address',
        'home_address',
        'created_at',
        'isActive'
    ];

    public function getBorrowers($search = '',$borrower_id = '')
    {
        $builder =  $this->db->table('borrowers b')
            ->select("
                b.*,
                bc.tin_no,
                bc.id_presented,
                bc.id_no,
                be.company_school,
                be.employer_name,
                be.company_address,
                be.employment_date,
                be.position_name,
                be.basic_salary,
                be.annual_income,
                bs.spouse_id,
                bs.last_name as spouse_last_name,
                bs.first_name as spouse_first_name,
                bs.middle_name as spouse_middle_name,
                bs.date_of_birth as spouse_date_of_birth,
                bs.mobile_no as spouse_mobile_no,
                bs.employer_name as spouse_employer_name,
                bs.position_name as spouse_position_name,
                bs.monthly_income,
                bs.home_address as spouse_home_address
            ")
            ->join('borrower_credentials bc', 'bc.borrower_id=b.borrower_id', 'left')
            ->join('borrower_employment be', 'be.borrower_id=b.borrower_id', 'left')
            ->join('borrower_spouses bs', 'bs.borrower_id=b.borrower_id', 'left');

       
        
        if (!empty($search)) {

            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('mobile_no', $search)
                ->orLike('email_address', $search)
                ->groupEnd();
        }
        
        if (!empty($borrower_id)) {
           $builder->where('b.borrower_id',$borrower_id);
        }
        $builder->where('isActive', 1);

        return $builder->get()->getResultArray();
    }

    public function getBorrowerDetails($borrowerId)
    {
        return $this->db->table('borrowers b')
            ->select("
                b.*,
                bc.tin_no,
                bc.id_presented,
                bc.id_no,
                be.company_school,
                be.employer_name,
                be.company_address,
                be.employment_date,
                be.position_name,
                be.basic_salary,
                be.annual_income,
                bs.spouse_id,
                bs.last_name as spouse_last_name,
                bs.first_name as spouse_first_name,
                bs.middle_name as spouse_middle_name,
                bs.date_of_birth as spouse_date_of_birth,
                bs.mobile_no as spouse_mobile_no,
                bs.employer_name as spouse_employer_name,
                bs.position_name as spouse_position_name,
                bs.monthly_income,
                bs.home_address as spouse_home_address
            ")
            ->join('borrower_credentials bc', 'bc.borrower_id=b.borrower_id', 'left')
            ->join('borrower_employment be', 'be.borrower_id=b.borrower_id', 'left')
            ->join('borrower_spouses bs', 'bs.borrower_id=b.borrower_id', 'left')
            ->where('b.borrower_id', $borrowerId)
            ->get()
            ->getRowArray();
    }
    public function getSummary()
    {

        $builder = $this->db
            ->table($this->table);

        $builder->select("
            SUM(CASE WHEN isActive= 1 THEN 1 ELSE 0 END) AS total_borrowers,
            SUM(CASE WHEN status='ACTIVE' && isActive= 1 THEN 1 ELSE 0 END) AS active_borrowers,
            SUM(CASE WHEN status='BLACKLISTED' && isActive= 1 THEN 1 ELSE 0 END) AS blacklisted,
            SUM(CASE WHEN status='FULLY PAID' && isActive= 1 THEN 1 ELSE 0 END) AS fullypaid,
            SUM(CASE WHEN status='DELINQUENT' && isActive= 1 THEN 1 ELSE 0 END) AS delinquent
        ");

        return $builder
            ->get()
            ->getRowArray();

    }
}