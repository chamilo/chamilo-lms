<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;

/**
 * Best-effort: fetch AiDisclosureHelper from Symfony container (legacy context).
 */
function aiken_get_ai_disclosure_helper(): ?AiDisclosureHelper
{
    try {
        if (!class_exists(Container::class)) {
            return null;
        }

        $container = Container::$container;
        if (!$container) {
            return null;
        }

        if (method_exists($container, 'has') && $container->has(AiDisclosureHelper::class)) {
            $svc = $container->get(AiDisclosureHelper::class);

            return $svc instanceof AiDisclosureHelper ? $svc : null;
        }
    } catch (Throwable) {
        // Ignore container errors in legacy context.
    }

    return null;
}

/**
 * Returns whether AI disclosure is enabled (best-effort).
 */
function aiken_is_ai_disclosure_enabled(): bool
{
    $helper = aiken_get_ai_disclosure_helper();
    if (!$helper) {
        return false;
    }

    try {
        return $helper->isDisclosureEnabled();
    } catch (Throwable) {
        return false;
    }
}

/**
 * Adds a visible prefix to the question title.
 * This makes the AI assistance visible in existing UI without template changes.
 */
function aiken_prefix_ai_title(string $title): string
{
    $title = (string) $title;
    $prefix = '[AI-assisted] ';

    if ('' === trim($title)) {
        return $title;
    }

    if (str_starts_with($title, $prefix)) {
        return $title;
    }

    return $prefix.$title;
}

/**
 * Builds AI metadata from the generator/import request (if present).
 *
 * @return array<string,mixed>|null
 */
function aiken_build_ai_meta_from_request(?array $request): ?array
{
    if (!is_array($request) || empty($request)) {
        return null;
    }

    // Hidden flag set by generator JS after a successful AI call.
    $aiGenerated = isset($request['ai_generated']) && '1' === (string) $request['ai_generated'];
    if (!$aiGenerated) {
        return null;
    }

    $provider = '';
    if (isset($request['ai_provider_used'])) {
        $provider = trim((string) $request['ai_provider_used']);
    }
    if ('' === $provider && isset($request['ai_provider'])) {
        $provider = trim((string) $request['ai_provider']);
    }
    if ('' === $provider) {
        $provider = 'default';
    }

    $userId = (int) api_get_user_id();
    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    return [
        'feature' => 'exercise_generator_aiken',
        'mode' => 'generated',
        'provider' => $provider,
        'user_id' => $userId,
        'course_id' => $courseId,
        'session_id' => $sessionId,
        'disclose' => aiken_is_ai_disclosure_enabled(),
    ];
}

/**
 * This function displays the form for import of the zip file with qti2.
 *
 * @param string Report message to show in case of error
 */
function aiken_display_form()
{
    $name_tools = get_lang('Import Aiken quiz');
    $form = '<div class="actions">';
    $form .= '<a href="exercise.php?show=test&'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to Tests tool')).'</a>';
    $form .= '</div>';
    $form_validator = new FormValidator(
        'aiken_upload',
        'post',
        api_get_self().'?'.api_get_cidreq(),
        null,
        ['enctype' => 'multipart/form-data']
    );
    $form_validator->addElement('header', $name_tools);
    $form_validator->addElement('text', 'total_weight', get_lang('Total weight'));
    $form_validator->addElement('file', 'userFile', get_lang('File'));
    $form_validator->addButtonUpload(get_lang('Upload'), 'submit');
    $form .= $form_validator->returnForm();
    $form .= '<blockquote>'.get_lang('The Aiken format comes in a simple text (.txt) file, with several question blocks, each separated by a blank line. The first line is the question, the answer lines are prefixed by a letter and a dot, and the correct answer comes next with the ANSWER: prefix. See example below.').'<br />
<pre>
This is the text for question 1
A. Answer 1
B. Answer 2
C. Answer 3
ANSWER: B

This is the text for question 2
A. Answer 1
B. Answer 2
C. Answer 3
D. Answer 4
ANSWER: D
ANSWER_EXPLANATION: this is an optional feedback comment that will appear next to the correct answer.
SCORE: 20
</pre></blockquote>';
    echo $form;
}

/**
 * Set the exercise information from an aiken text formatted.
 */
