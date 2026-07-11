<?php

use App\Models\UserModel;
use Config\Services;

    
     function generate_otp($data) {
       
        helper('email_helper');
        remove_used_otps($data['foreign_id']); // Remove used OTPs
        $otp = generate($data);
        $dataOTP =  sendOTP($otp);
        if($dataOTP){
            return array(
                'data' => $dataOTP,
                'otp' => $otp,
                'success' => true,
            );
        }else{
            return array(
                'data' => $dataOTP,
                'success' => false,
            );
        }
        
    }

    // Validate OTP
     function validate_otp($data,$deleteOTP = 1) {
        if (validate($data)) {
            remove_used_otps($data['foreign_id']); // Remove used OTPs
           return true;
        } else {
            return false;
        }
    }
    
      function saveOTP($payload,$otp) {
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Valid for 5 minutes

        $data = array(
            'otp' => $otp,
            'expires_at' => $expires_at,
            'action' => $payload['action'],
            'foreign_id'=> $payload['foreign_id'],
            'userid'=> $payload['foreign_id'],
        );

        if(isset($payload['customer_id'])){
            $data['customer_id'] = $payload['customer_id'];
        }
        if(isset($payload['name'])){
            $data['name'] = $payload['name'];
        }
        if(isset($payload['email'])){
            $data['email'] = $payload['email'];
        }
        if(isset($payload['mobile_number'])){
            $data['mobile'] = $payload['mobile_number'];
        }
        // CI->db->insert('otp_codes', $data);
        return $data;
    }

    function generate($payload) {
       
        // The rest of your code is CI4 compatible
        $otp = random_int(100000, 999999); // 6-digit OTP
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // Valid for 5 minutes
     

        $data = [
            'otp' => $otp,
            'expires_at' => $expires_at,
            'action' => $payload['action'],
            'foreign_id' => $payload['foreign_id'],
            'userid' => $payload['foreign_id'],
        ];

        
        if (isset($payload['customer_id'])) {
            $data['customer_id'] = $payload['customer_id'];
        }
        if (isset($payload['name'])) {
            $data['name'] = $payload['name'];
        }
        if (isset($payload['email'])) {
            $data['email'] = $payload['email'];
        }
        if (isset($payload['mobile_number'])) {
            $data['mobile'] = $payload['mobile_number'];
        }
        // -- CI4 Database Insert --

        // 1. Get the database connection
        $db = \Config\Database::connect();

        // 2. Get a new Query Builder instance for the 'otp_codes' table
        $builder = $db->table('otp_codes');

        // 3. Execute the insert query
        $builder->insert($data);

        return $data;
    }

    // Function to validate OTP
     function validate($data) {
      
        // Get the database connection
        $db = \Config\Database::connect();

        // Start building the query
        $builder = $db->table('otp_codes');

        if (isset($data['foreign_id'])) {
            $builder->where('foreign_id', $data['foreign_id']);
        }

        // Add remaining where clauses
        $builder->where('otp', $data['otp']);
        $builder->where('action', $data['action']);
        $builder->where('is_active', 1);
        $builder->where('expires_at >=', date('Y-m-d H:i:s'));

        // Get the result
        $query = $builder->get();

        // Check if any rows were returned
        return $query->getNumRows() > 0;
    }

    // Function to remove used OTPs
     function remove_used_otps($foreign_id) {
       // Get the database connection
        $db = \Config\Database::connect();

        // Get a new Query Builder instance for the 'otp_codes' table
        $builder = $db->table('otp_codes');

        // Set the WHERE clause
        $builder->where('foreign_id', $foreign_id);

        // Prepare the data to be updated
        $updateData = [
            'is_active' => 0,
        ];

        // Execute the update query
        $builder->update($updateData);
    }