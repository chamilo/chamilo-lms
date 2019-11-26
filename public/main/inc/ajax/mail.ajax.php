<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

api_protect_admin_script();

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

switch ($action) {
    case 'select_option':
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
        if (!empty($id)) {
            $mail = new MailTemplateManager();
            $item = $mail->get($id);
            echo $item['template'];
        } else {
            $templateName = isset($_REQUEST['template_name']) ? $_REQUEST['template_name'] : null;
            if (!empty($templateName)) {
                $templatePath = api_get_path(SYS_CODE_PATH).'template/default/mail/';
                if (Security::check_abs_path($templatePath.$templateName, $templatePath)) {
                    if (file_exists($templatePath.$templateName)) {
                        echo file_get_contents($templatePath.$templateName);
                    }
                }
            }
        }
        break;
}
