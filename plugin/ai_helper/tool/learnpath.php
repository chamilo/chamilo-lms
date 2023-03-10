<?php
/* For license terms, see /license.txt */

/**
Create a learnpath with contents based on existing knowledge.
 */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

require_once __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../AiHelperPlugin.php';
require_once api_get_path(SYS_CODE_PATH).'exercise/export/aiken/aiken_classes.php';
require_once api_get_path(SYS_CODE_PATH).'exercise/export/aiken/aiken_import.inc.php';

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
        $addTests = ('true' === $_REQUEST['add_tests']);
        $nQ = ($addTests ? (int) $_REQUEST['nro_questions'] : 0);

        $messageGetItems = 'Generate the table of contents of a course in "%s" in %d or less chapters on the topic of "%s" in a list separated with comma, without chapter number. Do not include a conclusion chapter.';
        $prompt = sprintf($messageGetItems, $courseLanguage, $chaptersCount, $topic);
        $resultText = $plugin->openAiGetCompletionText($prompt, 'learnpath');

        if (isset($resultText['error']) && true === $resultText['error']) {
            echo json_encode([
                'success' => false,
                'text' => $resultText['message'],
            ]);
            exit;
        }

        $lpItems = [];
        if (!empty($resultText)) {
            $style = api_get_css_asset('bootstrap/dist/css/bootstrap.min.css');
            $style .= api_get_css_asset('fontawesome/css/font-awesome.min.css');
            $style .= api_get_css(ChamiloApi::getEditorDocStylePath());
            $style .= api_get_css_asset('ckeditor/plugins/codesnippet/lib/highlight/styles/default.css');
            $style .= api_get_asset('ckeditor/plugins/codesnippet/lib/highlight/highlight.pack.js');
            $style .= '<script>hljs.initHighlightingOnLoad();</script>';

            $items = explode(',', $resultText);
            $position = 1;
            foreach ($items as $item) {
                $explodedItem = preg_split('/\d\./', $item);
                $title = count($explodedItem) > 1 ? $explodedItem[1] : $explodedItem[0];
                if (!empty($title)) {
                    $lpItems[$position]['title'] = trim($title);
                    $messageGetItemContent = 'In the context of "%s", generate a document with HTML tags in "%s" with %d words of content or less, about "%s"';
                    $promptItem = sprintf($messageGetItemContent, $topic, $courseLanguage, $wordsCount, $title);
                    $resultContentText = $plugin->openAiGetCompletionText($promptItem, 'learnpath');
                    $lpItemContent = (!empty($resultContentText) ? trim($resultContentText) : '');
                    if (false !== stripos($lpItemContent, '</head>')) {
                        $lpItemContent = preg_replace("|</head>|i", "\r\n$style\r\n\\0", $lpItemContent);
                    } else {
                        $lpItemContent = '<html><head><title>'.trim($title).'</title>'.$style.'</head><body>'.$lpItemContent.'</body></html>';
                    }
                    $lpItems[$position]['content'] = $lpItemContent;
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
                $order = 1;
                $lpItemsIds = [];
                foreach ($lpItems as $dspOrder => $item) {
                    $documentId = $lp->create_document(
                        $courseInfo,
                        $item['content'],
                        $item['title'],
                        'html'
                    );

                    if (!empty($documentId)) {
                        $prevDocItem = (isset($lpItemsIds[$order - 1]) ? (int) $lpItemsIds[$order - 1]['item_id'] : 0);
                        $lpItemId = $lp->add_item(
                            0,
                            $prevDocItem,
                            'document',
                            $documentId,
                            $item['title'],
                            '',
                            0,
                            0,
                            api_get_user_id(),
                            $order
                        );
                        $lpItemsIds[$order]['item_id'] = $lpItemId;
                        $lpItemsIds[$order]['item_type'] = 'document';
                        if ($addTests && !empty($lpItemId)) {
                            $promptQuiz = 'Generate %d "%s" questions in Aiken format in the %s language about "%s", making sure there is a \'ANSWER\' line for each question. \'ANSWER\' lines must only mention the letter of the correct answer, not the full answer text and not a parenthesis. The line starting with \'ANSWER\' must not be separated from the last possible answer by a blank line. Each answer starts with an uppercase letter, a dot, one space and the answer text without quotes. Include an \'ANSWER_EXPLANATION\' line after the \'ANSWER\' line for each question. The terms between single quotes above must not be translated. There must be a blank line between each question. Show the question directly without any prefix.';
                            $promptQuiz = sprintf($promptQuiz, $nQ, $courseLanguage, $item['title'], $topic);
                            $resultQuizText = $plugin->openAiGetCompletionText($promptQuiz, 'quiz');
                            if (!empty($resultQuizText)) {
                                $request = [];
                                $request['quiz_name'] = get_lang('Exercise').': '.$item['title'];
                                $request['nro_questions'] = $nQ;
                                $request['course_id'] = api_get_course_int_id($courseCode);
                                $request['aiken_format'] = trim($resultQuizText);
                                $exerciseId = aikenImportExercise(null, $request);
                                if (!empty($exerciseId)) {
                                    $order++;
                                    $prevQuizItem = (isset($lpItemsIds[$order - 1]) ? (int) $lpItemsIds[$order - 1]['item_id'] : 0);
                                    $lpQuizItemId = $lp->add_item(
                                        0,
                                        $prevQuizItem,
                                        'quiz',
                                        $exerciseId,
                                        $request['quiz_name'],
                                        '',
                                        0,
                                        0,
                                        api_get_user_id(),
                                        $order
                                    );
                                    if (!empty($lpQuizItemId)) {
                                        $maxScore = (float) $nQ;
                                        $minScore = round($nQ / 2, 2);
                                        $lpItemsIds[$order]['item_id'] = $lpQuizItemId;
                                        $lpItemsIds[$order]['item_type'] = 'quiz';
                                        $lpItemsIds[$order]['min_score'] = $minScore;
                                        $lpItemsIds[$order]['max_score'] = $maxScore;
                                    }
                                }
                            }
                        }
                    }
                    $order++;
                }

                // Add the final item
                if ($addTests) {
                    $finalTitle = get_lang('EndOfLearningPath');
                    $finalContent = file_get_contents(api_get_path(SYS_CODE_PATH).'lp/final_item_template/template.html');
                    $finalDocId = $lp->create_document(
                        $courseInfo,
                        $finalContent,
                        $finalTitle
                    );
                    $prevFinalItem = (isset($lpItemsIds[$order - 1]) ? (int) $lpItemsIds[$order - 1]['item_id'] : 0);
                    $lpFinalItemId = $lp->add_item(
                        0,
                        $prevFinalItem,
                        TOOL_LP_FINAL_ITEM,
                        $finalDocId,
                        $finalTitle,
                        '',
                        0,
                        0,
                        api_get_user_id(),
                        $order
                    );
                    $lpItemsIds[$order]['item_id'] = $lpFinalItemId;
                    $lpItemsIds[$order]['item_type'] = TOOL_LP_FINAL_ITEM;

                    // Set lp items prerequisites
                    if (count($lpItemsIds) > 0) {
                        for ($i = 1; $i <= count($lpItemsIds); $i++) {
                            $prevIndex = ($i - 1);
                            if (isset($lpItemsIds[$prevIndex])) {
                                $itemId = $lpItemsIds[$i]['item_id'];
                                $prerequisite = $lpItemsIds[$prevIndex]['item_id'];
                                $minScore = ('quiz' === $lpItemsIds[$prevIndex]['item_type'] ? $lpItemsIds[$prevIndex]['min_score'] : 0);
                                $maxScore = ('quiz' === $lpItemsIds[$prevIndex]['item_type'] ? $lpItemsIds[$prevIndex]['max_score'] : 100);
                                $lp->edit_item_prereq($itemId, $prerequisite, $minScore, $maxScore);
                            }
                        }
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
