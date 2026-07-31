<?php

namespace App\Models;

use CodeIgniter\Model;

class BankModel extends Model
{
    protected $table = 'banks';

    protected $primaryKey = 'bank_id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [

        'bank_name',

        'bank_code',

        'branch_name',

        'branch_code',

        'swift_code',

        'address',

        'contact_no',

        'email',

        'is_active'

    ];

    protected $useTimestamps = false;

    /**
     * Get active banks
     */
    public function getActiveBanks()
    {
        return $this->select('bank_id, bank_name')
            ->where('is_active', 1)
            ->orderBy('bank_name', 'ASC')
            ->findAll();
    }

    /**
     * Get bank details
     */
    public function getDetails($bankId)
    {
        return $this->where('bank_id', $bankId)
            ->first();
    }
    
}