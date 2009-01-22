<?php

// ============================== mail ================================= 

// smtp values for phpmailer Class 
$platform_email['SMTP_FROM_EMAIL']   = $administrator["email"]; 
$platform_email['SMTP_FROM_NAME']    = $administrator["name"]; 
$platform_email['SMTP_HOST']         = 'localhost'; 
$platform_email['SMTP_PORT']         = 25; 
$platform_email['SMTP_MAILER']       = 'mail'; //mail, sendmail or smtp 
$platform_email['SMTP_AUTH']         = 0; 
$platform_email['SMTP_USER']         = ''; 
$platform_email['SMTP_PASS']         = ''; 
$platform_email['SMTP_CHARSET']      = $charset; 

// ====================================================================

?>