<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CSurvey;
use ChamiloSession as Session;
use Doctrine\ORM\Query\Expr\Join;

/**
 * This class offers a series of general utility functions for survey querying and display.
 */
class SurveyUtil
{
    /**
     * Checks whether the given survey has a pagebreak question as the first
     * or the last question.
     * If so, break the current process, displaying an error message.
     *
     * @param int  $survey_id Survey ID (database ID)
     * @param bool $continue  Optional. Whether to continue the current
     *                        process or exit when breaking condition found. Defaults to true (do not break).
     */
    public static function check_first_last_question($survey_id, $continue = true)
    {
        // Table definitions
        $tbl_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $course_id = api_get_course_int_id();
        $survey_id = (int) $survey_id;

        // Getting the information of the question
        $sql = "SELECT * FROM $tbl_survey_question
                WHERE c_id = $course_id AND survey_id='".$survey_id."'
                ORDER BY sort ASC";
        $result = Database::query($sql);
        $total = Database::num_rows($result);
        $counter = 1;
        $error = false;
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if ($counter == 1 && $row['type'] == 'pagebreak') {
                echo Display::return_message(get_lang('PagebreakNotFirst'), 'error', false);
                $error = true;
            }
            if ($counter == $total && $row['type'] == 'pagebreak') {
                echo Display::return_message(get_lang('PagebreakNotLast'), 'error', false);
                $error = true;
            }
            $counter++;
        }

