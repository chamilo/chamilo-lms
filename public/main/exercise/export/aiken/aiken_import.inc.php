<?php

/* For licensing terms, see /license.txt */

/**
 * Library for the import of Aiken format.
 *
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author César Perales <cesar.perales@gmail.com> Parse function for Aiken format
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

/**
 * This function displays the form for import of the zip file with qti2.
 *
 * @param   string  Report message to show in case of error
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
    $form .= '<blockquote>'.get_lang('Import Aiken quizExplanation').'<br /><pre>'.get_lang('Import Aiken quizExplanationExample').'</pre></blockquote>';
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
        if (empty($line)) {
            continue;
        }

        if (!preg_match('/^[A-Z]\.\s/', $line) && !preg_match('/^ANSWER:\s?[A-Z]/', $line) && !preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line)) {
            $questionIndex++;
            $exerciseInfo['question'][$questionIndex] = [
                'type' => 'MCUA',
                'title' => $line,
                'answer' => [],
                'correct_answers' => [],
                'weighting' => [],
                'feedback' => '',
                'description' => '',
                'answer_tags' => []
            ];
            $answersArray = [];
            $currentQuestion = &$exerciseInfo['question'][$questionIndex];
            continue;
        }

        if (preg_match('/^([A-Z])\.\s(.*)/', $line, $matches)) {
            $answerIndex = count($currentQuestion['answer']);
            $currentQuestion['answer'][] = ['value' => $matches[2]];
            $answersArray[$matches[1]] = $answerIndex + 1;
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
        $exerciseInfo['question'][$key]['weighting'][0] = $totalWeight / $totalQuestions;
    }
}

/**
 * Imports an Aiken file or AI-generated text and creates an exercise in Chamilo 2.
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

    if ($file) {
        $fileIsSet = true;

        if (!is_dir($baseWorkDir . $uploadPath)) {
            mkdir($baseWorkDir . $uploadPath, api_get_permissions_for_new_directories(), true);
        }

        $exerciseInfo['name'] = preg_replace('/\.(zip|txt)$/i', '', basename($file));
        $exerciseInfo['question'] = [];

        if (!preg_match('/\.(zip|txt)$/i', $file)) {
            return get_lang('You must upload a .zip or .txt file');
        }

        $result = aiken_parse_file($exerciseInfo, $file);

        if ($result !== true) {
            return $result;
        }
    } elseif (!empty($request)) {
        $exerciseInfo['name'] = $request['quiz_name'];
        $exerciseInfo['total_weight'] = !empty($_POST['ai_total_weight']) ? (int) ($_POST['ai_total_weight']) : (int) $request['nro_questions'];
        $exerciseInfo['question'] = [];
        $exerciseInfo['course_id'] = api_get_course_int_id();
        setExerciseInfoFromAikenText($request['aiken_format'], $exerciseInfo);
    }

    return create_exercise_from_aiken($exerciseInfo, $fileIsSet ? $baseWorkDir . $uploadPath : null);
}

/**
 * Creates an exercise from Aiken format data.
 */
