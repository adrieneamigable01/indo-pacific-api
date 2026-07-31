<?php

namespace App\Models;

use CodeIgniter\Model;

class BankAccountModel extends Model
{
    protected $table            = 'bank_accounts';
    protected $primaryKey       = 'bank_account_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [

        'bank_id',

        'account_name',
        'account_number',
        'account_type',

        'currency',

        'opening_balance',
        'current_balance',

        'description',

        'is_active',

        'created_by'

    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getAccounts()
    {
        return $this->select('
                bank_accounts.*,
                banks.bank_name
            ')
            ->join(
                'banks',
                'banks.bank_id = bank_accounts.bank_id'
            )
            ->findAll();
    }

    public function getAccount($id)
    {
        return $this->select('
                bank_accounts.*,
                b.bank_name
            ')
            ->join(
                'banks',
                'banks.bank_id = bank_accounts.bank_id'
            )
            ->where(
                'bank_account_id',
                $id
            )
            ->first();
    }

    public function saveBankAccount($data)
    {
        return $this->insert($data);
    }

    public function getAccountDetails($bankAccountId)
    {
        return $this->select("
                bank_accounts.*,
                banks.bank_name,
                banks.branch_name,
                banks.bank_code,
                banks.swift_code,
                banks.address,
                banks.contact_no,
                banks.email
            ")
            ->join(
                'banks',
                'banks.bank_id = bank_accounts.bank_id',
                'left'
            )
            ->where(
                'bank_accounts.bank_account_id',
                $bankAccountId
            )
            ->first();
    }
}