<?php
/* For licensing terms, see /license.txt */
/**
 * Library for the import of Aiken format
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author César Perales <cesar.perales@gmail.com> Parse function for Aiken format
 * @package chamilo.exercise
 */
/**
 * Security check
 */
if (count(get_included_files()) == 1)
    die('---');

/**
 * Creates a temporary directory
 * @param $dir
 * @param string $prefix
 * @param int $mode
 * @return string
 */
function tempdir($dir, $prefix = 'tmp', $mode = 0777) {
    if (substr($dir, -1) != '/')
        $dir .= '/';

    do {
        $path = $dir . $prefix . mt_rand(0, 9999999);
    } while (!mkdir($path, $mode));

    return $path;
}

/**
 * This function displays the form for import of the zip file with qti2
 * @param   string  Report message to show in case of error
 */
function aiken_display_form($msg = '') {
    $name_tools = get_lang('ImportAikenQuiz');
    $form  = '<div class="actions">';
    $form .= '<a href="exercice.php?show=test">' . Display :: return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
    $form .= '</div>';
    $form .= $msg;
    $form_validator  = new FormValidator('aiken_upload', 'post',api_get_self()."?".api_get_cidreq(), null, array('enctype' => 'multipart/form-data') );
    $form_validator->addElement('header', $name_tools);
    $form_validator->addElement('text', 'total_weight', get_lang('TotalWeight'));
    $form_validator->addElement('file', 'userFile', get_lang('DownloadFile'));
    $form_validator->addElement('style_submit_button', 'submit', get_lang('Send'), 'class="upload"');
    $form .= $form_validator->return_form();
    $form .= '<blockquote>'.get_lang('ImportAikenQuizExplanation').'<br /><pre>'.get_lang('ImportAikenQuizExplanationExample').'</pre></blockquote>';
    echo $form;
}

/**
 * Gets the uploaded file (from $_FILES) and unzip it to the given directory
 * @param string The directory where to do the work
 * @param string The path of the temporary directory where the exercise was uploaded and unzipped
 * @return bool True on success, false on failure
 */
function get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath) {
    global $_course, $_user;
    //Check if the file is valid (not to big and exists)
    if (!isset ($_FILES['userFile']) || !is_uploaded_file($_FILES['userFile']['tmp_name'])) {
        // upload failed
        return false;
    }
    if (preg_match('/.zip$/i', $_FILES['userFile']['name']) && handle_uploaded_document($_course, $_FILES['userFile'], $baseWorkDir, $uploadPath, $_user['user_id'], 0, null, 1, 'overwrite', false)) {
        if (!function_exists('gzopen')) {
            return false;
        }
        // upload successful
        return true;
    } elseif (preg_match('/.txt/i', $_FILES['userFile']['name']) && handle_uploaded_document($_course, $_FILES['userFile'], $baseWorkDir, $uploadPath, $_user['user_id'], 0, null, 0, 'overwrite', false)) {
        return true;
    } else {
        return false;
    }
}
/**
 * Main function to import the Aiken exercise
 * @return mixed True on success, error message on failure
 */
function aiken_import_exercise($file) {
    global $exercise_info;
    global $element_pile;
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    // used to specify the question directory where files could be found in relation in any question
    global $questionTempDir;
    $archive_path = api_get_path(SYS_ARCHIVE_PATH) . 'aiken';
    $baseWorkDir = $archive_path;

    if (!is_dir($baseWorkDir)) {
        mkdir($baseWorkDir, api_get_permissions_for_new_directories(), true);
    }

    $uploadPath = '/';

    // set some default values for the new exercise
    $exercise_info = array ();
    $exercise_info['name'] = preg_replace('/.(zip|txt)$/i', '', $file);
    $exercise_info['question'] = array();
    $element_pile = array ();

    // create parser and array to retrieve info from manifest
    $element_pile = array (); //pile to known the depth in which we are
    //$module_info = array (); //array to store the info we need

   // if file is not a .zip, then we cancel all
    if (!preg_match('/.(zip|txt)$/i', $file)) {
        Display :: display_error_message(get_lang('YouMustUploadAZipOrTxtFile'));
        return 'YouMustUploadAZipOrTxtFile';
    }

    // unzip the uploaded file in a tmp directory
    if (preg_match('/.(zip|txt)$/i', $file)) {
        if (!get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)) {
            Display :: display_error_message(get_lang(''));
            return 'ThereWasAProblemWithYourFile';
        }
    }

    // find the different manifests for each question and parse them
    $exerciseHandle = opendir($baseWorkDir);
    //$question_number = 0;
    $file_found = false;
    $operation = false;
    $result = false;
    // parse every subdirectory to search txt question files
    while (false !== ($file = readdir($exerciseHandle))) {
        if (is_dir($baseWorkDir . '/' . $file) && $file != "." && $file != "..") {
            //find each manifest for each question repository found
            $questionHandle = opendir($baseWorkDir . '/' . $file);
            while (false !== ($questionFile = readdir($questionHandle))) {
                if (preg_match('/.txt$/i', $questionFile)) {
                    $result = aiken_parse_file($exercise_info, $baseWorkDir, $file, $questionFile);
                    $file_found = true;
                }
            }
        } elseif (preg_match('/.txt$/i', $file)) {
            $result = aiken_parse_file($exercise_info, $baseWorkDir, '', $file);
            $file_found = true;
        } // else ignore file
    }
    if (!$file_found) {
        //Display :: display_error_message(get_lang('NoTxtFileFoundInTheZip'));
        $result = 'NoTxtFileFoundInTheZip';
    }
    if ($result !== true ) {
        return $result;
    }
    //add exercise in tool

    //1.create exercise
    $exercise = new Exercise();
    $exercise->exercise = $exercise_info['name'];
    
    $exercise->save();
    $last_exercise_id = $exercise->selectId();
    if (!empty($last_exercise_id)) {
        //For each question found...
        foreach ($exercise_info['question'] as $key => $question_array) {
            //2.create question
            $question = new Aiken2Question();
            $question->type = $question_array['type'];
            $question->setAnswer();
            $question->updateTitle($question_array['title']); // question (short)...
            $question->updateDescription($question_array['description']); // question (long)...
            $type = $question->selectType();
            $question->type = constant($type); // type ...
            $question->save($last_exercise_id); // save computed grade
            $last_question_id = $question->selectId();
            //3.create answer
            $answer = new Answer($last_question_id);
            $answer->new_nbrAnswers = count($question_array['answer']);
            $max_score = 0;
            foreach ($question_array['answer'] as $key => $answers) {
                $key++;
                $answer->new_answer[$key] = $answers['value']; // answer ...
                //$answer->new_comment[$key] = $answers['feedback']; // comment ...
                $answer->new_position[$key] = $key; // position ...
                // correct answers ...
                if (in_array($key, $question_array['correct_answers'])) {
                    $answer->new_correct[$key] = 1;
                    $answer->new_comment[$key] = $question_array['feedback'];
                } else {
                    $answer->new_correct[$key] = 0;
                }
                $answer->new_weighting[$key] = $question_array['weighting'][$key - 1];
                $max_score += $question_array['weighting'][$key - 1];
            }
            $answer->save();
            // Now that we know the question score, set it!
            $question->updateWeighting($max_score);
            $question->save();
        }
        // delete the temp dir where the exercise was unzipped
        my_delete($baseWorkDir . $uploadPath);
        $operation = true;
    }
    return $operation;
}

