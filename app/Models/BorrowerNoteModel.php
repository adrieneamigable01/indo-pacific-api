<?php

namespace App\Models;

use CodeIgniter\Model;

class BorrowerNoteModel extends Model
{
    protected $table = 'borrower_notes';
    protected $primaryKey = 'note_id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'borrower_id',
        'note',
        'created_by',
        'created_by_name',
        'created_at',
        'updated_at'
    ];

    public function getBorrowerNotes(
        int $borrowerId,
        string $search = ''
    ): array {

        $builder = $this->db
            ->table('borrower_notes')
            ->where('borrower_id', $borrowerId);

        if ($search !== '') {
            $builder->like('note', $search);
        }

        return $builder
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function addBorrowerNote(array $data): int
    {
        $this->insert($data);

        return (int) $this->getInsertID();
    }

    public function deleteBorrowerNote(
        int $noteId,
        int $borrowerId
    ): bool {

        return $this->db
            ->table('borrower_notes')
            ->where('note_id', $noteId)
            ->where('borrower_id', $borrowerId)
            ->delete();
    }
}