<?php
/* For licensing terms, see /license.txt */

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

api_block_anonymous_users();

Display::display_header();

$tbl_stats_exercices = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$num = isset($_GET['num']) ? (int) $_GET['num'] : '';
$student_idd = isset($_GET['student_id']) ? (int) $_GET['student_id'] : '';

?>
<form action="update_exam.php" method="post" name="save_exam">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="6">
                <?php echo get_lang('edit_save'); ?>
            </th>
        <tr>
            <th><?php echo get_lang('module_no'); ?></th>
            <th><?php echo get_lang('result_exam'); ?></th>
            <th><?php echo get_lang('result_rep_1'); ?></th>
            <th><?php echo get_lang('result_rep_2'); ?></th>
            <th><?php echo get_lang('comment'); ?></th>
            <th><?php echo get_lang('action'); ?></th>
        </tr>
        <?php

        $sqlexam = "SELECT * FROM $tbl_stats_exercices WHERE exe_id = $num";
        $resultexam = Database::query($sqlexam);
        while ($a_exam = Database::fetch_array($resultexam)) {
            $exe_id = $a_exam['exe_id'];
            $mod_no = $a_exam['mod_no'];
            $score_ex = $a_exam['score_ex'];
            $score_rep1 = $a_exam['score_rep1'];
            $score_rep2 = $a_exam['score_rep2'];
            $coment = $a_exam['coment'];
            echo "
            <tr>
                <td>
                    <input type=text name=mod_no size=1 value= ".$a_exam['mod_no'].">
                </td>
                <td>
                    <input type=text name=score_ex size=1 value=".$a_exam['score_ex'].">
                </td>
                <td><input type=text name=score_rep1 size=1 value=".$a_exam['score_rep1']."></td>
                <td><input type=text name=score_rep2 size=1 value=".$a_exam['score_rep2']."></td>
                <td><textarea name=\"coment\" cols=\"65\" rows=\"2\">$coment</textarea><br /></td>
                <INPUT type=hidden name=ex_idd value=\"$exe_id\" />
                <INPUT type=hidden name=student_id value=\"$student_idd\" />
                <td>
                    <input type=\"submit\" value=\"".get_lang('Save')."\" name=\"B1\">
                </td>
            </tr>
            ";
        }
        ?>
    </table>
</form>
<?php

Display::display_footer();