        if (!$continue && $error) {
            Display::display_footer();
            exit;
        }
    }

    /**
     * This function removes an (or multiple) answer(s) of a user on a question of a survey.
     *
     * @param mixed   The user id or email of the person who fills the survey
     * @param int The survey id
     * @param int The question id
     * @param int The option id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function remove_answer($user, $survey_id, $question_id, $course_id, $sessionId = 0, $lpItemId = 0)
    {
        $course_id = intval($course_id);
        // table definition
        $table = Database::get_course_table(TABLE_SURVEY_ANSWER);

        // To select the answers by lp Item
        $lpItemCondition = '';
        if (true === api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }
        // To select the answers by session
        $sessionCondition = '';
        if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
            $sessionCondition = api_get_session_condition($sessionId);
        }

        $sql = "DELETE FROM $table
				WHERE
				    c_id = $course_id AND
                    user = '".Database::escape_string($user)."' AND
                    survey_id = '".intval($survey_id)."' AND
                    question_id = '".intval($question_id)."'
                    $sessionCondition
                    $lpItemCondition";
        Database::query($sql);
    }

    /**
     * This function stores an answer of a user on a question of a survey.
     *
     * @param mixed   The user id or email of the person who fills the survey
     * @param int Survey id
     * @param int Question id
     * @param int Option id
     * @param string  Option value
     * @param array $survey_data Survey data settings
     *
     * @return bool False if insufficient data, true otherwise
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function store_answer(
        $user,
        $survey_id,
        $question_id,
        $option_id,
        $option_value,
        $survey_data,
        $otherOption = '',
        $sessionId = 0,
        $lpItemId = 0
    ) {
        // If the question_id is empty, don't store an answer
        if (empty($question_id)) {
            return false;
        }

        // Table definition
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);

        // Make the survey anonymous
        if (1 == $survey_data['anonymous']) {
            $surveyUser = Session::read('surveyuser');
            if (empty($surveyUser)) {
                $user = md5($user.time());
                Session::write('surveyuser', $user);
            } else {
                $user = Session::read('surveyuser');
            }
        }

        $course_id = $survey_data['c_id'];

        if (!empty($otherOption)) {
            $option_id = $option_id.'@:@'.$otherOption;
        }
        // To select the answers by Lp Item
        $lpItemCondition = '';
        if (true === api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " , c_lp_item_id = $lpItemId";
        }
        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            $sessionCondition = " , session_id = $sessionId";
        }

        $sql = "INSERT INTO $table_survey_answer SET
                    c_id = $course_id,
                    user = '".Database::escape_string($user)."',
                    survey_id = '".Database::escape_string($survey_id)."',
                    question_id = '".Database::escape_string($question_id)."',
                    option_id = '".Database::escape_string($option_id)."',
                    value = '".Database::escape_string($option_value)."'
                    $sessionCondition
                    $lpItemCondition
                ";
        Database::query($sql);
        $insertId = Database::insert_id();
        if ($insertId) {
            $sql = "UPDATE $table_survey_answer SET answer_id = $insertId
                    WHERE iid = $insertId";
            Database::query($sql);

            return true;
        }

        return false;
    }

    /**
     * This function checks the parameters that are used in this page.
     *
     * @return string $people_filled The header, an error and the footer if any parameter fails, else it returns true
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function check_parameters($people_filled)
    {
        $error = false;

        // Getting the survey data
        $survey_data = SurveyManager::get_survey($_GET['survey_id']);

        // $_GET['survey_id'] has to be numeric
        if (!is_numeric($_GET['survey_id'])) {
            $error = get_lang('IllegalSurveyId');
        }

        // $_GET['action']
        $allowed_actions = [
            'overview',
            'questionreport',
            'userreport',
            'comparativereport',
            'completereport',
            'deleteuserreport',
        ];
        if (isset($_GET['action']) && !in_array($_GET['action'], $allowed_actions)) {
            $error = get_lang('ActionNotAllowed');
        }

        // User report
        if (isset($_GET['action']) && $_GET['action'] === 'userreport') {
            if ($survey_data['anonymous'] == 0) {
                foreach ($people_filled as $key => &$value) {
                    $people_filled_userids[] = $value['invited_user'];
                }
            } else {
                $people_filled_userids = $people_filled;
            }

            if (isset($_GET['user']) && !in_array($_GET['user'], $people_filled_userids)) {
                $error = get_lang('UnknowUser');
            }
        }

        // Question report
        if (isset($_GET['action']) && $_GET['action'] === 'questionreport') {
            if (isset($_GET['question']) && !is_numeric($_GET['question'])) {
                $error = get_lang('UnknowQuestion');
            }
        }

        if ($error) {
            $tool_name = get_lang('Reporting');
            Display::addFlash(
                Display::return_message(
                    get_lang('Error').': '.$error,
                    'error',
                    false
                )
            );
            Display::display_header($tool_name);
            Display::display_footer();
            exit;
        }

        return true;
    }

    /**
     * This function deals with the action handling.
     *
     * @param array $survey_data
     * @param array $people_filled
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function handle_reporting_actions($survey_data, $people_filled)
    {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        $lpItemId = isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : 0;
        // Getting the number of question
        $questions = SurveyManager::get_questions($survey_data['survey_id']);

        $counter = 0;
        foreach ($questions as $key => $value) {
            if ($value['type'] !== 'pagebreak') {
                $counter++;
            }
        }
        // Counting the number of questions that are relevant for the reporting
        $survey_data['number_of_questions'] = $counter;

        switch ($action) {
            case 'questionreport':
                self::display_question_report($survey_data);
                break;
            case 'userreport':
                self::displayUserReport($survey_data, $people_filled);
                break;
            case 'comparativereport':
                self::display_comparative_report();
                break;
            case 'completereport':
                if (api_get_configuration_value('allow_survey_tool_in_lp')) {
                    $surveysAnswered = SurveyManager::getInvitationsAnswered($survey_data['code'], api_get_course_int_id(), api_get_session_id());
                    if (count($surveysAnswered) > 0) {
                        $actionBar = '<div class="actions">';
                        $actionBar .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$survey_data['survey_id'].'&'.api_get_cidreq().'">'
                            .Display::return_icon(
                                'back.png',
                                get_lang('BackTo').' '.get_lang('ReportingOverview'),
                                [],
                                ICON_SIZE_MEDIUM
                            )
                            .'</a>';
                        $actionBar .= '</div>';
                        echo $actionBar;
                        foreach ($surveysAnswered as $survey) {
                            echo self::displayCompleteReport($survey_data, 0, true, true, !$survey_data['anonymous'], $survey->getLpItemId());
                        }
                    }
                } else {
                    echo self::displayCompleteReport($survey_data, 0, true, true, !$survey_data['anonymous']);
                }
                break;
            case 'deleteuserreport':
                self::delete_user_report($_GET['survey_id'], $_GET['user'], $lpItemId);
                break;
        }
    }

    /**
     * This function deletes the report of an user who wants to retake the survey.
     *
     * @param int $survey_id
     * @param int $user_id
     *
     * @author Christian Fasanando Flores <christian.fasanando@dokeos.com>
     *
     * @version November 2008
     */
    public static function delete_user_report($survey_id, $user_id, $lpItemId = 0)
    {
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        $course_id = api_get_course_int_id();
        $survey_id = (int) $survey_id;
        $user_id = Database::escape_string($user_id);

        if (!empty($survey_id) && !empty($user_id)) {

            // To delete or update the answer by session
            $sessionCondition = '';
            if (api_get_configuration_value('show_surveys_base_in_sessions')) {
                $sessionId = api_get_session_id();
                $sessionCondition = api_get_session_condition($sessionId);
            }

            $lpItemCondition = '';
            if (api_get_configuration_value('allow_survey_tool_in_lp')) {
                $lpItemCondition = " AND c_lp_item_id = $lpItemId";
            }

            // delete data from survey_answer by user_id and survey_id
            $sql = "DELETE FROM $table_survey_answer
			        WHERE c_id = $course_id AND survey_id = '".$survey_id."' AND user = '".$user_id."' $sessionCondition $lpItemCondition";
            Database::query($sql);
            // update field answered from survey_invitation by user_id and survey_id
            $sql = "UPDATE $table_survey_invitation SET answered = '0'
			        WHERE
			            c_id = $course_id AND
			            survey_code = (
                            SELECT code FROM $table_survey
                            WHERE
                                c_id = $course_id AND
                                survey_id = '".$survey_id."'
                        ) AND
			            user = '".$user_id."' $sessionCondition";
            $result = Database::query($sql);
        }

        if ($result !== false) {
            $message = get_lang('SurveyUserAnswersHaveBeenRemovedSuccessfully').'<br />
					<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&survey_id='
                .$survey_id.'">'.
                get_lang('GoBack').'</a>';
            echo Display::return_message($message, 'confirmation', false);
        }
    }

    /**
     * @param array $survey_data
     * @param array $people_filled
     *
     * @return string
     */
    public static function displayUserReportForm($survey_data, $people_filled)
    {
        $surveyId = $survey_data['survey_id'];

        if (empty($survey_data)) {
            return '';
        }

        // Step 1: selection of the user
        echo "<script>
        function jumpMenu(targ,selObj,restore) {
            eval(targ+\".location='\"+selObj.options[selObj.selectedIndex].value+\"'\");
            if (restore) selObj.selectedIndex=0;
        }
		</script>";
        echo get_lang('SelectUserWhoFilledSurvey').'<br />';
        echo '<select name="user" onchange="jumpMenu(\'parent\',this,0)">';
        echo '<option value="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='
            .Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'">'
            .get_lang('SelectUser').'</option>';

        foreach ($people_filled as $key => &$person) {
            if ($survey_data['anonymous'] == 0) {
                $name = $person['user_info']['complete_name_with_username'];
                $id = $person['user_id'];
                if ($id == '') {
                    $id = $person['invited_user'];
                    $name = $person['invited_user'];
                }
            } else {
                $name = get_lang('Anonymous').' '.($key + 1);
                $id = $person;
            }
            echo '<option value="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='
                .Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&user='
                .Security::remove_XSS($id).'&'.api_get_cidreq().'" ';
            if (isset($_REQUEST['user']) && $_REQUEST['user'] == $id) {
                echo 'selected="selected"';
            }
            echo '>'.$name.'</option>';
        }
        echo '</select>';
    }

    /**
     * @param int   $userId
     * @param array $survey_data
     * @param bool  $addMessage
     */
    public static function displayUserReportAnswers($userId, $survey_data, $addMessage = true, $lpItemId = 0)
    {
        // Database table definitions
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $course_id = (int) $survey_data['c_id'];
        $surveyId = (int) $survey_data['survey_id'];
        $userId = Database::escape_string($userId);

        $content = '';
        // Step 2: displaying the survey and the answer of the selected users
        if (!empty($userId)) {
            if ($addMessage) {
                $content .= Display::return_message(
                    get_lang('AllQuestionsOnOnePage'),
                    'normal',
                    false
                );
            }

            // Getting all the questions and options
            $sql = "SELECT
			            survey_question.question_id,
			            survey_question.survey_id,
			            survey_question.survey_question,
			            survey_question.display,
			            survey_question.max_value,
			            survey_question.sort,
			            survey_question.type,
                        survey_question_option.question_option_id,
                        survey_question_option.option_text,
                        survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON
					    survey_question.question_id = survey_question_option.question_id AND
					    survey_question_option.c_id = $course_id
					WHERE
					    survey_question NOT LIKE '%{{%' AND
					    survey_question.survey_id = '".$surveyId."' AND
                        survey_question.c_id = $course_id
					ORDER BY survey_question.sort, survey_question_option.sort ASC";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($row['type'] != 'pagebreak') {
                    $questions[$row['sort']]['question_id'] = $row['question_id'];
                    $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                    $questions[$row['sort']]['survey_question'] = Security::remove_XSS($row['survey_question']);
                    $questions[$row['sort']]['display'] = $row['display'];
                    $questions[$row['sort']]['type'] = $row['type'];
                    $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                    $questions[$row['sort']]['options'][$row['question_option_id']] = Security::remove_XSS($row['option_text']);
                }
            }
            // To select the answers by session
            $sessionCondition = '';
            $sessionId = api_get_session_id();
            if (api_get_configuration_value('show_surveys_base_in_sessions')) {
                if (!empty($sessionId)) {
                    $sessionCondition = api_get_session_condition($sessionId);
                }
            }

            // To select the answers by Lp item
            $lpItemCondition = '';
            if (api_get_configuration_value('allow_survey_tool_in_lp')) {
                $lpItemCondition = " AND c_lp_item_id = $lpItemId";
            }

            // Getting all the answers of the user
            $sql = "SELECT * FROM $table_survey_answer
			        WHERE
                        c_id = $course_id AND
                        survey_id = '".$surveyId."' AND
                        user = '".$userId."'
                        $sessionCondition $lpItemCondition";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $answers[$row['question_id']][] = $row['option_id'];
                $all_answers[$row['question_id']][] = $row;
            }

            // Displaying all the questions
            foreach ($questions as &$question) {
                // If the question type is a scoring then we have to format the answers differently
                switch ($question['type']) {
                    case 'score':
                        $finalAnswer = [];
                        if (is_array($question) && is_array($all_answers)) {
                            foreach ($all_answers[$question['question_id']] as $key => &$answer_array) {
                                $finalAnswer[$answer_array['option_id']] = $answer_array['value'];
                            }
                        }
                        break;
                    case 'multipleresponse':
                        $finalAnswer = isset($answers[$question['question_id']]) ? $answers[$question['question_id']] : '';
                        break;
                    default:
                        $finalAnswer = '';
                        if (isset($all_answers[$question['question_id']])) {
                            $finalAnswer = $all_answers[$question['question_id']][0]['option_id'];
                        }
                        break;
                }

                $display = survey_question::createQuestion($question['type']);
                $url = api_get_self();
                $form = new FormValidator('question', 'post', $url);
                $form->addHtml('<div class="survey_question_wrapper"><div class="survey_question">');
                $form->addHtml($question['survey_question']);
                $display->render($form, $question, $finalAnswer);
                $form->addHtml('</div></div>');
                $content .= $form->returnForm();
            }
        }

        return $content;
    }

    /**
     * This function displays the user report which is basically nothing more
     * than a one-page display of all the questions
     * of the survey that is filled with the answers of the person who filled the survey.
     *
     * @return string html code of the one-page survey with the answers of the selected user
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007 - Updated March 2008
     */
    public static function displayUserReport($survey_data, $people_filled, $addActionBar = true)
    {
        if (empty($survey_data)) {
            return '';
        }

        $surveyId = $survey_data['survey_id'];
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $reportingUrl = api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq();

        // Actions bar
        if ($addActionBar) {
            echo '<div class="actions">';
            echo '<a href="'.$reportingUrl.'">'.
                Display::return_icon(
                    'back.png',
                    get_lang('BackTo').' '.get_lang('ReportingOverview'),
                    '',
                    ICON_SIZE_MEDIUM
                )
                .'</a>';
            if (isset($_REQUEST['user'])) {
                if (api_is_allowed_to_edit()) {
                    // The delete link
                    echo '<a href="'.$reportingUrl.'&action=deleteuserreport&user='.Security::remove_XSS($_REQUEST['user']).'" >'.
                        Display::return_icon('delete.png', get_lang('Delete'), '', ICON_SIZE_MEDIUM).'</a>';
                }

                // Export the user report
                echo '<a href="javascript: void(0);" onclick="document.form1a.submit();">'
                    .Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a> ';
                echo '<a href="javascript: void(0);" onclick="document.form1b.submit();">'
                    .Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a> ';
                echo '<form id="form1a" name="form1a" method="post" action="'.api_get_self().'?action='
                    .Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'&user_id='
                    .Security::remove_XSS($_REQUEST['user']).'">';
                echo '<input type="hidden" name="export_report" value="export_report">';
                echo '<input type="hidden" name="export_format" value="csv">';
                echo '</form>';
                echo '<form id="form1b" name="form1b" method="post" action="'.api_get_self().'?action='
                    .Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'&user_id='
                    .Security::remove_XSS($_REQUEST['user']).'">';
                echo '<input type="hidden" name="export_report" value="export_report">';
                echo '<input type="hidden" name="export_format" value="xls">';
                echo '</form>';
                echo '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='
                    .Security::remove_XSS($_GET['action']).'&survey_id='.$surveyId.'&'.api_get_cidreq().'">';
                echo '</form>';
            }
            echo '</div>';
        }

        echo self::displayUserReportForm($survey_data, $people_filled);
        if (isset($_REQUEST['user'])) {
            if (api_get_configuration_value('allow_survey_tool_in_lp')) {
                self::addSelectLpItemsForm($surveyId, $course_id, $sessionId, 'userreport');
            }
            $lpItemId = isset($_REQUEST['lp_item']) ? (int) $_REQUEST['lp_item'] : 0;
            echo self::displayUserReportAnswers($_REQUEST['user'], $survey_data, true, $lpItemId);
        }
    }

    public static function addSelectLpItemsForm($surveyId, $courseId, $sessionId, $actionReport = '', &$form = null)
    {
        $em = Database::getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('sa.lpItemId, li.title, l.name')
            ->distinct()
            ->from('ChamiloCourseBundle:CSurveyAnswer', 'sa')
            ->innerJoin(
                'ChamiloCourseBundle:CLpItem',
                'li',
                Join::WITH,
                'sa.lpItemId = li.id'
            )
            ->innerJoin(
                'ChamiloCourseBundle:CLp',
                'l',
                Join::WITH,
                'l.iid = li.lpId'
            )
            ->where('sa.cId = :cId')
            ->andWhere('sa.surveyId = :surveyId')
            ->setParameter('cId', $courseId)
            ->setParameter('surveyId', $surveyId);

        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            $qb->andWhere('sa.sessionId = :sessionId')
               ->setParameter('sessionId', $sessionId);
        }

        $lpItemsArray = $qb->getQuery()
            ->getArrayResult();

        $options = [];
        if (count($lpItemsArray) > 0) {
            $options['0'] = ('userreport' == $actionReport) ? get_lang('None') : get_lang('All');
            foreach ($lpItemsArray as $lpItem) {
                $options[$lpItem['lpItemId']] = $lpItem['name'].' : '.$lpItem['title'];
            }
        }

        if ('questionreport' == $actionReport || 'userreport' == $actionReport) {
            $singlePage = isset($_GET['single_page']) ? (int) $_GET['single_page'] : '';
            $userId = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : '';
            $lpItemId = isset($_REQUEST['lp_item']) ? (int) $_REQUEST['lp_item'] : '';
            $filter = '';
            if (!empty($singlePage)) {
                $filter .= '&single_page='.$singlePage;
            }

            // Filter by Lp item
            $form = new FormValidator(
                'filter_lp',
                'post',
                api_get_self().'?'.api_get_cidreq().'&action='.$actionReport.'&survey_id='.$surveyId.$filter,
                '',
                ['id' => 'lp-search']
            );

            $form->addHtml('<hr />');
            $form->addSelect('lp_item', get_lang('FilterByLp'), $options);
            $form->addButtonSearch(get_lang('Filter'), 'submit_lp');
            $form->addHtml('<div class="clearfix"></div><hr />');
            if (!empty($userId)) {
                $form->addHidden('user', $userId);
            }
            $form->setDefaults(['lp_item' => $lpItemId]);
            echo $form->returnForm();
        } elseif ('comparativereport' == $actionReport) {
            $form->addSelect('lp_item', get_lang('FilterByLp'), $options);
        }
    }

    /**
     * This function displays the report by question.
     *
     * It displays a table with all the options of the question and the number of users who have answered positively on
     * the option. The number of users who answered positive on a given option is expressed in an absolute number, in a
     * percentage of the total and graphically using bars By clicking on the absolute number you get a list with the
     * persons who have answered this. You can then click on the name of the person and you will then go to the report
     * by user where you see all the answers of that user.
     *
     * @param    array    All the survey data
     *
     * @return string html code that displays the report by question
     *
     * @todo allow switching between horizontal and vertical.
     * @todo multiple response: percentage are probably not OK
     * @todo the question and option text have to be shortened and should expand when the user clicks on it.
     * @todo the pagebreak and comment question types should not be shown => removed from $survey_data before
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007 - Updated March 2008
     */
    public static function display_question_report($survey_data)
    {
        $singlePage = isset($_GET['single_page']) ? (int) $_GET['single_page'] : 0;
        // Determining the offset of the sql statement (the n-th question of the survey)
        $offset = !isset($_GET['question']) ? 0 : (int) $_GET['question'];
        $currentQuestion = isset($_GET['question']) ? (int) $_GET['question'] : 0;
        $surveyId = (int) $survey_data['survey_id'];
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
        $course_id = api_get_course_int_id();
        $lpItemId = isset($_REQUEST['lp_item']) ? (int) $_REQUEST['lp_item'] : '';
        $sessionId = api_get_session_id();

        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }

        // To select the aswers by lp item
        $lpItemCondition = '';
        $urlParams = '';
        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            if (!empty($lpItemId)) {
                $lpItemCondition = ' AND c_lp_item_id = '.$lpItemId;
                $urlParams .= '&lp_item='.$lpItemId;
            }
        }

        // Database table definitions
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $questions = [];

        echo '<div class="actions">';
        echo '<a
            href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq().'">'.
            Display::return_icon(
                'back.png',
                get_lang('BackTo').' '.get_lang('ReportingOverview'),
                '',
                ICON_SIZE_MEDIUM
            ).'</a>';

        echo Display::url(
            Display::return_icon(
                'pdf.png',
                get_lang('ExportToPdf'),
                '',
                ICON_SIZE_MEDIUM
            ),
            'javascript: void(0);',
            ['onclick' => 'exportToPdf();']
        );
        echo '</div>';

        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            self::addSelectLpItemsForm($surveyId, $course_id, $sessionId, 'questionreport');
        }

        $fromUntil = sprintf(get_lang('FromXUntilY'), $survey_data['avail_from'], $survey_data['avail_till']);
        $max = 80;
        $data = [
            get_lang('SurveyTitle') => cut(strip_tags($survey_data['title']), $max),
            get_lang('SurveySubTitle') => cut(strip_tags($survey_data['subtitle']), $max),
            get_lang('Dates') => $fromUntil,
            get_lang('SurveyIntroduction') => cut(strip_tags($survey_data['intro']), $max),
        ];

        $table = new HTML_Table(['id' => 'pdf_table', 'class' => 'table']);
        $row = 0;
        foreach ($data as $label => $item) {
            $table->setCellContents($row, 0, $label);
            $table->setCellContents($row, 1, Security::remove_XSS($item));
            $row++;
        }

        if ($survey_data['number_of_questions'] > 0) {
            $limitStatement = null;
            if (!$singlePage) {
                echo '<div id="question_report_questionnumbers" class="pagination">';
                if ($currentQuestion != 0) {
                    echo '<li><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action.'&'
                        .api_get_cidreq().'&survey_id='.$surveyId.'&question='.($offset - 1).$urlParams.'">'
                        .get_lang('PreviousQuestion').'</a></li>';
                }

                for ($i = 1; $i <= $survey_data['number_of_questions']; $i++) {
                    if ($offset != $i - 1) {
                        echo '<li><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action.'&'
                            .api_get_cidreq().'&survey_id='.$surveyId.'&question='.($i - 1).$urlParams.'">'.$i.'</a></li>';
                    } else {
                        echo '<li class="disabled"><a href="#">'.$i.'</a></li>';
                    }
                }
                if ($currentQuestion < ($survey_data['number_of_questions'] - 1)) {
                    echo '<li><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action.'&'
                        .api_get_cidreq().'&survey_id='.$surveyId.'&question='.($offset + 1).$urlParams.'">'
                        .get_lang('NextQuestion').'</li></a>';
                }
                echo '</ul>';
                echo '</div>';
                $limitStatement = " LIMIT $offset, 1";
            }

            // Getting the question information
            $sql = "SELECT * FROM $table_survey_question
			        WHERE
			            c_id = $course_id AND
                        survey_id = $surveyId AND
                        survey_question NOT LIKE '%{{%' AND
                        type <>'pagebreak'
                    ORDER BY sort ASC
                    $limitStatement";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $questions[$row['question_id']] = $row;
            }
        }

        echo '<div id="question_results">';
        foreach ($questions as $question) {
            echo '<div class="question-item">';
            $chartData = [];
            $options = [];
            $questionId = (int) $question['question_id'];

            echo '<div class="title-question">';
            echo Security::remove_XSS(strip_tags(isset($question['survey_question']) ? $question['survey_question'] : null));
            echo '</div>';

            if ('score' === $question['type']) {
                /** @todo This function should return the options as this is needed further in the code */
                $options = self::display_question_report_score($survey_data, $question, $offset, $lpItemId);
            } elseif ($question['type'] === 'open' || $question['type'] === 'comment') {
                echo '<div class="open-question" style="padding: 1px 0;">';
                /** @todo Also get the user who has answered this */
                $sql = "SELECT * FROM $table_survey_answer
                        WHERE
                            c_id = $course_id AND
                            survey_id= $surveyId AND
                            question_id = $questionId $sessionCondition $lpItemCondition";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    echo $row['option_id'].'<hr noshade="noshade" size="1" />';
                }
                echo '</div>';
            } else {
                // Getting the options ORDER BY sort ASC
                $sql = "SELECT * FROM $table_survey_question_option
                        WHERE
                            c_id = $course_id AND
                            survey_id = $surveyId AND
                            question_id = $questionId
                        ORDER BY sort ASC";
                $result = Database::query($sql);
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    $options[$row['question_option_id']] = $row;
                }
                // Getting the answers
                $sql = "SELECT *, count(answer_id) as total
                        FROM $table_survey_answer
                        WHERE
                            c_id = $course_id AND
                            survey_id = $surveyId AND
                            question_id = $questionId
                            $sessionCondition
                            $lpItemCondition
                        GROUP BY option_id, value";
                $result = Database::query($sql);
                $number_of_answers = [];
                $data = [];
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if (!isset($number_of_answers[$row['question_id']])) {
                        $number_of_answers[$row['question_id']] = 0;
                    }
                    $number_of_answers[$row['question_id']] += $row['total'];

                    if ('multiplechoiceother' === $question['type']) {
                        $parts = ch_multiplechoiceother::decodeOptionValue($row['option_id']);
                        $row['option_id'] = $parts[0];
                    }

                    if (!isset($data[$row['option_id']])) {
                        $data[$row['option_id']] = $row;
                    } else {
                        $data[$row['option_id']]['total'] = $data[$row['option_id']]['total'] + $row['total'];
                    }
                }

                foreach ($options as $option) {
                    $optionText = strip_tags($option['option_text']);
                    $optionText = html_entity_decode($optionText);
                    $optionText = Security::remove_XSS($optionText);

                    $votes = 0;
                    if (isset($data[$option['question_option_id']]['total'])) {
                        $votes = $data[$option['question_option_id']]['total'];
                    }
                    array_push($chartData, ['option' => $optionText, 'votes' => $votes]);
                }
                $chartContainerId = 'chartContainer'.$question['question_id'];
                echo '<div id="'.$chartContainerId.'" style="text-align:center;">';
                echo self::drawChart($chartData, false, $chartContainerId, false);
                echo '</div>';

                // displaying the table: headers
                echo '<table class="display-survey table" id="table_'.$chartContainerId.'">';
                echo '';
                echo '	<tr>';
                echo '		<th style="width: 50%">&nbsp;</th>';
                echo '		<th style="width: 10%">'.get_lang('AbsoluteTotal').'</th>';
                echo '		<th style="width: 10%">'.get_lang('Percentage').'</th>';
                echo '		<th style="width: 30%">'.get_lang('VisualRepresentation').'</th>';
                echo '	</tr>';

                // Displaying the table: the content
                if (is_array($options)) {
                    foreach ($options as &$value) {
                        if ('multiplechoiceother' === $question['type'] && 'other' === $value['option_text']) {
                            $value['option_text'] = get_lang('SurveyOtherAnswer');
                        }

                        $absolute_number = null;
                        if (isset($data[$value['question_option_id']])) {
                            $absolute_number = $data[$value['question_option_id']]['total'];
                        }
                        if ($question['type'] === 'percentage' && empty($absolute_number)) {
                            continue;
                        }
                        $number_of_answers[$option['question_id']] = isset($number_of_answers[$option['question_id']])
                            ? $number_of_answers[$option['question_id']]
                            : 0;
                        if ($number_of_answers[$option['question_id']] == 0) {
                            $answers_number = 0;
                        } else {
                            $answers_number = $absolute_number / $number_of_answers[$option['question_id']] * 100;
                        }
                        echo '<tr>';
                        echo '<td>'.Security::remove_XSS($value['option_text']).'</td>';
                        echo '<td>';
                        if ($absolute_number != 0) {
                            echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action
                                .'&survey_id='.$surveyId.'&question='.$offset.'&viewoption='
                                .$value['question_option_id'].'">'.$absolute_number.'</a>';
                        } else {
                            echo '0';
                        }
                        echo '</td>';
                        echo '<td>'.round($answers_number, 2).' %</td>';
                        echo '<td>';
                        $size = $answers_number * 2;
                        if ($size > 0) {
                            echo '<div
                                    style="border:1px solid #264269; background-color:#aecaf4; height:10px;
                                    width:'.$size.'px">
                                    &nbsp;
                                    </div>';
                        } else {
                            echo '<div style="text-align: left;">'.get_lang('NoDataAvailable').'</div>';
                        }
                        echo ' </td>';
                        echo ' </tr>';
                    }
                }

                $optionResult = '';
                if (isset($option['question_id']) && isset($number_of_answers[$option['question_id']])) {
                    if ($number_of_answers[$option['question_id']] == 0) {
                        $optionResult = '0';
                    } else {
                        $optionResult = $number_of_answers[$option['question_id']];
                    }
                }

                // displaying the table: footer (totals)
                echo '	<tr>
                            <td><b>'.get_lang('Total').'</b></td>
                            <td><b>'.$optionResult.'</b></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        </table>';
            }
            echo '</div>';
        }
        echo '</div>';

        // Survey information, needed for the PDF export.
        echo Display::page_subheader(get_lang('Survey')).'<br />';
        $table->display();

        if (isset($_GET['viewoption'])) {
            echo '<div class="answered-people">';
            echo '<h4>'.get_lang('PeopleWhoAnswered').': '
                .strip_tags($options[Security::remove_XSS($_GET['viewoption'])]['option_text']).'</h4>';

            if (is_numeric($_GET['value'])) {
                $sql_restriction = "AND value='".Database::escape_string($_GET['value'])."'";
            }

            $sql = "SELECT user FROM $table_survey_answer
                    WHERE
                        c_id = $course_id AND
                        option_id = '".Database::escape_string($_GET['viewoption'])."'
                        $sql_restriction $sessionCondition $lpItemCondition";
            $result = Database::query($sql);
            echo '<ul>';
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $user_info = api_get_user_info($row['user']);
                echo '<li><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&survey_id='
                    .$surveyId.'&user='.$row['user'].'">'
                    .$user_info['complete_name_with_username']
                    .'</a></li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    /**
     * Display score data about a survey question.
     *
     * @param    array    Question info
     * @param    int    The offset of results shown
     */
    public static function display_question_report_score($survey_data, $question, $offset, $lpItemId = 0)
    {
        // Database table definitions
        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $course_id = api_get_course_int_id();
        $surveyId = $survey_data['survey_id'];
        $sessionId = api_get_session_id();

        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }

        // Getting the options
        $sql = "SELECT * FROM $table_survey_question_option
                WHERE
                    c_id = $course_id AND
                    survey_id= $surveyId AND
                    question_id = '".intval($question['question_id'])."'
                ORDER BY sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $options[$row['question_option_id']] = $row;
        }

        $lpItemCondition = '';
        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }

        // Getting the answers
        $sql = "SELECT *, count(answer_id) as total
                FROM $table_survey_answer
                WHERE
                   c_id = $course_id AND
                   survey_id= $surveyId AND
                   question_id = '".Database::escape_string($question['question_id'])."'
                   $sessionCondition
                   $lpItemCondition
                GROUP BY option_id, value";
        $result = Database::query($sql);
        $number_of_answers = 0;
        while ($row = Database::fetch_array($result)) {
            $number_of_answers += $row['total'];
            $data[$row['option_id']][$row['value']] = $row;
        }

        $chartData = [];
        foreach ($options as $option) {
            $optionText = strip_tags($option['option_text']);
            $optionText = html_entity_decode($optionText);
            for ($i = 1; $i <= $question['max_value']; $i++) {
                $votes = null;
                if (isset($data[$option['question_option_id']][$i])) {
                    $votes = $data[$option['question_option_id']][$i]['total'];
                }

                if (empty($votes)) {
                    $votes = '0';
                }
                array_push(
                    $chartData,
                    [
                        'serie' => $optionText,
                        'option' => $i,
                        'votes' => $votes,
                    ]
                );
            }
        }
        echo '<div id="chartContainer" class="col-md-12">';
        echo self::drawChart($chartData, true);
        echo '</div>';

        // Displaying the table: headers
        echo '<table class="table">';
        echo '	<tr>';
        echo '		<th>&nbsp;</th>';
        echo '		<th>'.get_lang('Score').'</th>';
        echo '		<th>'.get_lang('AbsoluteTotal').'</th>';
        echo '		<th>'.get_lang('Percentage').'</th>';
        echo '		<th>'.get_lang('VisualRepresentation').'</th>';
        echo '	</tr>';

        // Displaying the table: the content
        foreach ($options as $key => &$value) {
            for ($i = 1; $i <= $question['max_value']; $i++) {
                $absolute_number = null;
                if (isset($data[$value['question_option_id']][$i])) {
                    $absolute_number = $data[$value['question_option_id']][$i]['total'];
                }

                echo '	<tr>';
                echo '		<td>'.$value['option_text'].'</td>';
                echo '		<td>'.$i.'</td>';
                echo '		<td><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action='.$action
                    .'&survey_id='.$surveyId.'&question='.Security::remove_XSS($offset)
                    .'&viewoption='.$value['question_option_id'].'&value='.$i.'">'.$absolute_number.'</a></td>';
                echo '		<td>'.round($absolute_number / $number_of_answers * 100, 2).' %</td>';
                echo '		<td>';
                $size = ($absolute_number / $number_of_answers * 100 * 2);
                if ($size > 0) {
                    echo '<div style="border:1px solid #264269; background-color:#aecaf4; height:10px; width:'.$size.'px">&nbsp;</div>';
                }
                echo '		</td>';
                echo '	</tr>';
            }
        }
        // Displaying the table: footer (totals)
        echo '	<tr>';
        echo '		<td style="border-top:1px solid black"><b>'.get_lang('Total').'</b></td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '		<td style="border-top:1px solid black"><b>'.$number_of_answers.'</b></td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '		<td style="border-top:1px solid black">&nbsp;</td>';
        echo '	</tr>';
        echo '</table>';
    }

    /**
     * This functions displays the complete reporting.
     *
     * @param array $survey_data
     * @param int   $userId
     * @param bool  $addActionBar
     * @param bool  $addFilters
     * @param bool  $addExtraFields
     *
     * @return string
     */
    public static function displayCompleteReport(
        $survey_data,
        $userId = 0,
        $addActionBar = true,
        $addFilters = true,
        $addExtraFields = true,
        $lpItemId = 0
    ) {
        // Database table definitions
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);

        $surveyId = (int) $survey_data['survey_id'];
        $course_id = (int) $survey_data['c_id'];
        $sessionId = api_get_session_id();

        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }

        // To select the answers by lp item
        $lpItemCondition = '';
        $lpItemId = (int) $lpItemId;
        if (true === api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }

        if (empty($surveyId) || empty($course_id)) {
            return '';
        }

        $action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
        $paramLpItem = '';
        $content = '';
        $suffixLpItem = '';
        if (!empty($lpItemId)) {
            $suffixLpItem = '-item'.$lpItemId;
            $paramLpItem = '&lp_item_id='.$lpItemId;
            $tableLp = Database::get_course_table(TABLE_LP_MAIN);
            $tableLpItem = Database::get_course_table(TABLE_LP_ITEM);
            $sql = "SELECT l.name,
                    li.title
                    FROM $tableLpItem li
                    INNER JOIN $tableLp l
                    ON l.iid = li.lp_id AND
                       l.c_id = li.c_id
                    WHERE li.c_id = $course_id AND
                          li.iid = $lpItemId";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) > 0) {
                $row = Database::fetch_assoc($rs);
                $lpName = $row['name'];
                $lpItemTitle = $row['title'];
                $content .= '<h3>'.$lpName.' : '.$lpItemTitle.'</h3>';
            }
        }

        if ($addActionBar) {
            $content .= '<div class="actions">';
            if (empty($lpItemId)) {
                $content .= '<a
                href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq().'">'
                    .Display::return_icon(
                        'back.png',
                        get_lang('BackTo').' '.get_lang('ReportingOverview'),
                        [],
                        ICON_SIZE_MEDIUM
                    )
                    .'</a>';
            }
            $content .= '<a class="survey_export_link" href="javascript: void(0);" onclick="formExportSubmit(\'form1a'.$suffixLpItem.'\')">'
                .Display::return_icon('export_csv.png', get_lang('ExportAsCSV'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '<a class="survey_export_link" href="javascript: void(0);" onclick="formExportSubmit(\'form1b'.$suffixLpItem.'\')">'
                .Display::return_icon('export_excel.png', get_lang('ExportAsXLS'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '<a class="survey_export_link" href="javascript: void(0);" onclick="formExportSubmit(\'form1c'.$suffixLpItem.'\')">'
                .Display::return_icon('export_compact_csv.png', get_lang('ExportAsCompactCSV'), '', ICON_SIZE_MEDIUM).'</a>';
            $content .= '</div>';

            // The form
            $content .= '<form id="form1a'.$suffixLpItem.'" name="form1a'.$suffixLpItem.'" method="post" action="'.api_get_self(
                ).'?action='.$action.'&survey_id='
                .$surveyId.'&'.api_get_cidreq().$paramLpItem.'">';
            $content .= '<input type="hidden" name="export_report" value="export_report">';
            $content .= '<input type="hidden" name="export_format" value="csv">';
            $content .= '</form>';
            $content .= '<form id="form1b'.$suffixLpItem.'" name="form1b'.$suffixLpItem.'" method="post" action="'.api_get_self(
                ).'?action='.$action.'&survey_id='
                .$surveyId.'&'.api_get_cidreq().$paramLpItem.'">';
            $content .= '<input type="hidden" name="export_report" value="export_report">';
            $content .= '<input type="hidden" name="export_format" value="xls">';
            $content .= '</form>';
            $content .= '<form id="form1c'.$suffixLpItem.'" name="form1c'.$suffixLpItem.'" method="post" action="'.api_get_self(
                ).'?action='.$action.'&survey_id='
                .$surveyId.'&'.api_get_cidreq().$paramLpItem.'">';
            $content .= '<input type="hidden" name="export_report" value="export_report">';
            $content .= '<input type="hidden" name="export_format" value="csv-compact">';
            $content .= '</form>';
        }

        $content .= '<form id="form2" name="form2" method="post" action="'.api_get_self().'?action='.$action.'&survey_id='
            .$surveyId.'&'.api_get_cidreq().$paramLpItem.'">';

        // The table
        $content .= '<br /><table class="table table-hover table-striped data_table" border="1">';
        // Getting the number of options per question
        $content .= '	<tr>';
        $content .= '		<th>';

        if ($addFilters) {
            if ((isset($_POST['submit_question_filter'.$suffixLpItem]) && $_POST['submit_question_filter'.$suffixLpItem]) ||
                (isset($_POST['export_report'.$suffixLpItem]) && $_POST['export_report'.$suffixLpItem])
            ) {
                $content .= '<button class="cancel"
                                type="submit"
                                name="reset_question_filter'.$suffixLpItem.'" value="'.get_lang('ResetQuestionFilter').'">'.
                                get_lang('ResetQuestionFilter').'</button>';
            }
            $content .= '<button
                            class = "save"
                            type="submit" name="submit_question_filter'.$suffixLpItem.'" value="'.get_lang('SubmitQuestionFilter').'">'.
                            get_lang('SubmitQuestionFilter').'</button>';
            $content .= '</th>';
        }

        $display_extra_user_fields = false;
        if ($addExtraFields) {
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem]) && $_POST['submit_question_filter'.$suffixLpItem] ||
                    isset($_POST['export_report'.$suffixLpItem]) && $_POST['export_report'.$suffixLpItem]) ||
                !empty($_POST['fields_filter'.$suffixLpItem])
            ) {
                // Show user fields section with a big th colspan that spans over all fields
                $extra_user_fields = UserManager::get_extra_fields(
                    0,
                    0,
                    5,
                    'ASC',
                    false,
                    true
                );
                $num = count($extra_user_fields);
                if ($num > 0) {
                    $content .= '<th '.($num > 0 ? ' colspan="'.$num.'"' : '').'>';
                    $content .= '<label>';
                    if ($addFilters) {
                        $content .= '<input type="checkbox" name="fields_filter'.$suffixLpItem.'" value="1" checked="checked"/> ';
                    }
                    $content .= get_lang('UserFields');
                    $content .= '</label>';
                    $content .= '</th>';
                    $display_extra_user_fields = true;
                }
            }
        }

        $sql = "SELECT
                  q.question_id,
                  q.type,
                  q.survey_question,
                  count(o.question_option_id) as number_of_options
				FROM $table_survey_question q
				LEFT JOIN $table_survey_question_option o
				ON q.question_id = o.question_id AND q.c_id = o.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    q.survey_id = '".$surveyId."' AND
				    q.c_id = $course_id
				GROUP BY q.question_id
				ORDER BY q.sort ASC";
        $result = Database::query($sql);
        $questions = [];
        while ($row = Database::fetch_array($result)) {
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem]) && $_POST['submit_question_filter'.$suffixLpItem]) ||
                (is_array($_POST['questions_filter'.$suffixLpItem]) &&
                in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem]))
            ) {
                // We do not show comment and pagebreak question types
                if ('pagebreak' !== $row['type']) {
                    $content .= ' <th';
                    if ($row['number_of_options'] > 0 && 'percentage' !== $row['type']) {
                        $content .= ' colspan="'.$row['number_of_options'].'"';
                    }
                    $content .= '>';
                    $content .= '<label>';
                    if ($addFilters) {
                        $content .= '<input
                                type="checkbox"
                                name="questions_filter'.$suffixLpItem.'[]" value="'.$row['question_id'].'" checked="checked"/>';
                    }
                    $content .= Security::remove_XSS($row['survey_question']);
                    $content .= '</label>';
                    $content .= '</th>';
                }
                // No column at all if it's not a question
            }
            $questions[$row['question_id']] = $row;
        }
        $content .= '	</tr>';

        // Getting all the questions and options
        $content .= '	<tr>';
        $content .= '		<th>&nbsp;</th>'; // the user column

        if (!(isset($_POST['submit_question_filter'.$suffixLpItem]) && $_POST['submit_question_filter'.$suffixLpItem] ||
            isset($_POST['export_report'.$suffixLpItem]) && $_POST['export_report'.$suffixLpItem]) || !empty($_POST['fields_filter'.$suffixLpItem])
        ) {
            if ($addExtraFields) {
                // show the fields names for user fields
                foreach ($extra_user_fields as &$field) {
                    $content .= '<th>'.$field[3].'</th>';
                }
            }
        }

        // cells with option (none for open question)
        $sql = "SELECT
                    sq.question_id,
                    sq.survey_id,
                    sq.survey_question,
                    sq.display,
                    sq.sort,
                    sq.type,
                    sqo.question_option_id,
                    sqo.option_text,
                    sqo.sort as option_sort
				FROM $table_survey_question sq
				LEFT JOIN $table_survey_question_option sqo
				ON sq.question_id = sqo.question_id AND sq.c_id = sqo.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    sq.survey_id = '".$surveyId."' AND
                    sq.c_id = $course_id
				ORDER BY sq.sort ASC, sqo.sort ASC";
        $result = Database::query($sql);

        $display_percentage_header = 1;
        $possible_answers = [];
        // in order to display only once the cell option (and not 100 times)
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a question filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem]) && $_POST['submit_question_filter'.$suffixLpItem]) ||
                (is_array($_POST['questions_filter'.$suffixLpItem]) && in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem]))
            ) {
                // we do not show comment and pagebreak question types
                if ('open' === $row['type'] || 'comment' === $row['type']) {
                    $content .= '<th>&nbsp;-&nbsp;</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 1;
                } elseif ($row['type'] == 'percentage' && $display_percentage_header) {
                    $content .= '<th>&nbsp;%&nbsp;</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 0;
                } elseif ($row['type'] === 'percentage') {
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                } elseif ($row['type'] !== 'pagebreak' && $row['type'] !== 'percentage') {
                    $content .= '<th>';
                    $content .= Security::remove_XSS($row['option_text']);
                    $content .= '</th>';
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                    $display_percentage_header = 1;
                }
            }
        }

        $content .= '	</tr>';

        $userCondition = '';
        if (!empty($userId)) {
            $userId = (int) $userId;
            $userCondition = " AND user = $userId ";
        }

        // Getting all the answers of the users
        $old_user = '';
        $answers_of_user = [];
        $sql = "SELECT * FROM $table_survey_answer
                WHERE
                    c_id = $course_id AND
                    survey_id = $surveyId
                    $userCondition
                    $sessionCondition
                    $lpItemCondition
                ORDER BY answer_id, user ASC";
        $result = Database::query($sql);
        $i = 1;
        while ($row = Database::fetch_array($result)) {
            if ($old_user != $row['user'] && $old_user != '') {
                $userParam = $old_user;
                if ($survey_data['anonymous'] != 0) {
                    $userParam = $i;
                    $i++;
                }
                $content .= self::display_complete_report_row(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $userParam,
                    $questions,
                    $display_extra_user_fields
                );
                $answers_of_user = [];
            }
            if (isset($questions[$row['question_id']]) &&
                'open' != $questions[$row['question_id']]['type'] &&
                'comment' != $questions[$row['question_id']]['type']
            ) {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            } else {
                $answers_of_user[$row['question_id']][0] = $row;
            }
            $old_user = $row['user'];
        }

        $userParam = $old_user;
        if (0 != $survey_data['anonymous']) {
            $userParam = $i;
            $i++;
        }

        $content .= self::display_complete_report_row(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $userParam,
            $questions,
            $display_extra_user_fields
        );

        // This is to display the last user
        $content .= '</table>';
        $content .= '</form>';

        return $content;
    }

    /**
     * Return user answers in a row.
     *
     * @param      $survey_data
     * @param      $possible_options
     * @param      $answers_of_user
     * @param      $user
     * @param      $questions
     * @param bool $display_extra_user_fields
     *
     * @return string
     */
    public static function display_complete_report_row(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $questions,
        $display_extra_user_fields = false
    ) {
        $user = Security::remove_XSS($user);
        $surveyId = (int) $survey_data['survey_id'];

        if (empty($surveyId)) {
            return '';
        }

        $content = '<tr>';
        $url = api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq();
        if ($survey_data['anonymous'] == 0) {
            if (intval($user) !== 0) {
                $userInfo = api_get_user_info($user);
                $user_displayed = '-';
                if (!empty($userInfo)) {
                    $user_displayed = $userInfo['complete_name_with_username'];
                }

                $content .= '<th>
                    <a href="'.$url.'&action=userreport&user='.$user.'">'
                        .$user_displayed.'
                    </a>
                    </th>'; // the user column
            } else {
                $content .= '<th>'.$user.'</th>'; // the user column
            }
        } else {
            $content .= '<th>'.get_lang('Anonymous').' '.$user.'</th>';
        }

        if ($display_extra_user_fields) {
            // Show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data(
                $user,
                false,
                false,
                false,
                true
            );
            if (!empty($user_fields_values)) {
                foreach ($user_fields_values as $fieldName => $value) {
                    $fieldValue = is_array($value) ? $value[$fieldName] : $value;
                    $content .= '<td align="center">'.$fieldValue.'</td>';
                }
            }
        }

        if (is_array($possible_options)) {
            foreach ($possible_options as $question_id => $possible_option) {
                if ($questions[$question_id]['type'] === 'open' || $questions[$question_id]['type'] === 'comment') {
                    $content .= '<td align="center">';
                    if (isset($answers_of_user[$question_id]) && isset($answers_of_user[$question_id]['0'])) {
                        $content .= $answers_of_user[$question_id]['0']['option_id'];
                    }
                    $content .= '</td>';
                } else {
                    foreach ($possible_option as $option_id => $value) {
                        if ($questions[$question_id]['type'] === 'multiplechoiceother') {
                            foreach ($answers_of_user[$question_id] as $key => $newValue) {
                                $parts = ch_multiplechoiceother::decodeOptionValue($key);
                                if (isset($parts[0])) {
                                    $data = $answers_of_user[$question_id][$key];
                                    unset($answers_of_user[$question_id][$key]);
                                    $newKey = $parts[0];
                                    $answers_of_user[$question_id][$newKey] = $data;
                                }
                            }
                        }
                        if ($questions[$question_id]['type'] === 'percentage') {
                            if (!empty($answers_of_user[$question_id][$option_id])) {
                                $content .= "<td align='center'>";
                                $content .= $answers_of_user[$question_id][$option_id]['value'];
                                $content .= "</td>";
                            }
                        } else {
                            $content .= '<td align="center">';
                            if (!empty($answers_of_user[$question_id][$option_id])) {
                                if (0 != $answers_of_user[$question_id][$option_id]['value']) {
                                    $content .= $answers_of_user[$question_id][$option_id]['value'];
                                } else {
                                    $content .= 'v';
                                }
                            }
                        }
                    }
                }
            }
        }

        $content .= '</tr>';

        return $content;
    }

    /**
     * Quite similar to display_complete_report(), returns an HTML string
     * that can be used in a csv file.
     *
     * @param array $survey_data The basic survey data as initially obtained by SurveyManager::get_survey()
     * @param int   $user_id     The ID of the user asking for the report
     * @param bool  $compact     Whether to present the long (v marks with multiple columns per question) or compact (one column per question) answers format
     *
     * @todo consider merging this function with display_complete_report
     *
     * @throws Exception
     *
     * @return string The contents of a csv file
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function export_complete_report($survey_data, $user_id = 0, $compact = false, $lpItemId = 0)
    {
        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

        if (empty($surveyId)) {
            return false;
        }

        $course = api_get_course_info();
        $course_id = $course['real_id'];
        $sessionId = api_get_session_id();

        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }

        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);

        $translate = false;
        if (api_get_configuration_value('translate_html') == true) {
            $translate = true;
        }

        // The first column
        $return = ';';

        // Show extra fields blank space (enough for extra fields on next line)
        $extra_user_fields = UserManager::get_extra_fields(
            0,
            0,
            5,
            'ASC',
            false,
            true
        );

        $num = count($extra_user_fields);
        if (!$survey_data['anonymous']) {
            $return .= str_repeat(';', $num);
        }
        $suffixLpItem = !empty($lpItemId) ? '-item'.$lpItemId : '';
        $sql = "SELECT
                    questions.question_id,
                    questions.type,
                    questions.survey_question,
                    count(options.question_option_id) as number_of_options
				FROM $table_survey_question questions
                LEFT JOIN $table_survey_question_option options
				ON
				  questions.question_id = options.question_id AND
				  options.c_id = questions.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    questions.type <> 'pagebreak' AND
				    questions.survey_id = $surveyId AND
                    questions.c_id = $course_id
				GROUP BY questions.question_id
				ORDER BY questions.sort ASC";

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if ($translate) {
                $row['survey_question'] = api_get_filtered_multilingual_HTML_string($row['survey_question'], $course['language']);
            }
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a question filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem])) ||
                (isset($_POST['submit_question_filter'.$suffixLpItem]) &&
                    is_array($_POST['questions_filter'.$suffixLpItem]) &&
                    in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem]))
            ) {
                if ($row['number_of_options'] == 0 || $compact) {
                    $return .= str_replace(
                        "\r\n",
                        '  ',
                        api_html_entity_decode(strip_tags($row['survey_question']), ENT_QUOTES)
                    )
                    .';';
                } else {
                    $numberOfOptions = $row['number_of_options'];
                    if ($row['type'] == 'multiplechoiceother') $numberOfOptions++;
                    for ($ii = 0; $ii < $numberOfOptions; $ii++) {
                        $return .= str_replace(
                            "\r\n",
                            '  ',
                            api_html_entity_decode(strip_tags($row['survey_question']), ENT_QUOTES)
                        )
                        .';';
                    }
                }
            }
        }

        $return .= "\n";
        // Getting all the questions and options
        $return .= ';';
        // Show the fields names for user fields
        if (!empty($extra_user_fields) && !$survey_data['anonymous']) {
            foreach ($extra_user_fields as &$field) {
                if ($translate) {
                    $field[3] = api_get_filtered_multilingual_HTML_string($field[3], $course['language']);
                }
                $return .= '"'
                    .str_replace(
                        "\r\n",
                        '  ',
                        api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES)
                    )
                    .'";';
            }
        }

        $sql = "SELECT DISTINCT
		            survey_question.question_id,
		            survey_question.survey_id,
		            survey_question.survey_question,
		            survey_question.display,
		            survey_question.sort,
		            survey_question.type,
                    survey_question_option.question_option_id,
                    survey_question_option.option_text,
                    survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON
				    survey_question.question_id = survey_question_option.question_id AND
				    survey_question_option.c_id = survey_question.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    survey_question.type <> 'pagebreak' AND
				    survey_question.survey_id = $surveyId AND
				    survey_question.c_id = $course_id
				ORDER BY survey_question.sort ASC, survey_question_option.sort ASC";
        $result = Database::query($sql);
        $possible_answers = [];
        $possible_answers_type = [];
        $current_question_type = '';
        $current_question_id = null;
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a question filter but the question is selected for display
            if ($translate) {
                $row['option_text'] = api_get_filtered_multilingual_HTML_string($row['option_text'], $course['language']);
            }
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem])) || (
                is_array($_POST['questions_filter'.$suffixLpItem]) &&
                in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem])
            )
            ) {
                if ($current_question_id != $row['question_id']) {
                    if ($current_question_type == 'multiplechoiceother') {
                        $return .= api_html_entity_decode(strip_tags(get_lang('Comment')), ENT_QUOTES).';';
                    }
                }

                $current_question_type = $row['type'];
                $current_question_id   = $row['question_id'];

                $row['option_text'] = str_replace(["\r", "\n"], ['', ''], $row['option_text']);
                if (!$compact) {
                    $return .= api_html_entity_decode(strip_tags($row['option_text']), ENT_QUOTES).';';



                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                } else {
                    $possible_answers[$row['question_id']][$row['question_option_id']] = $row['option_text'];
                }
                $possible_answers_type[$row['question_id']] = $row['type'];
            }
        }

        if ($current_question_type == 'multiplechoiceother') {
            $return .= api_html_entity_decode(strip_tags(get_lang('Comment')), ENT_QUOTES).';';
        }

        $return .= "\n";

        // To select the answers by Lp item
        $lpItemCondition = '';
        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }

        // Getting all the answers of the users
        $old_user = '';
        $answers_of_user = [];
        $sql = "SELECT * FROM $table_survey_answer
		        WHERE
		          c_id = $course_id AND
		          survey_id = $surveyId
                  $sessionCondition
                  $lpItemCondition
		          ";
        if ($user_id != 0) {
            $user_id = (int) $user_id;
            $sql .= " AND user = $user_id ";
        }
        $sql .= ' ORDER BY user ASC ';

        $questionIdList = array_keys($possible_answers_type);
        $open_question_iterator = 1;
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (!in_array($row['question_id'], $questionIdList)) {
                continue;
            }
            if ($old_user != $row['user'] && $old_user != '') {
                $return .= self::export_complete_report_row(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $old_user,
                    !$survey_data['anonymous'],
                    $compact,
                    $possible_answers_type
                );
                $answers_of_user = [];
            }

            if ('open' === $possible_answers_type[$row['question_id']] ||
                'comment' === $possible_answers_type[$row['question_id']]
            ) {
                $temp_id = 'open'.$open_question_iterator;
                $answers_of_user[$row['question_id']][$temp_id] = $row;
                $open_question_iterator++;
            } else {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            }
            $old_user = $row['user'];
        }

        // This is to display the last user
        $return .= self::export_complete_report_row(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $old_user,
            true,
            $compact,
            $possible_answers_type
        );

        return $return;
    }

    /**
     * Add a line to the csv file.
     *
     * @param array $survey_data               Basic survey data (we're mostly interested in the 'anonymous' index)
     * @param array $possible_options          Possible answers
     * @param array $answers_of_user           User's answers
     * @param mixed $user                      User ID or user details as string - Used as a string in the result string
     * @param bool  $display_extra_user_fields Whether to display user fields or not
     * @param bool  $compact                   Whether to show answers as different column values (true) or one column per option (false, default)
     *
     * @return string One line of the csv file
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function export_complete_report_row(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $display_extra_user_fields = false,
        $compact = false,
        $questionTypes = true
    ) {
        $return = '';
        if (0 == $survey_data['anonymous']) {
            if (0 !== intval($user)) {
                $userInfo = api_get_user_info($user);
                if (!empty($userInfo)) {
                    $user_displayed = $userInfo['complete_name_with_username'];
                } else {
                    $user_displayed = '-';
                }
                $return .= $user_displayed.';';
            } else {
                $return .= $user.';';
            }
        } else {
            $return .= '-;'; // The user column
        }

        if ($display_extra_user_fields) {
            // Show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data(
                $user,
                false,
                false,
                false,
                true
            );
            foreach ($user_fields_values as $fieldName => $value) {
                $fieldValue = is_array($value) ? $value[$fieldName] : $value;
                $return .= '"'.str_replace('"', '""', api_html_entity_decode(strip_tags($fieldValue), ENT_QUOTES)).'";';
            }
        }

        // Run through possible options
        if (is_array($possible_options)) {
            foreach ($possible_options as $question_id => $possible_option) {
                if (is_array($possible_option) && count($possible_option) > 0) {
                    $otherPaddingNeeded = ($questionTypes[$question_id] == 'multiplechoiceother' ? true : false);
                    foreach ($possible_option as $option_id => &$value) {
                        // For each option of this question, look if it matches the user's answer
                        $my_answer_of_user = !isset($answers_of_user[$question_id]) || isset($answers_of_user[$question_id]) && $answers_of_user[$question_id] == null ? [] : $answers_of_user[$question_id];
                        $key = array_keys($my_answer_of_user);
                        if (isset($key[0]) && substr($key[0], 0, 4) === 'open') {
                            // If this is an open type question (type starts by 'open'), take whatever answer is given
                            $return .= '"'.
                                str_replace(
                                    '"',
                                    '""',
                                    api_html_entity_decode(
                                        strip_tags(
                                            $answers_of_user[$question_id][$key[0]]['option_id']
                                        ),
                                        ENT_QUOTES
                                    )
                                ).
                                '";';
                        } elseif (!empty($answers_of_user[$question_id][$option_id])) {
                            // If this is a selected option from a list...
                            if ($compact) {
                                // If we asked for a compact view, show only one column for the question
                                // and fill it with the text of the selected option (i.e. "Yes") instead of an ID
                                if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                    $return .= $answers_of_user[$question_id][$option_id]['value'].";";
                                } else {
                                    $return .= '"'.
                                        str_replace(
                                            '"',
                                            '""',
                                            api_html_entity_decode(
                                                strip_tags(
                                                    $possible_option[$option_id]
                                                ),
                                                ENT_QUOTES
                                            )
                                        ).
                                        '";';
                                }
                            } else {
                                // If we don't want a compact view, show one column per possible option and mark a 'v'
                                // or the defined value in the corresponding column if the user selected it
                                if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                    $return .= '"'.$answers_of_user[$question_id][$option_id]['value'].'";';
                                } else {
                                    $return .= 'v;';
                                }
                            }
                        } elseif (isset($key[0]) && strpos($key[0], '@:@') !== false) {
                            list($idAnswer, $other) = explode('@:@', $key[0]);

                            if ($idAnswer == $option_id) {
                                $return .= (
                                    strlen($other) > 0
                                    ? 'v;"' . str_replace('"', '""', api_html_entity_decode(strip_tags($other), ENT_QUOTES)) . '";'
                                    : 'v;'
                                    );
                            } else {
                                if (!$compact) {
                                    $return .= ';';
                                    $otherPaddingNeeded = false;
                                }
                            }
                        } else {
                            if (!$compact || $questionTypes[$question_id] == 'multipleresponse') {
                                $return .= ';';
                            }
                        }
                    }
                    if ($otherPaddingNeeded == true) {
                        $return .= ';';
                    }
                }
            }
        }
        $return .= "\n";

        return $return;
    }

    /**
     * Quite similar to display_complete_report(), returns an HTML string
     * that can be used in a csv file.
     *
     * @todo consider merging this function with display_complete_report
     *
     * @return string The contents of a csv file
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function export_complete_report_xls($survey_data, $filename, $user_id = 0, $returnFile = false, $lpItemId = 0)
    {
        $course_id = api_get_course_int_id();
        $user_id = (int) $user_id;
        $surveyId = $survey_data['survey_id'];

        if (empty($course_id) || empty($surveyId)) {
            return false;
        }

        $spreadsheet = new PHPExcel();
        $spreadsheet->setActiveSheetIndex(0);
        $worksheet = $spreadsheet->getActiveSheet();
        $line = 1;
        $column = 1; // Skip the first column (row titles)

        // Show extra fields blank space (enough for extra fields on next line)
        // Show user fields section with a big th colspan that spans over all fields
        $extra_user_fields = UserManager::get_extra_fields(
            0,
            0,
            5,
            'ASC',
            false,
            true
        );
        $num = count($extra_user_fields);
        if (!$survey_data['anonymous']) {
            for ($i = 0; $i < $num; $i++) {
                $worksheet->setCellValueByColumnAndRow($column, $line, '');
                $column++;
            }
        }
        $display_extra_user_fields = true;

        // Database table definitions
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);

        // To select the answers by session
        $sessionId = api_get_session_id();
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }
        $suffixLpItem = !empty($lpItemId) ? '-item'.$lpItemId : '';
        // First line (questions)
        $sql = "SELECT
                    questions.question_id,
                    questions.type,
                    questions.survey_question,
                    count(options.question_option_id) as number_of_options
				FROM $table_survey_question questions
				LEFT JOIN $table_survey_question_option options
                ON
                  questions.question_id = options.question_id AND
                  options.c_id = questions.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    questions.type <> 'pagebreak' AND
				    questions.survey_id = $surveyId AND
				    questions.c_id = $course_id
				GROUP BY questions.question_id
				ORDER BY questions.sort ASC";
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            // We show the questions if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a question filter but the question is selected for display
            if (!(isset($_POST['submit_question_filter'.$suffixLpItem])) ||
                (isset($_POST['submit_question_filter'.$suffixLpItem]) && is_array($_POST['questions_filter'.$suffixLpItem]) &&
                in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem]))
            ) {
                if ($row['number_of_options'] == 0 && ($row['type'] === 'open' || $row['type'] === 'comment')) {
                    $worksheet->setCellValueByColumnAndRow(
                        $column,
                        $line,
                        api_html_entity_decode(
                            strip_tags($row['survey_question']),
                            ENT_QUOTES
                        )
                    );
                    $column++;
                } else {
                    $numberOfOptions = $row['number_of_options'];
                    if ($row['type'] == 'multiplechoiceother') $numberOfOptions++;
                    for ($ii = 0; $ii < $numberOfOptions; $ii++) {
                        $worksheet->setCellValueByColumnAndRow(
                            $column,
                            $line,
                            api_html_entity_decode(
                                strip_tags($row['survey_question']),
                                ENT_QUOTES
                            )
                        );
                        $column++;
                    }
                }
            }
        }

        $line++;
        $column = 1;
        // Show extra field values
        if ($display_extra_user_fields && !$survey_data['anonymous']) {
            // Show the fields names for user fields
            foreach ($extra_user_fields as &$field) {
                $worksheet->setCellValueByColumnAndRow(
                    $column,
                    $line,
                    api_html_entity_decode(strip_tags($field[3]), ENT_QUOTES)
                );
                $column++;
            }
        }

        // Getting all the questions and options (second line)
        $sql = "SELECT
                    survey_question.question_id,
                    survey_question.survey_id,
                    survey_question.survey_question,
                    survey_question.display,
                    survey_question.sort,
                    survey_question.type,
                    survey_question_option.question_option_id,
                    survey_question_option.option_text,
                    survey_question_option.sort as option_sort
				FROM $table_survey_question survey_question
				LEFT JOIN $table_survey_question_option survey_question_option
				ON
				    survey_question.question_id = survey_question_option.question_id AND
				    survey_question_option.c_id = survey_question.c_id
				WHERE
				    survey_question NOT LIKE '%{{%' AND
				    survey_question.type <> 'pagebreak' AND
				    survey_question.survey_id = $surveyId AND
				    survey_question.c_id = $course_id
				ORDER BY survey_question.sort ASC, survey_question_option.sort ASC";
        $result = Database::query($sql);
        $possible_answers = [];
        $possible_answers_type = [];
        $current_question_type = '';
        $current_question_id = null;
        while ($row = Database::fetch_array($result)) {
            // We show the options if
            // 1. there is no question filter and the export button has not been clicked
            // 2. there is a quesiton filter but the question is selected for display
            if (!isset($_POST['submit_question_filter'.$suffixLpItem]) ||
                (isset($_POST['questions_filter'.$suffixLpItem]) && is_array($_POST['questions_filter'.$suffixLpItem]) &&
                in_array($row['question_id'], $_POST['questions_filter'.$suffixLpItem]))
            ) {
                if ($current_question_id != $row['question_id']) {
                    if ($current_question_type == 'multiplechoiceother') {
                        $worksheet->setCellValueByColumnAndRow(
                            $column,
                            $line,
                            api_html_entity_decode(
                                strip_tags(get_lang('Comment')),
                                ENT_QUOTES
                            )
                        );
                        $column++;
                    }
                }

                $current_question_type = $row['type'];
                $current_question_id   = $row['question_id'];

                $worksheet->setCellValueByColumnAndRow(
                    $column,
                    $line,
                    api_html_entity_decode(
                        strip_tags($row['option_text']),
                        ENT_QUOTES
                    )
                );
                $possible_answers[$row['question_id']][$row['question_option_id']] = $row['question_option_id'];
                $possible_answers_type[$row['question_id']] = $row['type'];
                $column++;
            }
        }

        if ($current_question_type == 'multiplechoiceother') {
            $worksheet->setCellValueByColumnAndRow(
                $column,
                $line,
                api_html_entity_decode(
                    strip_tags(get_lang('Comment')),
                    ENT_QUOTES
                )
            );
        }

        // To select the answers by Lp item
        $lpItemCondition = '';
        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }

        // Getting all the answers of the users
        $line++;
        $column = 0;
        $old_user = '';
        $answers_of_user = [];
        $sql = "SELECT * FROM $table_survey_answer
                WHERE c_id = $course_id AND survey_id = $surveyId $sessionCondition $lpItemCondition";
        if ($user_id != 0) {
            $sql .= " AND user='".$user_id."' ";
        }
        $sql .= ' ORDER BY user ASC';

        $open_question_iterator = 1;
        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if ($old_user != $row['user'] && $old_user != '') {
                $return = self::export_complete_report_row_xls(
                    $survey_data,
                    $possible_answers,
                    $answers_of_user,
                    $old_user,
                    !$survey_data['anonymous'],
                    $possible_answers_type
                );
                foreach ($return as $elem) {
                    $worksheet->setCellValueByColumnAndRow($column, $line, $elem);
                    $column++;
                }
                $answers_of_user = [];
                $line++;
                $column = 0;
            }
            if ($possible_answers_type[$row['question_id']] === 'open' ||
                $possible_answers_type[$row['question_id']] === 'comment'
            ) {
                $temp_id = 'open'.$open_question_iterator;
                $answers_of_user[$row['question_id']][$temp_id] = $row;
                $open_question_iterator++;
            } else {
                $answers_of_user[$row['question_id']][$row['option_id']] = $row;
            }
            $old_user = $row['user'];
        }

        $return = self::export_complete_report_row_xls(
            $survey_data,
            $possible_answers,
            $answers_of_user,
            $old_user,
            true,
            $possible_answers_type
        );

        // this is to display the last user
        foreach ($return as $elem) {
            $worksheet->setCellValueByColumnAndRow($column, $line, $elem);
            $column++;
        }

        $file = api_get_path(SYS_ARCHIVE_PATH).api_replace_dangerous_char($filename);
        $writer = new PHPExcel_Writer_Excel2007($spreadsheet);
        $writer->save($file);

        if ($returnFile) {
            return $file;
        }

        DocumentManager::file_send_for_download($file, true, $filename);

        return null;
    }

    /**
     * Add a line to the csv file.
     *
     * @param array Possible answers
     * @param array User's answers
     * @param mixed User ID or user details as string - Used as a string in the result string
     * @param bool Whether to display user fields or not
     *
     * @return string One line of the csv file
     */
    public static function export_complete_report_row_xls(
        $survey_data,
        $possible_options,
        $answers_of_user,
        $user,
        $display_extra_user_fields = false,
        $questionTypes = true
    ) {
        $return = [];
        if ($survey_data['anonymous'] == 0) {
            if (intval($user) !== 0) {
                $userInfo = api_get_user_info($user);
                if ($userInfo) {
                    $user_displayed = $userInfo['complete_name_with_username'];
                } else {
                    $user_displayed = '-';
                }
                $return[] = $user_displayed;
            } else {
                $return[] = $user;
            }
        } else {
            $return[] = '-'; // The user column
        }

        if ($display_extra_user_fields) {
            //show user fields data, if any, for this user
            $user_fields_values = UserManager::get_extra_user_data(
                $user,
                false,
                false,
                false,
                true
            );
            foreach ($user_fields_values as $fieldName => $value) {
                $fieldValue = is_array($value) ? $value[$fieldName] : $value;
                $return[] = api_html_entity_decode(strip_tags($fieldValue), ENT_QUOTES);
            }
        }

        if (is_array($possible_options)) {
            foreach ($possible_options as $question_id => &$possible_option) {
                $otherPaddingNeeded = ($questionTypes[$question_id] == 'multiplechoiceother' ? true : false);
                if (is_array($possible_option) && count($possible_option) > 0) {
                    foreach ($possible_option as $option_id => &$value) {
                        $my_answers_of_user = isset($answers_of_user[$question_id])
                            ? $answers_of_user[$question_id]
                            : [];
                        $key = array_keys($my_answers_of_user);
                        if (isset($key[0]) && substr($key[0], 0, 4) == 'open') {
                            $return[] = api_html_entity_decode(
                                strip_tags($answers_of_user[$question_id][$key[0]]['option_id']),
                                ENT_QUOTES
                            );
                        } elseif (!empty($answers_of_user[$question_id][$option_id])) {
                            if ($answers_of_user[$question_id][$option_id]['value'] != 0) {
                                $return[] = $answers_of_user[$question_id][$option_id]['value'];
                            } else {
                                $return[] = 'v';
                            }
                        } elseif (isset($key[0]) && strpos($key[0], '@:@') !== false) {
                            list($idAnswer, $other) = explode('@:@', $key[0]);
                            if ($idAnswer == $option_id) {
                                if (strlen($other) > 0) {
                                    $return[] = 'v';
                                    $return[] = api_html_entity_decode(strip_tags($other), ENT_QUOTES);
                                    $otherPaddingNeeded = false;
                                } else {
                                    $return[] = 'v';
                                }
                            } else {
                                $return[] = '';
                            }
                        } else {
                            $return[] = '';
                        }
                    }
                    if ($otherPaddingNeeded == true) {
                        $return[] = '';
                    }
                }
            }
        }

        return $return;
    }

    /**
     * This function displays the comparative report which
     * allows you to compare two questions
     * A comparative report creates a table where one question
     * is on the x axis and a second question is on the y axis.
     * In the intersection is the number of people who have
     * answered positive on both options.
     *
     * @return string HTML code
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function display_comparative_report()
    {
        // Allowed question types for comparative report
        $allowed_question_types = [
            'yesno',
            'multiplechoice',
            'multipleresponse',
            'dropdown',
            'percentage',
            'score',
        ];

        $surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : 0;

        // Getting all the questions
        $questions = SurveyManager::get_questions($surveyId);

        // Actions bar
        echo '<div class="actions">';
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$surveyId.'&'.api_get_cidreq()
            .'">'
            .Display::return_icon(
                'back.png',
                get_lang('BackTo').' '.get_lang('ReportingOverview'),
                [],
                ICON_SIZE_MEDIUM
            )
            .'</a>';
        echo '</div>';

        // Displaying an information message that only the questions with predefined answers can be used in a comparative report
        echo Display::return_message(get_lang('OnlyQuestionsWithPredefinedAnswers'), 'normal', false);

        $xAxis = isset($_GET['xaxis']) ? Security::remove_XSS($_GET['xaxis']) : '';
        $yAxis = isset($_GET['yaxis']) ? Security::remove_XSS($_GET['yaxis']) : '';
        $course_id = api_get_course_int_id();
        $lpItemId = isset($_REQUEST['lp_item']) ? (int) $_REQUEST['lp_item'] : '';
        $sessionId = api_get_session_id();

        $url = api_get_self().'?'.api_get_cidreq().'&action='.Security::remove_XSS($_GET['action'])
            .'&survey_id='.$surveyId.'&xaxis='.$xAxis.'&y='.$yAxis;

        $form = new FormValidator('compare', 'get', $url);
        $form->addHidden('action', Security::remove_XSS($_GET['action']));
        $form->addHidden('survey_id', $surveyId);
        $optionsX = ['----'];
        $optionsY = ['----'];
        $defaults = [];
        foreach ($questions as &$question) {
            // Ignored tagged questions
            if ($question) {
                if (strpos($question['question'], '{{') !== false) {
                    $question = null;
                    continue;
                }
            }
            if (is_array($allowed_question_types)) {
                if (in_array($question['type'], $allowed_question_types)) {
                    if (isset($_GET['xaxis']) && $_GET['xaxis'] == $question['question_id']) {
                        $defaults['xaxis'] = $question['question_id'];
                    }

                    if (isset($_GET['yaxis']) && $_GET['yaxis'] == $question['question_id']) {
                        $defaults['yaxis'] = $question['question_id'];
                    }
                    $question['question'] = Security::remove_XSS($question['question']);

                    $optionsX[$question['question_id']] = api_substr(strip_tags($question['question']), 0, 90);
                    $optionsY[$question['question_id']] = api_substr(strip_tags($question['question']), 0, 90);
                }
            }
        }

        if (api_get_configuration_value('allow_survey_tool_in_lp')) {
            self::addSelectLpItemsForm($surveyId, $course_id, $sessionId, 'comparativereport', $form);
        }

        $form->addSelect('xaxis', get_lang('SelectXAxis'), $optionsX);
        $form->addSelect('yaxis', get_lang('SelectYAxis'), $optionsY);

        $form->addButtonSearch(get_lang('CompareQuestions'));
        $form->setDefaults($defaults);
        $form->display();

        // Getting all the information of the x axis
        if (!empty($xAxis)) {
            $question_x = SurveyManager::get_question($xAxis);
        }

        // Getting all the information of the y axis
        if (!empty($yAxis)) {
            $question_y = SurveyManager::get_question($yAxis);
        }

        if (!empty($xAxis) && !empty($yAxis)) {
            // Getting the answers of the two questions
            $answers_x = self::get_answers_of_question_by_user($surveyId, $xAxis, $lpItemId);
            $answers_y = self::get_answers_of_question_by_user($surveyId, $yAxis, $lpItemId);

            // Displaying the table
            $tableHtml = '<table border="1" class="table table-hover table-striped data_table">';
            $xOptions = [];
            // The header
            $tableHtml .= '<tr>';
            for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                if ($ii == 0) {
                    $tableHtml .= '<th>&nbsp;</th>';
                } else {
                    if ($question_x['type'] === 'score') {
                        for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                            $tableHtml .= '<th>'.Security::remove_XSS($question_x['answers'][($ii - 1)]).'<br />'.$x.'</th>';
                        }
                        $x = '';
                    } else {
                        $tableHtml .= '<th>'.Security::remove_XSS($question_x['answers'][($ii - 1)]).'</th>';
                    }
                    $optionText = strip_tags($question_x['answers'][$ii - 1]);
                    $optionText = html_entity_decode($optionText);
                    $optionText = Security::remove_XSS($optionText);
                    array_push($xOptions, trim($optionText));
                }
            }
            $tableHtml .= '</tr>';
            $chartData = [];
            // The main part
            for ($ij = 0; $ij < count($question_y['answers']); $ij++) {
                $currentYQuestion = strip_tags($question_y['answers'][$ij]);
                $currentYQuestion = html_entity_decode($currentYQuestion);
                // The Y axis is a scoring question type so we have more rows than the options (actually options * maximum score)
                if ($question_y['type'] == 'score') {
                    for ($y = 1; $y <= $question_y['maximum_score']; $y++) {
                        $tableHtml .= '<tr>';
                        for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                            if ($question_x['type'] == 'score') {
                                for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                                    if ($ii == 0) {
                                        $tableHtml .= '<th>'.Security::remove_XSS($question_y['answers'][($ij)]).' '.$y.'</th>';
                                        break;
                                    } else {
                                        $tableHtml .= '<td align="center">';
                                        $votes = self::comparative_check(
                                            $answers_x,
                                            $answers_y,
                                            $question_x['answersid'][($ii - 1)],
                                            $question_y['answersid'][($ij)],
                                            $x,
                                            $y
                                        );
                                        $tableHtml .= $votes;
                                        array_push(
                                            $chartData,
                                            [
                                                'serie' => [$currentYQuestion, $xOptions[$ii - 1]],
                                                'option' => $x,
                                                'votes' => $votes,
                                            ]
                                        );
                                        $tableHtml .= '</td>';
                                    }
                                }
                            } else {
                                if ($ii == 0) {
                                    $tableHtml .= '<th>'.Security::remove_XSS($question_y['answers'][$ij]).' '.$y.'</th>';
                                } else {
                                    $tableHtml .= '<td align="center">';
                                    $votes = self::comparative_check(
                                        $answers_x,
                                        $answers_y,
                                        $question_x['answersid'][($ii - 1)],
                                        $question_y['answersid'][($ij)],
                                        0,
                                        $y
                                    );
                                    $tableHtml .= $votes;
                                    array_push(
                                        $chartData,
                                        [
                                            'serie' => [$currentYQuestion, $xOptions[$ii - 1]],
                                            'option' => $y,
                                            'votes' => $votes,
                                        ]
                                    );
                                    $tableHtml .= '</td>';
                                }
                            }
                        }
                        $tableHtml .= '</tr>';
                    }
                } else {
                    // The Y axis is NOT a score question type so the number of rows = the number of options
                    $tableHtml .= '<tr>';
                    for ($ii = 0; $ii <= count($question_x['answers']); $ii++) {
                        if ($question_x['type'] === 'score') {
                            for ($x = 1; $x <= $question_x['maximum_score']; $x++) {
                                if ($ii == 0) {
                                    $tableHtml .= '<th>'.Security::remove_XSS($question_y['answers'][$ij]).'</th>';
                                    break;
                                } else {
                                    $tableHtml .= '<td align="center">';
                                    $votes = self::comparative_check(
                                        $answers_x,
                                        $answers_y,
                                        $question_x['answersid'][($ii - 1)],
                                        $question_y['answersid'][($ij)],
                                        $x,
                                        0
                                    );
                                    $tableHtml .= $votes;
                                    array_push(
                                        $chartData,
                                        [
                                            'serie' => [$currentYQuestion, $xOptions[$ii - 1]],
                                            'option' => $x,
                                            'votes' => $votes,
                                        ]
                                    );
                                    $tableHtml .= '</td>';
                                }
                            }
                        } else {
                            if ($ii == 0) {
                                $tableHtml .= '<th>'.Security::remove_XSS($question_y['answers'][($ij)]).'</th>';
                            } else {
                                $tableHtml .= '<td align="center">';
                                $votes = self::comparative_check(
                                    $answers_x,
                                    $answers_y,
                                    $question_x['answersid'][($ii - 1)],
                                    $question_y['answersid'][($ij)]
                                );
                                $tableHtml .= $votes;
                                array_push(
                                    $chartData,
                                    [
                                        'serie' => $xOptions[$ii - 1],
                                        'option' => $currentYQuestion,
                                        'votes' => $votes,
                                    ]
                                );
                                $tableHtml .= '</td>';
                            }
                        }
                    }
                    $tableHtml .= '</tr>';
                }
            }
            $tableHtml .= '</table>';
            echo '<div id="chartContainer" class="col-md-12">';
            echo self::drawChart($chartData, true);
            echo '</div>';
            echo $tableHtml;
        }
    }

    /**
     * Get all the answers of a question grouped by user.
     *
     * @param int $survey_id   Survey ID
     * @param int $question_id Question ID
     *
     * @return array Array containing all answers of all users, grouped by user
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007 - Updated March 2008
     */
    public static function get_answers_of_question_by_user($survey_id, $question_id, $lpItemId = 0)
    {
        $course_id = api_get_course_int_id();
        $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $sessionId = api_get_session_id();

        // To select the answers by session
        $sessionCondition = '';
        if (api_get_configuration_value('show_surveys_base_in_sessions')) {
            if (!empty($sessionId)) {
                $sessionCondition = api_get_session_condition($sessionId);
            }
        }
        // To select the answers by Lp item
        $lpItemCondition = '';
        if (api_get_configuration_value('allow_survey_tool_in_lp') && !empty($lpItemId)) {
            $lpItemCondition = " AND c_lp_item_id = $lpItemId";
        }

        $sql = "SELECT * FROM $table_survey_answer
                WHERE
                  c_id = $course_id AND
                  survey_id='".intval($survey_id)."' AND
                  question_id='".intval($question_id)."'
                  $sessionCondition
                  $lpItemCondition
                ORDER BY USER ASC";
        $result = Database::query($sql);
        $return = [];
        while ($row = Database::fetch_array($result)) {
            if ($row['value'] == 0) {
                $return[$row['user']][] = $row['option_id'];
            } else {
                $return[$row['user']][] = $row['option_id'].'*'.$row['value'];
            }
        }

        return $return;
    }

    /**
     * Count the number of users who answer positively on both options.
     *
     * @param array All answers of the x axis
     * @param array All answers of the y axis
     * @param int x axis value (= the option_id of the first question)
     * @param int y axis value (= the option_id of the second question)
     *
     * @return int Number of users who have answered positively to both options
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version February 2007
     */
    public static function comparative_check(
        $answers_x,
        $answers_y,
        $option_x,
        $option_y,
        $value_x = 0,
        $value_y = 0
    ) {
        if ($value_x == 0) {
            $check_x = $option_x;
        } else {
            $check_x = $option_x.'*'.$value_x;
        }
        if ($value_y == 0) {
            $check_y = $option_y;
        } else {
            $check_y = $option_y.'*'.$value_y;
        }

        $counter = 0;
        if (is_array($answers_x)) {
            foreach ($answers_x as $user => &$answers) {
                // Check if the user has given $option_x as answer
                if (in_array($check_x, $answers)) {
                    // Check if the user has given $option_y as an answer
                    if (!is_null($answers_y[$user]) &&
                        in_array($check_y, $answers_y[$user])
                    ) {
                        $counter++;
                    }
                }
            }
        }

        return $counter;
    }

    public static function saveInviteMail(CSurvey $survey, $content, $subject, $remind)
    {
        // Reminder or not
        if ($remind) {
            $survey->setReminderMail($content);
        } else {
            $survey->setInviteMail($content);
        }

        $survey->setMailSubject($subject);
        $em = Database::getManager();
        $em->persist($survey);
        $em->flush();
    }

    /**
     * This function saves all the invitations of course users
     * and additional users in the database
     * and sends the invitations by email.
     *
     * @param int    $surveyId
     * @param array  $users_array       Users array can be both a list of course uids AND a list of additional email addresses
     * @param string $invitation_title  title of the mail
     * @param string $invitation_text   text of the mail has to contain a **link** string or
     *                                  this will automatically be added to the end
     * @param int    $reminder
     * @param bool   $sendmail
     * @param int    $remindUnAnswered
     * @param bool   $isAdditionalEmail
     * @param bool   $hideLink
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya - Adding auto-generated link support
     *
     * @version January 2007
     */
    public static function saveInvitations(
        $surveyId,
        $users_array,
        $invitation_title,
        $invitation_text,
        $reminder = 0,
        $sendmail = false,
        $remindUnAnswered = 0,
        $isAdditionalEmail = false,
        $hideLink = false
    ) {
        $surveyId = (int) $surveyId;
        if (!is_array($users_array)) {
            return 0;
        }

        // Getting the survey information
        $survey_data = SurveyManager::get_survey($surveyId);
        $survey_invitations = self::get_invitations($survey_data['survey_code']);
        $already_invited = self::get_invited_users($survey_data['code']);

        // Remind unanswered is a special version of remind all reminder
        $exclude_users = [];
        if (1 == $remindUnAnswered) {
            // Remind only unanswered users
            $reminder = 1;
            $exclude_users = SurveyManager::get_people_who_filled_survey($surveyId);
        }

        $counter = 0; // Nr of invitations "sent" (if sendmail option)
        $course_id = api_get_course_int_id();
        $session_id = api_get_session_id();

        if (false == $isAdditionalEmail) {
            $result = CourseManager::separateUsersGroups($users_array);
            $groupList = $result['groups'];
            $users_array = $result['users'];

            foreach ($groupList as $groupId) {
                $userGroupList = GroupManager::getStudents($groupId, true);
                $userGroupIdList = array_column($userGroupList, 'user_id');
                $users_array = array_merge($users_array, $userGroupIdList);

                $params = [
                    'c_id' => $course_id,
                    'session_id' => $session_id,
                    'group_id' => $groupId,
                    'survey_code' => $survey_data['code'],
                ];

                $invitationExists = self::invitationExists(
                    $course_id,
                    $session_id,
                    $groupId,
                    $survey_data['code']
                );
                if (empty($invitationExists)) {
                    self::save_invitation($params);
                }
            }
        }

        $users_array = array_unique($users_array);
        foreach ($users_array as $key => $value) {
            if (!isset($value) || '' == $value) {
                continue;
            }

            // Skip user if reminding only unanswered people
            if (in_array($value, $exclude_users)) {
                continue;
            }
            // Get the unique invitation code if we already have it
            if ($reminder == 1 && array_key_exists($value, $survey_invitations)) {
                $invitation_code = $survey_invitations[$value]['invitation_code'];
            } else {
                $invitation_code = md5($value.microtime());
            }
            $new_user = false; // User not already invited
            // Store the invitation if user_id not in $already_invited['course_users'] OR email is not in $already_invited['additional_users']
            $addit_users_array = isset($already_invited['additional_users']) && !empty($already_invited['additional_users'])
                    ? explode(';', $already_invited['additional_users'])
                    : [];
            $my_alredy_invited = $already_invited['course_users'] == null ? [] : $already_invited['course_users'];
            if ((is_numeric($value) && !in_array($value, $my_alredy_invited)) ||
                (!is_numeric($value) && !in_array($value, $addit_users_array))
            ) {
                $new_user = true;
                if (!array_key_exists($value, $survey_invitations)) {
                    $params = [
                        'c_id' => $course_id,
                        'session_id' => $session_id,
                        'user' => $value,
                        'survey_code' => $survey_data['code'],
                        'invitation_code' => $invitation_code,
                        'invitation_date' => api_get_utc_datetime(),
                    ];
                    self::save_invitation($params);
                }
            }

            // Send the email if checkboxed
            if (($new_user || $reminder == 1) && $sendmail) {
                // Make a change for absolute url
                if (isset($invitation_text)) {
                    $invitation_text = api_html_entity_decode($invitation_text, ENT_QUOTES);
                    $invitation_text = str_replace('src="../../', 'src="'.api_get_path(WEB_PATH), $invitation_text);
                    $invitation_text = trim(stripslashes($invitation_text));
                }
                self::send_invitation_mail(
                    $value,
                    $invitation_code,
                    $invitation_title,
                    $invitation_text,
                    $hideLink
                );
                $counter++;
            }
        }

        return $counter; // Number of invitations sent
    }

    /**
     * @param $params
     *
     * @return bool|int
     */
    public static function save_invitation($params)
    {
        // Database table to store the invitations data
        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        if (!empty($params['c_id']) &&
            (!empty($params['user']) || !empty($params['group_id'])) &&
            !empty($params['survey_code'])
        ) {
            if (!isset($params['survey_invitation_id'])) {
                $params['survey_invitation_id'] = 0;
            }
            if (!isset($params['answered'])) {
                $params['answered'] = 0;
            }
            if (!isset($params['group_id'])) {
                $params['group_id'] = 0;
            }
            $insertId = Database::insert($table, $params);
            if ($insertId) {
                $sql = "UPDATE $table
                        SET survey_invitation_id = $insertId
                        WHERE iid = $insertId";
                Database::query($sql);
            }

            return $insertId;
        }

        return false;
    }

    /**
     * @param int    $courseId
     * @param int    $sessionId
     * @param int    $groupId
     * @param string $surveyCode
     *
     * @return int
     */
    public static function invitationExists($courseId, $sessionId, $groupId, $surveyCode)
    {
        $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $groupId = (int) $groupId;
        $surveyCode = Database::escape_string($surveyCode);

        $sql = "SELECT survey_invitation_id FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId AND
                    group_id = $groupId AND
                    survey_code = '$surveyCode'
                ";
        $result = Database::query($sql);

        return Database::num_rows($result);
    }

    /**
     * Send the invitation by mail.
     *
     * @param int invitedUser - the userId (course user) or emailaddress of additional user
     * $param string $invitation_code - the unique invitation code for the URL
     */
    public static function send_invitation_mail(
        $invitedUser,
        $invitation_code,
        $invitation_title,
        $invitation_text,
        $hideLink = false
    ) {
        $_user = api_get_user_info();
        $_course = api_get_course_info();
        $sessionId = api_get_session_id();

        // Replacing the **link** part with a valid link for the user
        $link = self::generateFillSurveyLink($invitation_code, $_course, $sessionId);
        if ($hideLink) {
            $full_invitation_text = str_replace('**link**', '', $invitation_text);
        } else {
            $text_link = '<a href="'.$link.'">'.get_lang('ClickHereToAnswerTheSurvey')."</a><br />\r\n<br />\r\n"
                .get_lang('OrCopyPasteTheFollowingUrl')." <br /> \r\n <br /> \r\n ".$link;

            $replace_count = 0;
            $full_invitation_text = api_str_ireplace('**link**', $text_link, $invitation_text, $replace_count);
            if ($replace_count < 1) {
                $full_invitation_text = $full_invitation_text."<br />\r\n<br />\r\n".$text_link;
            }
        }

        // Sending the mail
        $sender_name = api_get_person_name($_user['firstName'], $_user['lastName'], null, PERSON_NAME_EMAIL_ADDRESS);
        $sender_email = $_user['mail'];
        $sender_user_id = api_get_user_id();

        $replyto = [];
        if (api_get_setting('survey_email_sender_noreply') == 'noreply') {
            $noreply = api_get_setting('noreply_email_address');
            if (!empty($noreply)) {
                $replyto['Reply-to'] = $noreply;
                $sender_name = $noreply;
                $sender_email = $noreply;
                $sender_user_id = null;
            }
        }

        // Optionally: finding the e-mail of the course user
        if (is_numeric($invitedUser)) {
            MessageManager::send_message(
                $invitedUser,
                $invitation_title,
                $full_invitation_text,
                [],
                [],
                null,
                null,
                null,
                null,
                $sender_user_id,
                true
            );
        } else {
            $extraParameters = [];
            if (api_get_configuration_value('mail_header_from_custom_course_logo') == true) {
                $extraParameters = ['logo' => CourseManager::getCourseEmailPicture($_course)];
            }

            @api_mail_html(
                '',
                $invitedUser,
                $invitation_title,
                $full_invitation_text,
                $sender_name,
                $sender_email,
                $replyto,
                [],
                false,
                $extraParameters,
                ''
            );
        }
    }

    /**
     * This function recalculates the number of users who have been invited and updates the survey table with this
     * value.
     *
     * @param string Survey code
     * @param int $courseId
     * @param int $sessionId
     *
     * @return int
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function update_count_invited($survey_code, $courseId = 0, $sessionId = 0)
    {
        $survey_code = Database::escape_string($survey_code);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $courseId = $courseId ?: api_get_course_int_id();
        $sessionId = $sessionId ?: api_get_session_id();
        $sessionCondition = api_get_session_condition($sessionId);

        // Database table definition
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        // Counting the number of people that are invited
        $sql = "SELECT count(user) as total
                FROM $table_survey_invitation
		        WHERE
		            c_id = $courseId AND
		            survey_code = '".$survey_code."' AND
		            user <> ''
		            $sessionCondition
                ";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $total_invited = $row['total'];

        // Updating the field in the survey table
        $sql = "UPDATE $table_survey
		        SET invited = '".Database::escape_string($total_invited)."'
		        WHERE
		            c_id = $courseId AND
		            code = '".$survey_code."'
		            $sessionCondition
                ";
        Database::query($sql);

        return $total_invited;
    }

    /**
     * This function gets all the invited users for a given survey code.
     *
     * @param string Survey code
     * @param string optional - course database
     *
     * @return array Array containing the course users and additional users (non course users)
     *
     * @todo consider making $defaults['additional_users'] also an array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya, adding c_id fixes - Dec 2012
     *
     * @version January 2007
     */
    public static function get_invited_users($survey_code, $course_code = '', $session_id = 0)
    {
        $session_id = (int) $session_id;
        $survey_code = Database::escape_string($survey_code);
        $course_code = Database::escape_string($course_code);

        $course_id = api_get_course_int_id();

        if (!empty($course_code)) {
            $course_info = api_get_course_info($course_code);
            if ($course_info) {
                $course_id = $course_info['real_id'];
            }
        }

        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);

        // Selecting all the invitations of this survey AND the additional emailaddresses (the left join)
        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
        $sql = "SELECT user, group_id
				FROM $table_survey_invitation as table_invitation
				WHERE
				    table_invitation.c_id = $course_id AND
                    survey_code='".$survey_code."' AND
                    session_id = $session_id
                ";

        $defaults = [];
        $defaults['course_users'] = [];
        $defaults['additional_users'] = []; // Textarea
        $defaults['users'] = []; // user and groups

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            if (is_numeric($row['user'])) {
                $defaults['course_users'][] = $row['user'];
                $defaults['users'][] = 'USER:'.$row['user'];
            } else {
                if (!empty($row['user'])) {
                    $defaults['additional_users'][] = $row['user'];
                }
            }

            if (isset($row['group_id']) && !empty($row['group_id'])) {
                $defaults['users'][] = 'GROUP:'.$row['group_id'];
            }
        }

        if (!empty($defaults['course_users'])) {
            $user_ids = implode("','", $defaults['course_users']);
            $sql = "SELECT user_id FROM $table_user WHERE user_id IN ('$user_ids') $order_clause";
            $result = Database::query($sql);
            $fixed_users = [];
            while ($row = Database::fetch_array($result)) {
                $fixed_users[] = $row['user_id'];
            }
            $defaults['course_users'] = $fixed_users;
        }

        if (!empty($defaults['additional_users'])) {
            $defaults['additional_users'] = implode(';', $defaults['additional_users']);
        }

        return $defaults;
    }

    /**
     * Get all the invitations.
     *
     * @param string Survey code
     *
     * @return array Database rows matching the survey code
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version September 2007
     */
    public static function get_invitations($survey_code)
    {
        $course_id = api_get_course_int_id();
        $sessionId = api_get_session_id();
        // Database table definition
        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);

        $sql = "SELECT * FROM $table_survey_invitation
		        WHERE
		            c_id = $course_id AND
                    session_id = $sessionId AND
		            survey_code = '".Database::escape_string($survey_code)."'";
        $result = Database::query($sql);
        $return = [];
        while ($row = Database::fetch_array($result)) {
            $return[$row['user']] = $row;
        }

        return $return;
    }

    /**
     * This function displays the form for searching a survey.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     *
     * @todo consider moving this to surveymanager.inc.lib.php
     */
    public static function display_survey_search_form()
    {
        $url = api_get_path(WEB_CODE_PATH).'survey/survey_list.php?search=advanced&'.api_get_cidreq();
        $form = new FormValidator('search', 'get', $url);
        $form->addHeader(get_lang('SearchASurvey'));
        $form->addText('keyword_title', get_lang('Title'));
        $form->addText('keyword_code', get_lang('Code'));
        $form->addSelectLanguage('keyword_language', get_lang('Language'));
        $form->addHidden('cidReq', api_get_course_id());
        $form->addButtonSearch(get_lang('Search'), 'do_search');
        $form->display();
    }

    /**
     * Show table only visible by DRH users.
     */
    public static function displaySurveyListForDrh()
    {
        $parameters = [];
        $parameters['cidReq'] = api_get_course_id();

        // Create a sortable table with survey-data
        $table = new SortableTable(
            'surveys',
            'get_number_of_surveys',
            'get_survey_data_drh',
            2
        );
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));

        if (api_get_configuration_value('allow_mandatory_survey')) {
            $table->set_header(9, get_lang('IsMandatory'));
            $table->set_header(10, get_lang('Modify'), false, 'width="150"');
            $table->set_column_filter(9, 'anonymous_filter');
            $table->set_column_filter(10, 'modify_filter_drh');
        } else {
            $table->set_header(9, get_lang('Modify'), false, 'width="150"');
            $table->set_column_filter(9, 'modify_filter_drh');
        }

        $table->set_column_filter(8, 'anonymous_filter');
        $table->display();
    }

    /**
     * This function displays the sortable table with all the surveys.
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function display_survey_list()
    {
        $parameters = [];
        $parameters['cidReq'] = api_get_course_id();
        if (isset($_GET['do_search']) && $_GET['do_search']) {
            $message = get_lang('DisplaySearchResults').'<br />';
            $message .= '<a href="'.api_get_self().'?'.api_get_cidreq().'">'.get_lang('DisplayAll').'</a>';
            echo Display::return_message($message, 'normal', false);
        }

        // Create a sortable table with survey-data
        $table = new SortableTable(
            'surveys',
            'get_number_of_surveys',
            'get_survey_data',
            2
        );
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));

        if (api_get_configuration_value('allow_mandatory_survey')) {
            $table->set_header(9, get_lang('IsMandatory'));
            $table->set_header(10, get_lang('Modify'), false, 'width="150"');
            $table->set_column_filter(8, 'anonymous_filter');
            $table->set_column_filter(10, 'modify_filter');
        } else {
            $table->set_header(9, get_lang('Modify'), false, 'width="150"');
            $table->set_column_filter(9, 'modify_filter');
        }

        $table->set_column_filter(8, 'anonymous_filter');
        $actions = [
            'export_all' => get_lang('ExportResults'),
            'export_by_class' => get_lang('ExportByClass'),
            'send_to_tutors' => get_lang('SendToGroupTutors'),
            'multiplicate_by_class' => get_lang('MultiplicateQuestionsByClass'),
            'multiplicate_by_user' => get_lang('MultiplicateQuestionsByUser'),
            'delete' => get_lang('DeleteSurvey'),
        ];
        $table->set_form_actions($actions);

        $form = new FormValidator(
            'survey',
            'post',
            null,
            null,
            ['class' => 'form-vertical']
        );
        $form->addElement(
            'radio',
            'type',
            null,
            get_lang('MultiplicateQuestionsByClass'),
            'by_class',
            ['id' => 'by_class']
        );
        $form->addElement(
            'radio',
            'type',
            null,
            get_lang('MultiplicateQuestionsByUser'),
            'by_user',
            ['id' => 'by_user']
        );
        $form->setDefaults(['type' => 'by_class']);
        $formToString = $form->returnForm();

        echo '<div id="dialog-confirm">'.$formToString.'</div>';
        $table->display();
    }

    /**
     * Survey list for coach.
     */
    public static function display_survey_list_for_coach()
    {
        $parameters = [];
        $parameters['cidReq'] = api_get_course_id();
        if (isset($_GET['do_search'])) {
            $message = get_lang('DisplaySearchResults').'<br />';
            $message .= '<a href="'.api_get_self().'?'.api_get_cidreq().'">'.get_lang('DisplayAll').'</a>';
            echo Display::return_message($message, 'normal', false);
        }

        // Create a sortable table with survey-data
        $table = new SortableTable(
            'surveys_coach',
            'get_number_of_surveys_for_coach',
            'get_survey_data_for_coach',
            2
        );
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false);
        $table->set_header(1, get_lang('SurveyName'));
        $table->set_header(2, get_lang('SurveyCode'));
        $table->set_header(3, get_lang('NumberOfQuestions'));
        $table->set_header(4, get_lang('Author'));
        $table->set_header(5, get_lang('AvailableFrom'));
        $table->set_header(6, get_lang('AvailableUntil'));
        $table->set_header(7, get_lang('Invite'));
        $table->set_header(8, get_lang('Anonymous'));

        if (api_get_configuration_value('allow_mandatory_survey')) {
            $table->set_header(9, get_lang('Modify'), false, 'width="130"');
            $table->set_header(10, get_lang('Modify'), false, 'width="130"');
            $table->set_column_filter(8, 'anonymous_filter');
            $table->set_column_filter(10, 'modify_filter_for_coach');
        } else {
            $table->set_header(9, get_lang('Modify'), false, 'width="130"');
            $table->set_column_filter(9, 'modify_filter_for_coach');
        }

        $table->set_column_filter(8, 'anonymous_filter');
        $table->display();
    }

    /**
     * Check if the hide_survey_edition configurations setting is enabled.
     *
     * @param string $surveyCode
     *
     * @return bool
     */
    public static function checkHideEditionToolsByCode($surveyCode)
    {
        $hideSurveyEdition = api_get_configuration_value('hide_survey_edition');

        if (false === $hideSurveyEdition) {
            return false;
        }

        if ('*' === $hideSurveyEdition['codes']) {
            return true;
        }

        if (in_array($surveyCode, $hideSurveyEdition['codes'])) {
            return true;
        }

        return false;
    }

    /**
     * This function changes the modify column of the sortable table.
     *
     * @param int  $survey_id the id of the survey
     * @param bool $drh
     *
     * @return string html code that are the actions that can be performed on any survey
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function modify_filter($survey_id, $drh = false)
    {
        /** @var CSurvey $survey */
        $survey = Database::getManager()->find('ChamiloCourseBundle:CSurvey', $survey_id);
        $hideSurveyEdition = self::checkHideEditionToolsByCode($survey->getCode());

        if ($hideSurveyEdition) {
            return '';
        }

        if (empty($survey)) {
            return '';
        }

        $survey_id = $survey->getSurveyId();
        $actions = [];
        $hideReportingButton = api_get_configuration_value('hide_survey_reporting_button');
        $codePath = api_get_path(WEB_CODE_PATH);
        $params = [];
        parse_str(api_get_cidreq(), $params);

        $reportingLink = Display::url(
            Display::return_icon('statistics.png', get_lang('Reporting')),
            $codePath.'survey/reporting.php?'.http_build_query($params + ['survey_id' => $survey_id])
        );

        if ($drh) {
            return $hideReportingButton ? '-' : $reportingLink;
        }

        $type = $survey->getSurveyType();

        // Coach can see that only if the survey is in his session
        if (api_is_allowed_to_edit() || api_is_element_in_the_session(TOOL_SURVEY, $survey_id)) {
            $configUrl = $codePath.'survey/create_new_survey.php?'.
                http_build_query($params + ['action' => 'edit', 'survey_id' => $survey_id]);
            $editUrl = $codePath.'survey/survey.php?'.
                http_build_query($params + ['survey_id' => $survey_id]);
            if (3 == $survey->getSurveyType()) {
                $configUrl = $codePath.'survey/edit_meeting.php?'.
                    http_build_query($params + ['action' => 'edit', 'survey_id' => $survey_id]);
            }

            $actions[] = Display::url(
                Display::return_icon('edit.png', get_lang('EditQuestions')),
                $editUrl
            );
            $actions[] = Display::url(
                Display::return_icon('settings.png', get_lang('Configure')),
                $configUrl
            );

            if (SurveyManager::survey_generation_hash_available()) {
                $actions[] = Display::url(
                    Display::return_icon('new_link.png', get_lang('GenerateSurveyAccessLink')),
                    $codePath.'survey/generate_link.php?'.http_build_query($params + ['survey_id' => $survey_id])
                );
            }

            if ($type != 3) {
                $actions[] = Display::url(
                    Display::return_icon('backup.png', get_lang('CopySurvey')),
                    $codePath.'survey/copy_survey.php?'.http_build_query($params + ['survey_id' => $survey_id])
                );

                $actions[] = Display::url(
                    Display::return_icon('copy.png', get_lang('DuplicateSurvey')),
                    $codePath.'survey/survey_list.php?'
                    .http_build_query($params + ['action' => 'copy_survey', 'survey_id' => $survey_id])
                );

                $actions[] = Display::url(
                    Display::return_icon('multiplicate_survey.png', get_lang('MultiplicateQuestions')),
                    $codePath.'survey/survey_list.php?'
                    .http_build_query($params + ['action' => 'multiplicate', 'survey_id' => $survey_id]),
                    ['survey_id' => $survey_id, 'class' => 'multiplicate_popup']
                );

                $actions[] = Display::url(
                    Display::return_icon('multiplicate_survey_na.png', get_lang('RemoveMultiplicateQuestions')),
                    $codePath.'survey/survey_list.php?'
                    .http_build_query($params + ['action' => 'remove_multiplicate', 'survey_id' => $survey_id])
                );

                $warning = addslashes(api_htmlentities(get_lang('EmptySurvey').'?', ENT_QUOTES));
                $actions[] = Display::url(
                    Display::return_icon('clean.png', get_lang('EmptySurvey')),
                    $codePath.'survey/survey_list.php?'
                    .http_build_query($params + ['action' => 'empty', 'survey_id' => $survey_id]),
                    [
                        'onclick' => "javascript: if (!confirm('".$warning."')) return false;",
                    ]
                );
            }
        }

        if (3 != $type) {
            $actions[] = Display::url(
                Display::return_icon('preview_view.png', get_lang('Preview')),
                $codePath.'survey/preview.php?'.http_build_query($params + ['survey_id' => $survey_id])
            );
        }

        $actions[] = Display::url(
            Display::return_icon('mail_send.png', get_lang('Publish')),
            $codePath.'survey/survey_invite.php?'.http_build_query($params + ['survey_id' => $survey_id])
        );

        $extraFieldValue = new ExtraFieldValue('survey');
        $groupData = $extraFieldValue->get_values_by_handler_and_field_variable($survey_id, 'group_id');
        if ($groupData && !empty($groupData['value'])) {
            $actions[] = Display::url(
                Display::return_icon('teacher.png', get_lang('SendToGroupTutors')),
                $codePath.'survey/survey_list.php?action=send_to_tutors&'.http_build_query($params + ['survey_id' => $survey_id])
            );
        }

        if (3 != $type) {
            $actions[] = $hideReportingButton ? null : $reportingLink;
        }

        if (api_is_allowed_to_edit() ||
            api_is_element_in_the_session(TOOL_SURVEY, $survey_id)
        ) {
            $actions[] = self::getAdditionalTeacherActions($survey_id);
            $warning = addslashes(api_htmlentities(get_lang('DeleteSurvey').'?', ENT_QUOTES));
            $actions[] = Display::url(
                Display::return_icon('delete.png', get_lang('Delete')),
                $codePath.'survey/survey_list.php?'
                .http_build_query($params + ['action' => 'delete', 'survey_id' => $survey_id]),
                [
                    'onclick' => "javascript: if (!confirm('".$warning."')) return false;",
                ]
            );
        }

        return implode(PHP_EOL, $actions);
    }

    /**
     * Get the additional actions added in survey_additional_teacher_modify_actions configuration.
     *
     * @param int $surveyId
     * @param int $iconSize
     *
     * @return string
     */
    public static function getAdditionalTeacherActions($surveyId, $iconSize = ICON_SIZE_SMALL)
    {
        $additionalActions = api_get_configuration_value('survey_additional_teacher_modify_actions') ?: [];

        if (empty($additionalActions)) {
            return '';
        }

        $actions = [];
        foreach ($additionalActions as $additionalAction) {
            $actions[] = call_user_func(
                $additionalAction,
                ['survey_id' => $surveyId, 'icon_size' => $iconSize]
            );
        }

        return implode(PHP_EOL, $actions);
    }

    /**
     * @param int $survey_id
     *
     * @return string
     */
    public static function modify_filter_for_coach($survey_id)
    {
        $survey_id = (int) $survey_id;
        $actions = [];
        $codePath = api_get_path(WEB_CODE_PATH);
        $params = [];
        parse_str(api_get_cidreq(), $params);
        $actions[] = Display::url(
            Display::return_icon('preview_view.png', get_lang('Preview')),
            $codePath.'survey/preview.php?'.http_build_query($params + ['survey_id' => $survey_id])
        );
        $actions[] = Display::url(
            Display::return_icon('mail_send.png', get_lang('Publish')),
            $codePath.'survey/survey_invite.php?'.http_build_query($params + ['survey_id' => $survey_id])
        );
        $warning = addslashes(api_htmlentities(get_lang('EmptySurvey').'?', ENT_QUOTES));
        $actions[] = Display::url(
            Display::return_icon('clean.png', get_lang('EmptySurvey')),
            $codePath.'survey/survey_list.php?'
                .http_build_query($params + ['action' => 'empty', 'survey_id' => $survey_id]),
            [
                'onclick' => "javascript: if(!confirm('".$warning."')) return false;",
            ]
        );

        return implode(PHP_EOL, $actions);
    }

    /**
     * Returns "yes" when given parameter is one, "no" for any other value.
     *
     * @param int Whether anonymous or not
     *
     * @return string "Yes" or "No" in the current language
     */
    public static function anonymous_filter($anonymous)
    {
        if (1 == $anonymous) {
            return get_lang('Yes');
        } else {
            return get_lang('No');
        }
    }

    /**
     * This function handles the search restriction for the SQL statements.
     *
     * @return string Part of a SQL statement or false on error
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function survey_search_restriction()
    {
        if (isset($_GET['do_search'])) {
            if ($_GET['keyword_title'] != '') {
                $search_term[] = 'title like "%" \''.Database::escape_string($_GET['keyword_title']).'\' "%"';
            }
            if ($_GET['keyword_code'] != '') {
                $search_term[] = 'code =\''.Database::escape_string($_GET['keyword_code']).'\'';
            }
            if ($_GET['keyword_language'] != '%') {
                $search_term[] = 'lang =\''.Database::escape_string($_GET['keyword_language']).'\'';
            }
            $my_search_term = ($search_term == null) ? [] : $search_term;
            $search_restriction = implode(' AND ', $my_search_term);

            return $search_restriction;
        } else {
            return false;
        }
    }

    /**
     * This function calculates the total number of surveys.
     *
     * @return int Total number of surveys
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version January 2007
     */
    public static function get_number_of_surveys()
    {
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $course_id = api_get_course_int_id();

        $search_restriction = self::survey_search_restriction();
        if ($search_restriction) {
            $search_restriction = 'WHERE c_id = '.$course_id.' AND '.$search_restriction;
        } else {
            $search_restriction = "WHERE c_id = $course_id";
        }
        // To list the surveys base too
        $showSurveysBase = api_get_configuration_value('show_surveys_base_in_sessions');
        $sessionCondition = api_get_session_condition(api_get_session_id(), true, $showSurveysBase);

        $sql = "SELECT count(survey_id) AS total_number_of_items
		        FROM $table_survey $search_restriction $sessionCondition";
        $res = Database::query($sql);
        $obj = Database::fetch_object($res);

        return $obj->total_number_of_items;
    }

    /**
     * @return int
     */
    public static function get_number_of_surveys_for_coach()
    {
        $survey_tree = new SurveyTree();

        return count($survey_tree->surveylist);
    }

    /**
     * This function gets all the survey data that is to be displayed in the sortable table.
     *
     * @param int    $from
     * @param int    $number_of_items
     * @param int    $column
     * @param string $direction
     * @param bool   $isDrh
     *
     * @return array
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest - Adding intvals
     *
     * @version January 2007
     */
    public static function get_survey_data(
        $from,
        $number_of_items,
        $column,
        $direction,
        $isDrh = false
    ) {
        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $mandatoryAllowed = api_get_configuration_value('allow_mandatory_survey');
        $_user = api_get_user_info();
        $allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');

        // Searching
        $search_restriction = self::survey_search_restriction();
        if ($search_restriction) {
            $search_restriction = ' AND '.$search_restriction;
        }
        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
        $column = (int) $column;
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        // To list the surveys base too
        $showSurveysBase = api_get_configuration_value('show_surveys_base_in_sessions');
        $sessionId = api_get_session_id();
        $sessionCondition = api_get_session_condition($sessionId, true, $showSurveysBase);
        $course_id = api_get_course_int_id();

        $sql = "
            SELECT
                survey.survey_id AS col0,
                survey.title AS col1,
                survey.code AS col2,
                count(survey_question.question_id) AS col3, "
                .(api_is_western_name_order()
                ? "CONCAT(user.firstname, ' ', user.lastname)"
                : "CONCAT(user.lastname, ' ', user.firstname)")
                ."	AS col4,
                survey.avail_from AS col5,
                survey.avail_till AS col6,
                survey.invited AS col7,
                survey.anonymous AS col8,
                survey.iid AS col9,
                survey.session_id AS session_id,
                survey.answered,
                survey.invited,
                survey.survey_type
            FROM $table_survey survey
            LEFT JOIN $table_survey_question survey_question
            ON (survey.survey_id = survey_question.survey_id AND survey_question.c_id = $course_id)
            LEFT JOIN $table_user user
            ON (survey.author = user.id)
            WHERE survey.c_id = $course_id
            $search_restriction
            $sessionCondition
            GROUP BY survey.survey_id
            ORDER BY col$column $direction
            LIMIT $from,$number_of_items
        ";
        $res = Database::query($sql);
        $surveys = [];
        $array = [];
        $efv = new ExtraFieldValue('survey');
        while ($survey = Database::fetch_array($res)) {
            $array[0] = $survey[0];
            $survey[1] = Security::remove_XSS($survey[1]);

            if (self::checkHideEditionToolsByCode($survey['col2'])) {
                $array[1] = $survey[1];
            } else {
                // Doodle
                if (3 == $survey['survey_type']) {
                    $array[1] = Display::url(
                        $survey[1],
                        api_get_path(WEB_CODE_PATH).'survey/meeting.php?survey_id='.$survey[0].'&'.api_get_cidreq()
                    );
                } else {
                    $array[1] = Display::url(
                        $survey[1],
                        api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey[0].'&'.api_get_cidreq()
                    );
                }
            }

            // Validation when belonging to a session
            $session_img = api_get_session_image($survey['session_id'], $_user['status']);
            $array[2] = $survey[2].$session_img;
            $array[3] = $survey[3];
            $array[4] = $survey[4];

            // Dates
            $array[5] = '';

            if (!empty($survey[5]) && $survey[5] !== '0000-00-00' && $survey[5] !== '0000-00-00 00:00:00') {
                $array[5] = api_convert_and_format_date(
                    $survey[5],
                    $allowSurveyAvailabilityDatetime ? DATE_TIME_FORMAT_LONG : DATE_FORMAT_LONG
                );
            }

            $array[6] = '';
            if (!empty($survey[6]) && $survey[6] !== '0000-00-00' && $survey[6] !== '0000-00-00 00:00:00') {
                $array[6] = api_convert_and_format_date(
                    $survey[6],
                    $allowSurveyAvailabilityDatetime ? DATE_TIME_FORMAT_LONG : DATE_FORMAT_LONG
                );
            }

            $array[7] =
                Display::url(
                    $survey['answered'],
                    api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=answered&survey_id='.$survey[0].'&'
                        .api_get_cidreq()
                ).' / '.
                Display::url(
                    $survey['invited'],
                    api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=invited&survey_id='.$survey[0].'&'
                        .api_get_cidreq()
                );
            // Anon
            $array[8] = $survey['col8'];
            if ($mandatoryAllowed) {
                $efvMandatory = $efv->get_values_by_handler_and_field_variable(
                    $survey[9],
                    'is_mandatory'
                );

                $array[9] = $efvMandatory ? $efvMandatory['value'] : 0;
                // Survey id
                $array[10] = $survey['col9'];
            } else {
                // Survey id
                $array[9] = $survey['col9'];
            }

            if ($isDrh) {
                $array[1] = $survey[1];
                $array[7] = strip_tags($array[7]);
            }

            $surveys[] = $array;
        }

        return $surveys;
    }

    /**
     * @param $from
     * @param $number_of_items
     * @param $column
     * @param $direction
     *
     * @return array
     */
    public static function get_survey_data_for_coach($from, $number_of_items, $column, $direction)
    {
        $mandatoryAllowed = api_get_configuration_value('allow_mandatory_survey');
        $allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');
        $survey_tree = new SurveyTree();
        $last_version_surveys = $survey_tree->surveylist;
        $list = [];
        foreach ($last_version_surveys as &$survey) {
            $list[] = $survey['id'];
        }
        if (count($list) > 0) {
            $list_condition = " AND survey.survey_id IN (".implode(',', $list).") ";
        } else {
            $list_condition = '';
        }

        $from = (int) $from;
        $number_of_items = (int) $number_of_items;
        $column = (int) $column;
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'asc';
        }

        $table_survey = Database::get_course_table(TABLE_SURVEY);
        $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
        $table_user = Database::get_main_table(TABLE_MAIN_USER);
        $course_id = api_get_course_int_id();
        $efv = new ExtraFieldValue('survey');

        $sql = "
            SELECT
            survey.survey_id AS col0,
                survey.title AS col1,
                survey.code AS col2,
                count(survey_question.question_id) AS col3,
        "
            .(api_is_western_name_order()
                ? "CONCAT(user.firstname, ' ', user.lastname)"
                : "CONCAT(user.lastname, ' ', user.firstname)")
            ."	AS col4,
                survey.avail_from AS col5,
                survey.avail_till AS col6,
                CONCAT('<a href=\"survey_invitation.php?view=answered&survey_id=',survey.survey_id,'\">',survey.answered,'</a> / <a href=\"survey_invitation.php?view=invited&survey_id=',survey.survey_id,'\">',survey.invited, '</a>') AS col7,
                survey.anonymous AS col8,
                survey.survey_id AS col9
            FROM $table_survey survey
            LEFT JOIN $table_survey_question survey_question
            ON (survey.survey_id = survey_question.survey_id AND survey.c_id = survey_question.c_id),
            $table_user user
            WHERE survey.author = user.id AND survey.c_id = $course_id $list_condition
        ";
        $sql .= ' GROUP BY survey.survey_id';
        $sql .= " ORDER BY col$column $direction ";
        $sql .= " LIMIT $from,$number_of_items";

        $res = Database::query($sql);
        $surveys = [];
        while ($survey = Database::fetch_array($res)) {
            $survey['col5'] = api_convert_and_format_date(
                $survey['col5'],
                $allowSurveyAvailabilityDatetime ? DATE_TIME_FORMAT_LONG : DATE_FORMAT_LONG
            );
            $survey['col6'] = api_convert_and_format_date(
                $survey['col6'],
                $allowSurveyAvailabilityDatetime ? DATE_TIME_FORMAT_LONG : DATE_FORMAT_LONG
            );

            if ($mandatoryAllowed) {
                $survey['col10'] = $survey['col9'];
                $efvMandatory = $efv->get_values_by_handler_and_field_variable(
                    $survey['col9'],
                    'is_mandatory'
                );
                $survey['col9'] = $efvMandatory['value'];
            }
            $surveys[] = $survey;
        }

        return $surveys;
    }

    /**
     * Display all the active surveys for the given course user.
     *
     * @param int $user_id
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     *
     * @version April 2007
     */
    public static function getSurveyList($user_id)
    {
        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        $user_id = (int) $user_id;
        $sessionId = api_get_session_id();
        $mandatoryAllowed = api_get_configuration_value('allow_mandatory_survey');
        $allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');

        $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
        $table_survey = Database::get_course_table(TABLE_SURVEY);

        echo '<table id="list-survey" class="table ">';
        echo '<thead>';
        echo '<tr>';
        echo '	<th>'.get_lang('SurveyName').'</th>';
        echo '	<th class="text-center">'.get_lang('Anonymous').'</th>';
        if ($mandatoryAllowed) {
            echo '<th class="text-center">'.get_lang('IsMandatory').'</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        /** @var \DateTime $now */
        $now = api_get_utc_datetime(null, false, true);
        $filterDate = $allowSurveyAvailabilityDatetime ? $now->format('Y-m-d H:i') : $now->format('Y-m-d');

        // To list the surveys base too
        $showSurveysBase = api_get_configuration_value('show_surveys_base_in_sessions');
        $sessionCondition = api_get_session_condition($sessionId, true, $showSurveysBase, 'survey.session_id');

        $sql = "SELECT survey_invitation.answered,
                    survey_invitation.invitation_code,
                    survey_invitation.session_id,
                    survey.title,
                    survey.visible_results,
                    survey.survey_id,
                    survey.anonymous
                FROM $table_survey survey
                INNER JOIN
                $table_survey_invitation survey_invitation
                ON (
                    survey.code = survey_invitation.survey_code AND
                    survey.c_id = survey_invitation.c_id AND
                    survey_invitation.session_id = $sessionId
                )
				WHERE
                    survey_invitation.user = $user_id AND
                    survey.avail_from <= '$filterDate' AND
                    (survey.avail_till >= '$filterDate' OR
                    survey.avail_till IS NULL) AND
                    survey.c_id = $course_id AND
                    survey_invitation.c_id = $course_id $sessionCondition
				";
        $result = Database::query($sql);

        $efv = new ExtraFieldValue('survey');

        $surveyIds = [];

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            if (in_array($row['survey_id'], $surveyIds)) {
                continue;
            }

            $title = Security::remove_XSS($row['title']);

            echo '<tr>';
            if ($row['answered'] == 0) {
                echo '<td>';
                $url = self::generateFillSurveyLink($row['invitation_code'], $_course, $row['session_id']);
                $icon = Display::return_icon(
                    'survey.png',
                    get_lang('ClickHereToAnswerTheSurvey'),
                    ['style' => 'margin-top: -4px'],
                    ICON_SIZE_TINY
                );
                echo '<a href="'.$url.'">
                    '.$icon
                    .$title
                    .'</a></td>';
            } else {
                $isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
                    $user_id,
                    $_course
                );
                $icon = Display::return_icon(
                    'survey_na.png',
                    get_lang('SurveysDone'),
                    [],
                    ICON_SIZE_TINY
                );
                $showLink = (!api_is_allowed_to_edit(false, true) || $isDrhOfCourse)
                    && $row['visible_results'] != SURVEY_VISIBLE_TUTOR;

                echo '<td>';
                echo $showLink
                    ? Display::url(
                        $icon.PHP_EOL.$row['title'],
                        api_get_path(WEB_CODE_PATH).'survey/reporting.php?'.api_get_cidreq().'&'.http_build_query([
                            'action' => 'questionreport',
                            'survey_id' => $row['survey_id'],
                        ])
                    )
                    : $icon.PHP_EOL.$title;
                echo '</td>';
            }
            echo '<td class="text-center">';
            echo ($row['anonymous'] == 1) ? get_lang('Yes') : get_lang('No');
            echo '</td>';
            if ($mandatoryAllowed) {
                $efvMandatory = $efv->get_values_by_handler_and_field_variable(
                    $row['survey_id'],
                    'is_mandatory'
                );
                echo '<td class="text-center">'.($efvMandatory['value'] ? get_lang('Yes') : get_lang('No')).'</td>';
            }
            echo '</tr>';
            $surveyIds[] = $row['survey_id'];
        }
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Creates a multi array with the user fields that we can show.
     * We look the visibility with the api_get_setting function
     * The username is always NOT able to change it.
     *
     * @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification
     *
     * @return array array[value_name][name], array[value_name][visibilty]
     */
    public static function make_field_list()
    {
        //	LAST NAME and FIRST NAME
        $field_list_array = [];
        $field_list_array['lastname']['name'] = get_lang('LastName');
        $field_list_array['firstname']['name'] = get_lang('FirstName');

        if (api_get_setting('profile', 'name') != 'true') {
            $field_list_array['firstname']['visibility'] = 0;
            $field_list_array['lastname']['visibility'] = 0;
        } else {
            $field_list_array['firstname']['visibility'] = 1;
            $field_list_array['lastname']['visibility'] = 1;
        }

        $field_list_array['username']['name'] = get_lang('Username');
        $field_list_array['username']['visibility'] = 0;

        //	OFFICIAL CODE
        $field_list_array['official_code']['name'] = get_lang('OfficialCode');

        if (api_get_setting('profile', 'officialcode') != 'true') {
            $field_list_array['official_code']['visibility'] = 1;
        } else {
            $field_list_array['official_code']['visibility'] = 0;
        }

        // EMAIL
        $field_list_array['email']['name'] = get_lang('Email');
        if (api_get_setting('profile', 'email') != 'true') {
            $field_list_array['email']['visibility'] = 1;
        } else {
            $field_list_array['email']['visibility'] = 0;
        }

        // PHONE
        $field_list_array['phone']['name'] = get_lang('Phone');
        if (api_get_setting('profile', 'phone') != 'true') {
            $field_list_array['phone']['visibility'] = 0;
        } else {
            $field_list_array['phone']['visibility'] = 1;
        }
        //	LANGUAGE
        $field_list_array['language']['name'] = get_lang('Language');
        if (api_get_setting('profile', 'language') != 'true') {
            $field_list_array['language']['visibility'] = 0;
        } else {
            $field_list_array['language']['visibility'] = 1;
        }

        // EXTRA FIELDS
        $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC');

        foreach ($extra as $id => $field_details) {
            if (0 == $field_details[6]) {
                continue;
            }
            switch ($field_details[2]) {
                case UserManager::USER_FIELD_TYPE_TEXT:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_TEXTAREA:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_RADIO:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_SELECT:
                    $get_lang_variables = false;
                    if (in_array(
                        $field_details[1],
                        ['mail_notify_message', 'mail_notify_invitation', 'mail_notify_group_message']
                    )
                    ) {
                        $get_lang_variables = true;
                    }

                    if ($get_lang_variables) {
                        $field_list_array['extra_'.$field_details[1]]['name'] = get_lang($field_details[3]);
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    }

                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_SELECT_MULTIPLE:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DATE:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DATETIME:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DOUBLE_SELECT:
                    $field_list_array['extra_'.$field_details[1]]['name'] = $field_details[3];
                    if (0 == $field_details[7]) {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 0;
                    } else {
                        $field_list_array['extra_'.$field_details[1]]['visibility'] = 1;
                    }
                    break;
                case UserManager::USER_FIELD_TYPE_DIVIDER:
                    //$form->addElement('static',$field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
                    break;
            }
        }

        return $field_list_array;
    }

    /**
     * Display survey question chart.
     *
     * @param array  $chartData
     * @param bool   $hasSerie         Tells if the chart has a serie. False by default
     * @param string $chartContainerId
     *
     * @return string (direct output)
     */
    public static function drawChart($chartData, $hasSerie = false, $chartContainerId = 'chartContainer', $loadLibs = true)
    {
        $htmlChart = '';
        if (api_browser_support('svg')) {
            $serie = [];
            $order = [];
            $data = '';
            foreach ($chartData as $chartDataElement) {
                $data .= '{"';
                $option = str_replace(["\n", "\r"], '', $chartDataElement['option']);
                $serieValue = isset($chartDataElement['serie']) ? $chartDataElement['serie'] : null;

                if (!$hasSerie) {
                    $data .= get_lang('Option').'":"'.$option.'", "';
                    array_push($order, $option);
                } else {
                    if (!is_array($serieValue)) {
                        $data .=
                            get_lang('Option').'":"'.$serieValue.'", "'.
                            get_lang('Score').'":"'.$option.'", "';
                        array_push($serie, $serieValue);
                    } else {
                        $data .=
                            get_lang('Serie').'":"'.$serieValue[0].'", "'.
                            get_lang('Option').'":"'.$serieValue[1].'", "'.
                            get_lang('Score').'":"'.$option.'", "';
                    }
                }
                $data .= get_lang('Votes').'":"'.$chartDataElement['votes'].'"},';
                rtrim($data, ',');
            }

            if ($loadLibs) {
                $htmlChart .= api_get_js('d3/d3.v3.5.4.min.js');
                $htmlChart .= api_get_js('dimple.v2.1.2.min.js');
            }

            $htmlChart .= '
            <script>
                var svg = dimple.newSvg("#'.$chartContainerId.'", 600, 400);
                var data = ['.$data.'];
                var myChart = new dimple.chart(svg, data);
                myChart.setBounds(50, 30, 550, 300);
                var yAxis = myChart.addMeasureAxis("y", "'.get_lang('Votes').'");
                yAxis.fontSize = "14px";
            ';

            if (!$hasSerie) {
                $htmlChart .= '
                    var xAxisCategory = myChart.addCategoryAxis("x", "'.get_lang("Option").'");
                    xAxisCategory.fontSize = "14px";
                    xAxisCategory.addOrderRule('.json_encode($order).');
                    myChart.addSeries("'.get_lang("Option").'", dimple.plot.bar);';
            } else {
                if (!is_array($chartDataElement['serie'])) {
                    $serie = array_values(array_unique($serie));
                    $htmlChart .= '
                        var xAxisCategory =
                        myChart.addCategoryAxis("x", ["'.get_lang('Option').'","'.get_lang("Score").'"]);
                        xAxisCategory.addOrderRule('.json_encode($serie).');
                        xAxisCategory.addGroupOrderRule("'.get_lang('Score').'");

                        myChart.addSeries("'.get_lang('Option').'", dimple.plot.bar);';
                } else {
                    $htmlChart .= '
                        myChart.addCategoryAxis("x", ["'.get_lang('Option').'","'.get_lang("Score").'"]);
                        myChart.addSeries("'.get_lang('Serie').'", dimple.plot.bar);';
                }
            }

            $htmlChart .= 'myChart.draw();';
            $htmlChart .= '</script>';
        }

        return $htmlChart;
    }

    /**
     * Set a flag to the current survey as answered by the current user.
     *
     * @param string $surveyCode The survey code
     * @param int    $courseId   The course ID
     */
    public static function flagSurveyAsAnswered($surveyCode, $courseId)
    {
        $currentUserId = api_get_user_id();
        $flag = sprintf('%s-%s-%d', $courseId, $surveyCode, $currentUserId);

        if (!isset($_SESSION['filled_surveys'])) {
            $_SESSION['filled_surveys'] = [];
        }

        $_SESSION['filled_surveys'][] = $flag;
    }

    /**
     * Check whether a survey was answered by the current user.
     *
     * @param string $surveyCode The survey code
     * @param int    $courseId   The course ID
     *
     * @return bool
     */
    public static function isSurveyAnsweredFlagged($surveyCode, $courseId)
    {
        $currentUserId = api_get_user_id();
        $flagToCheck = sprintf('%s-%s-%d', $courseId, $surveyCode, $currentUserId);

        if (!isset($_SESSION['filled_surveys'])) {
            return false;
        }

        if (!is_array($_SESSION['filled_surveys'])) {
            return false;
        }

        foreach ($_SESSION['filled_surveys'] as $flag) {
            if ($flagToCheck != $flag) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Check if the current survey has answers.
     *
     * @param int $surveyId
     *
     * @return bool return true if the survey has answers, false otherwise
     */
    public static function checkIfSurveyHasAnswers($surveyId)
    {
        $tableSurveyAnswer = Database::get_course_table(TABLE_SURVEY_ANSWER);
        $courseId = api_get_course_int_id();
        $surveyId = (int) $surveyId;

        if (empty($courseId) || empty($surveyId)) {
            return false;
        }

        // To select the answers by session
        $sessionId = api_get_session_id();
        $sessionCondition = '';
        if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
            $sessionCondition = api_get_session_condition($sessionId);
        }

        $sql = "SELECT * FROM $tableSurveyAnswer
                WHERE
                    c_id = $courseId AND
                    survey_id = '".$surveyId."'
                    $sessionCondition
                ORDER BY answer_id, user ASC";
        $result = Database::query($sql);
        $response = Database::affected_rows($result);

        return $response > 0;
    }

    /**
     * Get the pending surveys for a user.
     *
     * @param int $userId
     *
     * @return array
     */
    public static function getUserPendingInvitations($userId)
    {
        $now = api_get_utc_datetime(null, false, true);

        $dql = "
            SELECT s, si FROM ChamiloCourseBundle:CSurvey s
            INNER JOIN ChamiloCourseBundle:CSurveyInvitation si
                WITH (s.code = si.surveyCode AND s.cId = si.cId AND s.sessionId = si.sessionId )
            WHERE
                si.user = :user_id AND
                s.availFrom <= :now AND
                s.availTill >= :now AND
                si.answered = 0
            ORDER BY s.availTill ASC
        ";

        $pendingSurveys = Database::getManager()
            ->createQuery($dql)
            ->setParameters(['user_id' => $userId, 'now' => $now->format('Y-m-d')])
            ->getResult();

        return $pendingSurveys;
    }

    /**
     * @param string $surveyCode
     * @param int    $courseId
     * @param int    $sessionId
     *
     * @return array
     */
    public static function getSentInvitations($surveyCode, $courseId, $sessionId = 0)
    {
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblSurveyInvitation = Database::get_course_table(TABLE_SURVEY_INVITATION);

        $sessionCondition = api_get_session_condition($sessionId);
        $surveyCode = Database::escape_string($surveyCode);
        $courseId = (int) $courseId;

        $sql = "SELECT survey_invitation.*, user.firstname, user.lastname, user.email
                FROM $tblSurveyInvitation survey_invitation
                LEFT JOIN $tblUser user
                ON (survey_invitation.user = user.id AND survey_invitation.c_id = $courseId)
                WHERE
                    survey_invitation.survey_code = '$surveyCode'
                    AND survey_invitation.c_id = $courseId
                    $sessionCondition";

        $query = Database::query($sql);

        return Database::store_result($query);
    }

    /**
     * @param string $code       invitation code
     * @param array  $courseInfo
     * @param int    $sessionId
     * @param string $surveyCode
     *
     * @return string
     */
    public static function generateFillSurveyLink($code, $courseInfo, $sessionId, $surveyCode = '')
    {
        $code = Security::remove_XSS($code);
        $sessionId = (int) $sessionId;

        if (empty($courseInfo)) {
            return '';
        }

        $params = [
            'invitationcode' => $code,
            'cidReq' => $courseInfo['code'],
            'course' => $courseInfo['code'],
            'id_session' => $sessionId,
        ];

        if (!empty($surveyCode)) {
            $params['scode'] = Security::remove_XSS($surveyCode);
        }
        if (!empty($courseInfo['language'])) {
            $params['language'] = $courseInfo['language'];
        }

        return api_get_path(WEB_CODE_PATH).'survey/fillsurvey.php?'.http_build_query($params);
    }
}
