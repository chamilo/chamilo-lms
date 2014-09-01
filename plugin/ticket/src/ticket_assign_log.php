<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.plugin.ticket
 */
$language_file = array('registration');
require_once '../config.php';
$plugin = TicketPlugin::create();

$ticket_id = intval($_POST['ticket_id']);
$history = TicketManager::get_assign_log($ticket_id);
?>
<table width="350px" border="0" cellspacing="2" cellpadding="2">
    <?php
    if (count($history) == 0) {
        ?>
        <tr>
            <td colspan="2"><?php echo api_ucfirst(('Sin Historial')); ?></td>
        </tr>
        <?php
    }
    ?>
    <?php for ($k = 0; $k < count($history); $k++) { ?>
        <tr>
            <td width="125px"><?php echo api_convert_encoding($history[$k]['assignuser'], 'UTF-8', $charset); ?></td>
            <td width="100px"><?php echo api_convert_encoding($history[$k]['assigned_date'], 'UTF-8', $charset); ?></td>
            <td width="125px"><?php echo api_convert_encoding($history[$k]['insertuser'], 'UTF-8', $charset); ?></td>
        </tr>
    <?php } ?>
</table>
