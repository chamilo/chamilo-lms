<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$surveyId = isset($_GET['survey']) ? (int) $_GET['survey'] : 0;
$surveyData = SurveyManager::get_survey($surveyId);
$courseId = api_get_course_int_id();

if (empty($surveyData)) {
    api_not_allowed(true);
}

$plugin = SurveyExportCsvPlugin::create();
$allowExportIncomplete = 'true' === $plugin->get('export_incomplete');

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$questionsData = getExportableQuestions($surveyId, $courseId);
$numberOfQuestions = count($questionsData);
$content = [];
$content[] = buildHeaderRow($questionsData, $surveyData);

$surveyAnswers = getSurveyRespondents($surveyId);
$isAnonymous = isSurveyAnonymous($surveyData);

$counter = 1;
foreach ($surveyAnswers as $answer) {
    $answerData = buildReadableRow($questionsData, (string) $answer['user'], $counter, $isAnonymous);

    if (!$allowExportIncomplete && $answerData['answered_count'] < $numberOfQuestions) {
        continue;
    }

    $content[] = $answerData['row'];
    $counter++;
}

$fileName = 'survey_'.$surveyId.'_readable_'.date('Ymd_His');

Export::arrayToCsv($content, $fileName, false, "'");

function getExportableQuestions(int $surveyId, int $courseId): array
{
    $questions = SurveyManager::get_questions($surveyId, $courseId);
    $questions = array_values(
        array_filter(
            $questions,
            function (array $question): bool {
                return in_array($question['type'], ['yesno', 'multiplechoice', 'open'], true);
            }
        )
    );

    usort(
        $questions,
        function (array $left, array $right): int {
            if ($left['sort'] == $right['sort']) {
                return 0;
            }

            return $left['sort'] < $right['sort'] ? -1 : 1;
        }
    );

    return $questions;
}

function buildHeaderRow(array $questions, array $surveyData): array
{
    $row = [
        'Response #',
        'User ID',
        'Username',
        'First name',
        'Last name',
    ];

    foreach ($questions as $index => $question) {
        $questionNumber = sprintf('Q%02d', $index + 1);
        $questionText = cleanExportText($question['question'] ?? '');
        $label = $questionNumber;

        if ('' !== $questionText) {
            $label .= ' - '.$questionText;
        }

        $row[] = $label;

        if ('open' !== $question['type']) {
            $row[] = $questionNumber.' code';
        }
    }

    return $row;
}

function getSurveyRespondents(int $surveyId): array
{
    $table = Database::get_course_table(TABLE_SURVEY_ANSWER);
    $surveyId = (int) $surveyId;
    $sessionCondition = getSurveyExportSessionCondition();

    $sql = "SELECT user, MIN(iid) AS id
            FROM $table
            WHERE survey_id = $surveyId
            $sessionCondition
            GROUP BY user
            ORDER BY id ASC";

    $result = Database::query($sql);
    $rows = [];

    while ($row = Database::fetch_assoc($result)) {
        $rows[] = $row;
    }

    return $rows;
}

function buildReadableRow(array $questions, string $user, int $counter, bool $isAnonymous): array
{
    $userInfo = getExportUserInfo($user, $isAnonymous);
    $answeredCount = 0;

    $row = [
        $counter,
        $userInfo['user_id'],
        $userInfo['username'],
        $userInfo['firstname'],
        $userInfo['lastname'],
    ];

    foreach ($questions as $question) {
        $answers = getQuestionReadableAnswers($question, $user);

        if (!empty($answers['labels']) || '' !== $answers['open_answer']) {
            $answeredCount++;
        }

        if ('open' === $question['type']) {
            $row[] = $answers['open_answer'];
            continue;
        }

        $row[] = implode(' | ', $answers['labels']);
        $row[] = implode(' | ', $answers['codes']);
    }

    return [
        'row' => $row,
        'answered_count' => $answeredCount,
    ];
}