function setExerciseInfoFromAikenText($aikenText, &$exerciseInfo): void
{
    $detect = mb_detect_encoding($aikenText, 'ASCII', true);
    if ('ASCII' === $detect) {
        $data = explode("\n", $aikenText);
    } else {
        if (false !== stripos($aikenText, "\x0D") || false !== stripos($aikenText, "\r\n")) {
            $text = str_ireplace(["\x0D", "\r\n"], "\n", $aikenText);
            $data = explode("\n", $text);
        } else {
            $data = explode("\n", $aikenText);
        }
    }

    $questionIndex = -1;
    $answersArray = [];
    $currentQuestion = null;

    foreach ($data as $line) {
        $line = trim($line);

        if ('' === $line) {
            continue;
        }

        if ('```' === $line || str_starts_with($line, '```')) {
            continue;
        }

        if (aiken_is_question_title_line($line)) {
            $questionIndex++;
            $answersArray = [];

            $title = aiken_strip_leading_question_number($line);

            $exerciseInfo['question'][$questionIndex] = [
                'type' => 'MCUA',
                'title' => $title,
                'answer' => [],
                'correct_answers' => [],
                'weighting' => [],
                'feedback' => '',
                'description' => '',
                'answer_tags' => [],
            ];

            $currentQuestion = &$exerciseInfo['question'][$questionIndex];
            continue;
        }

        if (null === $currentQuestion) {
            continue;
        }

        if (preg_match('/^([A-Z])\.\s(.*)/', $line, $matches)) {
            $answerIndex = count($currentQuestion['answer']);
            $currentQuestion['answer'][] = ['value' => $matches[2]];
            $answersArray[$matches[1]] = $answerIndex;
            continue;
        }

        if (preg_match('/^ANSWER:\s?([A-Z])/', $line, $matches)) {
            if (isset($answersArray[$matches[1]])) {
                $currentQuestion['correct_answers'][] = $answersArray[$matches[1]];
            }
            continue;
        }

        if (preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line, $matches)) {
            if ($questionIndex >= 0) {
                $exerciseInfo['question'][$questionIndex]['feedback'] = $matches[1];
            }
            continue;
        }
    }

    $totalQuestions = count($exerciseInfo['question']);
    $totalWeight = (int) ($exerciseInfo['total_weight'] ?? 20);
    foreach ($exerciseInfo['question'] as $key => $question) {
        $exerciseInfo['question'][$key]['weighting'][0] = $totalWeight / max(1, $totalQuestions);
    }
}

/**
 * Imports an Aiken file or AI-generated text and creates an exercise in Chamilo.
 *
 * @param string|null $file Path to the Aiken file (optional)
 * @param array|null $request AI form data (optional)
 *
 * @return mixed Exercise ID on success, error message on failure
 */
function aiken_import_exercise(string $file = null, ?array $request = [])
{
    $exerciseInfo = [];
    $fileIsSet = false;
    $baseWorkDir = api_get_path(SYS_ARCHIVE_PATH) . 'aiken/';
    $uploadPath = 'aiken_' . api_get_unique_id();

    $aiMeta = null;

    if ($file) {
        $fileIsSet = true;

        if (!is_dir($baseWorkDir . $uploadPath)) {
            mkdir($baseWorkDir . $uploadPath, api_get_permissions_for_new_directories(), true);
        }

        $exerciseInfo['name'] = preg_replace('/\.(zip|txt)$/i', '', basename($file));
        $exerciseInfo['question'] = [];

        if (!preg_match('/\.(zip|txt)$/i', $file)) {
            return get_lang('You must upload a .txt or .zip file');
        }

        $result = aiken_parse_file($exerciseInfo, $file);

        if ($result !== true) {
            return $result;
        }
    } elseif (!empty($request)) {
        $exerciseInfo['name'] = $request['quiz_name'] ?? '';
        $exerciseInfo['total_weight'] = !empty($_POST['ai_total_weight']) ? (int) ($_POST['ai_total_weight']) : (int) ($request['nro_questions'] ?? 20);
        $exerciseInfo['question'] = [];
        $exerciseInfo['course_id'] = api_get_course_int_id();

        setExerciseInfoFromAikenText($request['aiken_format'] ?? '', $exerciseInfo);

        // Only AI generator import will have this flag set
        $aiMeta = aiken_build_ai_meta_from_request($request);
    }

    return create_exercise_from_aiken($exerciseInfo, $fileIsSet ? $baseWorkDir . $uploadPath : null, $aiMeta);
}

/**
 * Creates an exercise from Aiken format data.
 *
 * @param array<string,mixed> $exerciseInfo
 * @param array<string,mixed>|null $aiMeta
 */
