<?php

/* For licensing terms, see /license.txt */

/**
 * Library for the import of Aiken format.
 *
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author César Perales <cesar.perales@gmail.com> Parse function for Aiken format
 */

/**
 * This function displays the form for import of the zip file with qti2.
 *
 * @param   string  Report message to show in case of error
 */
function aiken_display_form()
{
    $name_tools = get_lang('ImportAikenQuiz');
    $form = '<div class="actions">';
    $form .= '<a href="exercise.php?show=test&'.api_get_cidreq().'">'.
        Display::return_icon(
            'back.png',
            get_lang('BackToExercisesList'),
            '',
            ICON_SIZE_MEDIUM
        ).'</a>';
    $form .= '</div>';
    $form_validator = new FormValidator(
        'aiken_upload',
        'post',
        api_get_self()."?".api_get_cidreq(),
        null,
        ['enctype' => 'multipart/form-data']
    );
    $form_validator->addElement('header', $name_tools);
    $form_validator->addElement('text', 'total_weight', get_lang('TotalWeight'));
    $form_validator->addElement('file', 'userFile', get_lang('File'));
    $form_validator->addButtonUpload(get_lang('Upload'), 'submit');
    $form .= $form_validator->returnForm();
    $form .= '<blockquote>'.get_lang('ImportAikenQuizExplanation').'<br /><pre>'.get_lang('ImportAikenQuizExplanationExample').'</pre></blockquote>';
    echo $form;
}

/**
 * Generates aiken format using AI api.
 * Requires plugin ai_helper to connect to the api.
 */
