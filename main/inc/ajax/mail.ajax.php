<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'select_option':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $mail = new MailTemplateManager();
        $item = $mail->get($id);
        echo $item['template'];
        break;
}


