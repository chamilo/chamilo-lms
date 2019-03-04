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

foreach ($_POST as $x) {
    echo "$x <br />";
}

foreach ($_POST as $index => $valeur) {
    $$index = Database::escape_string(trim($valeur));
}

?>
<?php echo get_lang('edit_save'); ?>
<?php
$d_id = (int) $d_id;
$d_number = (int) $d_number;
$sql4 = "UPDATE set_module SET cal_day_num = $d_number WHERE id = $d_id ";
Database::query($sql4);
print_r(
    UnserializeApi::unserialize(
        'not_allowed_classes',
        Security::remove_XSS($_POST['aaa'])
    )
);

Display::display_footer();
