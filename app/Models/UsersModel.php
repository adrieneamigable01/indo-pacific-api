<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'userid';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'lastname',
        'firstname',
        'middlename',
        'email',
        'mobile_number',
        'password',
        'birthdate',
        'usertype',
        'role',
        'date_added',
        'is_active'
    ];

    protected $useTimestamps = false;

    /*
    |--------------------------------------------------------------------------
    | Validation Rules (Optional but Recommended)
    |--------------------------------------------------------------------------
    */
    protected $validationRules = [
        'lastname'      => 'required',
        'firstname'     => 'required',
        'email'         => 'required|valid_email',
        'mobile_number' => 'required',
    ];

    protected $validationMessages = [
        'email' => [
            'valid_email' => 'Please provide a valid email address.'
        ]
    ];

    /*
    |--------------------------------------------------------------------------
    | Custom Helper Methods
    |--------------------------------------------------------------------------
    */

    // Get only active users
    public function getActiveUsers()
    {
        return $this->where('is_active', 1)
                    ->orderBy('lastname', 'ASC')
                    ->findAll();
    }

    // Find by email
    public function findByEmail($email)
    {
        return $this->where('email', $email)
                    ->first();
    }

    // Find by role
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)
                    ->where('is_active', 1)
                    ->findAll();
    }

    // Deactivate user (void)
    public function voidUser($id)
    {
        return $this->update($id, [
            'is_active' => 0
        ]);
    }

    // Activate user
    public function activateUser($id)
    {
        return $this->update($id, [
            'is_active' => 1
        ]);
    }
}