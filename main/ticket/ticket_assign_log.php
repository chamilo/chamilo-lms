<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

if (!isset($_POST['ticket_id'])) {
    exit;
}

$ticket_id = (int) $_POST['ticket_id'];
$history = TicketManager::get_assign_log($ticket_id);
?>
<table width="200px" border="0" cellspacing="2" cellpadding="2">
<?php
if (count($history) == 0) {
    ?>
    <tr>
        <td colspan="2"><?php echo api_ucfirst(get_lang('TicketNoHistory')); ?></td>
    </tr>
    <?php
}
foreach ($history as $item) {
    ?>
    <tr>
        <td width="50px">
            <?php echo api_convert_encoding($item['insertuser'], 'UTF-8', $charset); ?>
        </td>
        <td width="80px">
            <?php echo api_convert_encoding($item['assigned_date'], 'UTF-8', $charset); ?>
        </td>
        <td width="50px">
            <?php echo api_convert_encoding($item['assignuser'], 'UTF-8', $charset); ?>
        </td>
    </tr>
<?php
} ?>
</table>
