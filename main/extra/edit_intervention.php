<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

api_block_anonymous_users();

Display::display_header();
$num = isset($_GET['num']) ? (int) $_GET['num'] : 0;
$student_idd = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

?>
<form action="update_intervention.php" method="post" name="save_intercention">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="4"><?php echo get_lang('Edit'); ?>
        <tr>
            <th><?php echo get_lang('Level'); ?></th>
            <th><?php echo get_lang('Date'); ?></th>
            <th><?php echo get_lang('interventions_commentaires'); ?></th>
            <th><?php echo get_lang('Action'); ?></th>
        </tr>
        <?php
        $sqlinter = "SELECT * FROM $tbl_stats_exercices WHERE exe_id = $num";
        $resultinter = Database::query($sqlinter);
        while ($a_inter = Database::fetch_array($resultinter)) {
            $level = $a_inter['level'];
            $mod_no = $a_inter['mod_no'];
            $score_ex = $a_inter['score_ex'];
            $inter_coment = stripslashes($a_inter['inter_coment']);
            echo "
                <tr>
                <td> ".$a_inter['level']."
                </td>
                <td>
                    ".$a_inter['exe_date']."
                </td>";
            $exe_id = $a_inter['exe_id']; ?>
            <td>
                <textarea name="inter_coment" cols="65" rows="2">
                    <?php echo $inter_coment; ?>
                </textarea>
            </td>
            <INPUT type=hidden name=exe_id value= <?php echo "$exe_id"; ?>/>
            <INPUT type=hidden name=student_id value= <?php echo "$student_idd"; ?>/>
            <td><input type="submit" value="Sauvegarder" name="B1"></td>
            </tr>
            <?php
        }
        ?>
    </table>
</form>
<?php

Display::display_footer();
