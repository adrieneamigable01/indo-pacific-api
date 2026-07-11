<?php

namespace App\Validation;

use App\Models\UserModel;
use Exception;

class UserRules
{
    public function validateUser(string $str, string $fields, array $data): bool
    {
        try {
            $model = new \App\Models\UserModel();
            $user = $model->findUserByEmailAddress($data['email']);

            if (!$user) {
                return false;
            }

            // --- THE FIX ---
            // Check if $user is an array or an object and get the password accordingly
            $hashInDb = is_array($user) ? $user['password'] : $user->password;

            // Ensure we actually found a hash string
            if (empty($hashInDb)) {
                return false;
            }

            return password_verify($data['password'], $hashInDb);

        } catch (\Exception $e) {
            return false;
        }
    }
}