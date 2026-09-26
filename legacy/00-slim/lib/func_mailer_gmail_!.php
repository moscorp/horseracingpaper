<?php

// $recipients_BCC = array(
//     'hkhorsepaper@gmail.com' => '.',
//     'melvin@juraron.com.hk' => '.',
//     // ..
//  );
// mailto('1','2','3','',$recipients_BCC,'4');

function mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log){
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';
    require 'PHPMailer-master/src/Exception.php';

    // $Subject = $Subject;
    // $Body    = $Body;
    // $AltBody = $AltBody;
    // $recipients = $recipients;

    // Instantiation and passing `true` enables exceptions
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        //Server settings
        // $mail->SMTPDebug = 1;   //SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                    // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'hkhorsepaper@gmail.com';                     // SMTP username
        $mail->Password   = 'mm96692244';                               // SMTP password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
        // $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        // $mail->Port       = 465;

        // $mail->SMTPSecure = "ssl"; 	 
        // $mail->Port = 465; 
        // $mail->CharSet = "utf-8"; 
        
        //Recipients
        $mail->setFrom('hkhorsepaper@gmail.com', 'HK Horse Paper');
        // $mail->addAddress('hkhorsepaper@gmail.com', 'Joe User');     // Add a recipient
        // $mail->addAddress('ellen@example.com');               // Name is optional
        // $mail->addReplyTo('hkhorsepaper@gmail.com', 'Information');
        // $mail->addCC('hkhorsepaper@gmail.com');
        // $mail->addBCC('hkhorsepaper@gmail.com');
        // $mail->addBCC('melvin@juraron.com.hk');
        if($recipients){
            foreach($recipients as $email => $name){
                $mail->addAddress($email, $name);
            }
        }
        if($recipients_BCC){
            foreach($recipients_BCC as $email => $name){
                $mail->addBCC($email, $name);
            }
        }
        // Attachments
        // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->CharSet = "utf-8"; 
        $mail->Subject = $Subject;
        $mail->Body    = $Body;
        $mail->AltBody = $AltBody;
        $mail->DebugOutput = function ($str, $level) {
            file_put_contents(
              '/PHPMailerlogs.txt',
              date('Y-m-d H:i:s') . "\t" . $str,
              FILE_APPEND | LOCK_EX
            );
          };
        $mail->send();
        return 'Message has been sent';
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

?>