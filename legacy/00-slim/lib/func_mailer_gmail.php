<?php
require_once("class.phpmailer.php");
// $recipients_BCC = array(
//     'hkhorsepaper@gmail.com' => '.'

//     // ..
//  );
// mailto('1','2','3','',$recipients_BCC,'4');

function mailto($Subject,$Body,$AltBody,$recipients,$recipients_BCC,$log){
	$mail = new PHPMailer();
    $mail->SMTPDebug = 1;   //SMTP::DEBUG_SERVER;                      // Enable verbose debug output

	$mail->IsSMTP();							// set mailer to use SMTP
    $mail->CharSet = "utf-8"; 
	$mail->Host = "mail.buycarl.com";		// specify main and backup server
	$mail->SMTPAuth = true;						// turn on SMTP authentication
	$mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

	$mail->Username = "support@buycarl.com";	// SMTP username
	$mail->Password = "Mm.9669.2244";					// SMTP password

	$mail->From = "support@buycarl.com";
	$mail->FromName = "HKHP update";
	// $mail->AddBCC($recipients,".");
	// $mail->AddBCC($recipients_BCC,".");
    if($recipients_BCC){
        foreach($recipients_BCC as $email => $name){
            $mail->addBCC($email, $name);
        }
    }

// 	$mail->WordWrap = 50;                                 // set word wrap to 50 characters
	$mail->IsHTML(true);                                  // set email format to HTML

	$mail->Subject = $Subject;
	//$mail->AddEmbeddedImage($string, 'mflow_c', 'mflow_c.png');
	$mail->Body = $Body;	//"<img src='".$string."'>"; 

	if(!$mail->Send()){
		// echo "error";
	}else{
		// echo "done";
	}
	$mail->ClearAddresses();
	$mail->ClearAttachments(); 
}

?>