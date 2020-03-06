<?php

//Get SMTP Connection Details...
$mailq = "SELECT * FROM `organizations` WHERE `org_id` = '" . $_SESSION['org_id'] . "'";
$mailg = mysqli_query($ign_conn, $mailq) or die($ign_conn->error);
$mailr = mysqli_fetch_array($mailg);

//Settings for the PHPMailer Class

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = $mailr['mg_host'];  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = $mailr['mg_username'];                 // SMTP username
$mail->Password = $mailr['mg_password'];                           // SMTP password
//$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = 587;                                    // TCP port to connect to

$mail->isHTML(true); 
?>