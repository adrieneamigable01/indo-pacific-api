<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'module',
        'record_id',
        'action',
        'user_id',
        'username',
        'old_data',
        'new_data',
        'remarks',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $useTimestamps = false;

    public function createLog(array $data)
    {
        return $this->insert([
            'module'      => $data['module'] ?? '',
            'record_id'   => $data['record_id'] ?? null,
            'action'      => strtoupper($data['action'] ?? ''),
            'user_id'     => $data['user_id'] ?? null,
            'username'    => $data['username'] ?? null,
            'old_data'    => isset($data['old_data']) ? json_encode($data['old_data']) : null,
            'new_data'    => isset($data['new_data']) ? json_encode($data['new_data']) : null,
            'remarks'     => $data['remarks'] ?? null,
            'ip_address'  => $data['ip_address'] ?? null,
            'user_agent'  => $data['user_agent'] ?? null,
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }

    public function getLogs(
        $search = '',
        $limit = 100,
        $offset = 0
    ) {

        $builder = $this->db->table($this->table);

        if (!empty($search)) {
            $builder->groupStart()
                ->like('module', $search)
                ->orLike('action', $search)
                ->orLike('username', $search)
                ->groupEnd();
        }

        return $builder
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
    }

    public function getLog($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();
    }

    public function getLogsByRecord(
        $module,
        $recordId
    ) {

        return $this->db->table($this->table)
            ->where('module', $module)
            ->where('record_id', $recordId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getLogsByUser($userId)
    {
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function removeLog($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
}