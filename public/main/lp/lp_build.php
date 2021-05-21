<?php

// @deprecated? not used
exit;

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
if (0 == $learnpath_id) {
    $is_new = true;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = ['url' => 'lp_controller.php?action=list&'.api_get_cidreq(), 'name' => get_lang('Learning paths')];
$interbreadcrumb[] = ['url' => '#', 'name' => $learnPath->getNameNoTags()];

// Theme calls.
$lp_theme_css = $learnPath->get_theme();
$show_learn_path = true;

$rightColumn = $learnPath->showBuildSideBar();

if (isset($is_success) && true === $is_success) {
    $rightColumn .= Display::return_message(get_lang('The learning object has been removed'), 'confirmation');
} else {
    if ($is_new) {
        $rightColumn .= Display::return_message(get_lang('Course added'), 'normal', false);
    }
    $rightColumn .= Display::page_subheader(get_lang('Welcome to the Chamilo course authoring tool !'));
    $rightColumn .= '<ul id="lp_overview" class="thumbnails">';
    $rightColumn .= show_block(
        'lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.$learnPath->get_id(),
        get_lang("Add learning object or activity"),
        get_lang('Add learning object or activityComment'),
        'tools.png'
    );
    $rightColumn .= show_block(
        'lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnPath->get_id(),
        get_lang("Ranking"),
        get_lang('RankingComment'),
        'view.png'
    );
    $rightColumn .= '</ul>';
}

echo 'ju';
exit;

$tpl = new Template();
$tpl->assign('left', $learnPath->build_action_menu());
$tpl->assign('right', $rightColumn);
echo $tpl->fetch('@ChamiloCore/Layout/layout_two_col.html.twig');

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
