<?php

namespace App\Models;

use CodeIgniter\Model;

class IncentiveTypeModel extends Model
{
    protected $table            = 'incentive_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'full_texts',
        'loan_product_id',
        'showInSelection'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[255]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Incentive type name is required.'
        ]
    ];

    /**
     * Get incentive type list for DataTable
     */
    public function getIncentiveTypeList($search = '', $start = 0, $length = 10, $orderColumn = 'id', $orderDir = 'ASC')
    {
        $builder = $this->db->table($this->table);

        $builder->select('*');

        if (!empty($search)) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('full_texts', $search);
            $builder->groupEnd();
        }

        // Validate order column
        $allowedColumns = ['id', 'name', 'full_texts', 'loan_product_id', 'showInSelection'];
        if (!in_array($orderColumn, $allowedColumns)) {
            $orderColumn = 'id';
        }

        $builder->orderBy($orderColumn, $orderDir);
        $builder->limit($length, $start);

        return $builder->get()->getResultArray();
    }

    /**
     * Count all incentive types
     */
    public function countAll()
    {
        return $this->countAllResults();
    }

    /**
     * Count filtered incentive types
     */
    public function countFiltered($search = '')
    {
        $builder = $this->db->table($this->table);

        if (!empty($search)) {
            $builder->groupStart();
            $builder->like('name', $search);
            $builder->orLike('full_texts', $search);
            $builder->groupEnd();
        }

        return $builder->countAllResults();
    }

    public function getForDropdown()
    {
        return $this->select('id, name')
            ->where('showInSelection', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}