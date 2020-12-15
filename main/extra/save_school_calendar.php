<?php
/* For licensing terms, see /license.txt */

// not used??
exit;

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

Display::display_header($nameTools, "Tracking");

foreach ($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}

?>
<form action="upgrade_school_calendar.php" method="post" name="upgrade_cal">
    <th colspan="6">
        <?php echo get_lang('edit_save'); ?>
    </th>
    <tr>
        </th>
    </tr>
    <?php

    echo "<table border='1'><tr>";

    if ($i % $nbcol == 0) {
        $sqlexam = "SELECT * FROM set_module
                    WHERE cal_name =  '$d_title'";
    }

    $resultexam = Database::query($sqlexam);
    while ($a_exam = Database::fetch_array($resultexam)) {
        $name = $a_exam['cal_name'];
        $id = $a_exam['id'];
        $num = $a_exam['cal_day_num'];
        $c_date = $a_exam['cal_date'];
        echo "
            <td><input type=text  name=d_cal_date size=8 value=".$c_date."></td>
            <td><input type=text name=d_number size=5 value=".$num."></td>
            <td><input type=text  name=d_title size=8 value=".$name."></td>
            <td><input  name=d_id size=8 value=".$id."></td>";
        if ($i % $nbcol == ($nbcol - 1)) {
            echo "</tr>";
        }
    }

    $nb = count($d_number);
    $nbcol = 2;
    ?>
    </td>
    </tr>
    <input type=hidden name=aaa value=<?php echo serialize(Database::fetch_array($resultexam)); ?>/>
    <input type="submit" value="Sauvegarder" name="B1">
    <?php
    echo $id, $tableau;
    ?>
</form>
</table>
<?php

Display::display_footer();