function create_exercise_from_aiken(array $exerciseInfo, ?string $workDir): int|false
{
    if (empty($exerciseInfo)) {
        return false;
    }

    // 1. Create a new exercise
    $exercise = new Exercise();
    $exercise->exercise = $exerciseInfo['name'];
    $exercise->save();
    $lastExerciseId = $exercise->getId();

    if (!$lastExerciseId) {
        return false;
    }

    // Database table references
    $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
    $tableAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
    $courseId = api_get_course_int_id();

    // 2. Iterate over each question in the parsed Aiken data
    foreach ($exerciseInfo['question'] as $index => $questionData) {
        if (!isset($questionData['title'])) {
            continue;
        }


        // 3. Create a new question
        $question = new Aiken2Question();
        $question->type = $questionData['type'];
        $question->setAnswer();
        $question->updateTitle($questionData['title']);

        if (isset($questionData['description'])) {
            $question->updateDescription($questionData['description']);
        }

        // Determine question type
        $type = $question->selectType();
        $question->type = constant($type);

        // Try to save the question
        try {
            $question->save($exercise);
            $lastQuestionId = $question->id;

            if (!$lastQuestionId) {
                throw new Exception("Question ID is NULL after saving.");
            }

        } catch (Exception $e) {
            error_log("[ERROR] create_exercise_from_aiken: Error saving question '{$questionData['title']}' - " . $e->getMessage());
            continue;
        }

        // 4. Create answers for the question
        $answer = new Answer($lastQuestionId, $courseId, $exercise, false);
        $answer->new_nbrAnswers = count($questionData['answer']);
        $maxScore = 0;
        $scoreFromFile = $questionData['score'] ?? 0;

        foreach ($questionData['answer'] as $key => $answerData) {
            $answerIndex = $key + 1;
            $answer->new_answer[$answerIndex] = $answerData['value'];
            $answer->new_position[$answerIndex] = $answerIndex;
            $answer->new_comment[$answerIndex] = '';

            // Check if the answer is correct
            if (isset($questionData['correct_answers']) && in_array($answerIndex, $questionData['correct_answers'])) {
                $answer->new_correct[$answerIndex] = 1;
                if (isset($questionData['feedback'])) {
                    $answer->new_comment[$answerIndex] = $questionData['feedback'];
                }

                // Set answer weight (score)
                if (isset($questionData['weighting'])) {
                    $answer->new_weighting[$answerIndex] = $questionData['weighting'][0];
                    $maxScore += $questionData['weighting'][0];
                }

            } else {
                $answer->new_correct[$answerIndex] = 0;
            }

            if (!empty($scoreFromFile) && $answer->new_correct[$answerIndex]) {
                $answer->new_weighting[$answerIndex] = $scoreFromFile;
            }

            // Insert answer into database
            $params = [
                'c_id' => $courseId,
                'question_id' => $lastQuestionId,
                'answer' => $answer->new_answer[$answerIndex],
                'correct' => $answer->new_correct[$answerIndex],
                'comment' => $answer->new_comment[$answerIndex],
                'ponderation' => $answer->new_weighting[$answerIndex] ?? 0,
                'position' => $answer->new_position[$answerIndex],
                'hotspot_coordinates' => '',
                'hotspot_type' => '',
            ];

            $answerId = Database::insert($tableAnswer, $params);

            if (!$answerId) {
                error_log("[ERROR] create_exercise_from_aiken: Failed to insert answer for question ID: $lastQuestionId");
                continue;
            }

            Database::update($tableAnswer, ['iid' => $answerId], ['iid = ?' => [$answerId]]);
        }

        // Update question score
        if (!empty($scoreFromFile)) {
            $maxScore = $scoreFromFile;
        }

        Database::update($tableQuestion, ['ponderation' => $maxScore], ['iid = ?' => [$lastQuestionId]]);
    }

    // 5. Clean up temporary files if needed
    if ($workDir) {
        my_delete($workDir);
    }

    return $lastExerciseId;
}

/**
 * Parses an Aiken file and builds an array of exercise + questions to be
 * imported by the import_exercise() function.
 *
 * @param array $exercise_info The reference to the array in which to store the questions
 * @param string $file
 *
 * @return string|bool True on success, error message on error
 * @assert ('','','') === false
 */
