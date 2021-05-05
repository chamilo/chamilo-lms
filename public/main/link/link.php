<?php

/* For licensing terms, see /license.txt */

/**
 * Main script for the links tool.
 *
 * Features:
 * - Organize links into categories;
 * - favorites/bookmarks-like interface;
 * - move links up/down within a category;
 * - move categories up/down;
 * - expand/collapse all categories (except the main "non"-category);
 * - add link to 'root' category => category-less link is always visible.
 *
 * @author Julio Montoya code rewritten
 * @author Patrick Cool
 * @author René Haentjens, added CSV file import (October 2004)
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_LINK;
$this_section = SECTION_COURSES;
api_protect_course_script(true);

$htmlHeadXtra[] = '<script>
    $(function() {
        for (i=0;i<$(".actions").length;i++) {
            if ($(".actions:eq("+i+")").html()=="<table border=\"0\"></table>" || $(".actions:eq("+i+")").html()=="" || $(".actions:eq("+i+")").html()==null) {
                $(".actions:eq("+i+")").hide();
            }
        }
     });

     function check_url(id, url) {
        var url = "'.api_get_path(WEB_AJAX_PATH).'link.ajax.php?a=check_url&url=" +url;
        var loading = " '.addslashes(Display::return_icon('loading1.gif')).'";
        $("#url_id_"+id).html(loading);
        $("#url_id_"+id).load(url);
     }
</script>';

$down = !empty($_GET['down']) ? $_GET['down'] : '';
$up = !empty($_GET['up']) ? $_GET['up'] : '';
$id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '';
$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';

$nameTools = get_lang('Links');
$course_id = api_get_course_int_id();
$session_id = api_get_session_id();
$courseInfo = api_get_course_info();

if ('addlink' === $action) {
    $nameTools = '';
    $interbreadcrumb[] = ['url' => 'link.php', 'name' => get_lang('Links')];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add a link')];
}

if ('addcategory' === $action) {
    $nameTools = '';
    $interbreadcrumb[] = ['url' => 'link.php', 'name' => get_lang('Links')];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add category')];
}

if ('editlink' === $action) {
    $nameTools = get_lang('Edit link');
    $interbreadcrumb[] = ['url' => 'link.php', 'name' => get_lang('Links')];
}

Event::event_access_tool(TOOL_LINK);

/*	Action Handling */
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$scope = isset($_REQUEST['scope']) ? $_REQUEST['scope'] : null;
$show = isset($_REQUEST['show']) && in_array(trim($_REQUEST['show']), ['all', 'none']) ? $_REQUEST['show'] : '';
$categoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : '';
$linkListUrl = api_get_self().'?'.api_get_cidreq().'&category_id='.$categoryId.'&show='.$show;
$content = '';
$token = Security::get_existing_token();

$protectedActions = [
    'addlink',
    'editlink',
    'addcategory',
    'editcategory',
    'deletelink',
    'deletecategory',
    'visible',
    'invisible',
    'up',
    'down',
    'move_link_up',
    'move_link_down',
];

// block access
if (in_array($action, $protectedActions) &&
    !api_is_allowed_to_edit(null, true)
) {
    api_not_allowed(true);
}

switch ($action) {
    case 'addlink':
        $form = Link::getLinkForm(null, 'addlink', $token);
        if ($form->validate() && Security::check_token('get')) {
            $link = new Link();
            $link->setCourse($courseInfo);
            $linkId = $link->save($form->exportValues());
            Skill::saveSkills($form, ITEM_TYPE_LINK, $linkId);

            Security::clear_token();
            header('Location: '.$linkListUrl);
            exit;
        }
        $content = $form->returnForm();

        break;
    case 'editlink':
        $form = Link::getLinkForm($id, 'editlink');
        if ($form->validate()) {
            Link::editLink($id, $form->getSubmitValues());
            Skill::saveSkills($form, ITEM_TYPE_LINK, $id);
            header('Location: '.$linkListUrl);
            exit;
        }
        $content = $form->returnForm();

        break;
    case 'addcategory':
        $form = Link::getCategoryForm(null, 'addcategory');

        if ($form->validate()) {
            // Here we add a category
            Link::addCategory();
            header('Location: '.$linkListUrl);
            exit;
        }
        $content = $form->returnForm();

        break;
    case 'editcategory':
        $form = Link::getCategoryForm($id, 'editcategory');

        if ($form->validate()) {
            // Here we edit a category
            Link::editCategory($id, $form->getSubmitValues());

            header('Location: '.$linkListUrl);
            exit;
        }
        $content = $form->returnForm();

        break;
    case 'deletelink':
        // Here we delete a link
        Link::deleteLink($id);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'deletecategory':
        // Here we delete a category
        Link::deleteCategory($id);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'visible':
        // Here we edit a category
        Link::setVisible($id, $scope);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'invisible':
        // Here we edit a category
        Link::setInvisible($id, $scope);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'up':
        Link::movecatlink('up', $up);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'down':
        Link::movecatlink('down', $down);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'move_link_up':
        Link::moveLinkUp($id);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'move_link_down':
        Link::moveLinkDown($id);
        header('Location: '.$linkListUrl);
        exit;

        break;
    case 'export':
        $content = Link::listLinksAndCategories($course_id, $session_id, $categoryId, $show, null, false, true);
        $courseInfo = api_get_course_info_by_id($course_id);
        if (!empty($session_id)) {
            $sessionInfo = api_get_session_info($session_id);
            $courseInfo['title'] .= ' '.$sessionInfo['name'];
        }
        $pdf = new PDF();
        $pdf->content_to_pdf(
            $content,
            null,
            $courseInfo['title'].'_'.get_lang('Link'),
            $courseInfo['code'],
            'D',
            false,
            null,
            false,
            true
        );

        break;
    case 'list':
    default:
        $content = Link::listLinksAndCategories($course_id, $session_id, $categoryId, $show);

        break;
}

Display::display_header($nameTools, 'Links');
Display::display_introduction_section(TOOL_LINK);
echo $content;
Display::display_footer();
