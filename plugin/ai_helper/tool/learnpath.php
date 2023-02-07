<?php
/* For license terms, see /license.txt */

/**
Create a learnpath with contents based on existing knowledge.
 */
require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../AiHelperPlugin.php';

$plugin = AiHelperPlugin::create();

$apiList = $plugin->getApiList();
$apiName = $plugin->get('api_name');

if (!in_array($apiName, array_keys($apiList))) {
    throw new Exception("Ai API is not available for this request.");
}

switch ($apiName) {
    case AiHelperPlugin::OPENAI_API:

        $courseLanguage = (string) $_REQUEST['language'];
        $chaptersCount = (int) $_REQUEST['nro_items'];
        $topic = (string) $_REQUEST['lp_name'];
        $wordsCount = (int) $_REQUEST['words_count'];
        $courseCode = (string) $_REQUEST['course_code'];
        $sessionId = (int) $_REQUEST['session_id'];

        $messageGetItems = 'Generate the table of contents of a course in "%s" in %d or less chapters on the topic of "%s" in a list separated with comma, without chapter number';
        $prompt = sprintf($messageGetItems, $courseLanguage, $chaptersCount, $topic);
        $resultText = $plugin->openAiGetCompletionText($prompt);
        $lpItems = [];
        if (!empty($resultText)) {
            $items = explode(',', $resultText);
            $position = 1;
            foreach ($items as $item) {
                $explodedItem = preg_split('/\d\./', $item);
                $title = count($explodedItem) > 1 ? $explodedItem[1] : $explodedItem[0];
                if (!empty($title)) {
                    $lpItems[$position]['title'] = trim($title);
                    $messageGetItemContent = 'In the context of "%s", generate a document with HTML tags in "%s" with %d words of content or less, about "%s"';
                    $promptItem = sprintf($messageGetItemContent, $topic, $courseLanguage, $wordsCount, $title);
                    $resultContentText = $plugin->openAiGetCompletionText($promptItem);
                    if (!empty($resultContentText)) {
                        $lpItems[$position]['content'] = trim($resultContentText);
                    }
                    $position++;
                }
            }
        }

        // Create the learnpath and return the id generated.
        $return = ['success' => false, 'lp_id' => 0];
        if (!empty($lpItems)) {
            $lpId = learnpath::add_lp(
                $courseCode,
                $topic,
                '',
                'chamilo',
                'manual'
            );

            if (!empty($lpId)) {
                learnpath::toggle_visibility($lpId, 0);
                $courseInfo = api_get_course_info($courseCode);
                $lp = new \learnpath(
                    $courseCode,
                    $lpId,
                    api_get_user_id()
                );
                $lp->generate_lp_folder($courseInfo, $topic);

                foreach ($lpItems as $dspOrder => $item) {
                    $documentId = $lp->create_document(
                        $courseInfo,
                        $item['content'],
                        $item['title'],
                        'html'
                    );

                    if (!empty($documentId)) {
                        $lpItemId = $lp->add_item(
                            0,
                            0,
                            'document',
                            $documentId,
                            $item['title'],
                            '',
                            0,
                            0,
                            api_get_user_id(),
                            $dspOrder
                        );
                    }
                }
            }
            $return = [
                'success' => true,
                'lp_id' => $lpId,
            ];
        }
        echo json_encode($return);
        break;
}
