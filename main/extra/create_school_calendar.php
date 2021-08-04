<?php
/* For licensing terms, see /license.txt */

// not used??
exit;

require_once '../inc/global.inc.php';

$allow = api_get_configuration_value('extra');
if (empty($allow)) {
    exit;
}

$view = $_REQUEST['view'];
Display::display_header($nameTools, "Tracking");

$title = isset($_POST['title']) ? $_POST['title'] : "";
$je = isset($_POST['je']) ? Security::remove_XSS($_POST['je']) : "";
$me = isset($_POST['me']) ? Security::remove_XSS($_POST['me']) : "";
$ye = isset($_POST['ye']) ? Security::remove_XSS($_POST['ye']) : "";

foreach ($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}

$start_time = "$y-$m-$j";
$end_time = "$ye-$me-$je";

// On v�rifie si les champs sont vides
if (empty($title)) {
    echo '<font color="red">Attention, vous avez oubliez le nom du calendrier</font>';
}
?>
<form action="save_school_calendar.php" method="post" name="save_cal">
    <table class='table table-hover table-striped data_table'>
        <tr>
            <th colspan="3">
                <?php echo get_lang('edit_save'); ?>
            </th>
        <tr>
            <th><?php echo get_lang('title_calendar'); ?></th>
            <th><?php echo get_lang('period'); ?></th>
            <th><?php echo get_lang('action'); ?></th>
        </tr>
        <td>
            <input type=texte name=title value=<?php echo "$title"; ?>/>
        </td>
        <td>
            <input SIZE=25 NAME=period
                   value=<?php echo "$langFrom", ":", "$start_time", "$langTo", "$end_time"; ?>/>
        </td>
        <?php

        $date1 = strtotime($start_time); //Premiere date
        $date2 = strtotime($end_time); //Deuxieme date
        $nbjour = ($date2 - $date1) / 60 / 60 / 24; //Nombre de jours entre les deux
        $nbcol = 2;

        echo "<table border='1'><tr>";
        if (0 == $i % $nbcol) {
            for ($i = 0; $i <= $nbjour; $i++) {
                echo "<td><input type='text' NAME='date_case' size='8' value=".date('Y-m-d', $date1)."> ";
                $date1 += 60 * 60 * 24; //On additionne d'un jour (en seconde)
                echo '<br>';
                echo '</td>';
                echo "<td><input type='text' NAME='day_number' size='4' value=".$number."/></td>";
                echo "<td><input type='text' NAME='d_title' size='4' value=".$title."/></td>";
                $sql4 = "INSERT INTO set_module (cal_name,cal_day_num,cal_date)
                         VALUES ('$title','$number','".date('Y-m-d', $date1)."') ";
                Database::query($sql4);
                if ($i % $nbcol == ($nbcol - 1)) {
                    echo "</tr>";
                }
            }
        }
        ?>
        </tr>
        <input type="submit" value="Sauvegarder" name="B1">
    </table>
</form>
<?php
Display::display_footer();