function aiken_parse_file(&$exercise_info, $file)
{
    if (!is_file($file)) {
        return 'FileNotFound';
    }

    $text = file_get_contents($file);
    $detect = mb_detect_encoding($text, 'ASCII', true);
    if ('ASCII' === $detect) {
        $data = explode("\n", $text);
    } else {
        $text = str_ireplace(["\x0D", "\r\n"], "\n", $text); // Removes ^M char from win files.
        $data = explode("\n\n", $text);
    }

    $question_index = 0;
    $answers_array = [];
    foreach ($data as $line => $info) {
        $info = trim($info);
        if (empty($info)) {
            continue;
        }
        //make sure it is transformed from iso-8859-1 to utf-8 if in that form
        if (!mb_check_encoding($info, 'utf-8') && mb_check_encoding($info, 'iso-8859-1')) {
            $info = utf8_encode($info);
        }
        $exercise_info['question'][$question_index]['type'] = 'MCUA';
        if (preg_match('/^([A-Za-z])(\)|\.)\s(.*)/', $info, $matches)) {
            //adding one of the possible answers
            $exercise_info['question'][$question_index]['answer'][]['value'] = $matches[3];
            $answers_array[] = $matches[1];
        } elseif (preg_match('/^ANSWER:\s?([A-Z])\s?/', $info, $matches)) {
            //the correct answers
            $correct_answer_index = array_search($matches[1], $answers_array);
            $exercise_info['question'][$question_index]['correct_answers'][] = $correct_answer_index + 1;
            //weight for correct answer
            $exercise_info['question'][$question_index]['weighting'][$correct_answer_index] = 1;
            $next = $line + 1;

            if (false !== strpos($data[$next], 'ANSWER_EXPLANATION:')) {
                continue;
            }

            if (false !== strpos($data[$next], 'DESCRIPTION:')) {
                continue;
            }
            // Check if next has score, otherwise loop too next question.
            if (false === strpos($data[$next], 'SCORE:')) {
                $answers_array = [];
                $question_index++;
                continue;
            }
        } elseif (preg_match('/^SCORE:\s?(.*)/', $info, $matches)) {
            $exercise_info['question'][$question_index]['score'] = (float) $matches[1];
            $answers_array = [];
            $question_index++;
            continue;
        } elseif (preg_match('/^DESCRIPTION:\s?(.*)/', $info, $matches)) {
            $exercise_info['question'][$question_index]['description'] = $matches[1];
            $next = $line + 1;

            if (false !== strpos($data[$next], 'ANSWER_EXPLANATION:')) {
                continue;
            }
            // Check if next has score, otherwise loop too next question.
            if (false === strpos($data[$next], 'SCORE:')) {
                $answers_array = [];
                $question_index++;
                continue;
            }
        } elseif (preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $info, $matches)) {
            //Comment of correct answer
            $correct_answer_index = array_search($matches[1], $answers_array);
            $exercise_info['question'][$question_index]['feedback'] = $matches[1];
            $next = $line + 1;
            // Check if next has score, otherwise loop too next question.
            if (false === strpos($data[$next], 'SCORE:')) {
                $answers_array = [];
                $question_index++;
                continue;
            }
        } elseif (preg_match('/^TEXTO_CORRECTA:\s?(.*)/', $info, $matches)) {
            //Comment of correct answer (Spanish e-ducativa format)
            $correct_answer_index = array_search($matches[1], $answers_array);
            $exercise_info['question'][$question_index]['feedback'] = $matches[1];
        } elseif (preg_match('/^T:\s?(.*)/', $info, $matches)) {
            //Question Title
            $correct_answer_index = array_search($matches[1], $answers_array);
            $exercise_info['question'][$question_index]['title'] = $matches[1];
        } elseif (preg_match('/^TAGS:\s?([A-Z])\s?/', $info, $matches)) {
            //TAGS for chamilo >= 1.10
            $exercise_info['question'][$question_index]['answer_tags'] = explode(',', $matches[1]);
        } elseif (preg_match('/^ETIQUETAS:\s?([A-Z])\s?/', $info, $matches)) {
            //TAGS for chamilo >= 1.10 (Spanish e-ducativa format)
            $exercise_info['question'][$question_index]['answer_tags'] = explode(',', $matches[1]);
        } elseif (empty($info)) {
            /*if (empty($exercise_info['question'][$question_index]['title'])) {
                $exercise_info['question'][$question_index]['title'] = $info;
            }
            //moving to next question (tolerate \r\n or just \n)
            if (empty($exercise_info['question'][$question_index]['correct_answers'])) {
                error_log('Aiken: Error in question index '.$question_index.': no correct answer defined');

                return 'ExerciseAikenErrorNoCorrectAnswerDefined';
            }
            if (empty($exercise_info['question'][$question_index]['answer'])) {
                error_log('Aiken: Error in question index '.$question_index.': no answer option given');

                return 'ExerciseAikenErrorNoAnswerOptionGiven';
            }
            $question_index++;
            //emptying answers array when moving to next question
            $answers_array = [];
        } else {
            if (empty($exercise_info['question'][$question_index]['title'])) {
                $exercise_info['question'][$question_index]['title'] = $info;
            }
            /*$question_index++;
            //emptying answers array when moving to next question
            $answers_array = [];
            $new_question = true;*/
        }
    }
    $total_questions = count($exercise_info['question']);
    $total_weight = !empty($_POST['total_weight']) ? (int) ($_POST['total_weight']) : 20;
    foreach ($exercise_info['question'] as $key => $question) {
        if (!isset($exercise_info['question'][$key]['weighting'])) {
            continue;
        }
        $exercise_info['question'][$key]['weighting'][current(array_keys($exercise_info['question'][$key]['weighting']))] = $total_weight / $total_questions;
    }

    return true;
}

/**
 * Imports the zip file.
 *
 * @param array $array_file ($_FILES)
 */
