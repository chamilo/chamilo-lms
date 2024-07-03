<?php
/* For license terms, see /license.txt */

/**
    Answer questions based on existing knowledge.
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../AiHelperPlugin.php';
require_once __DIR__.'/../src/openai/OpenAi.php';

$plugin = AiHelperPlugin::create();

$apiList = $plugin->getApiList();
$apiName = $plugin->get('api_name');

if (!in_array($apiName, array_keys($apiList))) {
    throw new Exception("Ai API is not available for this request.");
}

switch ($apiName) {
    case AiHelperPlugin::OPENAI_API:

        $questionTypes = [
            'multiple_choice' => 'multiple choice',
            'unique_answer' => 'unique answer',
        ];

        $nQ = (int) $_REQUEST['nro_questions'];
        $lang = (string) $_REQUEST['language'];
        $topic = (string) $_REQUEST['quiz_name'];
        $questionType = $questionTypes[$_REQUEST['question_type']] ?? $questionTypes['multiple_choice'];

        $prompt = 'Generate %d "%s" questions in Aiken format in the %s language about "%s", making sure there is a \'ANSWER\' line for each question. \'ANSWER\' lines must only mention the letter of the correct answer, not the full answer text and not a parenthesis. The line starting with \'ANSWER\' must not be separated from the last possible answer by a blank line. Each answer starts with an uppercase letter, a dot, one space and the answer text without quotes. Include an \'ANSWER_EXPLANATION\' line after the \'ANSWER\' line for each question. The terms between single quotes above must not be translated. There must be a blank line between each question.';
        $prompt = sprintf($prompt, $nQ, $questionType, $lang, $topic);

        $resultText = $plugin->openAiGetCompletionText($prompt, 'quiz');

        if (isset($resultText['error']) && true === $resultText['error']) {
            echo json_encode([
                'success' => false,
                'text' => $resultText['message'],
            ]);
            exit;
        }

        // Returns the text answers generated.
        $return = ['success' => false, 'text' => ''];
        if (!empty($resultText)) {
            $return = [
                'success' => true,
                'text' => trim($resultText),
            ];
        }

        echo json_encode($return);
        break;
}
