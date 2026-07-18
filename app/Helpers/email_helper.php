<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use App\Models\UserModel;
use Config\Services;


    function emailConfirm(){
        // $config = [
        //     'protocol'      =>'smtp',
        //     'smtp_host'     =>'smtp.gmail.com',
        //     'smtp_user'     =>'notificationbariliprimelending@gmail.com',
        //     'smtp_pass'     =>'wuji mvst chmz yhaa',
        //     'smtp_port'     =>'587',
        //     'validate'      =>'true',
        //     'encrypt'       =>'tls',
        //     'from_name'     => 'Barili Prime Lending Corp.',
        //     'from_email'    =>'notificationbariliprimelending@gmail.com',
        //     'reply'         =>'notificationbariliprimelending@gmail.com',
        // ];

        $config = [
            'protocol'      =>'smtp',
            'smtp_host'     =>'smtp.gmail.com',
            'smtp_user'     =>'iplcnotification@gmail.com',
            'smtp_pass'     =>'qzgd genr idrr aimx',
            'smtp_port'     =>'587',
            'validate'      =>'true',
            'encrypt'       =>'tls',
            'from_name'     => 'INDO - PACIFIC LENDING CORPORATION.',
            'from_email'    =>'indopaficiclendingcorporaion@gmail.com',
            'reply'         =>'indopaficiclendingcorporaion@gmail.com',
        ];
        
        return $config;
    }

        function otpEmailBody($data){
            $action = $data['action'];
            $actionMessages = [

                'login'              => 'login',
                'delete_loan'        => 'loan deletion',
                'update_schedule'    => 'loan schedule update',
                'delete_borrower'    => 'borrower deletion',
                'full_paid_borrower' => 'mark a borrower as fully paid',
                'approve_return'     => 'return approval',
                'return_to_manager'  => 'return to manager'

            ];

            $actionText = $actionMessages[$action] ?? 'requested';
            
            $template = "
                Dear {name},<br><br>

                We received a request to <strong>{$actionText}</strong> on your account.<br><br>

                To continue, please use the following One-Time Password (OTP):<br><br>

                <div style='font-size:28px;font-weight:bold;letter-spacing:5px;text-align:center;'>
                    {otp}
                </div>

                <br>

                This OTP is valid until <strong>{expires_at}</strong> and can only be used once.<br><br>

                If you did not initiate this request, please ignore this email or contact your system administrator immediately.<br><br>

                Thank you,<br>
                <strong>Indo-Pacific Lending Corporation</strong>
            ";

            $name = isset($data['name']) ? $data['name'] : 'user';
            $resdata = str_replace(
                array('{name}','{otp}','{expires_at}','{action}'),
                array($name,$data['otp'],$data['expires_at'],$data['action']),
                $template
            );

            return $resdata;
        }

        function sendOTP($data){
        
        $html = otpEmailBody($data);
        
        return send($html,$data['email']);
    }

    function sendEmail($email,$body){
        
        $email       = $_POST['email'];
        $name        = isset($_POST['name']) ? $_POST['name'] : '';
        $bodyMessage = $_POST['body'];
        $date        =  isset($_POST['date']) ? $_POST['date'] : '';
        $subj        = !empty($_POST['subject']) ? $_POST['subject'] : "Notification";
        $cc          = !empty($_POST['cc']) ? $_POST['cc'] : "";
        // $cc         = "giov.tx86@yahoo.com,giov.tx86@gmail.com";

        
        $html = emailNotificationTemplate($body,$subj);
        $send = send($html,$email,$subj,$cc);
        return $send;
    }

     function send($body,$recipient = 'noreply@gmail.com',$subject = "POS",$cc = ""){
        
        $default = emailConfirm();
        // print_r($default);exit;
        
        $send =  new PHPMailer(true);
        

        $send->SMTPDebug = 0; // Enable verbose debug output
        // $send->SMTPDebug = 0; 
        $send->isSMTP(); // Set mailer to use SMTP
        $send->Host = $default['smtp_host'];
        $send->SMTPAuth = true; // Enable SMTP authentication
        $send->Username = $default['smtp_user']; // SMTP username
        $send->Password = $default['smtp_pass']; // SMTP password
        $send->SMTPSecure = $default['encrypt']; // Enable TLS encryption, `ssl` also accepted
        $send->Port = $default['smtp_port'];
        $send->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        $send->isHTML(true);
        $send->AddReplyTo('indopacificlendingcorporation@gmail.com');
        // Sender information
        $send->setFrom('indopacificlendingcorporation@gmail.com', 'INDO-PACIFIC LENDING CORPORATION');
        $send->addAddress($recipient);
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];

            // Loop through each uploaded file
            for ($i = 0; $i < count($files['name']); $i++) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];

                // Add each file as an attachment
                $send->addAttachment($file_tmp, $file_name);
            }
        }

        $send->Subject = $subject.' '.date("F d, Y H:i:s");
        $send->Body = $body;

        $send->AltBody = $body;

        if(!empty($cc)){
            $cc = explode(",", $cc);
            for ($i=0; $i < sizeof($cc); $i++) { 
                $send->AddCC($cc[$i]);
            }
        }
        
        
        if($send->send()){
            return true;
        }else{
            return false;
        }
    }