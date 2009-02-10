<?php
require ('../inc/global.inc.php');
$language_file = array('registration','messages');
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td><div class="actions"><?php echo utf8_encode(get_lang('MyPersonalData')) ?></div></td>
    </tr>
    <tr>
        <td><a href="../auth/profile.php?show=1"><?php echo utf8_encode(get_lang("AlterPersonalData"));?></a>
        </td>
    </tr>
</table>