function create_exercise_from_aiken(array $exerciseInfo, ?string $workDir, ?array $aiMeta = null): int|false
{
    if (empty($exerciseInfo)) {
        return false;
    }

    $exercise = new Exercise();
    $exercise->exercise = $exerciseInfo['name'];
    $exercise->save();
    $lastExerciseId = $exercise->iId;

    if (!$lastExerciseId) {
        return false;
    }

    // Mark exercise as AI-assisted using extra fields (do NOT alter stored titles/text).
    if (is_array($aiMeta) && !empty($aiMeta['disclose'])) {
        $helper = aiken_get_ai_disclosure_helper();
        if ($helper) {
            $helper->markAiAssistedExtraField('exercise', (int) $lastExerciseId, true);
        }
    }

    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $tableAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $courseId = api_get_course_int_id();

    // AI audit for the exercise
    if (is_array($aiMeta)) {
        $helper = aiken_get_ai_disclosure_helper();
        if ($helper) {
            $helper->logAudit(
                targetKey: 'exercise:'.$lastExerciseId,
                userId: (int) ($aiMeta['user_id'] ?? 0),
                meta: [
                    'feature' => $aiMeta['feature'] ?? 'exercise_generator_aiken',
                    'mode' => $aiMeta['mode'] ?? 'generated',
                    'provider' => $aiMeta['provider'] ?? 'default',
                    'scope' => 'exercise',
                ],
                courseId: (int) ($aiMeta['course_id'] ?? $courseId),
                sessionId: (int) ($aiMeta['session_id'] ?? api_get_session_id())
            );
        }
    }

    foreach ($exerciseInfo['question'] as $index => $questionData) {
        if (!isset($questionData['title'])) {
            continue;
        }

        $question = new Aiken2Question();
        $question->type = $questionData['type'];
        $question->setAnswer();

        $title = (string) $questionData['title'];
        $question->updateTitle($title);

        if (isset($questionData['description'])) {
            $question->updateDescription($questionData['description']);
        }

        $type = $question->selectType();
        $question->type = constant($type);

        try {
            $question->save($exercise);
            $lastQuestionId = $question->id;

            if (!$lastQuestionId) {
                throw new Exception('Question ID is NULL after saving.');
            }
        } catch (Exception $e) {
            error_log("[ERROR] create_exercise_from_aiken: Error saving question '{$title}' - ".$e->getMessage());
            continue;
        }

        // Mark question as AI-assisted using extra fields (do NOT alter stored titles/text).
        if (is_array($aiMeta) && !empty($aiMeta['disclose'])) {
            $helper = aiken_get_ai_disclosure_helper();
            if ($helper) {
                $helper->markAiAssistedExtraField('question', (int) $lastQuestionId, true);
            }
        }

        // Create answers for the question
        // AI audit per created question (kept as-is)
        if (is_array($aiMeta)) {
            $helper = aiken_get_ai_disclosure_helper();
            if ($helper) {
                $helper->logAudit(
                    targetKey: 'question:'.$lastQuestionId,
                    userId: (int) ($aiMeta['user_id'] ?? 0),
                    meta: [
                        'feature' => $aiMeta['feature'] ?? 'exercise_generator_aiken',
                        'mode' => $aiMeta['mode'] ?? 'generated',
                        'provider' => $aiMeta['provider'] ?? 'default',
                        'scope' => 'question',
                        'exercise_id' => $lastExerciseId,
                    ],
                    courseId: (int) ($aiMeta['course_id'] ?? $courseId),
                    sessionId: (int) ($aiMeta['session_id'] ?? api_get_session_id())
                );
            }
        }

        $answer = new Answer($lastQuestionId, $courseId, $exercise, false);
        $answer->new_nbrAnswers = count($questionData['answer']);
        $maxScore = 0;
        $scoreFromFile = 0;

        foreach ($questionData['answer'] as $answerIndex => $answerData) {
            $answerIndex = (int) $answerIndex;
            $answer->new_answer[$answerIndex] = $answerData['value'];
            $answer->new_position[$answerIndex] = $answerIndex;
            $answer->new_comment[$answerIndex] = '';

            if (isset($questionData['correct_answers']) && in_array($answerIndex, $questionData['correct_answers'], true)) {
                $answer->new_correct[$answerIndex] = 1;
                if (isset($questionData['feedback'])) {
                    $answer->new_comment[$answerIndex] = $questionData['feedback'];
                }

                if (isset($questionData['weighting'])) {
                    $answer->new_weighting[$answerIndex] = $questionData['weighting'][0];
                    $maxScore += $questionData['weighting'][0];
                } else {
                    $answer->new_weighting[$answerIndex] = 1;
                    $maxScore += 1;
                }
            } else {
                $answer->new_correct[$answerIndex] = 0;
                $answer->new_weighting[$answerIndex] = 0;
            }

            if (isset($questionData['score'])) {
                $scoreFromFile = (float) $questionData['score'];
                $answer->new_weighting[$answerIndex] = $scoreFromFile;
            }

            $params = [
                'question_id' => $lastQuestionId,
                'answer' => $answer->new_answer[$answerIndex],
                'correct' => $answer->new_correct[$answerIndex],
                'comment' => $answer->new_comment[$answerIndex],
                'ponderation' => $answer->new_weighting[$answerIndex],
                'position' => $answer->new_position[$answerIndex],
                'hotspot_coordinates' => '',
                'hotspot_type' => '',
            ];

            $answerId = Database::insert($tableAnswer, $params);

            if (!$answerId) {
                error_log("[ERROR] create_exercise_from_aiken: Failed to insert answer for question ID: {$lastQuestionId}");
                continue;
            }

            Database::update($tableAnswer, ['iid' => $answerId], ['iid = ?' => [$answerId]]);
        }

        if (!empty($scoreFromFile)) {
            $maxScore = $scoreFromFile;
        }

        Database::update($tableQuestion, ['ponderation' => $maxScore], ['iid = ?' => [$lastQuestionId]]);
    }

    if ($workDir) {
        my_delete($workDir);
    }

    return $lastExerciseId;
}

