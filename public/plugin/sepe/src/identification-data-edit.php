<?php
/* For licensing terms, see /license.txt */

/**
 * This script displays a data center edit form.
 */
require_once '../config.php';
$plugin = SepePlugin::create();

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $centerOrigin = Database::escape_string(trim($_POST['center_origin']));
        $centerCode = Database::escape_string(trim($_POST['center_code']));
        $centerName = Database::escape_string(trim($_POST['center_name']));
        $url = Database::escape_string(trim($_POST['url']));
        $trackingUrl = Database::escape_string(trim($_POST['tracking_url']));
        $phone = Database::escape_string(trim($_POST['phone']));
        $mail = Database::escape_string(trim($_POST['mail']));
        $id = intval($_POST['id']);

        if (checkIdentificationData()) {
            $sql = "UPDATE $tableSepeCenter SET 
                        center_origin = '".$centerOrigin."', 
                        center_code = '".$centerCode."', 
                        center_name = '".$centerName."', 
                        url = '".$url."', 
                        tracking_url = '".$trackingUrl."', 
                        phone = '".$phone."', 
                        mail = '".$mail."' 
                    WHERE id = $id";
        } else {
            $sql = "INSERT INTO $tableSepeCenter (
                        id, 
                        center_origin, 
                        center_code, 
                        center_name, 
                        url, 
                        tracking_url, 
                        phone, 
                        mail
                    ) VALUES (
                        1,
                        '".$centerOrigin."',
                        '".$centerCode."',
                        '".$centerName."',
                        '".$url."',
                        '".$trackingUrl."',
                        '".$phone."',
                        '".$mail."'
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
        }
        header("Location: identification-data.php");
    } else {
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        Security::clear_token();
        $token = Security::get_token();
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = ["url" => "identification-data.php", "name" => $plugin->get_lang('DataCenter')];
    $templateName = $plugin->get_lang('DataCenterEdit');
    $tpl = new Template($templateName);
    $info = getInfoIdentificationData();
    $tpl->assign('info', $info);
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);
    $listing_tpl = 'sepe/view/identification-data-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
