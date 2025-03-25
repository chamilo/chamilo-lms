<?php
/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;

class LpAiHelper
{
    /**
     * AiHelper constructor.
     * Requires AI helpers to be enabled in the settings.
     */
    public function __construct() {}

    /**
     * Get the form to generate Learning Path (LP) items using AI.
     */
    public function aiHelperForm()
    {
        if ('true' !== api_get_setting('ai_helpers.enable_ai_helpers') ||
            'true' !== api_get_course_setting('learning_path_generator')) {

            return false;
        }

        // Get AI providers from settings
        $aiProvidersJson = api_get_setting('ai_helpers.ai_providers');
        $availableApis = json_decode($aiProvidersJson, true) ?? [];
        $hasSingleApi = count($availableApis) === 1;
        $configuredApi = $hasSingleApi ? array_key_first($availableApis) : null;

        $form = new FormValidator(
            'lp_ai_generate',
            'post',
            api_get_self()."?".api_get_cidreq(),
            null
        );
        $form->addElement('header', get_lang('AI generator'));

        // Show the AI provider being used
        if ($hasSingleApi) {
            $apiName = $availableApis[$configuredApi]['model'] ?? $configuredApi;
            $form->addHtml('<div style="margin-bottom: 10px; font-size: 14px; color: #555;">'
                .sprintf(get_lang('Using AI Provider: %s'), '<strong>'.htmlspecialchars($apiName).'</strong>').'</div>');
        }

        // Input fields for LP generation
        $form->addElement('text', 'lp_name', get_lang('Topic'));
        $form->addRule('lp_name', get_lang('This field is required'), 'required');
        $form->addElement('number', 'nro_items', get_lang('Number of items'));
        $form->addRule('nro_items', get_lang('This field is required'), 'required');
        $form->addElement('number', 'words_count', get_lang('Words count per page'));
        $form->addRule('words_count', get_lang('This field is required'), 'required');

        // Checkbox for adding quizzes
        $form->addElement('checkbox', 'add_lp_quiz', null, get_lang('Add test after each page'), ['id' => 'add-lp-quiz']);
        $form->addHtml('<div id="lp-quiz-area">');
        $form->addElement('number', 'nro_questions', get_lang('Number of questions'));
        $form->addRule('nro_questions', get_lang('This field is required'), 'required');
        $form->addHtml('</div>');
        $form->setDefaults(['nro_questions' => 2]);

        // Allow provider selection if multiple are available
        if (!$hasSingleApi) {
            $form->addSelect(
                'ai_provider',
                get_lang('AI provider'),
                array_combine(array_keys($availableApis), array_keys($availableApis))
            );
        }

        // API URLs
        $generateUrl = api_get_path(WEB_PATH).'ai/generate_learnpath';
        $courseInfo = api_get_course_info();
        $language = $courseInfo['language'];
        $courseCode = api_get_course_id();
        $sessionId = api_get_session_id();
        $redirectSuccess = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&isStudentView=false&lp_id=';

        // JavaScript to handle form submission
        $form->addHtml('<script>
        $(function () {
            $("#lp-quiz-area").hide();
            $("#add-lp-quiz").change(function() {
                $("#lp-quiz-area").toggle(this.checked);
            });

            $("#create-lp-ai").on("click", function (e) {
                e.preventDefault();
                e.stopPropagation();

                var btnGenerate = $(this);
                var lpName = $("[name=\'lp_name\']").val().trim();
                var nroItems = parseInt($("[name=\'nro_items\']").val());
                var wordsCount = parseInt($("[name=\'words_count\']").val());
                var addTests = $("#add-lp-quiz").is(":checked");
                var nroQuestions = parseInt($("[name=\'nro_questions\']").val());
                var provider = '.(!$hasSingleApi ? '$("[name=\'ai_provider\']").val()' : '"'.$configuredApi.'"').';

                var isValid = true;

                $(".error-message").remove();

                if (lpName === "") {
                    $("[name=\'lp_name\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('This field is required').'</div>");
                    isValid = false;
                }

                if (isNaN(nroItems) || nroItems <= 0) {
                    $("[name=\'nro_items\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('Please enter a valid number').'</div>");
                    isValid = false;
                }

                if (isNaN(wordsCount) || wordsCount <= 0) {
                    $("[name=\'words_count\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('Please enter a valid word count').'</div>");
                    isValid = false;
                }

                if (addTests && (isNaN(nroQuestions) || nroQuestions <= 0 || nroQuestions > 5)) {
                    $("[name=\'nro_questions\']").after("<div class=\'error-message\' style=\'color: red;\'>'.sprintf(get_lang('Number of questions limited from %d to %d'), 1, 5).'</div>");
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                btnGenerate.attr("disabled", true).text("'.get_lang('Please wait, this could take a while...').'");

                var requestData = JSON.stringify({
                    "lp_name": lpName,
                    "nro_items": nroItems,
                    "words_count": wordsCount,
                    "language": "'.$language.'",
                    "add_tests": addTests,
                    "nro_questions": nroQuestions,
                    "ai_provider": provider,
                    "course_code": "'.$courseCode.'",
                });

                $.ajax({
                    url: "'.$generateUrl.'",
                    type: "POST",
                    contentType: "application/json",
                    data: requestData,
                    dataType: "json",
                    success: function (data) {
                        btnGenerate.attr("disabled", false).text("'.get_lang('Generate').'");

                        if (data.success) {
                            $.ajax({
                                url: "'.api_get_path(WEB_AJAX_PATH).'lp.ajax.php?a=add_lp_ai&'.api_get_cidreq().'",
                                type: "POST",
                                contentType: "application/json",
                                data: JSON.stringify({
                                    "lp_data": data.data,
                                    "course_code": "'.$courseCode.'"
                                }),
                                success: function (result) {
                                    try {
                                        let parsedResult = (typeof result === "string") ? JSON.parse(result) : result;
                                        let isSuccess = Boolean(parsedResult.success);

                                        if (isSuccess) {
                                            location.href = "'.$redirectSuccess.'" + parsedResult.lp_id;
                                        } else {
                                            alert("Error: " + (parsedResult.text || "'.get_lang('Error creating learning path').'"));
                                        }
                                    } catch (e) {
                                        alert("'.get_lang('Invalid server response').'");
                                    }
                                }
                            });
                        } else {
                            alert(data.text || "'.get_lang('No results found').'. '.get_lang('Please Try Again!').'");
                        }
                    },
                    error: function (jqXHR) {
                        btnGenerate.attr("disabled", false).text("'.get_lang('Generate').'");

                        try {
                            var response = JSON.parse(jqXHR.responseText);
                            var errorMessage = "'.get_lang('An unexpected error occurred. Please try again later.').'";

                            if (response && response.text) {
                                errorMessage = response.text;
                            }

                            alert("'.get_lang('Request failed').': " + errorMessage);
                        } catch (e) {
                            alert("'.get_lang('Request failed').': " + "'.get_lang('An unexpected error occurred. Please contact support.').'");
                        }
                    }
                });

            });
        });
        </script>');

        $form->addButton('create_lp_button', get_lang('Generate'), 'check', 'primary', '', null, ['id' => 'create-lp-ai']);
        echo $form->returnForm();
    }

    public function createLearningPathFromAI(array $lpData, string $courseCode): array
    {
        if (!isset($lpData['topic'])) {
            return ['success' => false, 'text' => 'Error: Topic not set in AI response.'];
        }

        $lp = learnpath::add_lp(
            $courseCode,
            $lpData['topic'],
            '',
            'chamilo',
            'manual'
        );

        if (null === $lp || empty($lp->getIid())) {
            return ['success' => false, 'text' => 'Failed to create Learning Path.'];
        }

        $lpId = $lp->getIid();
        $courseInfo = api_get_course_info($courseCode);
        $learningPath = new learnpath($lp, $courseInfo, api_get_user_id());

        $lpItemRepo = Container::getLpItemRepository();

        $parent = $lpItemRepo->getRootItem($lpId);
        $lpItemsIds = [];
        $order = 1;

        require_once api_get_path(SYS_CODE_PATH).'exercise/export/aiken/aiken_import.inc.php';
        require_once api_get_path(SYS_CODE_PATH).'exercise/export/aiken/aiken_classes.php';

        foreach ($lpData['lp_items'] as $index => $item) {
            $documentId = $learningPath->create_document(
                $courseInfo,
                $item['content'],
                $item['title']
            );
            if (!empty($documentId)) {
                $previousId = $order > 1 ? (int) $lpItemsIds[$order - 1]['item_id'] : 0;
                $lpItemId = $learningPath->add_item(
                    $parent,
                    $previousId,
                    TOOL_DOCUMENT,
                    $documentId,
                    $item['title']
                );
                $lpItemsIds[$order] = ['item_id' => $lpItemId, 'item_type' => TOOL_DOCUMENT];
                $previousId = $lpItemId;
                $order++;
            }

            if (!empty($lpData['quiz_items'][$index]) && !empty(trim($lpData['quiz_items'][$index]['content']))) {
                $quiz = $lpData['quiz_items'][$index];

                $request = [
                    'quiz_name' => get_lang('Exercise') . ': ' . $quiz['title'],
                    'nro_questions' => count(explode("\n", trim($quiz['content']))),
                    'course_id' => api_get_course_int_id($courseCode),
                    'aiken_format' => trim($quiz['content']),
                ];

                $exerciseId = aiken_import_exercise(null, $request);

                if (!empty($exerciseId)) {
                    $lpQuizItemId = $learningPath->add_item(
                        $parent,
                        $previousId,
                        TOOL_QUIZ,
                        $exerciseId,
                        $request['quiz_name']
                    );

                    if (!empty($lpQuizItemId)) {
                        $lpItemsIds[$order] = [
                            'item_id' => $lpQuizItemId,
                            'item_type' => TOOL_QUIZ,
                            'min_score' => round($request['nro_questions'] / 2, 2),
                            'max_score' => (float) $request['nro_questions'],
                        ];
                        $previousId = $lpQuizItemId;
                        $order++;
                    }
                }
            }
        }

        return ['success' => true, 'lp_id' => $lpId];
    }
}
