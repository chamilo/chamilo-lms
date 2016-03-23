<?php

/**
 *	This file holds the configuration settings
 *	for phpmailer Class.
 *
 *	@package chamilo.configuration
 */

$platform_email['SMTP_FROM_EMAIL'] = (isset($administrator['email']) ? $administrator['email'] : 'admin@example.com');
$platform_email['SMTP_FROM_NAME'] = (isset($administrator['name']) ? $administrator['name'] : 'Admin');
$platform_email['SMTP_HOST'] = 'localhost';
$platform_email['SMTP_PORT'] = 25;
$platform_email['SMTP_MAILER'] = IS_WINDOWS_OS ? 'smtp' : 'mail'; // mail, sendmail or smtp
$platform_email['SMTP_AUTH'] = 0;
$platform_email['SMTP_USER'] = '';
$platform_email['SMTP_PASS'] = '';
$platform_email['SMTP_CHARSET'] = 'UTF-8';
$platform_email['SMTP_UNIQUE_SENDER'] = 0; // to send all mails from the same user
$platform_email['SMTP_DEBUG'] = 0; // change to 1 to enable smtp debug
$platform_email['SMTP_SECURE'] = 'tls'; // if you're using SSL: ssl; or TLS: tls. (only used if SMTP_AUTH==1)
