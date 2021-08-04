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
 */
$this_section = SECTION_COURSES;

api_protect_course_script();

/* Constants and variables */
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$isStudentView = (int) $_REQUEST['isStudentView'];
$learnpath_id = (int) $_REQUEST['lp_id'];
$submit = $_POST['submit_button'];

/* MAIN CODE */
if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}
/* The learnpath has been just created, go get the last id. */
$is_new = false;
if ($learnpath_id == 0) {
    $is_new = true;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}
$interbreadcrumb[] = ['url' => 'lp_controller.php?action=list&'.api_get_cidreq(), 'name' => get_lang('LearningPaths')];
$interbreadcrumb[] = ['url' => '#', 'name' => $learnPath->getNameNoTags()];

// Theme calls.
$lp_theme_css = $learnPath->get_theme();
$show_learn_path = true;
Display::display_header('', 'Path');
$suredel = trim(get_lang('AreYouSureToDeleteJS'));

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
    if (confirm("<?php echo $suredel; ?> " + name + " ?")) {
        return true;
    } else {
        return false;
    }
}
</script>
<?php

/* DISPLAY SECTION */
echo $learnPath->build_action_menu();

echo '<div class="row">';
echo '<div class="col-md-4">';
// Build the tree with the menu items in it.
echo $learnPath->return_new_tree();
echo '</div>';
echo '<div class="col-md-8">';

if (isset($is_success) && $is_success === true) {
    echo Display::return_message(get_lang('ItemRemoved'), 'confirmation');
} else {
    if ($is_new) {
        echo Display::return_message(get_lang('LearnpathAdded'), 'normal', false);
    }
    echo Display::page_subheader(get_lang('LearnPathAddedTitle'));
    echo '<ul id="lp_overview" class="thumbnails">';
    echo show_block(
        'lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$learnPath->get_id(),
        get_lang("NewStep"),
        get_lang('NewStepComment'),
        'tools.png'
    );
    echo show_block(
        'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnPath->get_id(),
        get_lang("Display"),
        get_lang('DisplayComment'),
        'view.png'
    );
    echo '</ul>';
}
echo '</div>';
echo '</div>';

function show_block($link, $title, $subtitle, $icon)
{
    $html = '<li class="col-md-4">';
    $html .= '<div class="thumbnail">';
    $html .= '<a href="'.$link.'" title="'.$title.'">';
    $html .= Display::return_icon($icon, $title, [], ICON_SIZE_BIG);
    $html .= '</a>';
    $html .= '<div class="caption">';
    $html .= '<strong>'.$title.'</strong></a> '.$subtitle;
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</li>';

    return $html;
}

Display::display_footer();
