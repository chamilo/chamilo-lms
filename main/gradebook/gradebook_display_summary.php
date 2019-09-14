<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @package chamilo.gradebook
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_GRADEBOOK;

api_protect_course_script(true);
api_set_more_memory_and_time_limits();
api_block_anonymous_users();
GradebookUtils::block_students();

$cat_id = isset($_GET['selectcat']) ? (int) $_GET['selectcat'] : null;
$action = isset($_GET['action']) && $_GET['action'] ? $_GET['action'] : null;

$sessionId = api_get_session_id();
$courseInfo = api_get_course_info();
$statusToFilter = empty($sessionId) ? STUDENT : 0;

$userList = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    $sessionId,
    null,
    null,
    $statusToFilter
);

$loadStats = [];
if (api_get_setting('gradebook_detailed_admin_view') === 'true') {
    $loadStats = [1, 2, 3];
} else {
    if (api_get_configuration_value('gradebook_enable_best_score') !== false) {
        $loadStats = [2];
    }
}

/*Session::write('use_gradebook_cache', false);
$useCache = api_get_configuration_value('gradebook_use_apcu_cache');
$cacheAvailable = api_get_configuration_value('apc') && $useCache;

if ($cacheAvailable) {
    $cacheDriver = new \Doctrine\Common\Cache\ApcuCache();
    $cacheDriver->deleteAll();
    $cacheDriver->flushAll();
}*/

switch ($action) {
    case 'export_all':
        //Session::write('use_gradebook_cache', true);
        $cats = Category::load($cat_id, null, null, null, null, null, false);
        /** @var Category $cat */
        $cat = $cats[0];
        $allcat = $cat->get_subcategories(
            null,
            api_get_course_id(),
            api_get_session_id()
        );
        $alleval = $cat->get_evaluations(
            null,
            true,
            api_get_course_id(),
            api_get_session_id()
        );
        $alllink = $cat->get_links(
            null,
            true,
            api_get_course_id(),
            api_get_session_id()
        );

        $gradebooktable = new GradebookTable(
            $cat,
            $allcat,
            $alleval,
            $alllink,
            null, // params
            true, // $exportToPdf
            false, // showteacher
            null,
            $userList,
            $loadStats
        );

        $key = $gradebooktable->getPreloadDataKey();
        // preloads data
        Session::erase($key);
        $defaultData = $gradebooktable->preloadData();

        $tpl = new Template('', false, false, false);
        $params = [
            'pdf_title' => sprintf(get_lang('GradeFromX'), $courseInfo['name']),
            'session_info' => '',
            'course_info' => '',
            'pdf_date' => '',
            'course_code' => api_get_course_id(),
            'student_info' => null,
            'show_grade_generated_date' => true,
            'show_real_course_teachers' => false,
            'show_teacher_as_myself' => false,
            'orientation' => 'P',
        ];
        $pdf = new PDF('A4', $params['orientation'], $params, $tpl);
        $counter = 0;
        $htmlList = [];
        foreach ($userList as $index => $value) {
            $htmlList[] = GradebookUtils::generateTable(
                $courseInfo,
                $value['user_id'],
                $cats,
                false,
                true,
                $userList,
                $pdf
            );
            $counter++;
        }

        if (!empty($htmlList)) {
            $counter = 0;
            //error_log('Loading html list');
            $content = '';
            foreach ($htmlList as $value) {
                $content .= $value.'<pagebreak>';
                //error_log('Loading html: '.$counter);
                $counter++;
            }

            $tempFile = api_get_path(SYS_ARCHIVE_PATH).uniqid('gradebook_export_all').'.html';
            file_put_contents($tempFile, $content);
            //error_log('generating pdf');
            $pdf->html_to_pdf(
                $tempFile,
                null,
                null,
                false,
                true,
                true
            );
            //error_log('End generating');
        }

        // Delete calc_score session data
        Session::erase('calc_score');
        break;
    case 'download':
        //Session::write('use_gradebook_cache', true);
        $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
        $cats = Category::load($cat_id, null, null, null, null, null, false);
        GradebookUtils::generateTable($courseInfo, $userId, $cats);
        break;
}

$course_code = api_get_course_id();

$interbreadcrumb[] = [
    'url' => Category::getUrl(),
    'name' => get_lang('Gradebook'),
];
$interbreadcrumb[] = [
    'url' => '#',
    'name' => get_lang('GradebookListOfStudentsReports'),
];

$this_section = SECTION_COURSES;
Display::display_header('');
$token = Security::get_token();
echo Display::page_header(get_lang('GradebookListOfStudentsReports'));

echo '<div class="btn-group">';
if (count($userList) > 0) {
    $url = api_get_self().'?action=export_all&'.api_get_cidreq().'&selectcat='.$cat_id;
    echo Display::url(get_lang('ExportAllToPDF'), $url, ['class' => 'btn btn-default']);
}
echo '</div>';

$allowSkillRelItem = api_get_configuration_value('allow_skill_rel_items');

if (count($userList) == 0) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<br /><br /><table class="data_table">';
    echo '<tr><th>';
    echo get_lang('Student');
    echo '</th>';
    echo '<th>';
    echo get_lang('Action');
    echo '</th></tr>';
    foreach ($userList as $index => $value) {
        echo '<tr>
                <td width="70%">'
                .api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].') </td>';
        echo '<td>';
        $link = '';
        if ($allowSkillRelItem) {
            $url = api_get_path(WEB_CODE_PATH).'gradebook/skill_rel_user.php?'.api_get_cidreq().'&user_id='.$value['user_id'].'&selectcat='.$cat_id;
            $link = Display::url(
                get_lang('Skills'),
                $url,
                ['class' => 'btn btn-default']
            ).'&nbsp;';
        }

        $url = api_get_self().'?'.api_get_cidreq().'&action=download&user_id='.$value['user_id'].'&selectcat='.$cat_id;
        $link .= Display::url(
            get_lang('ExportToPDF'),
            $url,
            ['target' => '_blank', 'class' => 'btn btn-default']
        );
        echo $link;
        echo '</td></tr>';
    }
    echo '</table>';
}

Display::display_footer();
