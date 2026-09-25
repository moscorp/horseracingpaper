<?php
require_once("class.phpmailer.php");

function mailto($Subject, $Body, $AltBody, $recipients, $recipients_BCC, $log, $jpgfilename) {
    $mail = new PHPMailer();
    $mail->SMTPDebug = 2; // Enable detailed debug output
    $mail->IsSMTP();
    $mail->CharSet = "utf-8"; 
    $mail->Host = "mail.buycarl.com";
    $mail->SMTPAuth = true; 
    $mail->Port = 587; 

    $mail->Username = "support@buycarl.com"; 
    $mail->Password = "Mm.9669.2244"; 

    $mail->From = "support@buycarl.com";
    $mail->FromName = "Fi";

    if ($recipients_BCC) {
        foreach ($recipients_BCC as $email => $name) {
            $mail->addBCC($email, $name);
        }
    }

    $mail->IsHTML(true);
    $mail->Subject = $Subject;
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . '/00/';
    $filenamefull = $base_url . $jpgfilename;
    $mail->Body = $Body . "<br><img src='" . $filenamefull . "' alt='Image'>"; // Add alt text for accessibility

    // Attach the existing JPEG file
    // if (file_exists($jpgfilename)) {
    //     $mail->addEmbeddedImage($jpgfilename, 'image_cid'); // Embed image with a CID
    // } else {
    //     error_log("File not found: " . $jpgfilename);
    // }
    
    // // Use CID in the email body
    // $mail->Body = $Body . "<br><img src='cid:image_cid' alt='Image'>"; // Reference the embedded image

    // Attach the JPEG file
    if (file_exists($jpgfilename)) {
        // $mail->addAttachment($jpgfilename); // Attach the file
        // $mail->AddAttachment($jpgfilename); // Attach the file
        $mail->addAttachment($filenamefull, basename($filenamefull)); // Attach the file
    } else {
        error_log("File not found: " . $jpgfilename); // Log error if file doesn't exist
        return false; // Stop if the file does not exist
    }

    // Send the email
    if (!$mail->Send()) {
        error_log("Mailer Error: " . $mail->ErrorInfo, 3, "Mailer-error.log");
        return false; // Indicate failure
    } else {
        return true; // Indicate success
    }
    
    $mail->ClearAddresses();
    $mail->ClearAttachments(); 
}
?>