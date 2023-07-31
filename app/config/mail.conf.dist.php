<?php
/**
 *	This file holds the configuration settings
 *	for phpmailer Class.
 *
 *	The settings can use an optional index at the first level to represent the ID of the
 *	URL in case you use multi-url for example: $platform_email[2]['SMTP_HOST'] = 'awesome.mail.server'.
 *
 *	@package chamilo.configuration
 */

$platform_email['SMTP_FROM_EMAIL'] = ''; //See the function __construct() in main/inc/lib/notification.lib.php for more details on how the SMTP FROM email is defined and what to indicate here if needed to override users configuration
$platform_email['SMTP_FROM_NAME'] = '';
$platform_email['SMTP_HOST'] = 'localhost'; // If using SMTP use the domain name example: mywebmail.example.net
$platform_email['SMTP_PORT'] = 25;
$platform_email['SMTP_MAILER'] = 'mail'; // mail, sendmail or smtp (Windows probably only supports smtp)
$platform_email['SMTP_AUTH'] = 0;
$platform_email['SMTP_USER'] = '';
$platform_email['SMTP_PASS'] = '';
$platform_email['SMTP_CHARSET'] = 'UTF-8';
$platform_email['SMTP_UNIQUE_SENDER'] = 0; // to send all mails from the same user
$platform_email['SMTP_DEBUG'] = 0; // change to 1 to enable smtp debug
$platform_email['SMTP_SECURE'] = 'tls'; // if you're using SSL: ssl; or TLS: tls. (only used if SMTP_AUTH==1)
$platform_email['SMTP_UNIQUE_REPLY_TO'] = 0; // to use AWS SMS service, SMTP_UNIQUE_SENDER and this have to be = 1
// If you have certificate problems see:
// https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#updating-ca-certificates
/*$platform_email['SMTPOptions'] = [
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
    ],
];*/
// DKIM requires the generation of a public/private keypair and the configuration of a TXT record in your DNS
// The TXT record should look like this: chamilo._domainkey.yourdomain.ext IN TXT "v=1; k=rsa; s=chamilo; p=PubKey..."
// to match the following selector
// Also, using SMTP_UNIQUE_SENDER is required if users have e-mails from different domains
$platform_email['DKIM'] = 0; //enable DKIM by setting this to 1
$platform_email['DKIM_SELECTOR'] = 'chamilo'; // an indicator of the application sending the e-mail through this specific DKIM key
$platform_email['DKIM_DOMAIN'] = 'mydomain.com'; //the domain for e-mail sending, not necessarily api_get_path(WEB_PATH)
$platform_email['DKIM_PRIVATE_KEY_STRING'] = ''; //the private key in a string format
$platform_email['DKIM_PRIVATE_KEY'] = ''; //the private key as the path to a file. The file needs to be accessible to PHP!
// Some e-mail clients do not understand the descriptive LD+JSON format,
// showing it as a loose JSON string to the final user. If this is your case,
// you might want to set the variable below to 'false' to disable this header.
$platform_email['EXCLUDE_JSON'] = false;

// Fill the following only for mail services with OAuth2.0 authentication. Otherwise leave untouched.
$platform_email['XOAUTH2_METHOD'] = false;
$platform_email['XOAUTH2_URL_AUTHORIZE'] = 'https://provider.example/oauth2/auth';
$platform_email['XOAUTH2_URL_ACCES_TOKEN'] = 'https://provider.example/token';
$platform_email['XOAUTH2_URL_RESOURCE_OWNER_DETAILS'] = 'https://provider.example/userinfo';
$platform_email['XOAUTH2_SCOPES'] = '';
$platform_email['XOAUTH2_CLIENT_ID'] = '';
$platform_email['XOAUTH2_CLIENT_SECRET'] = '';
$platform_email['XOAUTH2_REFRESH_TOKEN'] = '';
