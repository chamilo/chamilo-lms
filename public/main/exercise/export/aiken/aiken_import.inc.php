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

    $feature = trim((string) ($request['ai_feature'] ?? 'exercise_generator_aiken'));
    if ('' === $feature) {
        $feature = 'exercise_generator_aiken';
    }

    $userId = (int) api_get_user_id();
    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();

    return [
        'feature' => $feature,
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
        $exerciseTitle = trim((string) ($request['exercise_title'] ?? ''));
        if ('' === $exerciseTitle) {
            $exerciseTitle = trim((string) ($request['quiz_name'] ?? ''));
        }
        if ('' === $exerciseTitle) {
            $exerciseTitle = get_lang('Imported Aiken quiz');
        }

        $exerciseInfo['name'] = $exerciseTitle;
        $exerciseInfo['total_weight'] = !empty($_POST['ai_total_weight'])
            ? (int) ($_POST['ai_total_weight'])
            : (int) ($request['nro_questions'] ?? 20);
        $exerciseInfo['question'] = [];
        $exerciseInfo['course_id'] = api_get_course_int_id();

        setExerciseInfoFromAikenText($request['aiken_format'] ?? '', $exerciseInfo);

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
 * Returns the configured AI providers array.
 *
 * @return array<string,mixed>
 */
function aiken_get_ai_provider_config(): array
{
    $aiProvidersJson = api_get_setting('ai_helpers.ai_providers');
    $rawProviders = json_decode($aiProvidersJson, true);

    return is_array($rawProviders) ? $rawProviders : [];
}

/**
 * Returns provider options for text generation.
 *
 * @return array<string,string>
 */
function aiken_get_text_provider_options(): array
{
    $rawProviders = aiken_get_ai_provider_config();
    $providerOptions = [];

    foreach ($rawProviders as $key => $cfg) {
        if (!is_array($cfg)) {
            continue;
        }

        $supportsText = false;

        if (
            (!isset($cfg['document']) || !is_array($cfg['document']))
            && (!isset($cfg['document_process']) || !is_array($cfg['document_process']))
        ) {
            continue;
        }

        $model = '';
        if (isset($cfg['document']['model'])) {
            $model = (string) $cfg['document']['model'];
        } elseif (isset($cfg['document_process']['model'])) {
            $model = (string) $cfg['document_process']['model'];
        }

        if (isset($cfg['text']) && is_array($cfg['text'])) {
            $supportsText = true;
            $model = (string) ($cfg['text']['model'] ?? '');
        } elseif (isset($cfg['model']) || isset($cfg['url'])) {
            $supportsText = true;
            $model = (string) ($cfg['model'] ?? '');
        }

        if (!$supportsText) {
            continue;
        }

        $label = (string) $key;
        if ('' !== trim($model)) {
            $label .= ' ('.$model.')';
        }

        $providerOptions[(string) $key] = $label;
    }

    return $providerOptions;
}

/**
 * Returns provider options for document processing.
 * We use document_process because the backend endpoint will analyze PDF files.
 *
 * @return array<string,string>
 */
function aiken_get_document_provider_options(): array
{
    $rawProviders = aiken_get_ai_provider_config();
    $providerOptions = [];

    foreach ($rawProviders as $key => $cfg) {
        if (!is_array($cfg)) {
            continue;
        }

        if (!isset($cfg['document_process']) || !is_array($cfg['document_process'])) {
            continue;
        }

        $model = (string) ($cfg['document_process']['model'] ?? '');
        $label = (string) $key;

        if ('' !== trim($model)) {
            $label .= ' ('.$model.')';
        }

        $providerOptions[(string) $key] = $label;
    }

    return $providerOptions;
}

/**
 * Returns the single provider key if the options array contains exactly one provider.
 */
function aiken_get_single_provider_key(array $providerOptions): ?string
{
    if (1 !== count($providerOptions)) {
        return null;
    }

    $key = array_key_first($providerOptions);

    return is_string($key) && '' !== trim($key) ? $key : null;
}

/**
 * Generates the Aiken question form with AI integration.
 */
function generateAikenForm()
{
    if ('true' !== api_get_setting('ai_helpers.enable_ai_helpers')) {
        return false;
    }

    $textProviderOptions = aiken_get_text_provider_options();
    if (empty($textProviderOptions)) {
        echo Display::return_message(get_lang('No AI text providers configured.'), 'warning');

        return false;
    }

    $documentProviderOptions = aiken_get_document_provider_options();

    $singleTextProvider = aiken_get_single_provider_key($textProviderOptions);
    $singleDocumentProvider = aiken_get_single_provider_key($documentProviderOptions);

    $hasSingleTextProvider = null !== $singleTextProvider;
    $hasSingleDocumentProvider = null !== $singleDocumentProvider;
    $hasDocumentTab = !empty($documentProviderOptions);

    $form = new FormValidator(
        'aiken_generate',
        'post',
        api_get_self().'?'.api_get_cidreq(),
        null
    );

    $form->addElement('header', get_lang('AI Questions Generator'));

    // Canonical hidden fields reused by the import step.
    $form->addElement('hidden', 'ai_generated', '0');
    $form->addElement('hidden', 'ai_provider_used', '');
    $form->addElement('hidden', 'ai_generation_ts', '');
    $form->addElement('hidden', 'ai_feature', '');
    $form->addElement('hidden', 'exercise_title', '');
    $form->addElement('hidden', 'quiz_name', '');
    $form->addElement('hidden', 'resource_file_id', '');
    $form->addElement('hidden', 'document_title', '');
    $form->addElement('hidden', 'nro_questions', '');
    $form->addElement('hidden', 'question_type', '');

    $courseInfo = api_get_course_info();
    $language = $courseInfo['language'] ?? 'en';
    $courseId = (int) api_get_course_int_id();
    $sessionId = (int) api_get_session_id();
    $groupId = (int) api_get_group_id();

    $classicGenerateUrl = api_get_path(WEB_PATH).'ai/generate_aiken';
    $documentGenerateUrl = api_get_path(WEB_PATH).'ai/generate_aiken_from_document';
    $documentPickerUrl = api_get_path(WEB_PATH).'main/inc/ajax/exercise.ajax.php?'.api_get_cidreq().'&a=list_aiken_documents';

    $questionTypeOptions = [
        'multiple_choice' => get_lang('Multiple answer'),
    ];

    $panelClass = 'rounded-2xl border border-gray-25 bg-white shadow-sm';
    $panelBodyClass = 'p-6 md:p-8';
    $fieldLabelClass = 'block text-body-2 font-semibold text-gray-90';
    $fieldHintClass = 'mt-2 block text-caption text-gray-50';
    $inputClass = 'mt-2 block w-full rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 text-gray-90 shadow-sm transition focus:border-primary focus:ring-2 focus:ring-support-3';
    $readonlyInputClass = 'mt-2 block w-full rounded-xl border border-gray-25 bg-gray-10 px-4 py-3 text-body-2 text-gray-90 shadow-sm';
    $primaryButtonClass = 'inline-flex items-center justify-center rounded-xl bg-primary px-5 py-3 text-body-2 font-semibold text-white shadow-sm transition hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-support-3 disabled:cursor-not-allowed disabled:opacity-60';
    $secondaryButtonClass = 'inline-flex items-center justify-center rounded-xl bg-secondary px-5 py-3 text-body-2 font-semibold text-secondary-button-text shadow-sm transition hover:bg-secondary-hover focus:outline-none focus:ring-2 focus:ring-support-3 disabled:cursor-not-allowed disabled:opacity-60';
    $ghostButtonClass = 'inline-flex items-center justify-center rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 font-semibold text-gray-90 shadow-sm transition hover:border-primary hover:text-primary focus:outline-none focus:ring-2 focus:ring-support-3';
    $providerBadgeClass = 'rounded-xl bg-support-2 px-4 py-3 text-body-2 text-gray-90';
    $tabBaseClass = 'aiken-ai-tab inline-flex items-center justify-center rounded-xl px-5 py-3 text-body-2 font-semibold transition focus:outline-none focus:ring-2 focus:ring-support-3';
    $tabActiveClass = 'bg-primary text-white shadow-sm';
    $tabInactiveClass = 'bg-transparent text-gray-90 hover:bg-white hover:text-primary';
    $sectionTitleClass = 'text-lg font-semibold text-gray-90';
    $sectionTextClass = 'mt-1 text-body-2 text-gray-50';

    $html = '';
    $html .= '<div class="mx-auto w-full space-y-6">';
    $html .= '  <div class="'.$panelClass.'">';
    $html .= '      <div class="border-b border-gray-25 px-6 py-5 md:px-8">';
    $html .= '          <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">';
    $html .= '              <div>';
    $html .= '                  <h2 class="text-xl font-bold text-gray-90">'.get_lang('AI Questions Generator').'</h2>';
    $html .= '                  <p class="mt-1 text-body-2 text-gray-50">'.get_lang('Generate an Aiken quiz from a topic or from one course document.').'</p>';
    $html .= '              </div>';
    $html .= '          </div>';
    $html .= '      </div>';
    $html .= '      <div class="'.$panelBodyClass.'">';
    $html .= '          <div class="mb-6 inline-flex rounded-2xl border border-gray-25 bg-gray-20 p-1">';
    $html .= '              <button type="button" class="'.$tabBaseClass.' '.$tabActiveClass.'" data-tab="topic">'.get_lang('Test from topic').'</button>';
    if ($hasDocumentTab) {
        $html .= '          <button type="button" class="'.$tabBaseClass.' '.$tabInactiveClass.'" data-tab="document">'.get_lang('Test from document').'</button>';
    }
    $html .= '          </div>';

    // Topic panel
    $html .= '      <div id="aiken-tab-topic" class="aiken-ai-panel '.$panelClass.'">';
    $html .= '          <div class="'.$panelBodyClass.'">';
    $html .= '              <div class="mb-6">';
    $html .= '                  <h3 class="'.$sectionTitleClass.'">'.get_lang('Generate from topic').'</h3>';
    $html .= '                  <p class="'.$sectionTextClass.'">'.get_lang('Use a topic prompt to generate a new Aiken questionnaire.').'</p>';
    $html .= '              </div>';

    $html .= '              <div class="grid grid-cols-1 gap-6 md:grid-cols-2">';
    $html .= '                  <div>';
    $html .= '                      <label for="classic_exercise_title" class="'.$fieldLabelClass.'">'.get_lang('Quiz title').'</label>';
    $html .= '                      <input type="text" id="classic_exercise_title" class="'.$inputClass.'" />';
    $html .= '                  </div>';
    $html .= '                  <div>';
    $html .= '                      <label for="classic_topic_prompt" class="'.$fieldLabelClass.'">'.get_lang('Questions topic').'</label>';
    $html .= '                      <input type="text" id="classic_topic_prompt" class="'.$inputClass.'" />';
    $html .= '                  </div>';
    $html .= '                  <div>';
    $html .= '                      <label for="classic_nro_questions" class="'.$fieldLabelClass.'">'.get_lang('Number of questions').'</label>';
    $html .= '                      <input type="number" id="classic_nro_questions" min="1" class="'.$inputClass.'" />';
    $html .= '                  </div>';
    $html .= '                  <div>';
    $html .= '                      <label for="classic_question_type" class="'.$fieldLabelClass.'">'.get_lang('Question type').'</label>';
    $html .= '                      <select id="classic_question_type" class="'.$inputClass.'">';
    foreach ($questionTypeOptions as $key => $label) {
        $html .= '                  <option value="'.htmlspecialchars($key).'">'.htmlspecialchars($label).'</option>';
    }
    $html .= '                      </select>';
    $html .= '                  </div>';

    if ($hasSingleTextProvider && null !== $singleTextProvider) {
        $html .= '              <div class="md:col-span-2">';
        $html .= '                  <div class="'.$providerBadgeClass.'">';
        $html .= '                      <span class="font-semibold text-support-4">'.get_lang('AI provider').':</span> ';
        $html .= '                      <span class="font-semibold text-gray-90">'.htmlspecialchars($textProviderOptions[$singleTextProvider]).'</span>';
        $html .= '                  </div>';
        $html .= '              </div>';
    } else {
        $html .= '              <div class="md:col-span-2">';
        $html .= '                  <label for="classic_ai_provider" class="'.$fieldLabelClass.'">'.get_lang('AI provider').'</label>';
        $html .= '                  <select id="classic_ai_provider" class="'.$inputClass.'">';
        foreach ($textProviderOptions as $key => $label) {
            $html .= '              <option value="'.htmlspecialchars($key).'">'.htmlspecialchars($label).'</option>';
        }
        $html .= '                  </select>';
        $html .= '              </div>';
    }

    $html .= '              </div>';
    $html .= '              <div class="mt-8 flex justify-end">';
    $html .= '                  <button type="button" id="generate-aiken-topic" class="'.$primaryButtonClass.'">'.get_lang('Generate Aiken').'</button>';
    $html .= '              </div>';
    $html .= '          </div>';
    $html .= '      </div>';

    // Document panel
    if ($hasDocumentTab) {
        $html .= '  <div id="aiken-tab-document" class="aiken-ai-panel '.$panelClass.'" style="display:none;">';
        $html .= '      <div class="'.$panelBodyClass.'">';
        $html .= '          <div class="mb-6">';
        $html .= '              <h3 class="'.$sectionTitleClass.'">'.get_lang('Generate from document').'</h3>';
        $html .= '              <p class="'.$sectionTextClass.'">'.get_lang('Select one course document and generate questions based only on its contents.').'</p>';
        $html .= '          </div>';

        $html .= '          <div class="grid grid-cols-1 gap-6 md:grid-cols-2">';
        $html .= '              <div>';
        $html .= '                  <label for="document_exercise_title" class="'.$fieldLabelClass.'">'.get_lang('Quiz title').'</label>';
        $html .= '                  <input type="text" id="document_exercise_title" class="'.$inputClass.'" />';
        $html .= '              </div>';

        $html .= '              <div>';
        $html .= '                  <label class="'.$fieldLabelClass.'">'.get_lang('Selected document').'</label>';
        $html .= '                  <div class="mt-2 flex flex-col gap-3 sm:flex-row">';
        $html .= '                      <input type="text" id="document_selected_label" readonly class="'.$readonlyInputClass.' sm:flex-1" />';
        $html .= '                      <button type="button" id="open-document-picker" class="'.$secondaryButtonClass.'">'.get_lang('Choose document').'</button>';
        $html .= '                  </div>';
        $html .= '                  <small class="'.$fieldHintClass.'">'.get_lang('The document is selected from the course documents list.').'</small>';
        $html .= '              </div>';

        $html .= '              <input type="hidden" id="document_resource_file_id" />';
        $html .= '              <input type="hidden" id="document_title_input" />';

        $html .= '              <div class="md:col-span-2">';
        $html .= '                  <div id="aiken-document-picker" style="display:none;" class="rounded-2xl border border-gray-25 bg-gray-10 p-4 shadow-sm">';
        $html .= '                      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">';
        $html .= '                          <div>';
        $html .= '                              <h4 class="text-body-1 font-semibold text-gray-90">'.get_lang('Select a document').'</h4>';
        $html .= '                              <p class="mt-1 text-caption text-gray-50">'.get_lang('Only supported documents will be listed here.').'</p>';
        $html .= '                          </div>';
        $html .= '                          <button type="button" id="close-document-picker" class="'.$ghostButtonClass.'">'.get_lang('Close').'</button>';
        $html .= '                      </div>';
        $html .= '                      <div class="mb-4">';
        $html .= '                          <input type="text" id="document_picker_search" class="'.$inputClass.' mt-0" placeholder="'.htmlspecialchars(get_lang('Search')).'" />';
        $html .= '                      </div>';
        $html .= '                      <div id="document-picker-status" class="mb-3 text-caption text-gray-50"></div>';
        $html .= '                      <div id="document-picker-results" class="max-h-72 overflow-y-auto rounded-2xl border border-gray-25 bg-white p-2"></div>';
        $html .= '                  </div>';
        $html .= '              </div>';

        $html .= '              <div class="md:col-span-2">';
        $html .= '                  <label for="document_topic_prompt" class="'.$fieldLabelClass.'">'.get_lang('Questions topic').'</label>';
        $html .= '                  <input type="text" id="document_topic_prompt" class="'.$inputClass.'" />';
        $html .= '                  <small class="'.$fieldHintClass.'">'.get_lang('Optional. Used only as an extra hint for the AI.').'</small>';
        $html .= '              </div>';

        $html .= '              <div>';
        $html .= '                  <label for="document_nro_questions" class="'.$fieldLabelClass.'">'.get_lang('Number of questions').'</label>';
        $html .= '                  <input type="number" id="document_nro_questions" min="1" class="'.$inputClass.'" />';
        $html .= '              </div>';

        $html .= '              <div>';
        $html .= '                  <label for="document_question_type" class="'.$fieldLabelClass.'">'.get_lang('Question type').'</label>';
        $html .= '                  <select id="document_question_type" class="'.$inputClass.'">';
        foreach ($questionTypeOptions as $key => $label) {
            $html .= '              <option value="'.htmlspecialchars($key).'">'.htmlspecialchars($label).'</option>';
        }
        $html .= '                  </select>';
        $html .= '              </div>';

        if ($hasSingleDocumentProvider && null !== $singleDocumentProvider) {
            $html .= '          <div class="md:col-span-2">';
            $html .= '              <div class="'.$providerBadgeClass.'">';
            $html .= '                  <span class="font-semibold text-support-4">'.get_lang('AI provider').':</span> ';
            $html .= '                  <span class="font-semibold text-gray-90">'.htmlspecialchars($documentProviderOptions[$singleDocumentProvider]).'</span>';
            $html .= '              </div>';
            $html .= '          </div>';
        } else {
            $html .= '          <div class="md:col-span-2">';
            $html .= '              <label for="document_ai_provider" class="'.$fieldLabelClass.'">'.get_lang('AI provider').'</label>';
            $html .= '              <select id="document_ai_provider" class="'.$inputClass.'">';
            foreach ($documentProviderOptions as $key => $label) {
                $html .= '          <option value="'.htmlspecialchars($key).'">'.htmlspecialchars($label).'</option>';
            }
            $html .= '              </select>';
            $html .= '          </div>';
        }

        $html .= '          </div>';
        $html .= '          <div class="mt-8 flex justify-end">';
        $html .= '              <button type="button" id="generate-aiken-document" class="'.$primaryButtonClass.'">'.get_lang('Generate Aiken').'</button>';
        $html .= '          </div>';
        $html .= '      </div>';
        $html .= '  </div>';
    }

    $html .= '      </div>';
    $html .= '  </div>';

    $html .= '  <div id="aiken-area" style="display:none;" class="'.$panelClass.'">';
    $html .= '      <div class="'.$panelBodyClass.'">';
    $html .= '          <div class="mb-6">';
    $html .= '              <h3 class="'.$sectionTitleClass.'">'.get_lang('Generated Aiken content').'</h3>';
    $html .= '              <p class="'.$sectionTextClass.'">'.get_lang('Review the generated content before importing it as a new exercise.').'</p>';
    $html .= '          </div>';
    $html .= '      </div>';
    $html .= '  </div>';
    $html .= '</div>';

    $form->addHtml($html);

    $form->addElement(
        'textarea',
        'aiken_format',
        get_lang('Answers'),
        [
            'id' => 'textarea-aiken',
            'class' => 'mx-auto mt-4 block w-full rounded-2xl border border-gray-25 bg-white px-4 py-4 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-2 focus:ring-support-3',
            'style' => 'min-height: 320px;',
        ]
    );
    $form->addElement(
        'number',
        'ai_total_weight',
        get_lang('Total weight'),
        [
            'class' => 'mx-auto mt-4 block w-full max-w-xs rounded-xl border border-gray-25 bg-white px-4 py-3 text-body-2 text-gray-90 shadow-sm focus:border-primary focus:ring-2 focus:ring-support-3',
        ]
    );
    $form->addButtonImport(
        get_lang('Import'),
        'submit_aiken_generated',
        false,
        [
            'class' => $primaryButtonClass.' mx-auto mt-4',
        ]
    );

    $classicProviderJs = $hasSingleTextProvider
        ? json_encode($singleTextProvider, JSON_UNESCAPED_SLASHES)
        : '$("#classic_ai_provider").val()';

    $documentProviderJs = $hasSingleDocumentProvider
        ? json_encode($singleDocumentProvider, JSON_UNESCAPED_SLASHES)
        : '$("#document_ai_provider").val()';

    $documentTabEnabledJs = $hasDocumentTab ? 'true' : 'false';

    $classicGenerateUrlJs = json_encode($classicGenerateUrl, JSON_UNESCAPED_SLASHES);
    $documentGenerateUrlJs = json_encode($documentGenerateUrl, JSON_UNESCAPED_SLASHES);
    $documentPickerUrlJs = json_encode($documentPickerUrl, JSON_UNESCAPED_SLASHES);
    $languageJs = json_encode((string) $language, JSON_UNESCAPED_SLASHES);

    $requiredFieldLabel = json_encode(get_lang('Required field'), JSON_UNESCAPED_SLASHES);
    $invalidQuestionsLabel = json_encode(get_lang('Please enter a valid number of questions'), JSON_UNESCAPED_SLASHES);
    $waitLabel = json_encode(get_lang('Please wait. This could take a while...'), JSON_UNESCAPED_SLASHES);
    $genericErrorLabel = json_encode(get_lang('An error occurred.'), JSON_UNESCAPED_SLASHES);
    $unexpectedErrorLabel = json_encode(get_lang('An unexpected error occurred. Please try again later.'), JSON_UNESCAPED_SLASHES);
    $generateAikenLabel = json_encode(get_lang('Generate Aiken'), JSON_UNESCAPED_SLASHES);
    $generateAikenFromDocumentLabel = json_encode(get_lang('Generate Aiken'), JSON_UNESCAPED_SLASHES);
    $loadingDocumentsLabel = json_encode(get_lang('Loading documents...'), JSON_UNESCAPED_SLASHES);
    $noDocumentsLabel = json_encode(get_lang('No documents available.'), JSON_UNESCAPED_SLASHES);
    $requestFailedLabel = json_encode(get_lang('Request failed'), JSON_UNESCAPED_SLASHES);
    $tabActiveClassJs = json_encode($tabActiveClass, JSON_UNESCAPED_SLASHES);
    $tabInactiveClassJs = json_encode($tabInactiveClass, JSON_UNESCAPED_SLASHES);

    $script = <<<JS
<script>
$(function () {
    var documentPickerUrl = {$documentPickerUrlJs};
    var documentItems = [];
    var documentListLoaded = false;
    var tabActiveClass = {$tabActiveClassJs};
    var tabInactiveClass = {$tabInactiveClassJs};

    function clearErrors() {
        $(".aiken-ai-error").remove();
    }

    function escapeHtml(value) {
        return $("<div>").text(value == null ? "" : String(value)).html();
    }

    function showFieldError(selector, message) {
        var \$field = $(selector);
        if (!\$field.length) {
            return;
        }
        \$field.after("<div class='aiken-ai-error mt-2 text-caption font-medium text-danger'>" + escapeHtml(message) + "</div>");
    }

    function resetGeneratedArea() {
        $("#textarea-aiken").text("");
        $("#aiken-area").hide();
        $("input[name='ai_generated']").val("0");
        $("input[name='ai_provider_used']").val("");
        $("input[name='ai_generation_ts']").val("");
        $("input[name='ai_feature']").val("");
    }

    function setCanonicalFields(data) {
        $("input[name='exercise_title']").val(data.exercise_title || "");
        $("input[name='quiz_name']").val(data.quiz_name || "");
        $("input[name='resource_file_id']").val(data.resource_file_id || "");
        $("input[name='document_title']").val(data.document_title || "");
        $("input[name='nro_questions']").val(data.nro_questions || "");
        $("input[name='question_type']").val(data.question_type || "");
        $("input[name='ai_feature']").val(data.ai_feature || "");
    }

    function activateTab(tabName) {
        $(".aiken-ai-panel").hide();
        $("#aiken-tab-" + tabName).show();

        $(".aiken-ai-tab").each(function () {
            var \$tab = $(this);
            \$tab.removeClass(tabActiveClass + " " + tabInactiveClass);

            if (\$tab.data("tab") === tabName) {
                \$tab.addClass(tabActiveClass);
            } else {
                \$tab.addClass(tabInactiveClass);
            }
        });

        if (tabName === "document" && !documentListLoaded) {
            loadDocumentList();
        }

        clearErrors();
    }

    $(".aiken-ai-tab").on("click", function (e) {
        e.preventDefault();
        activateTab($(this).data("tab"));
    });

    function finalizeSuccess(provider, feature, text) {
        $("#aiken-area").show();
        $("#textarea-aiken").text(text);
        $("#textarea-aiken").focus();

        $("input[name='ai_generated']").val("1");
        $("input[name='ai_provider_used']").val(provider || "");
        $("input[name='ai_generation_ts']").val(String(Date.now()));
        $("input[name='ai_feature']").val(feature || "");
    }

    function handleAjaxError(jqXHR, defaultMessage, button, buttonLabel) {
        button.prop("disabled", false);
        button.text(buttonLabel);

        try {
            var response = JSON.parse(jqXHR.responseText);
            if (response && response.text) {
                alert(response.text);
                return;
            }
        } catch (e) {
        }

        alert(defaultMessage);
    }

    function normalizeDocumentList(response) {
        if (Array.isArray(response)) {
            return response;
        }

        if (response && Array.isArray(response.documents)) {
            return response.documents;
        }

        return [];
    }

    function resolveDocumentId(item) {
        var candidates = [
            item.resource_file_id,
            item.resourceFileId,
            item.document_resource_file_id,
            item.documentResourceFileId,
            item.file_id,
            item.fileId,
            item.document_id,
            item.documentId,
            item.iid,
            item.id
        ];

        for (var i = 0; i < candidates.length; i++) {
            var value = candidates[i];
            if (value === null || value === undefined || value === "") {
                continue;
            }

            var parsed = parseInt(value, 10);
            if (!isNaN(parsed) && parsed > 0) {
                return parsed;
            }
        }

        return 0;
    }

    function resolveDocumentTitle(item) {
        return String(
            item.title ||
            item.filename ||
            item.name ||
            ""
        ).trim();
    }

    function resolveDocumentSize(item) {
        return String(
            item.size_human ||
            item.size_readable ||
            item.sizeHuman ||
            item.sizeReadable ||
            item.size ||
            ""
        ).trim();
    }

    function resolveDocumentExtension(item) {
        return String(item.extension || "").trim().toUpperCase();
    }

    function selectDocument(item) {
        var id = resolveDocumentId(item);
        var title = resolveDocumentTitle(item);

        $("#document_resource_file_id").val(id > 0 ? String(id) : "");
        $("#document_title_input").val(title);
        $("#document_selected_label").val(title !== "" ? title : "");
        $("#aiken-document-picker").hide();
    }

    function renderDocumentItems(items, filterValue) {
        var query = String(filterValue || "").trim().toLowerCase();
        var html = "";
        var visibleCount = 0;

        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var id = resolveDocumentId(item);
            var title = resolveDocumentTitle(item);
            var size = resolveDocumentSize(item);
            var extension = resolveDocumentExtension(item);

            if (title === "") {
                continue;
            }

            if (query !== "" && title.toLowerCase().indexOf(query) === -1) {
                continue;
            }

            visibleCount++;

            var disabledAttr = id > 0 ? "" : " disabled";
            var meta = "";

            if (extension !== "") {
                meta += "<span class='rounded-full bg-support-1 px-2 py-1 text-tiny font-semibold text-support-4'>" + escapeHtml(extension) + "</span>";
            }

            if (size !== "") {
                meta += "<span class='text-caption text-gray-50'>" + escapeHtml(size) + "</span>";
            }

            html += ""
                + "<label class='flex cursor-pointer items-start gap-3 rounded-xl border border-gray-25 bg-white px-4 py-3 transition hover:border-primary hover:bg-support-2'>"
                +   "<input type='radio' name='aiken_document_picker_choice' value='" + escapeHtml(String(i)) + "'" + disabledAttr + " class='mt-1 h-4 w-4 border-gray-30 text-primary focus:ring-primary' />"
                +   "<span class='min-w-0 flex-1'>"
                +     "<span class='block truncate text-body-2 font-semibold text-gray-90'>" + escapeHtml(title) + "</span>"
                +     "<span class='mt-2 flex flex-wrap items-center gap-2'>" + meta + "</span>"
                +   "</span>"
                + "</label>";
        }

        if (0 === visibleCount) {
            html = "<div class='rounded-xl border border-dashed border-gray-25 bg-gray-10 px-4 py-6 text-center text-body-2 text-gray-50'>" + escapeHtml({$noDocumentsLabel}) + "</div>";
        }

        $("#document-picker-results").html(html);
    }

    function loadDocumentList() {
        $("#document-picker-status").text({$loadingDocumentsLabel});
        $("#document-picker-results").html("");

        fetch(documentPickerUrl, {
            method: "GET",
            credentials: "same-origin"
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }

            return response.json();
        })
        .then(function (data) {
            documentItems = normalizeDocumentList(data);
            documentListLoaded = true;
            $("#document-picker-status").text("");
            renderDocumentItems(documentItems, $("#document_picker_search").val());
        })
        .catch(function () {
            documentItems = [];
            documentListLoaded = false;
            $("#document-picker-status").text({$requestFailedLabel});
            $("#document-picker-results").html("<div class='rounded-xl border border-dashed border-danger bg-gray-10 px-4 py-6 text-center text-body-2 text-danger'>" + escapeHtml({$unexpectedErrorLabel}) + "</div>");
        });
    }

    $(document).on("change", "input[name='aiken_document_picker_choice']", function () {
        var index = parseInt($(this).val(), 10);

        if (isNaN(index) || !documentItems[index]) {
            return;
        }

        selectDocument(documentItems[index]);
    });

    $("#document_picker_search").on("input", function () {
        renderDocumentItems(documentItems, $(this).val());
    });

    $("#open-document-picker").on("click", function (e) {
        e.preventDefault();
        $("#aiken-document-picker").show();

        if (!documentListLoaded) {
            loadDocumentList();
            return;
        }

        renderDocumentItems(documentItems, $("#document_picker_search").val());
    });

    $("#close-document-picker").on("click", function (e) {
        e.preventDefault();
        $("#aiken-document-picker").hide();
    });

    $("#generate-aiken-topic").on("click", function (e) {
        e.preventDefault();
        clearErrors();
        resetGeneratedArea();

        var button = $(this);
        var exerciseTitle = $("#classic_exercise_title").val().trim();
        var quizName = $("#classic_topic_prompt").val().trim();
        var nroQuestions = parseInt($("#classic_nro_questions").val(), 10);
        var questionType = $("#classic_question_type").val();
        var provider = {$classicProviderJs};

        var valid = true;

        if (exerciseTitle === "") {
            showFieldError("#classic_exercise_title", {$requiredFieldLabel});
            valid = false;
        }

        if (quizName === "") {
            showFieldError("#classic_topic_prompt", {$requiredFieldLabel});
            valid = false;
        }

        if (isNaN(nroQuestions) || nroQuestions <= 0) {
            showFieldError("#classic_nro_questions", {$invalidQuestionsLabel});
            valid = false;
        }

        if (!valid) {
            return;
        }

        setCanonicalFields({
            exercise_title: exerciseTitle,
            quiz_name: quizName,
            resource_file_id: "",
            document_title: "",
            nro_questions: nroQuestions,
            question_type: questionType,
            ai_feature: "exercise_generator_aiken"
        });

        button.prop("disabled", true);
        button.text({$waitLabel});

        $.ajax({
            url: {$classicGenerateUrlJs},
            type: "POST",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                quiz_name: quizName,
                nro_questions: nroQuestions,
                question_type: questionType,
                language: {$languageJs},
                ai_provider: provider,
                cid: {$courseId},
                sid: {$sessionId}
            }),
            success: function (data) {
                button.prop("disabled", false);
                button.text({$generateAikenLabel});

                if (data.success) {
                    finalizeSuccess(provider, "exercise_generator_aiken", data.text);
                    return;
                }

                alert(data.text || {$genericErrorLabel});
            },
            error: function (jqXHR) {
                handleAjaxError(
                    jqXHR,
                    {$unexpectedErrorLabel},
                    button,
                    {$generateAikenLabel}
                );
            }
        });
    });

    if ({$documentTabEnabledJs}) {
        $("#generate-aiken-document").on("click", function (e) {
            e.preventDefault();
            clearErrors();
            resetGeneratedArea();

            var button = $(this);
            var exerciseTitle = $("#document_exercise_title").val().trim();
            var resourceFileId = parseInt($("#document_resource_file_id").val(), 10);
            var documentTitle = $("#document_title_input").val().trim();
            var topicPrompt = $("#document_topic_prompt").val().trim();
            var nroQuestions = parseInt($("#document_nro_questions").val(), 10);
            var questionType = $("#document_question_type").val();
            var provider = {$documentProviderJs};

            var valid = true;

            if (exerciseTitle === "") {
                showFieldError("#document_exercise_title", {$requiredFieldLabel});
                valid = false;
            }

            if (isNaN(resourceFileId) || resourceFileId <= 0) {
                showFieldError("#document_selected_label", {$requiredFieldLabel});
                valid = false;
            }

            if (documentTitle === "") {
                showFieldError("#document_selected_label", {$requiredFieldLabel});
                valid = false;
            }

            if (isNaN(nroQuestions) || nroQuestions <= 0) {
                showFieldError("#document_nro_questions", {$invalidQuestionsLabel});
                valid = false;
            }

            if (!valid) {
                return;
            }

            setCanonicalFields({
                exercise_title: exerciseTitle,
                quiz_name: topicPrompt,
                resource_file_id: resourceFileId,
                document_title: documentTitle,
                nro_questions: nroQuestions,
                question_type: questionType,
                ai_feature: "exercise_generator_aiken_document"
            });

            button.prop("disabled", true);
            button.text({$waitLabel});

            $.ajax({
                url: {$documentGenerateUrlJs},
                type: "POST",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({
                    prompt: topicPrompt,
                    quiz_name: topicPrompt,
                    nro_questions: nroQuestions,
                    question_type: questionType,
                    language: {$languageJs},
                    ai_provider: provider,
                    resource_file_id: resourceFileId,
                    document_title: documentTitle,
                    cid: {$courseId},
                    sid: {$sessionId},
                    gid: {$groupId}
                }),
                success: function (data) {
                    button.prop("disabled", false);
                    button.text({$generateAikenFromDocumentLabel});

                    if (data.success) {
                        finalizeSuccess(provider, "exercise_generator_aiken_document", data.text);
                        return;
                    }

                    alert(data.text || {$genericErrorLabel});
                },
                error: function (jqXHR) {
                    handleAjaxError(
                        jqXHR,
                        {$unexpectedErrorLabel},
                        button,
                        {$generateAikenFromDocumentLabel}
                    );
                }
            });
        });
    }

    activateTab("topic");
});
</script>
JS;

    $form->addHtml($script);

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