function getQuestionReadableAnswers(array $question, string $user): array
{
    $questionId = (int) $question['question_id'];
    $surveyId = (int) $question['survey_id'];

    if ('open' === $question['type']) {
        return [
            'labels' => [],
            'codes' => [],
            'open_answer' => getOpenAnswer($questionId, $surveyId, $user) ?? '',
        ];
    }

    $answerTable = Database::get_course_table(TABLE_SURVEY_ANSWER);
    $optionTable = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);

    $escapedUser = Database::escape_string($user);
    $sessionCondition = getSurveyExportSessionCondition('sa');

    $sql = "SELECT sa.option_id, sqo.sort, sqo.option_text
            FROM $answerTable sa
            LEFT JOIN $optionTable sqo
                ON sqo.iid = CAST(sa.option_id AS UNSIGNED)
                AND sqo.question_id = sa.question_id
                AND sqo.survey_id = sa.survey_id
            WHERE sa.user = '$escapedUser'
              AND sa.survey_id = $surveyId
              AND sa.question_id = $questionId
              $sessionCondition
            ORDER BY sa.iid ASC";

    $result = Database::query($sql);
    $labels = [];
    $codes = [];

    while ($row = Database::fetch_assoc($result)) {
        if (!empty($row['option_text'])) {
            $labels[] = cleanExportText($row['option_text']);
            $codes[] = (string) $row['sort'];
            continue;
        }

        $labels[] = cleanExportText((string) $row['option_id']);
    }

    return [
        'labels' => $labels,
        'codes' => $codes,
        'open_answer' => '',
    ];
}

function getOpenAnswer(int $questionId, int $surveyId, string $user): ?string
{
    $answerTable = Database::get_course_table(TABLE_SURVEY_ANSWER);

    $escapedUser = Database::escape_string($user);
    $sessionCondition = getSurveyExportSessionCondition();

    $sql = "SELECT option_id
            FROM $answerTable
            WHERE survey_id = $surveyId
              AND question_id = $questionId
              AND user = '$escapedUser'
              $sessionCondition
            ORDER BY iid ASC
            LIMIT 1";

    $result = Database::query($sql);
    $row = Database::fetch_assoc($result);

    if (empty($row)) {
        return null;
    }

    return cleanExportText((string) $row['option_id']);
}

function getExportUserInfo(string $user, bool $isAnonymous): array
{
    if ($isAnonymous || !ctype_digit($user)) {
        return [
            'user_id' => '',
            'username' => get_lang('Anonymous'),
            'firstname' => '',
            'lastname' => '',
        ];
    }

    $userInfo = api_get_user_info((int) $user);

    if (empty($userInfo)) {
        return [
            'user_id' => $user,
            'username' => '',
            'firstname' => '',
            'lastname' => '',
        ];
    }

    return [
        'user_id' => $userInfo['user_id'] ?? $user,
        'username' => cleanExportText($userInfo['username'] ?? ''),
        'firstname' => cleanExportText($userInfo['firstname'] ?? ''),
        'lastname' => cleanExportText($userInfo['lastname'] ?? ''),
    ];
}

function isSurveyAnonymous(array $surveyData): bool
{
    return isset($surveyData['anonymous']) && (1 === (int) $surveyData['anonymous']);
}

function cleanExportText(string $value): string
{
    return trim(api_html_entity_decode(strip_tags(Security::remove_XSS($value))));
}

function getSurveyExportSessionCondition(string $alias = ''): string
{
    $showBaseInSessions = api_get_configuration_value('show_surveys_base_in_sessions');

    if (true !== $showBaseInSessions && 'true' !== $showBaseInSessions) {
        return '';
    }

    $prefix = '' === $alias ? '' : $alias.'.';
    $sessionId = (int) api_get_session_id();

    if ($sessionId > 0) {
        return " AND {$prefix}session_id = $sessionId";
    }

    return " AND ({$prefix}session_id IS NULL OR {$prefix}session_id = 0)";
}