function generateAikenForm()
{
    if (!('true' === api_get_plugin_setting('ai_helper', 'tool_enable') && 'true' === api_get_plugin_setting('ai_helper', 'tool_quiz_enable'))) {
        return false;
    }

    $form = new FormValidator(
        'aiken_generate',
        'post',
        api_get_self()."?".api_get_cidreq(),
        null
    );
    $form->addElement('header', get_lang('AIQuestionsGenerator'));
    $form->addElement('text', 'quiz_name', [get_lang('QuestionsTopic'), get_lang('QuestionsTopicHelp')]);
    $form->addRule('quiz_name', get_lang('ThisFieldIsRequired'), 'required');
    $form->addElement('number', 'nro_questions', [get_lang('NumberOfQuestions'), get_lang('AIQuestionsGeneratorNumberHelper')]);
    $form->addRule('nro_questions', get_lang('ThisFieldIsRequired'), 'required');

    $options = [
        'multiple_choice' => get_lang('MultipleAnswer'),
    ];
    $form->addElement(
        'select',
        'question_type',
        get_lang('QuestionType'),
        $options
    );

    $generateUrl = api_get_path(WEB_PLUGIN_PATH).'ai_helper/tool/answers.php';
    $language = api_get_interface_language();
    $form->addHtml('<script>
                $(function () {
                    $("#aiken-area").hide();
                    $("#generate-aiken").on("click", function (e) {
                      e.preventDefault();
                      e.stopPropagation();

                      var btnGenerate = $(this);
                      var quizName = $("[name=\'quiz_name\']").val();
                      var nroQ = parseInt($("[name=\'nro_questions\']").val());
                      var qType = $("[name=\'question_type\']").val();
                      var valid = (quizName != \'\' && nroQ > 0);
                      var qWeight = 1;

                      if (valid) {
                        btnGenerate.attr("disabled", true);
                        btnGenerate.text("'.get_lang('PleaseWaitThisCouldTakeAWhile').'");
                        $("#textarea-aiken").text("");
                        $("#aiken-area").hide();
                        $.getJSON("'.$generateUrl.'", {
                            "quiz_name": quizName,
                            "nro_questions": nroQ,
                            "question_type": qType,
                            "language": "'.$language.'"
                        }).done(function (data) {
                          btnGenerate.attr("disabled", false);
                          btnGenerate.text("'.get_lang('Generate').'");
                          if (data.success && data.success == true) {
                            $("#aiken-area").show();
                            $("#textarea-aiken").text(data.text);
                            $("input[name=\'ai_total_weight\']").val(nroQ * qWeight);
                            $("#textarea-aiken").focus();
                          } else {
                            var errorMessage = "'.get_lang('NoSearchResults').'. '.get_lang('PleaseTryAgain').'";
                            if (data.text) {
                                errorMessage = data.text;
                            }
                            alert(errorMessage);
                          }
                        });
                      }
                    });
                });
            </script>');

    $form->addButton(
        'generate_aiken_button',
        get_lang('Generate'),
        '',
        'default',
        'default',
        null,
        ['id' => 'generate-aiken']
    );

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
    $form->addElement('number', 'ai_total_weight', get_lang('TotalWeight'));
    $form->addButtonImport(get_lang('Import'), 'submit_aiken_generated');
    $form->addHtml('</div>');

    echo $form->returnForm();
}

/**
 * Gets the uploaded file (from $_FILES) and unzip it to the given directory.
 *
 * @param string The directory where to do the work
 * @param string The path of the temporary directory where the exercise was uploaded and unzipped
 * @param string $baseWorkDir
 * @param string $uploadPath
 *
 * @return bool True on success, false on failure
 */
function get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)
{
    $_course = api_get_course_info();
    $_user = api_get_user_info();

    // Check if the file is valid (not to big and exists)
    if (!isset($_FILES['userFile']) || !is_uploaded_file($_FILES['userFile']['tmp_name'])) {
        // upload failed
        return false;
    }

    if (preg_match('/.zip$/i', $_FILES['userFile']['name']) &&
        handle_uploaded_document(
            $_course,
            $_FILES['userFile'],
            $baseWorkDir,
            $uploadPath,
            $_user['user_id'],
            0,
            null,
            1,
            'overwrite',
            false,
            true
        )
    ) {
        if (!function_exists('gzopen')) {
            return false;
        }
        // upload successful
        return true;
    } elseif (preg_match('/.txt/i', $_FILES['userFile']['name']) &&
        handle_uploaded_document(
            $_course,
            $_FILES['userFile'],
            $baseWorkDir,
            $uploadPath,
            $_user['user_id'],
            0,
            null,
            0,
            'overwrite',
            false
        )
    ) {
        return true;
    }

    return false;
}

/**
 * Main function to import the Aiken exercise.
 *
 * @param string $file
 * @param array  $request
 *
 * @return mixed True on success, error message on failure
 */
function aikenImportExercise($file = null, $request = [])
{
    $exerciseInfo = [];
    $fileIsSet = false;

    if (isset($file)) {
        $fileIsSet = true;
        // The import is from aiken file format.
        $archivePath = api_get_path(SYS_ARCHIVE_PATH).'aiken/';
        $baseWorkDir = $archivePath;

        $uploadPath = 'aiken_'.api_get_unique_id();
        if (!is_dir($baseWorkDir.$uploadPath)) {
            mkdir($baseWorkDir.$uploadPath, api_get_permissions_for_new_directories(), true);
        }

        // set some default values for the new exercise
        $exerciseInfo['name'] = preg_replace('/.(zip|txt)$/i', '', $file);
        $exerciseInfo['total_weight'] = !empty($_POST['total_weight']) ? (int) ($_POST['total_weight']) : 20;
        $exerciseInfo['question'] = [];

        // if file is not a .zip, then we cancel all
        if (!preg_match('/.(zip|txt)$/i', $file)) {
            return 'YouMustUploadAZipOrTxtFile';
        }

        // unzip the uploaded file in a tmp directory
        if (preg_match('/.(zip|txt)$/i', $file)) {
            if (!get_and_unzip_uploaded_exercise($baseWorkDir.$uploadPath, '/')) {
                return 'ThereWasAProblemWithYourFile';
            }
        }

        // find the different manifests for each question and parse them
        $exerciseHandle = opendir($baseWorkDir.$uploadPath);
        $fileFound = false;
        $operation = false;
        $result = false;

        // Parse every subdirectory to search txt question files
        while (false !== ($file = readdir($exerciseHandle))) {
            if (is_dir($baseWorkDir.'/'.$uploadPath.$file) && $file != "." && $file != "..") {
                //find each manifest for each question repository found
                $questionHandle = opendir($baseWorkDir.'/'.$uploadPath.$file);
                while (false !== ($questionFile = readdir($questionHandle))) {
                    if (preg_match('/.txt$/i', $questionFile)) {
                        $result = aiken_parse_file(
                            $exerciseInfo,
                            $baseWorkDir,
                            $file,
                            $questionFile
                        );
                        $fileFound = true;
                    }
                }
            } elseif (preg_match('/.txt$/i', $file)) {
                $result = aiken_parse_file($exerciseInfo, $baseWorkDir.$uploadPath, '', $file);
                $fileFound = true;
            }
        }

        if (!$fileFound) {
            $result = 'NoTxtFileFoundInTheZip';
        }

        if (true !== $result) {
            return $result;
        }
    } elseif (!empty($request)) {
        // The import is from aiken generated in textarea.
        $exerciseInfo['name'] = $request['quiz_name'];
        $exerciseInfo['total_weight'] = !empty($_POST['ai_total_weight']) ? (int) ($_POST['ai_total_weight']) : (int) $request['nro_questions'];
        $exerciseInfo['question'] = [];
        $exerciseInfo['course_id'] = isset($request['course_id']) ? (int) $request['course_id'] : 0;
        setExerciseInfoFromAikenText($request['aiken_format'], $exerciseInfo);
    }

    // 1. Create exercise.
    if (!empty($exerciseInfo)) {
        $exercise = new Exercise($exerciseInfo['course_id']);
        $exercise->exercise = $exerciseInfo['name'];
        $exercise->disable(); // Invisible by default
        $exercise->updateResultsDisabled(0); // Auto-evaluation mode: show score and expected answers
        $exercise->save();
        $lastExerciseId = $exercise->selectId();
        $tableQuestion = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $tableAnswer = Database::get_course_table(TABLE_QUIZ_ANSWER);
        if (!empty($lastExerciseId)) {
            $courseId = !empty($exerciseInfo['course_id']) ? (int) $exerciseInfo['course_id'] : api_get_course_int_id();
            foreach ($exerciseInfo['question'] as $key => $questionArray) {
                if (!isset($questionArray['title'])) {
                    continue;
                }
                // 2. Create question.
                $question = new Aiken2Question();
                $question->type = $questionArray['type'];
                $question->setAnswer();
                $question->updateTitle($questionArray['title']);

                if (isset($questionArray['description'])) {
                    $question->updateDescription($questionArray['description']);
                }
                $type = $question->selectType();
                $question->course = api_get_course_info_by_id($courseId);
                $question->type = constant($type);
                $question->save($exercise);
                $last_question_id = $question->selectId();

                // 3. Create answer
                $answer = new Answer($last_question_id, $courseId, $exercise, false);
                $answer->new_nbrAnswers = isset($questionArray['answer']) ? count($questionArray['answer']) : 0;
                $max_score = 0;

                $scoreFromFile = 0;
                if (isset($questionArray['score']) && !empty($questionArray['score'])) {
                    $scoreFromFile = $questionArray['score'];
                }

                foreach ($questionArray['answer'] as $key => $answers) {
                    $key++;
                    $answer->new_answer[$key] = $answers['value'];
                    $answer->new_position[$key] = $key;
                    $answer->new_comment[$key] = '';
                    // Correct answers ...
                    if (isset($questionArray['correct_answers']) &&
                        in_array($key, $questionArray['correct_answers'])
                    ) {
                        $answer->new_correct[$key] = 1;
                        if (isset($questionArray['feedback'])) {
                            $answer->new_comment[$key] = $questionArray['feedback'];
                        }
                    } else {
                        $answer->new_correct[$key] = 0;
                    }

                    if (isset($questionArray['weighting'][$key - 1])) {
                        $answer->new_weighting[$key] = $questionArray['weighting'][$key - 1];
                        $max_score += $questionArray['weighting'][$key - 1];
                    }

                    if (!empty($scoreFromFile) && $answer->new_correct[$key]) {
                        $answer->new_weighting[$key] = $scoreFromFile;
                    }

                    $params = [
                        'c_id' => $courseId,
                        'question_id' => $last_question_id,
                        'answer' => $answer->new_answer[$key],
                        'correct' => $answer->new_correct[$key],
                        'comment' => $answer->new_comment[$key],
                        'ponderation' => isset($answer->new_weighting[$key]) ? $answer->new_weighting[$key] : '',
                        'position' => $answer->new_position[$key],
                        'hotspot_coordinates' => '',
                        'hotspot_type' => '',
                    ];

                    $answerId = Database::insert($tableAnswer, $params);
                    if ($answerId) {
                        $params = [
                            'id_auto' => $answerId,
                            'iid' => $answerId,
                        ];
                        Database::update($tableAnswer, $params, ['iid = ?' => [$answerId]]);
                    }
                }

                if (!empty($scoreFromFile)) {
                    $max_score = $scoreFromFile;
                }
                $params = ['ponderation' => $max_score];
                Database::update(
                    $tableQuestion,
                    $params,
                    ['iid = ?' => [$last_question_id]]
                );
            }

            // Delete the temp dir where the exercise was unzipped
            if ($fileIsSet) {
                my_delete($baseWorkDir.$uploadPath);
            }

            // Invisible by default
            api_item_property_update(
                api_get_course_info(),
                TOOL_QUIZ,
                $lastExerciseId,
                'invisible',
                api_get_user_id()
            );

            return $lastExerciseId;
        }
    }

    return false;
}

/**
 * Set the exercise information from an aiken text formatted.
 */
function setExerciseInfoFromAikenText($aikenText, &$exerciseInfo)
{
    $detect = mb_detect_encoding($aikenText, 'ASCII', true);
    if ('ASCII' === $detect) {
        $data = explode("\n", $aikenText);
    } else {
        if (false !== stripos($aikenText, "\x0D") || false !== stripos($aikenText, "\r\n")) {
            $text = str_ireplace(["\x0D", "\r\n"], "\n", $aikenText); // Removes ^M char from win files.
            $data = explode("\n\n", $text);
        } else {
            $data = explode("\n", $aikenText);
        }
    }

    $questionIndex = 0;
    $answersArray = [];
    foreach ($data as $line => $info) {
        $info = trim($info);
        if (empty($info)) {
            continue;
        }

        //make sure it is transformed from iso-8859-1 to utf-8 if in that form
        if (!mb_check_encoding($info, 'utf-8') && mb_check_encoding($info, 'iso-8859-1')) {
            $info = utf8_encode($info);
        }
        $exerciseInfo['question'][$questionIndex]['type'] = 'MCUA';

        if (preg_match('/^([A-Za-z])(\)|\.)\s(.*)/', $info, $matches)) {
            //adding one of the possible answers
            $exerciseInfo['question'][$questionIndex]['answer'][]['value'] = $matches[3];
            $answersArray[] = $matches[1];
        } elseif (preg_match('/^ANSWER:\s?([A-Z])\s?/', $info, $matches)) {
            //the correct answers
            $correctAnswerIndex = array_search($matches[1], $answersArray);
            $exerciseInfo['question'][$questionIndex]['correct_answers'][] = $correctAnswerIndex + 1;
            //weight for correct answer
            $exerciseInfo['question'][$questionIndex]['weighting'][$correctAnswerIndex] = 1;
            $next = $line + 1;

            if (isset($data[$next]) && false !== strpos($data[$next], 'ANSWER_EXPLANATION:')) {
                continue;
            }

            if (isset($data[$next]) && false !== strpos($data[$next], 'DESCRIPTION:')) {
                continue;
            }

            // Check if next has score, otherwise loop too next question.
            if (isset($data[$next]) && false === strpos($data[$next], 'SCORE:')) {
                $answersArray = [];
                $questionIndex++;
                continue;
            }
        } elseif (preg_match('/^SCORE:\s?(.*)/', $info, $matches)) {
            $exerciseInfo['question'][$questionIndex]['score'] = (float) $matches[1];
            $answersArray = [];
            $questionIndex++;
            continue;
        } elseif (preg_match('/^DESCRIPTION:\s?(.*)/', $info, $matches)) {
            $exerciseInfo['question'][$questionIndex]['description'] = $matches[1];
            $next = $line + 1;

            if (isset($data[$next]) && false !== strpos($data[$next], 'ANSWER_EXPLANATION:')) {
                continue;
            }
            // Check if next has score, otherwise loop too next question.
            if (isset($data[$next]) && false === strpos($data[$next], 'SCORE:')) {
                $answersArray = [];
                $questionIndex++;
                continue;
            }
        } elseif (preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $info, $matches)) {
            // Comment of correct answer
            $correctAnswerIndex = array_search($matches[1], $answersArray);
            $exerciseInfo['question'][$questionIndex]['feedback'] = $matches[1];
            $next = $line + 1;
            // Check if next has score, otherwise loop too next question.
            if (isset($data[$next]) && false === strpos($data[$next], 'SCORE:')) {
                $answersArray = [];
                $questionIndex++;
                continue;
            }
        } elseif (preg_match('/^TEXTO_CORRECTA:\s?(.*)/', $info, $matches)) {
            //Comment of correct answer (Spanish e-ducativa format)
            $correctAnswerIndex = array_search($matches[1], $answersArray);
            $exerciseInfo['question'][$questionIndex]['feedback'] = $matches[1];
        } elseif (preg_match('/^T:\s?(.*)/', $info, $matches)) {
            //Question Title
            $correctAnswerIndex = array_search($matches[1], $answersArray);
            $exerciseInfo['question'][$questionIndex]['title'] = $matches[1];
        } elseif (preg_match('/^TAGS:\s?([A-Z])\s?/', $info, $matches)) {
            //TAGS for chamilo >= 1.10
            $exerciseInfo['question'][$questionIndex]['answer_tags'] = explode(',', $matches[1]);
        } elseif (preg_match('/^ETIQUETAS:\s?([A-Z])\s?/', $info, $matches)) {
            //TAGS for chamilo >= 1.10 (Spanish e-ducativa format)
            $exerciseInfo['question'][$questionIndex]['answer_tags'] = explode(',', $matches[1]);
        } else {
            if (empty($exerciseInfo['question'][$questionIndex]['title'])) {
                $exerciseInfo['question'][$questionIndex]['title'] = $info;
            }
        }
    }

    $totalQuestions = count($exerciseInfo['question']);
    $totalWeight = (int) $exerciseInfo['total_weight'];
    foreach ($exerciseInfo['question'] as $key => $question) {
        if (!isset($exerciseInfo['question'][$key]['weighting'])) {
            continue;
        }
        $exerciseInfo['question'][$key]['weighting'][current(array_keys($exerciseInfo['question'][$key]['weighting']))] = $totalWeight / $totalQuestions;
    }
}

