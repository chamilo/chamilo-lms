<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University:
 * cleanup, refactoring and rewriting large parts of the code
 * @author Julio Montoya
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;
$current_course_tool = TOOL_SURVEY;

api_protect_course_script(true);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
// Coach can't view this page
$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(api_get_user_id(), api_get_course_info());

if ($isDrhOfCourse) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq());
    exit;
}
if (!api_is_allowed_to_edit(false, true) ||
    (api_is_session_general_coach() && 'false' === $extend_rights_for_coachs)
) {
    api_not_allowed(true);
    exit;
}

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_question_group = Database::get_course_table(TABLE_SURVEY_QUESTION_GROUP);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$survey_id = (int) $_GET['survey_id'];
$course_id = api_get_course_int_id();
$action = $_GET['action'] ?? null;

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];

Session::erase('answer_count');
Session::erase('answer_list');

// Getting the survey information
if (!empty($_GET['survey_id'])) {
    $course_code = api_get_course_id();
    if (-1 != $course_code) {
        $survey_data = SurveyManager::get_survey($survey_id);
    } else {
        api_not_allowed(true);
    }
} else {
    api_not_allowed(true);
}

$tool_name = strip_tags($survey_data['title'], '<span>');
$is_survey_type_1 = 1 == $survey_data['survey_type'];

if (api_strlen(strip_tags($survey_data['title'])) > 40) {
    $tool_name .= '...';
}

if ($is_survey_type_1 && ('addgroup' === $action || 'deletegroup' === $action)) {
    $_POST['name'] = trim($_POST['name']);
    if ('addgroup' === $action) {
        if (!empty($_POST['group_id'])) {
            Database::query('UPDATE '.$table_survey_question_group.' SET description = \''.Database::escape_string($_POST['description']).'\'
                             WHERE c_id = '.$course_id.' AND id = \''.Database::escape_string($_POST['group_id']).'\'');
            Display::addFlash(Display::return_message(get_lang('Update successful')));
        } elseif (!empty($_POST['name'])) {
            Database::query('INSERT INTO '.$table_survey_question_group.' (c_id, name,description,survey_id) values ('.$course_id.', \''.Database::escape_string($_POST['name']).'\',\''.Database::escape_string($_POST['description']).'\',\''.$survey_id.'\') ');
            Display::addFlash(Display::return_message(get_lang('Item added')));
        } else {
            Display::addFlash(Display::return_message(get_lang('Group need name'), 'warning'));
        }
    }

    if ('deletegroup' === $action) {
        $sql = 'DELETE FROM '.$table_survey_question_group.'
                WHERE c_id = '.$course_id.' AND id = '.intval($_GET['gid']).' AND survey_id = '.$survey_id;
        Database::query($sql);
        Display::addFlash(Display::return_message(get_lang('Deleted')));
    }

    api_location(api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq());
}

$my_question_id_survey = isset($_GET['question_id']) ? (int) $_GET['question_id'] : null;
$my_survey_id_survey = (int) $_GET['survey_id'];
// Displaying the header
if (!empty($action)) {
    switch ($action) {
        case 'copyquestion':
            $copied = SurveyManager::copyQuestion($_GET['question_id']);
            if (false !== $copied) {
                Display::addFlash(Display::return_message(get_lang('The question has been added.')));
            } else {
                Display::addFlash(Display::return_message(get_lang('An error occurred.'), 'warning'));
            }
            break;
        case 'delete':
            $result = SurveyManager::deleteQuestion($my_question_id_survey);
            if (false == $result) {
                Display::addFlash(Display::return_message(get_lang('An error occurred.'), 'warning'));
            } else {
                Display::addFlash(Display::return_message(get_lang('Deleted')));
            }
            break;
        case 'moveup':
        case 'movedown':
            SurveyManager::move_survey_question(
                $action,
                $my_question_id_survey,
                $my_survey_id_survey
            );
            Display::addFlash(Display::return_message(get_lang('The question has been moved')));
            break;
    }

    api_location(api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq());
}

Display::display_header($tool_name, 'Survey');

if (!empty($survey_data['survey_version'])) {
    echo '<b>'.get_lang('Version').': '.$survey_data['survey_version'].'</b>';
}

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($_GET['survey_id']);

