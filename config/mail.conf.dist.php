<?php

/**
 *	This file holds the email configuration settings
 *	@package chamilo.configuration
 */

$platform_email['SMTP_FROM_EMAIL']   = (isset($administrator['email'])?$administrator['email']:'admin@example.com');
$platform_email['SMTP_FROM_NAME']    = (isset($administrator['name'])?$administrator['name']:'Admin');
$platform_email['SMTP_HOST']         = 'localhost';
$platform_email['SMTP_PORT']         = 25;
$platform_email['SMTP_MAILER']       = 'mail'; // mail, sendmail or smtp
$platform_email['SMTP_AUTH']         = 0;
$platform_email['SMTP_USER']         = '';
$platform_email['SMTP_PASS']         = '';
$platform_email['SMTP_CHARSET']      = 'UTF-8';