/**
 * Parses an Aiken file and builds an array of exercise + questions to be
 * imported by the import_exercise() function.
 *
 * @param array The reference to the array in which to store the questions
 * @param string Path to the directory with the file to be parsed (without final /)
 * @param string Name of the last directory part for the file (without /)
 * @param string Name of the file to be parsed (including extension)
 * @param string $exercisePath
 * @param string $file
 * @param string $questionFile
 *
 * @return string|bool True on success, error message on error
 * @assert ('','','') === false
 */
function aiken_parse_file(&$exercise_info, $exercisePath, $file, $questionFile)
{
    $questionTempDir = $exercisePath.'/'.$file.'/';
    $questionFilePath = $questionTempDir.$questionFile;

    if (!is_file($questionFilePath)) {
        return 'FileNotFound';
    }

    $text = file_get_contents($questionFilePath);
    setExerciseInfoFromAikenText($text, $exercise_info);

    return true;
}

/**
 * Imports the zip file.
 *
 * @param array $array_file ($_FILES)
 *
 * @return bool
 */
function aiken_import_file($array_file)
{
    $unzip = 0;
    $process = process_uploaded_file($array_file, false);
    if (preg_match('/\.(zip|txt)$/i', $array_file['name'])) {
        // if it's a zip, allow zip upload
        $unzip = 1;
    }

    if ($process && $unzip == 1) {
        $imported = aikenImportExercise($array_file['name']);
        if (is_numeric($imported) && !empty($imported)) {
            Display::addFlash(Display::return_message(get_lang('Uploaded')));

            return $imported;
        } else {
            Display::addFlash(Display::return_message(get_lang($imported), 'error'));

            return false;
        }
    }
}
