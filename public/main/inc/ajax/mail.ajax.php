<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\FileHelper;

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
                    if (Container::$container->get(FileHelper::class)->exists($templatePath.$templateName)) {
                        echo Container::$container->get(FileHelper::class)->read($templatePath.$templateName);
                    }
                }
            }
        }
        break;
}