function aiken_import_file(array $array_file)
{
    $unzip = 0;
    $process = process_uploaded_file($array_file, false);
    if (preg_match('/\.(zip|txt)$/i', $array_file['name'])) {
        // if it's a zip, allow zip upload
        $unzip = 1;
    }

    if ($process && 1 == $unzip) {
        $imported = aiken_import_exercise($array_file['name']);
        if (is_numeric($imported) && !empty($imported)) {
            Display::addFlash(Display::return_message(get_lang('Uploaded.')));

            return $imported;
        } else {
            Display::addFlash(Display::return_message(get_lang($imported), 'error'));

            return false;
        }
    }

    return false;
}

/**
 * Generates the Aiken question form with AI integration.
 */
function generateAikenForm()
{
    if ('true' !== api_get_setting('ai_helpers.enable_ai_helpers')) {
        return false;
    }

    // Get AI providers configuration from settings
    $aiProvidersJson = api_get_setting('ai_helpers.ai_providers');

    $configuredApi = api_get_setting('ai_helpers.default_ai_provider');

    $availableApis = json_decode($aiProvidersJson, true) ?? [];
    $hasSingleApi = count($availableApis) === 1 || isset($availableApis[$configuredApi]);

    $form = new FormValidator(
        'aiken_generate',
        'post',
        api_get_self()."?".api_get_cidreq(),
        null
    );
    $form->addElement('header', get_lang('AI Questions Generator'));

    if ($hasSingleApi) {
        $apiName = $availableApis[$configuredApi]['model'] ?? $configuredApi;
        $form->addHtml('<div style="margin-bottom: 10px; font-size: 14px; color: #555;">'
            .sprintf(get_lang('Using AI provider %s'), '<strong>'.htmlspecialchars($apiName).'</strong>').'</div>');
    }

    $form->addHtml('<div class="alert alert-info">
        <strong>'.get_lang('Aiken Format Example').'</strong><br>
        What is the capital of France?<br>
        A. Berlin<br>
        B. Madrid<br>
        C. Paris<br>
        D. Rome<br>
        ANSWER: C
    </div>');

    $form->addElement('text', 'quiz_name', get_lang('Questions topic'));
    $form->addRule('quiz_name', get_lang('This field is required'), 'required');
    $form->addElement('number', 'nro_questions', get_lang('Number of questions'));
    $form->addRule('nro_questions', get_lang('This field is required'), 'required');

    $options = [
        'multiple_choice' => get_lang('Multiple answer'),
    ];

    $form->addSelect(
        'question_type',
        get_lang('Question yype'),
        $options
    );

    if (!$hasSingleApi) {
        $form->addSelect(
            'ai_provider',
            get_lang('Ai provider'),
            array_combine(array_keys($availableApis), array_keys($availableApis))
        );
    }

    $generateUrl = api_get_path(WEB_PATH).'ai/generate_aiken';

    $courseInfo = api_get_course_info();
    $language = $courseInfo['language'];
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
        . (!$hasSingleApi ? 'var provider = $("[name=\'ai_provider\']").val();' : 'var provider = "'.$configuredApi.'";') .
        'var isValid = true;

            // Remove previous error messages
            $(".error-message").remove();

            // Validate quiz name
            if (quizName === "") {
                $("[name=\'quiz_name\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('This field is required').'</div>");
                isValid = false;
            }

            // Validate number of questions
            if (isNaN(nroQ) || nroQ <= 0) {
                $("[name=\'nro_questions\']").after("<div class=\'error-message\' style=\'color: red;\'>'.get_lang('Please enter a valid number of questions').'</div>");
                isValid = false;
            }

            if (!isValid) {
                return; // Stop execution if validation fails
            }

            btnGenerate.attr("disabled", true);
            btnGenerate.text("'.get_lang('Please wait this could take a while').'");

            $("#textarea-aiken").text("");
            $("#aiken-area").hide();

            var requestData = JSON.stringify({
                "quiz_name": quizName,
                "nro_questions": nroQ,
                "question_type": qType,
                "language": "'.$language.'",
                "ai_provider": provider
            });

            $.ajax({
                url: "'.$generateUrl.'",
                type: "POST",
                contentType: "application/json",
                data: requestData,
                dataType: "json",
                success: function (data) {
                    btnGenerate.attr("disabled", false);
                    btnGenerate.text("'.get_lang('Generate').'");

                    if (data.success) {
                        $("#aiken-area").show();
                        $("#textarea-aiken").text(data.text);
                        $("#textarea-aiken").focus();
                    } else {
                        alert("'.get_lang('Error occurred').': " + data.text);
                    }
                },
                 error: function (jqXHR) {
                    btnGenerate.attr("disabled", false);
                    btnGenerate.text("'.get_lang('Generate').'");

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
