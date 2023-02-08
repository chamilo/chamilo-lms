<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

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

        $loadStats = GradebookTable::getExtraStatsColumnsToDisplay();

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
            $content = '';
            foreach ($htmlList as $value) {
                $content .= $value.'<pagebreak>';
                $counter++;
            }

            $tempFile = api_get_path(SYS_ARCHIVE_PATH).uniqid('gradebook_export_all').'.html';
            file_put_contents($tempFile, $content);
            $pdf->html_to_pdf(
                $tempFile,
                null,
                null,
                false,
                true,
                true
            );
        }

        // Delete calc_score session data
        Session::erase('calc_score');
        break;
    case 'download':
        $userId = isset($_GET['user_id']) && $_GET['user_id'] ? $_GET['user_id'] : null;
        $cats = Category::load($cat_id, null, null, null, null, null, false);
        GradebookUtils::generateTable($courseInfo, $userId, $cats, false, false, $userList);
        break;
    case 'add_comment':
        if (!api_is_allowed_to_edit()) {
            exit;
        }
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $gradeBookId = isset($_GET['gradebook_id']) ? (int) $_GET['gradebook_id'] : 0;
        $comment = '';
        $commentInfo = GradebookUtils::getComment($gradeBookId, $userId);
        if ($commentInfo) {
            $comment = $commentInfo['comment'];
        }
        $ajaxPath = api_get_path(WEB_AJAX_PATH).'gradebook.ajax.php?a=add_gradebook_comment';
        $save = Display::return_message(get_lang('Saved'));
        echo '<script>
            $(function() {
                $("form").on("submit", function(e) {
                   e.preventDefault();
                   $.ajax({
                        url: "'.$ajaxPath.'",
                        data: {
                            gradebook_id: "'.$gradeBookId.'",
                            user_id: "'.$userId.'",
                            comment: $("#comment").val()
                        },
                        success: function(data) {
                            $(".result").html("'.addslashes($save).'");
                        }
                    });
                });
            });
        </script>';
        $student = api_get_user_info($userId);
        $form = new FormValidator('add_comment');
        $form->addLabel(get_lang('User'), $student['complete_name']);
        $form->addTextarea('comment', get_lang('Comment'), ['id' => 'comment']);
        $form->addHtml('<div class="result"></div>');
        $form->addButtonSave(get_lang('Save'));
        $form->setDefaults(['comment' => $comment]);
        $form->display();
        exit;
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
$allowSkillRelItem = api_get_configuration_value('allow_skill_rel_items');
if ($allowSkillRelItem) {
    $htmlContentExtraClass[] = 'feature-item-user-skill-on';
}

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

if (count($userList) == 0) {
    echo Display::return_message(get_lang('NoResultsAvailable'), 'warning');
} else {
    echo '<br /><br /><div class="table-responsive">
            <table class="table table-hover table-striped table-bordered data_table">';
    echo '<tr><th>';
    echo get_lang('Student');
    echo '</th>';
    echo '<th>';
    echo get_lang('Action');
    echo '</th></tr>';
    $allowComments = api_get_configuration_value('allow_gradebook_comments');
    foreach ($userList as $index => $value) {
        $userData = api_get_person_name($value['firstname'], $value['lastname']).' ('.$value['username'].')';
        echo '<tr>
                <td width="70%">'.$userData.'</td>';
        echo '<td>';
        $link = '';
        if ($allowSkillRelItem) {
            $url = api_get_path(WEB_CODE_PATH).
                'gradebook/skill_rel_user.php?'.api_get_cidreq().'&user_id='.$value['user_id'].'&selectcat='.$cat_id;
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

        if ($allowComments) {
            $url = api_get_self().'?'.api_get_cidreq().'&action=add_comment&user_id='.$value['user_id'].'&gradebook_id='.$cat_id;
            $link .= '&nbsp;'.Display::url(
                get_lang('AddGradebookComment'),
                $url,
                ['target' => '_blank', 'class' => 'ajax btn btn-default']
            );
        }

        echo $link;

        echo '</td></tr>';
    }
    echo '</table></div>';
}

Display::display_footer();
