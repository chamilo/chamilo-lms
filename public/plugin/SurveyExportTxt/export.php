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

$plugin = SurveyExportTxtPlugin::create();
$allowExportIncomplete = 'true' === $plugin->get('export_incomplete');

if (!$plugin->isEnabled()) {
    api_not_allowed(true);
}

$questionsData = getExportableQuestions($surveyId, $courseId);
$numberOfQuestions = count($questionsData);
$surveyAnswers = getSurveyRespondents($surveyId);
$isAnonymous = isSurveyAnonymous($surveyData);
$courseInfo = api_get_course_info();

$content = [];
$content[] = 'Survey: '.cleanExportText($surveyData['title'] ?? '');
$content[] = 'Course: '.cleanExportText($courseInfo['title'] ?? $courseInfo['name'] ?? '');
$content[] = 'Exported at: '.date('Y-m-d H:i:s');
$content[] = 'Anonymous: '.($isAnonymous ? get_lang('Yes') : get_lang('No'));
$content[] = str_repeat('=', 80);

$counter = 1;
$exportedRespondents = 0;

foreach ($surveyAnswers as $answer) {
    $user = (string) $answer['user'];
    $answerData = buildReadableRespondentBlock($questionsData, $user, $counter, $isAnonymous);

    if (!$allowExportIncomplete && $answerData['answered_count'] < $numberOfQuestions) {
        continue;
    }

    foreach ($answerData['lines'] as $line) {
        $content[] = $line;
    }

    $content[] = str_repeat('-', 80);
    $counter++;
    $exportedRespondents++;
}

if (0 === $exportedRespondents) {
    $content[] = get_lang('No data available');
}

$fileContent = array_map(
    function (string $line): string {
        return html_entity_decode($line).PHP_EOL;
    },
    $content
);

$fileName = api_get_path(SYS_ARCHIVE_PATH).'survey_'.$surveyId.'_readable_'.date('Ymd_His').'.txt';

file_put_contents($fileName, $fileContent);

DocumentManager::file_send_for_download($fileName, true);

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

function getSurveyRespondents(int $surveyId): array
{
    $table = Database::get_course_table(TABLE_SURVEY_ANSWER);
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

function buildReadableRespondentBlock(array $questions, string $user, int $counter, bool $isAnonymous): array
{
    $userInfo = getExportUserInfo($user, $isAnonymous);
    $answeredCount = 0;

    $lines = [];
    $lines[] = 'Respondent #'.$counter;
    $lines[] = 'User ID: '.$userInfo['user_id'];
    $lines[] = 'Username: '.$userInfo['username'];
    $lines[] = 'Name: '.trim($userInfo['firstname'].' '.$userInfo['lastname']);
    $lines[] = '';

    foreach ($questions as $index => $question) {
        $questionNumber = sprintf('Q%02d', $index + 1);
        $questionText = cleanExportText($question['question'] ?? '');
        $answers = getQuestionReadableAnswers($question, $user);

        $lines[] = $questionNumber.'. '.$questionText;

        if ('open' === $question['type']) {
            if ('' !== $answers['open_answer']) {
                $answeredCount++;
            }

            $lines[] = 'Answer: '.$answers['open_answer'];
            $lines[] = '';
            continue;
        }

        if (!empty($answers['labels'])) {
            $answeredCount++;
        }

        $lines[] = 'Answer: '.implode(' | ', $answers['labels']);
        $lines[] = 'Code: '.implode(' | ', $answers['codes']);
        $lines[] = '';
    }

    return [
        'lines' => $lines,
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