// Action links
$survey_actions = '';
if (3 != $survey_data['survey_type']) {
    $survey_actions = '<a href="'.api_get_path(WEB_CODE_PATH).'survey/create_new_survey.php?'.api_get_cidreq(
        ).'&action=edit&survey_id='.$survey_id.'">'.
        Display::return_icon('edit.png', get_lang('Edit survey'), '', ICON_SIZE_MEDIUM).'</a>';
}
$survey_actions .= '<a
    href="'.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=delete&survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('Delete survey').'?', ENT_QUOTES)).'\')) return false;">'.
    Display::return_icon('delete.png', get_lang('Delete survey'), '', ICON_SIZE_MEDIUM).'</a>';

if (3 != $survey_data['survey_type']) {
    $survey_actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/preview.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
        Display::return_icon('preview_view.png', get_lang('Preview'), '', ICON_SIZE_MEDIUM).'</a>';
}

$survey_actions .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?'.api_get_cidreq().'&survey_id='.$survey_id.'">'.
    Display::return_icon('mail_send.png', get_lang('Publish'), '', ICON_SIZE_MEDIUM).'</a>';

if (3 != $survey_data['survey_type']) {
    if (!api_get_configuration_value('hide_survey_reporting_button')) {
        $survey_actions .= Display::url(
            Display::return_icon('statistics.png', get_lang('Reporting'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.api_get_cidreq().'&survey_id='.$survey_id
        );
    }
}

$survey_actions .= SurveyUtil::getAdditionalTeacherActions($survey_id, ICON_SIZE_MEDIUM);
echo Display::toolbarAction('survey', [$survey_actions]);

$urlQuestion = api_get_path(WEB_CODE_PATH).'survey/question.php?'.api_get_cidreq().'&action=add';
if (0 == $survey_data['survey_type']) {
    $questions = Display::url(
        Display::return_icon('yesno.png', get_lang('Yes / No'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=yesno&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('mcua.png', get_lang('Multiple choice'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=multiplechoice&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('mcma.png', get_lang('Multiple answers'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=multipleresponse&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('open_answer.png', get_lang('Open'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=open&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('dropdown.png', get_lang('Dropdown'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=dropdown&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('percentagequestion.png', get_lang('Percentage'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=percentage&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('scorequestion.png', get_lang('Score'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=score&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('commentquestion.png', get_lang('Comment'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=comment&survey_id='.$survey_id
    );
    $questions .= Display::url(
        Display::return_icon('mcua.png', get_lang('SurveyMultipleAnswerWithOther'), null, ICON_SIZE_BIG),
        $urlQuestion.'&type=multiplechoiceother&survey_id='.$survey_id
    );
    if (0 == $survey_data['one_question_per_page']) {
        $questions .= Display::url(
            Display::return_icon('yesno.png', get_lang('SurveyQuestionSelectiveDisplay'), null, ICON_SIZE_BIG),
            $urlQuestion.'&type=selectivedisplay&survey_id='.$survey_id
        );
        $questions .= Display::url(
            Display::return_icon('page_end.png', get_lang('Pagebreak'), null, ICON_SIZE_BIG),
            $urlQuestion.'&type=pagebreak&survey_id='.$survey_id
        );
    }

    echo Display::toolbarAction('questions', [$questions]);
} else {
    if (3 != $survey_data['survey_type']) {
        echo '<div class="well">';
        echo Display::url(
            Display::return_icon('yesno.png', get_lang('Yes / No'), null, ICON_SIZE_BIG),
            $urlQuestion.'&type=personality&survey_id='.$survey_id
        );
        echo '</a></div>';
    }
}

// Displaying the table header with all the questions
echo '<table class="table table-bordered data_table">';
echo '<thead>';
echo '<tr>';
echo '		<th width="5%">'.get_lang('N°').'</th>';
echo '		<th width="50%">'.get_lang('Title').'</th>';
echo '		<th width="15%">'.get_lang('Type').'</th>';
echo '		<th width="15%" >'.get_lang('Options').'</th>';
echo '		<th width="15%">'.get_lang('Edit').'</th>';
if ($is_survey_type_1) {
    echo '<th width="100">'.get_lang('Condition').'</th>';
    echo '<th width="40">'.get_lang('Group').'</th>';
}
echo '	</tr>';
echo '</thead>';

// Displaying the table contents with all the questions
$question_counter = 1;
/*$sql = "SELECT * FROM $table_survey_question_group
        WHERE c_id = $course_id AND survey_id = $survey_id
        ORDER BY iid";
$result = Database::query($sql);
$groups = [];
while ($row = Database::fetch_array($result)) {
    $groups[$row['id']] = $row['name'];
}*/
$sql = "SELECT survey_question.*, count(survey_question_option.iid) as number_of_options
        FROM $table_survey_question survey_question
        LEFT JOIN $table_survey_question_option survey_question_option
        ON
            survey_question.iid = survey_question_option.question_id
        WHERE
            survey_question.survey_id = $survey_id
        GROUP BY survey_question.iid
        ORDER BY survey_question.sort ASC";

$result = Database::query($sql);
$question_counter_max = Database::num_rows($result);
$questionsGroupClass = '';
while ($row = Database::fetch_array($result, 'ASSOC')) {
    $questionId = $row['iid'];

    $breakClass = '';
    // Visually impact questions between page breaks by changing the bg color
    if ('pagebreak' === $row['type']) {
        $breakClass = ' highlight';
        if (empty($questionsGroupClass)) {
            $questionsGroupClass = 'row_even';
        } else {
            $questionsGroupClass = '';
        }
    }

    echo '<tr class="'.$questionsGroupClass.$breakClass.'">';
    echo '	<td>'.$question_counter.'</td>';
    echo '	<td>';

    if (3 != $survey_data['survey_type']) {
        if (api_strlen($row['survey_question']) > 100) {
            echo api_substr(strip_tags($row['survey_question']), 0, 100).' ... ';
        } else {
            echo $row['survey_question'];
        }
    } else {
        $parts = explode('@@', $row['survey_question']);
        echo api_get_local_time($parts[0]).' - '.api_get_local_time($parts[1]);
    }

    if ('yesno' === $row['type']) {
        $tool_name = get_lang('YesNo');
    } elseif ('multiplechoice' === $row['type']) {
        $tool_name = get_lang('UniqueSelect');
    } elseif ('multipleresponse' === $row['type']) {
        $tool_name = get_lang('MultipleChoiceMultipleAnswers');
    } elseif ('selectivedisplay' === $row['type']) {
        $tool_name = get_lang('SurveyQuestionSelectiveDisplay');
    } else {
        $tool_name = get_lang(api_ucfirst(Security::remove_XSS($row['type'])));
    }

    echo '</td>';
    echo '<td>'.$tool_name.'</td>';
    echo '<td>'.$row['number_of_options'].'</td>';
    echo '<td>';
    if (3 != $survey_data['survey_type']) {
        echo '<a
            href="'.api_get_path(WEB_CODE_PATH).
            'survey/question.php?'.api_get_cidreq().'&action=edit&type='.$row['type'].'&survey_id='.$survey_id.'&question_id='.$questionId.'">'.
            Display::return_icon('edit.png', get_lang('Edit')).'</a>';
    }

    echo '<a
        href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?'.
        api_get_cidreq().'&action=copyquestion&type='.$row['type'].'&survey_id='.$survey_id.'&question_id='.$questionId.'">'.
        Display::return_icon('copy.png', get_lang('Copy'), '', ICON_SIZE_SMALL).'</a>';

    echo '<a
        href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?'.
        api_get_cidreq().'&action=delete&survey_id='.$survey_id.'&question_id='.$questionId.'"
        onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("DeleteSurveyQuestion").'?', ENT_QUOTES, $charset)).'\')) return false;">'.
        Display::return_icon('delete.png', get_lang('Delete')).'</a>';
    if (3 != $survey_data['survey_type']) {
        if ($question_counter > 1) {
            echo '<a
                href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?'.
                api_get_cidreq().'&action=moveup&survey_id='.$survey_id.'&question_id='.$questionId.'">'.
                Display::return_icon('up.png', get_lang('Move up')).'</a>';
        } else {
            echo Display::return_icon('up_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
        }
        if ($question_counter < $question_counter_max) {
            echo '<a
                href="'.api_get_path(WEB_CODE_PATH).
                'survey/survey.php?'.api_get_cidreq().'&action=movedown&survey_id='.$survey_id.'&question_id='.$questionId.'">'.
                Display::return_icon('down.png', get_lang('Move down')).'</a>';
        } else {
            echo Display::return_icon('down_na.png', '&nbsp;', '', ICON_SIZE_SMALL);
        }
    }
    echo '	</td>';
    $question_counter++;

    /*if ($is_survey_type_1) {
        echo '<td>'.((0 == $row['survey_group_pri']) ? get_lang('Secondary') : get_lang('Primary')).'</td>';
        echo '<td>'.((0 == $row['survey_group_pri']) ? $groups[$row['survey_group_sec1']].'-'.$groups[$row['survey_group_sec2']] : $groups[$row['survey_group_pri']]).'</td>';
    }*/
    echo '</tr>';
}

echo '</table>';

if ($is_survey_type_1) {
    echo '<br /><br /><b>'.get_lang('Manage groups').'</b><br /><br />';
    if (in_array(
        $_GET['sendmsg'],
        ['GroupUpdatedSuccessfully', 'GroupDeletedSuccessfully', 'GroupCreatedSuccessfully']
    )
    ) {
        echo Display::return_message(
            get_lang($_GET['sendmsg']),
            'confirmation',
            false
        );
    }

    if (in_array($_GET['sendmsg'], ['GroupNeedName'])) {
        echo Display::return_message(
            get_lang($_GET['sendmsg']),
            'warning',
            false
        );
    }
    echo '<table border="0">
            <tr><td width="100">'.get_lang('Name').'</td><td>'.get_lang('Description').'</td></tr></table>';
    echo '<form
        action="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?action=addgroup&survey_id='.$survey_id.'" method="post">';
    if ('editgroup' === $_GET['action']) {
        $sql = 'SELECT name,description FROM '.$table_survey_question_group.'
                WHERE id = '.intval($_GET['gid']).' AND survey_id = '.$survey_id.'
                LIMIT 1';
        $rs = Database::query($sql);
        $editedrow = Database::fetch_array($rs, 'ASSOC');
        echo '<input type="text" maxlength="20" name="name" value="'.$editedrow['name'].'" size="10" disabled>';
        echo '<input type="text" maxlength="150" name="description" value="'.$editedrow['description'].'" size="40">';
        echo '<input type="hidden" name="group_id" value="'.Security::remove_XSS($_GET['gid']).'">';
        echo '<input
                type="submit"
                value="'.get_lang('Save').'"'.'
                <input type="button" value="'.get_lang('Cancel').'"
                onclick="window.location.href = \'survey.php?survey_id='.Security::remove_XSS($survey_id).'\';" />';
    } else {
        echo '<input type="text" maxlength="20" name="name" value="" size="10">';
        echo '<input type="text" maxlength="250" name="description" value="" size="80">';
        echo '<input type="submit" value="'.get_lang('Create').'"';
    }
    echo '</form><br />';
    echo '<table class="data_table">';
    echo '	<tr class="row_odd">';
    echo '		<th width="200">'.get_lang('Name').'</th>';
    echo '		<th>'.get_lang('Description').'</th>';
    echo '		<th width="100">'.get_lang('Edit').'</th>';
    echo '	</tr>';

    $sql = 'SELECT id,name,description
            FROM '.$table_survey_question_group.'
            WHERE
                c_id = '.$course_id.' AND
                survey_id = '.intval($survey_id).'
            ORDER BY name';

    $rs = Database::query($sql);
    $grouplist = '';
    while ($row = Database::fetch_array($rs, 'ASSOC')) {
        $grouplist .= '<tr><td>'.$row['name'].'</td><td>'.$row['description'].'</td><td>'.
        '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&gid='.$row['id'].'&action=editgroup">'.
        Display::return_icon('edit.png', get_lang('Edit'), '', ICON_SIZE_SMALL).'</a> '.
        '<a
            href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&gid='.$row['id'].'&action=deletegroup"
            onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('Delete surveyGroup'), $row['name']).'?', ENT_QUOTES)).'\')) return false;">'.
        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_SMALL).'</a>'.
        '</td></tr>';
    }
    echo $grouplist.'</table>';
}

Session::erase('answer_count');
Display::display_footer();