/**
 * Parses an Aiken file and builds an array of exercise + questions to be
 * imported by the import_exercise() function
 * @param array The reference to the array in which to store the questions
 * @param string Path to the directory with the file to be parsed (without final /)
 * @param string Name of the last directory part for the file (without /)
 * @param string Name of the file to be parsed (including extension)
 * @return mixed True on success, error message on error
 * @assert ('','','') === false
 */
function aiken_parse_file(&$exercise_info, $exercisePath, $file, $questionFile) {
    global $questionTempDir;

    $questionTempDir = $exercisePath . '/' . $file . '/';
    $questionFilePath = $questionTempDir . $questionFile;

    if (!is_file($questionFilePath)) {
        return 'FileNotFound';
    }
    $data = file($questionFilePath);

    $question_index = 0;
    $correct_answer = '';
    $answers_array = array();
    $new_question = true;
    foreach ($data as $line => $info) {
        if ($question_index > 0 && $new_question == true && preg_match('/^(\r)?\n/',$info)) {
            // double empty line
            continue;
        }
        $new_question = false;
        //make sure it is transformed from iso-8859-1 to utf-8 if in that form
        if (!mb_check_encoding($info,'utf-8') && mb_check_encoding($info,'iso-8859-1')) {
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
        } elseif (preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $info, $matches)) {
            //Comment of correct answer
            $correct_answer_index = array_search($matches[1], $answers_array);
            $exercise_info['question'][$question_index]['feedback'] = $matches[1];
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
        } elseif (preg_match('/^(\r)?\n/',$info)) {
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
            $answers_array = array();
            $new_question = true;
        } else {
            if (empty($exercise_info['question'][$question_index]['title']))
            {
                if (strlen($info) < 40)
                {
                    $exercise_info['question'][$question_index]['title'] = $info; 
                } else
                {
                    //Question itself (use a 40-chars long title and a larger description)
                    $exercise_info['question'][$question_index]['title'] = trim(substr($info,0,40)).'...';
                    $exercise_info['question'][$question_index]['description'] = $info;
                }
            } else {
                $exercise_info['question'][$question_index]['description'] = $info;
            }
        }
    }
    $total_questions = count($exercise_info['question']);
    $total_weight = (!empty($_POST['total_weight'])) ? intval($_POST['total_weight']) : 20;
    foreach  ($exercise_info['question'] as $key => $question) {
        $exercise_info['question'][$key]['weighting'][current(array_keys($exercise_info['question'][$key]['weighting']))] = $total_weight / $total_questions;
    }
    return true;
}

/**
 * This function will import the zip file with the respective qti2
 * @param array $uploaded_file ($_FILES)
 */
function aiken_import_file($array_file) {

    $unzip = 0;
    $process = process_uploaded_file($array_file);
    if (preg_match('/\.(zip|txt)$/i', $array_file['name'])) {
        // if it's a zip, allow zip upload
        $unzip = 1;
    }

    if ($process && $unzip == 1) {
        $imported = aiken_import_exercise($array_file['name']);
        if ($imported === true) {
            header('Location: exercice.php?'.api_get_cidreq());
        } else {
            $msg = Display::return_message(get_lang($imported),'error');
            return $msg;
        }
    }
}