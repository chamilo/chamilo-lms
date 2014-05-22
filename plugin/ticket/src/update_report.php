<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */

require_once '../config.php';
$plugin = TicketPlugin::create();

$work_id = intval($_POST['work_id']);
$forum_id = intval($_POST['forum_id']);
$rs_id = intval($_POST['rs_id']);
api_protect_course_script();

if (!api_is_allowed_to_edit()) {
    Display::display_error_message($plugin->get_lang("DeniedAccess"));
} else {
    $sql = "UPDATE " . Database::get_main_table('rp_reporte_semanas') . "
        SET work_id = '$work_id', forum_id = '$forum_id'
        WHERE  id ='$rs_id'";
    Database::query($sql);
    Display::display_confirmation_message(get_lang('UpdatedSuccessfully'));
}
