<?php
/* For license terms, see /license.txt */

/**
Answer questions based on existing knowledge.
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../AiHelperPlugin.php';

$plugin = AiHelperPlugin::create();

try {
    $nQ = (int) $_REQUEST['nro_questions'];
    $lang = (string) $_REQUEST['language'];
    $topic = (string) $_REQUEST['quiz_name'];
    $questionType = $_REQUEST['question_type'] ?? 'multiple_choice';

    $resultText = $plugin->generateQuestions($nQ, $lang, $topic, $questionType);

    echo json_encode([
        'success' => true,
        'text' => trim($resultText),
    ]);
} catch (Exception $e) {
    error_log("Error: ".$e->getMessage());
    echo json_encode([
        'success' => false,
        'text' => $e->getMessage(),
    ]);
}