/**
 * Generates the Aiken question form with AI integration.
 */
function generateAikenForm()
{
    if ('true' !== api_get_setting('ai_helpers.enable_ai_helpers')) {
        return false;
    }

    $aiProvidersJson = api_get_setting('ai_helpers.ai_providers');
    $rawProviders = json_decode($aiProvidersJson, true);
    $rawProviders = is_array($rawProviders) ? $rawProviders : [];

    $availableApis = [];
    foreach ($rawProviders as $key => $cfg) {
        if (!is_array($cfg)) {
            continue;
        }
        if (isset($cfg['text']) && is_array($cfg['text'])) {
            $availableApis[$key] = $cfg;
            continue;
        }
        if (isset($cfg['model']) || isset($cfg['url'])) {
            $availableApis[$key] = $cfg;
            continue;
        }
    }

    if (empty($availableApis)) {
        echo Display::return_message(get_lang('No AI text providers configured.'), 'warning');
        return false;
    }

    $providerOptions = [];
    foreach ($availableApis as $key => $cfg) {
        $model = '';
        if (isset($cfg['text']['model'])) {
            $model = (string) $cfg['text']['model'];
        } elseif (isset($cfg['model'])) {
            $model = (string) $cfg['model'];
        }

        $label = $key;
        if ('' !== trim($model)) {
            $label .= ' ('.$model.')';
        }

        $providerOptions[$key] = $label;
    }

    $hasSingleApi = count($providerOptions) === 1;
    $configuredApi = $hasSingleApi ? array_key_first($providerOptions) : null;

    $form = new FormValidator(
        'aiken_generate',
        'post',
        api_get_self().'?'.api_get_cidreq(),
        null
    );

    $form->addElement('header', get_lang('AI Questions Generator'));

    // Hidden flags so the import step can log/tag as AI-generated.
    $form->addElement('hidden', 'ai_generated', '0');
    $form->addElement('hidden', 'ai_provider_used', '');
    $form->addElement('hidden', 'ai_generation_ts', '');

    if ($hasSingleApi && null !== $configuredApi) {
        $form->addHtml(
            '<div style="margin-bottom: 10px; font-size: 14px; color: #555;">'
            .sprintf(get_lang('Using AI provider %s'), '<strong>'.htmlspecialchars($providerOptions[$configuredApi]).'</strong>')
            .'</div>'
        );
    }

    $form->addElement('text', 'quiz_name', get_lang('Questions topic'));
    $form->addRule('quiz_name', get_lang('Required field'), 'required');
    $form->addElement('number', 'nro_questions', get_lang('Number of questions'));
    $form->addRule('nro_questions', get_lang('Required field'), 'required');

    $options = [
        'multiple_choice' => get_lang('Multiple answer'),
    ];

    $form->addSelect('question_type', get_lang('Question type'), $options);

    if (!$hasSingleApi) {
        $form->addSelect('ai_provider', get_lang('AI provider'), $providerOptions);
    }

    $generateUrl = api_get_path(WEB_PATH).'ai/generate_aiken';

    $courseInfo = api_get_course_info();
    $language = $courseInfo['language'] ?? 'en';

    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    $form->addHtml('<script>
    $(function () {
        $("#aiken-area").hide();

        $("#generate-aiken").on("click", function (e) {
            e.preventDefault();
            e.stopPropagation();

            var btnGenerate = $(this);
            var quizName = $("[name=\'quiz_name\']").val().trim();
            var nroQ = parseInt($("[name=\'nro_questions\']").val());
            var qType = $("[name=\'question_type\']").val();'
        . (!$hasSingleApi
            ? 'var provider = $("[name=\'ai_provider\']").val();'
            : 'var provider = "'.addslashes((string) $configuredApi).'";'
        ) . '
            var isValid = true;

            $(".error-message").remove();

            if (quizName === "") {
                $("[name=\'quiz_name\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('Required field').'</div>");
                isValid = false;
            }

            if (isNaN(nroQ) || nroQ <= 0) {
                $("[name=\'nro_questions\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('Please enter a valid number of questions').'</div>");
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            btnGenerate.attr("disabled", true);
            btnGenerate.text("'.get_lang('Please wait. This could take a while...').'");

            $("input[name=\'ai_generated\']").val("0");
            $("input[name=\'ai_provider_used\']").val("");
            $("input[name=\'ai_generation_ts\']").val("");

            $("#textarea-aiken").text("");
            $("#aiken-area").hide();

            var requestData = JSON.stringify({
                "quiz_name": quizName,
                "nro_questions": nroQ,
                "question_type": qType,
                "language": "'.addslashes((string) $language).'",
                "ai_provider": provider,
                "cid": '.(int) $courseId.',
                "sid": '.(int) $sessionId.'
            });

            $.ajax({
                url: "'.addslashes((string) $generateUrl).'",
                type: "POST",
                contentType: "application/json",
                data: requestData,
                dataType: "json",
                success: function (data) {
                    btnGenerate.attr("disabled", false);
                    btnGenerate.text("'.get_lang('Generate Aiken').'");

                    if (data.success) {
                        $("#aiken-area").show();
                        $("#textarea-aiken").text(data.text);
                        $("#textarea-aiken").focus();

                        $("input[name=\'ai_generated\']").val("1");
                        $("input[name=\'ai_provider_used\']").val(provider);
                        $("input[name=\'ai_generation_ts\']").val(String(Date.now()));
                    } else {
                        alert("'.get_lang('An error occurred.').': " + data.text);
                    }
                },
                error: function (jqXHR) {
                    btnGenerate.attr("disabled", false);
                    btnGenerate.text("'.get_lang('Generate Aiken').'");

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

    $form->addButtonSend(get_lang('Generate Aiken'), 'submit', false, ['id' => 'generate-aiken']);
    $form->addHtml('<div id="aiken-area">');
    $form->addElement(
        'textarea',
        'aiken_format',
        get_lang('Answers'),
        [
            'id' => 'textarea-aiken',
            'style' => 'width: 100%; height: 250px;',
        ]
    );
    $form->addElement('number', 'ai_total_weight', get_lang('Total weight'));
    $form->addButtonImport(get_lang('Import'), 'submit_aiken_generated');
    $form->addHtml('</div>');

    echo $form->returnForm();
}

/**
 * Removes a leading question number generated by LLMs, e.g. "1. ", "2) ", "3 - ".
 */
function aiken_strip_leading_question_number(string $line): string
{
    $line = trim($line);

    if ('' === $line) {
        return $line;
    }

    $normalized = preg_replace('/^\d+\s*[\.\)\-:]\s+/u', '', $line);
    if (null === $normalized) {
        return $line;
    }

    $normalized = trim($normalized);

    return '' !== $normalized ? $normalized : $line;
}

/**
 * Detects whether a line should be treated as the question title line in Aiken text.
 */
function aiken_is_question_title_line(string $line): bool
{
    return !preg_match('/^[A-Z]\.\s/', $line)
        && !preg_match('/^ANSWER:\s?[A-Z]/', $line)
        && !preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line);
}
