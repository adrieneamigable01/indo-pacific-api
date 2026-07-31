<?php

namespace App\Models;

use CodeIgniter\Model;

class BankTransactionModel extends Model
{
    protected $table            = 'bank_transactions';
    protected $primaryKey       = 'bank_transaction_id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'transaction_ref',

        'bank_account_id',

        'transaction_date',

        'transaction_type',

        'transfer_type',

        'destination_bank_account_id',

        'destination_bank_name',

        'destination_account_name',

        'destination_account_number',

        'amount',

        'balance_before',

        'balance_after',

        'reference_no',

        'check_no',

        'description',

        'source',

        'created_by'

    ];

    protected $useTimestamps = true;

    protected $createdField = 'created_at';
    protected $updatedField = '';

    /**
     * Deposit
     */
    public function deposit($data)
    {
        $accountModel = new BankAccountModel();

        $account = $accountModel->find(
            $data['bank_account_id']
        );

        if (!$account) {
            return false;
        }

        $before = $account['current_balance'];

        $after = $before + $data['amount'];

        $data['balance_before'] = $before;
        $data['balance_after'] = $after;
        $data['transaction_type'] = 'DEPOSIT';

        $this->insert($data);

        $accountModel->update(
            $account['bank_account_id'],
            [
                'current_balance' => $after
            ]
        );

        return $this->insertID();
    }

    /**
     * Withdrawal
     */
    public function withdraw($data)
    {
        $accountModel = new BankAccountModel();

        $account = $accountModel->find(
            $data['bank_account_id']
        );

        if (!$account) {
            return false;
        }

        if (
            $account['current_balance'] <
            $data['amount']
        ) {
            return [
                'status' => false,
                'message' => 'Insufficient balance.'
            ];
        }

        $before = $account['current_balance'];

        $after = $before - $data['amount'];

        $data['balance_before'] = $before;
        $data['balance_after'] = $after;
        $data['transaction_type'] = 'WITHDRAWAL';

        $this->insert($data);

        $accountModel->update(
            $account['bank_account_id'],
            [
                'current_balance' => $after
            ]
        );

        return $this->insertID();
    }

    /**
     * Account Ledger
     */
    public function getLedger($bankAccountId)
    {
        return $this->select('
                bank_transactions.*,
                bank_accounts.account_name,
                bank_accounts.account_number,
                banks.bank_name
            ')
            ->join(
                'bank_accounts',
                'bank_accounts.bank_account_id = bank_transactions.bank_account_id'
            )
            ->join(
                'banks',
                'banks.bank_id = bank_accounts.bank_id'
            )
            ->where(
                'bank_transactions.bank_account_id',
                $bankAccountId
            )
            ->orderBy(
                'transaction_date',
                'ASC'
            )
            ->findAll();
    }

    /**
     * Transaction History
     */
    public function getTransactions(
        $start,
        $length,
        $search,
        $orderColumn,
        $orderDir,
        $filters
    )
    {

        $columns = [

            'transaction_date',
            'reference_no',
            'check_no',
            'transaction_type',
            'amount',
            'balance_after'

        ];

        $builder = $this->db
            ->table('bank_transactions bt')

            ->select('
                bt.*
            ');

        if (!empty($filters['bank_account_id'])) {

            $builder->where(
                'bt.bank_account_id',
                $filters['bank_account_id']
            );

        }

        if (!empty($filters['transaction_type'])) {

            $builder->where(
                'bt.transaction_type',
                $filters['transaction_type']
            );

        }

        if (!empty($filters['date_from'])) {

            $builder->where(
                'bt.transaction_date >=',
                $filters['date_from']
            );

        }

        if (!empty($filters['date_to'])) {

            $builder->where(
                'bt.transaction_date <=',
                $filters['date_to']
            );

        }

        if (!empty($search)) {

            $builder->groupStart()

                ->like(
                    'bt.reference_no',
                    $search
                )

                ->orLike(
                    'bt.check_no',
                    $search
                )

                ->orLike(
                    'bt.description',
                    $search
                )

                ->orLike(
                    'bt.transaction_type',
                    $search
                )

            ->groupEnd();

        }

        $filteredBuilder = clone $builder;

        $filtered = $filteredBuilder
            ->countAllResults(false);

        $totalBuilder = $this->db
            ->table('bank_transactions');

        if (!empty($filters['bank_account_id'])) {

            $totalBuilder->where(
                'bank_account_id',
                $filters['bank_account_id']
            );

        }

        $total = $totalBuilder->countAllResults();

        $builder

            ->orderBy(
                $columns[$orderColumn] ?? 'transaction_date',
                $orderDir
            )

            ->limit(
                $length,
                $start
            );

        return [

            'total' => $total,

            'filtered' => $filtered,

            'data' => $builder
                ->get()
                ->getResultArray()

        ];

    }
    public function getServerSide(
            $search,
            $where,
            $start,
            $length,
            $orderColumn,
            $orderDir
        ) {

            $builder = $this->db->table('bank_accounts ba');

            $builder->select("
                ba.*,
                b.bank_name
            ");

            $builder->join(
                'banks b',
                'b.bank_id = ba.bank_id',
                'left'
            );

            /*
            |--------------------------------------------------------------------------
            | SEARCH
            |--------------------------------------------------------------------------
            */

            if (!empty($search)) {

                $builder->groupStart();

                $builder->like('b.bank_name', $search);
                $builder->orLike('ba.account_name', $search);
                $builder->orLike('ba.account_number', $search);
                $builder->orLike('ba.account_type', $search);

                $builder->groupEnd();

            }

            /*
            |--------------------------------------------------------------------------
            | FILTERS
            |--------------------------------------------------------------------------
            */

            if (!empty($where['bank_account_id'])) {
                $builder->where('ba.bank_account_id', $where['bank_account_id']);
            }

            /*
            |--------------------------------------------------------------------------
            | ORDER COLUMN MAPPING
            |--------------------------------------------------------------------------
            */

            $allowedColumns = [
                'bank_account_id' => 'ba.bank_account_id',
                'bank_name'       => 'b.bank_name',
                'account_name'    => 'ba.account_name',
                'account_number'  => 'ba.account_number',
                'account_type'    => 'ba.account_type',
                'currency'        => 'ba.currency',
                'opening_balance' => 'ba.opening_balance',
                'current_balance' => 'ba.current_balance',
                'created_at'      => 'ba.created_at'
            ];

            $orderColumn = $allowedColumns[$orderColumn] ?? 'ba.bank_account_id';

            $builder->orderBy($orderColumn, strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC');

            /*
            |--------------------------------------------------------------------------
            | LIMIT
            |--------------------------------------------------------------------------
            */

            if ($length != -1) {
                $builder->limit($length, $start);
            }

            return $builder->get()->getResultArray();
        }

    public function countTransactions($where = [])
    {
        $builder = $this->db->table('bank_accounts ba');

        $builder->join(
            'banks b',
            'b.bank_id = ba.bank_id',
            'left'
        );

        if (!empty($where['bank_account_id'])) {
            $builder->where('ba.bank_account_id', $where['bank_account_id']);
        }

        return $builder->countAllResults();
    }

    public function countFilteredTransactions($search, $where = [])
    {
        $builder = $this->db->table('bank_accounts ba');

        $builder->join(
            'banks b',
            'b.bank_id = ba.bank_id',
            'left'
        );

        if (!empty($where['bank_account_id'])) {
            $builder->where('ba.bank_account_id', $where['bank_account_id']);
        }

        if (!empty($search)) {

            $builder->groupStart();

            $builder->like('b.bank_name', $search);
            $builder->orLike('ba.account_name', $search);
            $builder->orLike('ba.account_number', $search);
            $builder->orLike('ba.account_type', $search);

            $builder->groupEnd();
        }

        return $builder->countAllResults();
    }
    

    public function recalculateAccountBalance(
        int $bankAccountId
    )
    {
        /*
        |--------------------------------------------------------------------------
        | GET ALL TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        $transactions = $this

            ->where('bank_account_id', $bankAccountId)

            ->orderBy('transaction_date', 'ASC')

            ->orderBy('bank_transaction_id', 'ASC')

            ->findAll();

        /*
        |--------------------------------------------------------------------------
        | START RUNNING BALANCE
        |--------------------------------------------------------------------------
        */

        $runningBalance = 0;

        /*
        |--------------------------------------------------------------------------
        | RECALCULATE ALL TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        foreach ($transactions as $transaction) {

            $balanceBefore = $runningBalance;

            switch ($transaction['transaction_type']) {

                case 'DEPOSIT':

                case 'TRANSFER_IN':

                    $runningBalance += (float)$transaction['amount'];

                    break;

                case 'WITHDRAWAL':

                case 'TRANSFER_OUT':

                    $runningBalance -= (float)$transaction['amount'];

                    break;

            }

            $this->update(

                $transaction['bank_transaction_id'],

                [

                    'balance_before' => $balanceBefore,

                    'balance_after'  => $runningBalance

                ]

            );

        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE ACCOUNT BALANCE
        |--------------------------------------------------------------------------
        */

        $bankAccountModel = new BankAccountModel();

        $bankAccountModel->update(

            $bankAccountId,

            [

                'current_balance' => $runningBalance

            ]

        );

        return true;
    }
    public function generateTransactionRef(string $transactionType): string
    {
        switch (strtoupper($transactionType)) {

            case 'DEPOSIT':
                $prefix = 'DP';
                break;

            case 'WITHDRAWAL':
                $prefix = 'WD';
                break;

            case 'TRANSFER':
            case 'TRANSFER_OUT':
            case 'TRANSFER_IN':
                $prefix = 'TXN';
                break;

            default:
                $prefix = 'TRX';
                break;
        }

        do {

            $reference = sprintf(
                '%s-%s-%06d',
                $prefix,
                date('Ymd'),
                random_int(1, 999999)
            );

        } while (
            $this->where('transaction_ref', $reference)->countAllResults() > 0
        );

        return $reference;
    }
    public function getTransactionDetails($transactionId)
    {
        return $this->db
            ->table('bank_transactions bt')

            ->select("
                bt.*,
                ba.account_name,
                ba.account_number,
                ba.account_type,
                b.bank_name
            ")

            ->join(
                'bank_accounts ba',
                'ba.bank_account_id = bt.bank_account_id'
            )

            ->join(
                'banks b',
                'b.bank_id = ba.bank_id'
            )

            ->where(
                'bt.bank_transaction_id',
                $transactionId
            )

            ->get()

            ->getRowArray();
    }
    public function getDashboardTransactionSummary($bankAccountId)
    {
        $summary = [];

        // Total Deposits
        $summary['total_deposits'] = (float) $this->builder()
            ->selectSum('amount', 'total')
            ->where('bank_account_id', $bankAccountId)
            ->where('transaction_type', 'DEPOSIT')
            ->get()
            ->getRow()
            ->total ?? 0;

        // Total Withdrawals
        $summary['total_withdrawals'] = (float) $this->builder()
            ->selectSum('amount', 'total')
            ->where('bank_account_id', $bankAccountId)
            ->where('transaction_type', 'WITHDRAWAL')
            ->get()
            ->getRow()
            ->total ?? 0;

        // Total Transfers
        $summary['total_transfers'] = (float) $this->builder()
            ->selectSum('amount', 'total')
            ->where('bank_account_id', $bankAccountId)
            ->where('transaction_type', 'TRANSFER_OUT')
            ->get()
            ->getRow()
            ->total ?? 0;

        // Total Transactions
        $summary['total_transactions'] = $this->builder()
            ->where('bank_account_id', $bankAccountId)
            ->countAllResults();

        // Account Details
        $account = $this->db->table('bank_accounts')
            ->select('opening_balance, current_balance, account_type')
            ->where('bank_account_id', $bankAccountId)
            ->get()
            ->getRowArray();

        $summary['opening_balance'] = (float)($account['opening_balance'] ?? 0);
        $summary['current_balance'] = (float)($account['current_balance'] ?? 0);
        $summary['account_type'] = $account['account_type'] ?? '-';

        // Last Transaction
        $lastTransaction = $this->builder()
            ->select('transaction_date')
            ->where('bank_account_id', $bankAccountId)
            ->orderBy('transaction_date', 'DESC')
            ->get()
            ->getRowArray();

        $summary['last_transaction_date'] = $lastTransaction
            ? date('M d, Y h:i A', strtotime($lastTransaction['transaction_date']))
            : '-';

        return $summary;
    }
}