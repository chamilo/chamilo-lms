<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
exit;
require_once __DIR__.'/../inc/global.inc.php';

$work_id = (int) $_POST['work_id'];
$forum_id = (int) $_POST['forum_id'];
$rs_id = (int) $_POST['rs_id'];
api_protect_course_script();

if (!api_is_allowed_to_edit()) {
    echo Display::return_message(get_lang("DeniedAccess"), 'error');
} else {
    $sql = "UPDATE ".Database::get_main_table('rp_reporte_semanas')."
            SET work_id = $work_id, forum_id = $forum_id
            WHERE  id = $rs_id";
    Database::query($sql);
    echo Display::return_message(get_lang('Updated'), 'confirm');
}
