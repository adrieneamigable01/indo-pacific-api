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
        'status',              // IMPORTANT: Added
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

    // Always retrieve review notes independently of table search
    public function getBorrowerReviewNotes(int $borrowerId): array
    {
        return $this->db
            ->table('borrower_notes')
            ->where('borrower_id', $borrowerId)
            ->where('status', 'borrower_review')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    // Find the latest existing review note
    public function getExistingReviewNote(int $borrowerId): ?array
    {
        return $this->db
            ->table('borrower_notes')
            ->where('borrower_id', $borrowerId)
            ->where('status', 'borrower_review')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getRowArray();
    }

    public function addBorrowerNote(array $data): int
    {
        if (!$this->insert($data)) {
            return 0;
        }

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
