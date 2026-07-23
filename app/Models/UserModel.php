<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class UserModel extends Model
{   
    protected $primaryKey = 'userid';
    protected $table = 'users';
    protected $allowedFields = [
        'firstname',
        'middlename',
        'lastname',
        'email',
        'mobile_number',
        'birthdate',
        'user_image'
    ];
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];
    protected $returnType = 'array';

    protected function beforeInsert(array $data): array
    {
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    protected function beforeUpdate(array $data): array
    {
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    private function getUpdatedDataWithHashedPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $plaintextPassword = $data['data']['password'];
            $data['data']['password'] = $this->hashPassword($plaintextPassword);
        }
        return $data;
    }

    public function hashPassword(string $plaintextPassword): string
    {
        return password_hash($plaintextPassword, PASSWORD_BCRYPT);
    }
                                      
    public function findUserByEmailAddress(string $emailAddress)
    {
        $user = $this
            ->asArray()
            ->where(['email' => $emailAddress])
            ->first();

        if (!$user) 
            throw new Exception('User does not exist for specified email address');

        return $user;
    }
    public function findUserByEmail(string $email)
    {
        $user = $this
            ->asArray()
            ->where(['email' => $email])
            ->first();

        if (!$user) 
            throw new Exception('User does not exist for specified email address');

        return $user;
    }
    public function findUserByUserId(string $userid)
    {
        $user = $this
            ->asArray()
            ->where(['userid' => $userid])
            ->first();
        
        if (!$user) 
            throw new Exception('User does not exist for specified email address');

        return $user;
    }
    public function getCashiers(
        string $search = NULL
    )
    {

        $builder =

            $this->db

            ->table('users')

            ->select("

                userid,

                lastname,

                firstname,

                middlename,

                email,

                mobile_number,

                role,

                usertype

            ")

            ->where(
                'is_active',
                1
            )

            ->where(
                'UPPER(role)',
                'CASHIER'
            );

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if(!empty($search)){

            $builder

            ->groupStart()

            ->like(
                'firstname',
                $search
            )

            ->orLike(
                'lastname',
                $search
            )

            ->orLike(
                'email',
                $search
            )

            ->orLike(
                'mobile_number',
                $search
            )

            ->groupEnd();

        }

        return

            $builder

            ->orderBy(
                'lastname',
                'ASC'
            )

            ->orderBy(
                'firstname',
                'ASC'
            )

            ->get()

            ->getResultArray();

    }


   

   
}