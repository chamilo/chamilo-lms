<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'get_captcha':
        header('Content-Type: image/jpeg');

        $sessionVar = empty($_REQUEST['var']) ? '_HTML_QuickForm_CAPTCHA' : $_REQUEST['var'];
        if (isset($_SESSION[$sessionVar]) && !empty($_SESSION[$sessionVar])) {
            $obj = $_SESSION[$sessionVar];
            // Force a new CAPTCHA for each one displayed/** @var Text_CAPTCHA $obj */;
            $obj->generate(true);
            echo $image = $obj->getCAPTCHA();
        }
        exit;
        break;
}
