<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 *
 * @package chamilo.learnpath
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

/* Header and action code */
$htmlHeadXtra[] = '<script>'.
$learnPath->get_js_dropdown_array().
'$().ready(function() {'."\n".
  'if ($(\'#previous\')) {'."\n".
    'if(\'parent is\'+$(\'#idParent\').val()) {'.
      'load_cbo($(\'#idParent\').val());'."\n".
  '}}'."\n".
'});</script>';

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$isStudentView = isset($_REQUEST['isStudentView']) ? (int) $_REQUEST['isStudentView'] : '';
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = isset($_POST['submit_button']) ? $_POST['submit_button'] : '';

/* MAIN CODE */
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$course_id = api_get_course_int_id();

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&type=step&lp_id=$learnpath_id&".api_get_cidreq(),
    'name' => get_lang('Add learning object or activity'),
];

// Theme calls
$show_learn_path = true;
$lp_theme_css = $learnPath->get_theme();

Display::display_header(get_lang('Move'), 'Path');

$suredel = trim(get_lang('Are you sure to delete'));
?>
<script>
/* <![CDATA[ */
function stripslashes(str) {
    str=str.replace(/\\'/g,'\'');
    str=str.replace(/\\"/g,'"');
    str=str.replace(/\\\\/g,'\\');
    str=str.replace(/\\0/g,'\0');
    return str;
}
function confirmation(name) {
    name=stripslashes(name);
    if (confirm("<?php echo $suredel; ?> " + name + " ?"))
    {
        return true;
    }
    else
    {
        return false;
    }
}
</script>
<?php

echo $learnPath->build_action_menu();
echo '<div class="row">';
echo '<div class="col-md-3">';
    echo $learnPath->return_new_tree();
echo '</div>';

echo '<div class="col-md-9">';

if (isset($is_success) && $is_success === true) {
    $msg = '<div class="lp_message" style="margin-bottom:10px;">';
    $msg .= 'The item has been moved.';
    $msg .= '</div>';
    echo $learnPath->display_item($_GET['id'], $msg);
} else {
    echo $learnPath->display_move_item($_GET['id']);
}
echo '</div>';
echo '</div>';

Display::display_footer();
