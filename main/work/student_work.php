<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_STUDENTPUBLICATION;

/*	Configuration settings */

api_protect_course_script(true);

// Including necessary files
require_once 'work.lib.php';
$this_section = SECTION_COURSES;

//$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$studentId = isset($_GET['studentId']) ? intval($_GET['studentId']) : null;

if (empty($studentId)) {
    api_not_allowed(true);
}

$tool_name = get_lang('StudentPublications');
$group_id = api_get_group_id();

$userInfo = api_get_user_info($studentId);

if (empty($userInfo)) {
    api_not_allowed(true);
}

if (!empty($group_id)) {
    $group_properties  = GroupManager :: get_group_properties($group_id);
    $show_work = false;

    if (api_is_allowed_to_edit(false, true)) {
        $show_work = true;
    } else {
        // you are not a teacher
        $show_work = GroupManager::user_has_access($user_id, $group_id, GroupManager::GROUP_TOOL_WORK);
    }

    if (!$show_work) {
        api_not_allowed();
    }

    $interbreadcrumb[] = array ('url' => '../group/group.php', 'name' => get_lang('Groups'));
    $interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$group_id, 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
} else {
    if (!api_is_allowed_to_edit(false, true)) {
        api_not_allowed(true);
    }
}

$interbreadcrumb[] = array ('url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(), 'name' => get_lang('StudentPublications'));
$interbreadcrumb[] = array ('url' => '#', 'name' => $userInfo['complete_name']);

Display :: display_header(null);

echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'&origin='.$origin.'&gradebook='.$gradebook.'">'.Display::return_icon('back.png', get_lang('BackToWorksList'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$workPerUser = getWorkPerUser($studentId);

$table = new HTML_Table(array('class' => 'data_table'));
$column = 0;
$row = 0;
$headers = array(get_lang('Title'), get_lang('HandedOutDate'), get_lang('HandOutDateLimit'), get_lang('Score'), get_lang('Actions'));
foreach ($headers as $header) {
    $table->setHeaderContents($row, $column, $header);
    $column++;
}
$row++;
$column = 0;

foreach ($workPerUser as $work) {
    $work = $work['work'];
    $scoreWeight = intval($work->qualification) == 0 ? null : $work->qualification;
    $workId = $work->id;
    $workExtraData = get_work_assignment_by_id($workId);

    foreach ($work->user_results as $userResult) {
        $table->setCellContents($row, $column, $work->title.' ['.strip_tags($userResult['title']).']');
        $table->setCellAttributes($row, $column, array('width' => '300px'));
        $column++;
        $table->setCellContents($row, $column, $userResult['sent_date']);
        $column++;
        //$dateQualification = !empty($workExtraData['expires_on']) && $workExtraData['expires_on'] != '0000-00-00 00:00:00' ? api_get_utc_datetime($workExtraData['expires_on']) : '-';
        $dateQualification = !empty($workExtraData['expires_on']) && $workExtraData['expires_on'] != '0000-00-00 00:00:00' ? api_get_local_time($workExtraData['expires_on']) : '-';
        $table->setCellContents($row, $column, $dateQualification);
        $column++;
        //var_dump($userResult);
        $score = '-';
        if (!empty($scoreWeight)) {
            $score = strip_tags($userResult['qualification'])."/".$scoreWeight;
        }
        $table->setCellContents($row, $column, $score);
        $column++;

        // Actions
        $links = null;

        if (empty($userResult['url'])) {
            // is a text
            $url = api_get_path(WEB_CODE_PATH).'work/view.php?'.api_get_cidreq().'&id='.$userResult['id'];
            $links .= Display::url(Display::return_icon('default.png'), $url);
        } else {

            $url = api_get_path(WEB_CODE_PATH).'work/download.php?'.api_get_cidreq().'&id='.$userResult['id'];
            $links .= Display::url(Display::return_icon('save.png'), $url);
        }

        $url = api_get_path(WEB_CODE_PATH).'work/edit.php?'.api_get_cidreq().'&item_id='.$userResult['id'].'&id='.$workId.'&parent_id='.$workId;
        $links .= Display::url(Display::return_icon('edit.png', get_lang('Comment')), $url);

        $table->setCellContents($row, $column, $links);

        $row++;
        $column = 0;
    }
}

echo $table->toHtml();

Display :: display_footer();
