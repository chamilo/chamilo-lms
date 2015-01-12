<?php

require_once '/var/www/chamilo-lms/main/inc/global.inc.php';
require_once __DIR__ . '/config.php';

MessageManager::send_message(
    18,
    get_lang('MailStudentRequest'),
    'HOLA!!! :)',
    null,
    null,
    null,
    null,
    null,
    null,
    17
);
//api_mail_html('RECIPIENT', '9leinad0@gmail.com', 'TEST!', 'ESTOY TESTEANDO D:!', 'Dan', 'dbarreto@kabuto.com');

/*
$advSub = AdvancedSubscriptionPlugin::create();
$advSub->install();
*/