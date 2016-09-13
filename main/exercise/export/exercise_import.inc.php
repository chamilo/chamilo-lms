<?php
/* For licensing terms, see /license.txt */
/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 * @package chamilo.exercise
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Unzip the exercise in the temp folder
 * @param string The path of the temporary directory where the exercise was uploaded and unzipped
 * @param string
 * @param string $baseWorkDir
 * @param string $uploadPath
 * @return bool
 */
function get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)
{
    $_course = api_get_course_info();
    $_user = api_get_user_info();

    //Check if the file is valid (not to big and exists)
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
            null,
            null,
            null,
            null,
            null,
            false
        )
    ) {
        return true;
    }
    return false;
}

/**
 * Imports an exercise in QTI format if the XML structure can be found in it
 * @param array $file
 * @return string|array as a backlog of what was really imported, and error or debug messages to display
 */
function import_exercise($file)
{
    global $exercise_info;
    global $element_pile;
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    // used to specify the question directory where files could be found in relation in any question
    global $questionTempDir;
    global $resourcesLinks;

    $baseWorkDir = api_get_path(SYS_ARCHIVE_PATH) . 'qti2';

    if (!is_dir($baseWorkDir)) {
        mkdir($baseWorkDir, api_get_permissions_for_new_directories(), true);
    }

    $uploadPath = '/';

    // set some default values for the new exercise
    $exercise_info = array();
    $exercise_info['name'] = preg_replace('/.zip$/i', '', $file);
    $exercise_info['question'] = array();
    $element_pile = array();

    // create parser and array to retrieve info from manifest
    $element_pile = array(); //pile to known the depth in which we are
    //$module_info = array (); //array to store the info we need

    // if file is not a .zip, then we cancel all

    if (!preg_match('/.zip$/i', $file)) {

        return 'UplZipCorrupt';
    }

    // unzip the uploaded file in a tmp directory
    if (!get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)) {

        return 'UplZipCorrupt';
    }

    // find the different manifests for each question and parse them.
    $exerciseHandle = opendir($baseWorkDir);
    //$question_number = 0;
    $file_found = false;
    $operation = false;
    $result = false;
    $filePath = null;
    $resourcesLinks = array();

    // parse every subdirectory to search xml question files and other assets to be imported
    // The assets-related code is a bit fragile as it has to deal with files renamed by Chamilo and it only works if
    // the imsmanifest.xml file is read.
    while (false !== ($file = readdir($exerciseHandle))) {
        if (is_dir($baseWorkDir . '/' . $file) && $file != "." && $file != "..") {
            // Find each manifest for each question repository found
            $questionHandle = opendir($baseWorkDir . '/' . $file);
            // Only analyse one level of subdirectory - no recursivity here
            while (false !== ($questionFile = readdir($questionHandle))) {
                if (preg_match('/.xml$/i', $questionFile)) {
                    $isQti = isQtiQuestionBank($baseWorkDir . '/' . $file . '/' . $questionFile);
                    if ($isQti) {
                        $result = qti_parse_file($baseWorkDir, $file, $questionFile);
                        $filePath = $baseWorkDir . $file;
                        $file_found = true;
                    } else {
                        $isManifest = isQtiManifest($baseWorkDir . '/' . $file . '/' . $questionFile);
                        if ($isManifest) {
                            $resourcesLinks = qtiProcessManifest($baseWorkDir . '/' . $file . '/' . $questionFile);
                        }
                    }
                }
            }
        } elseif (preg_match('/.xml$/i', $file)) {
            $isQti = isQtiQuestionBank($baseWorkDir . '/' . $file);
            if ($isQti) {
                $result = qti_parse_file($baseWorkDir, '', $file);
                $filePath = $baseWorkDir . '/' . $file;
                $file_found = true;
            } else {
                $isManifest = isQtiManifest($baseWorkDir . '/' . $file);
                if ($isManifest) {
                    $resourcesLinks = qtiProcessManifest($baseWorkDir . '/' . $file);
                }
            }

        }
    }

    if (!$file_found) {

        return 'NoXMLFileFoundInTheZip';
    }

    if ($result == false) {

        return false;
    }

    $doc = new DOMDocument();
    $doc->load($filePath);

    // 1. Create exercise.
    $exercise = new Exercise();
    $exercise->exercise = $exercise_info['name'];
    if (!empty($exercise_info['description'])) {
        $exercise->updateDescription($exercise_info['description']);
    }

    $exercise->save();
    $last_exercise_id = $exercise->selectId();
    if (!empty($last_exercise_id)) {
        // For each question found...
        foreach ($exercise_info['question'] as $question_array) {
            //2. Create question
            $question = new Ims2Question();
            $question->type = $question_array['type'];
            if (empty($question->type)) {
                // If the type was not provided, assume this is a multiple choice, unique answer type (the most basic)
                $question->type = MCUA;
            }
            $question->setAnswer();
            $description = '';
            if (strlen($question_array['title']) < 50) {
                $question->updateTitle(formatText(strip_tags($question_array['title'])) . '...');
            } else {
                $question->updateTitle(formatText(substr(strip_tags($question_array['title']), 0, 50)));
                $description .= $question_array['title'];
            }
            if (!empty($question_array['description'])) {
                $description .= $question_array['description'];
            }
            $question->updateDescription($description);

            $question->save($last_exercise_id);
            $last_question_id = $question->selectId();
            //3. Create answer
            $answer = new Answer($last_question_id);
            $answer->new_nbrAnswers = count($question_array['answer']);
            $totalCorrectWeight = 0;
            $j = 1;
            $matchAnswerIds = array();
            foreach ($question_array['answer'] as $key => $answers) {
                if (preg_match('/_/', $key)) {
                    $split = explode('_', $key);
                    $i = $split[1];
                } else {
                    $i = $j;
                    $j++;
                    $matchAnswerIds[$key] = $j;
                }
                // Answer
                $answer->new_answer[$i] = formatText($answers['value']);
                // Comment
                $answer->new_comment[$i] = isset($answers['feedback']) ? formatText($answers['feedback']) : null;
                // Position
                $answer->new_position[$i] = $i;
                // Correct answers
                if (in_array($key, $question_array['correct_answers'])) {
                    $answer->new_correct[$i] = 1;
                } else {
                    $answer->new_correct[$i] = 0;
                }
                $answer->new_weighting[$i] = $question_array['weighting'][$key];
                if ($answer->new_correct[$i]) {
                    $totalCorrectWeight += $answer->new_weighting[$i];
                }
            }
            $question->updateWeighting($totalCorrectWeight);
            $question->save($last_exercise_id);
            $answer->save();
        }

        // delete the temp dir where the exercise was unzipped
        my_delete($baseWorkDir . $uploadPath);
        return $last_exercise_id;
    }

    return false;
}

/**
 * We assume the file charset is UTF8
 **/
function formatText($text)
{
    return api_html_entity_decode($text);
}

/**
 * Parses a given XML file and fills global arrays with the elements
 * @param string $exercisePath
 * @param string $file
 * @param string $questionFile
 * @return bool
 */
function qti_parse_file($exercisePath, $file, $questionFile)
{
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    global $questionTempDir;

    $questionTempDir = $exercisePath . '/' . $file . '/';
    $questionFilePath = $questionTempDir . $questionFile;

    if (!($fp = fopen($questionFilePath, 'r'))) {
        Display::addFlash(Display::return_message(get_lang('Error opening question\'s XML file'), 'error'));

        return false;
    } else {
        $data = fread($fp, filesize($questionFilePath));
    }

    //parse XML question file
    //$data = str_replace(array('<p>', '</p>', '<front>', '</front>'), '', $data);
    $data = ChamiloApi::stripGivenTags($data, array('p', 'front'));
    $qtiVersion = array();
    $match = preg_match('/ims_qtiasiv(\d)p(\d)/', $data, $qtiVersion);
    $qtiMainVersion = 2; //by default, assume QTI version 2
    if ($match) {
        $qtiMainVersion = $qtiVersion[1];
    }

    //used global variable start values declaration :

    $record_item_body = false;
    $non_HTML_tag_to_avoid = array(
        "SIMPLECHOICE",
        "CHOICEINTERACTION",
        "INLINECHOICEINTERACTION",
        "INLINECHOICE",
        "SIMPLEMATCHSET",
        "SIMPLEASSOCIABLECHOICE",
        "TEXTENTRYINTERACTION",
        "FEEDBACKINLINE",
        "MATCHINTERACTION",
        "ITEMBODY",
        "BR",
        "IMG"
    );

    $question_format_supported = true;

    $xml_parser = xml_parser_create();
    xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, false);
    if ($qtiMainVersion == 1) {
        xml_set_element_handler($xml_parser, 'startElementQti1', 'endElementQti1');
        xml_set_character_data_handler($xml_parser, 'elementDataQti1');
    } else {
        xml_set_element_handler($xml_parser, 'startElementQti2', 'endElementQti2');
        xml_set_character_data_handler($xml_parser, 'elementDataQti2');
    }
    if (!xml_parse($xml_parser, $data, feof($fp))) {
        // if reading of the xml file in not successful :
        // set errorFound, set error msg, break while statement
        $error = xml_get_error_code();
        Display::addFlash(
            Display::return_message(
                get_lang('Error reading XML file') . sprintf('[%d:%d]', xml_get_current_line_number($xml_parser), xml_get_current_column_number($xml_parser)),
                'error'
            )
        );

        return false;
    }

    //close file
    fclose($fp);
    if (!$question_format_supported) {
        Display::addFlash(
            Display::return_message(
                get_lang(
                    'Unknown question format in file %file',
                    array(
                        '%file' => $questionFile,
                    )
                ),
                'error'
            )
        );

        return false;
    }
    return true;
}

/**
 * Function used by the SAX xml parser when the parser meets a opening tag
 *
 * @param object $parser xml parser created with "xml_parser_create()"
 * @param string $name name of the element
 * @param array $attributes
 */
function startElementQti2($parser, $name, $attributes)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $questionTempDir;

    array_push($element_pile, $name);
    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }

    if ($record_item_body) {

        if ((!in_array($current_element, $non_HTML_tag_to_avoid))) {
            $current_question_item_body .= "<" . $name;
            foreach ($attributes as $attribute_name => $attribute_value) {
                $current_question_item_body .= " " . $attribute_name . "=\"" . $attribute_value . "\"";
            }
            $current_question_item_body .= ">";
        } else {
            //in case of FIB question, we replace the IMS-QTI tag b y the correct answer between "[" "]",
            //we first save with claroline tags ,then when the answer will be parsed, the claroline tags will be replaced

            if ($current_element == 'INLINECHOICEINTERACTION') {
                $current_question_item_body .= "**claroline_start**" . $attributes['RESPONSEIDENTIFIER'] . "**claroline_end**";
            }
            if ($current_element == 'TEXTENTRYINTERACTION') {
                $correct_answer_value = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];
                $current_question_item_body .= "[" . $correct_answer_value . "]";

            }
            if ($current_element == 'BR') {
                $current_question_item_body .= "<br />";
            }
        }
    }

    switch ($current_element) {
        case 'ASSESSMENTITEM':
            //retrieve current question
            $current_question_ident = $attributes['IDENTIFIER'];
            $exercise_info['question'][$current_question_ident] = array();
            $exercise_info['question'][$current_question_ident]['answer'] = array();
            $exercise_info['question'][$current_question_ident]['correct_answers'] = array();
            $exercise_info['question'][$current_question_ident]['title'] = $attributes['TITLE'];
            $exercise_info['question'][$current_question_ident]['tempdir'] = $questionTempDir;
            break;
        case 'SECTION':
            //retrieve exercise name
            if (isset($attributes['TITLE']) && !empty($attributes['TITLE'])) {
                $exercise_info['name'] = $attributes['TITLE'];
            }
            break;
        case 'RESPONSEDECLARATION':
            // Retrieve question type
            if ("multiple" == $attributes['CARDINALITY']) {
                $exercise_info['question'][$current_question_ident]['type'] = MCMA;
                $cardinality = 'multiple';
            }
            if ("single" == $attributes['CARDINALITY']) {
                $exercise_info['question'][$current_question_ident]['type'] = MCUA;
                $cardinality = 'single';
            }
            //needed for FIB
            $current_answer_id = $attributes['IDENTIFIER'];
            break;
        case 'INLINECHOICEINTERACTION':
            $exercise_info['question'][$current_question_ident]['type'] = FIB;
            $exercise_info['question'][$current_question_ident]['subtype'] = 'LISTBOX_FILL';
            $current_answer_id = $attributes['RESPONSEIDENTIFIER'];
            break;
        case 'INLINECHOICE':
            $current_inlinechoice_id = $attributes['IDENTIFIER'];
            break;
        case 'TEXTENTRYINTERACTION':
            $exercise_info['question'][$current_question_ident]['type'] = FIB;
            $exercise_info['question'][$current_question_ident]['subtype'] = 'TEXTFIELD_FILL';
            $exercise_info['question'][$current_question_ident]['response_text'] = $current_question_item_body;
            //replace claroline tags
            break;
        case 'MATCHINTERACTION':
            $exercise_info['question'][$current_question_ident]['type'] = MATCHING;
            break;
        case 'SIMPLEMATCHSET':
            if (!isset($current_match_set)) {
                $current_match_set = 1;
            } else {
                $current_match_set++;
            }
            $exercise_info['question'][$current_question_ident]['answer'][$current_match_set] = array();
            break;
        case 'SIMPLEASSOCIABLECHOICE':
            $currentAssociableChoice = $attributes['IDENTIFIER'];
            break;
        //retrieve answers id for MCUA and MCMA questions
        case 'SIMPLECHOICE':
            $current_answer_id = $attributes['IDENTIFIER'];
            if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id])) {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id] = array();
            }
            break;
        case 'MAPENTRY':
            if ($parent_element == "MAPPING") {
                $answer_id = $attributes['MAPKEY'];
                if (!isset ($exercise_info['question'][$current_question_ident]['weighting'])) {
                    $exercise_info['question'][$current_question_ident]['weighting'] = array();
                }
                $exercise_info['question'][$current_question_ident]['weighting'][$answer_id] = $attributes['MAPPEDVALUE'];
            }
            break;
        case 'MAPPING':
            if (isset ($attributes['DEFAULTVALUE'])) {
                $exercise_info['question'][$current_question_ident]['default_weighting'] = $attributes['DEFAULTVALUE'];
            }
        case 'ITEMBODY':
            $record_item_body = true;
            $current_question_item_body = '';
            break;
        case 'IMG':
            $exercise_info['question'][$current_question_ident]['attached_file_url'] = $attributes['SRC'];
            break;
    }
}

/**
 * Function used by the SAX xml parser when the parser meets a closing tag
 *
 * @param $parser xml parser created with "xml_parser_create()"
 * @param $name name of the element
 */
function endElementQti2($parser, $name)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $record_item_body;
    global $current_question_item_body;
    global $non_HTML_tag_to_avoid;
    global $cardinality;

    array_push($element_pile, $name);
    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }
    if (sizeof($element_pile) >= 3) {
        $grand_parent_element = $element_pile[sizeof($element_pile) - 3];
    } else {
        $grand_parent_element = "";
    }
    if (sizeof($element_pile) >= 4) {
        $great_grand_parent_element = $element_pile[sizeof($element_pile) - 4];
    } else {
        $great_grand_parent_element = "";
    }

    //treat the record of the full content of itembody tag :

    if ($record_item_body && (!in_array($current_element, $non_HTML_tag_to_avoid))) {
        $current_question_item_body .= "</" . $name . ">";
    }

    switch ($name) {
        case 'ITEMBODY':
            $record_item_body = false;
            if ($exercise_info['question'][$current_question_ident]['type'] == FIB) {
                $exercise_info['question'][$current_question_ident]['response_text'] = $current_question_item_body;
            } else {
                $exercise_info['question'][$current_question_ident]['statement'] = $current_question_item_body;
            }
            break;
    }
    array_pop($element_pile);
}

/**
 * @param $parser
 * @param $data
 */
function elementDataQti2($parser, $data)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $resourcesLinks;

    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }
    if (sizeof($element_pile) >= 3) {
        $grand_parent_element = $element_pile[sizeof($element_pile) - 3];
    } else {
        $grand_parent_element = "";
    }
    if (sizeof($element_pile) >= 4) {
        $great_grand_parent_element = $element_pile[sizeof($element_pile) - 4];
    } else {
        $great_grand_parent_element = "";
    }

    //treat the record of the full content of itembody tag (needed for question statment and/or FIB text:

    if ($record_item_body && (!in_array($current_element, $non_HTML_tag_to_avoid))) {
        $current_question_item_body .= $data;
    }

    switch ($current_element) {
        case 'SIMPLECHOICE':
            if (!isset ($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'])) {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] = trim($data);
            } else {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] .= '' . trim($data);
            }
            break;
        case 'FEEDBACKINLINE':
            if (!isset ($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'])) {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'] = trim($data);
            } else {
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'] .= ' ' . trim($data);
            }
            break;
        case 'SIMPLEASSOCIABLECHOICE':
            $exercise_info['question'][$current_question_ident]['answer'][$current_match_set][$currentAssociableChoice] = trim($data);
            break;
        case 'VALUE':
            if ($parent_element == "CORRECTRESPONSE") {
                if ($cardinality == "single") {
                    $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id] = $data;
                } else {
                    $exercise_info['question'][$current_question_ident]['correct_answers'][] = $data;
                }
            }
            break;
        case 'ITEMBODY':
            // Replace relative links by links to the documents in the course
            // $resourcesLinks is only defined by qtiProcessManifest()
            if (isset($resourcesLinks) && isset($resourcesLinks['manifest']) && isset($resourcesLinks['web'])) {
                foreach ($resourcesLinks['manifest'] as $key => $value) {
                    $data = preg_replace('|' . $value . '|', $resourcesLinks['web'][$key], $data);
                }
            }
            $current_question_item_body .= $data;
            break;
        case 'INLINECHOICE':
            // if this is the right answer, then we must replace the claroline tags in the FIB text bye the answer between "[" and "]" :
            $answer_identifier = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];
            if ($current_inlinechoice_id == $answer_identifier) {
                $current_question_item_body = str_replace(
                    "**claroline_start**" . $current_answer_id . "**claroline_end**",
                    "[" . $data . "]",
                    $current_question_item_body
                );
            } else {
                if (!isset ($exercise_info['question'][$current_question_ident]['wrong_answers'])) {
                    $exercise_info['question'][$current_question_ident]['wrong_answers'] = array();
                }
                $exercise_info['question'][$current_question_ident]['wrong_answers'][] = $data;
            }
            break;
        case 'MATTEXT':
            if ($grand_parent_element == 'FLOW_MAT' && ($great_grand_parent_element == 'PRESENTATION_MATERIAL' || $great_grand_parent_element == 'SECTION')) {
                if (!empty(trim($data))) {
                    $exercise_info['description'] = $data;
                }
            }

    }
}

/**
 * Function used by the SAX xml parser when the parser meets a opening tag for QTI1
 *
 * @param object $parser xml parser created with "xml_parser_create()"
 * @param string $name name of the element
 * @param array $attributes
 */
function startElementQti1($parser, $name, $attributes)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $questionTempDir;
    global $lastLabelFieldName;
    global $lastLabelFieldValue;

    array_push($element_pile, $name);
    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }
    if (sizeof($element_pile) >= 3) {
        $grand_parent_element = $element_pile[sizeof($element_pile) - 3];
    } else {
        $grand_parent_element = "";
    }
    if (sizeof($element_pile) >= 4) {
        $great_grand_parent_element = $element_pile[sizeof($element_pile) - 4];
    } else {
        $great_grand_parent_element = "";
    }


    if ($record_item_body) {

        if ((!in_array($current_element, $non_HTML_tag_to_avoid))) {
            $current_question_item_body .= "<" . $name;
            foreach ($attributes as $attribute_name => $attribute_value) {
                $current_question_item_body .= " " . $attribute_name . "=\"" . $attribute_value . "\"";
            }
            $current_question_item_body .= ">";
        } else {
            //in case of FIB question, we replace the IMS-QTI tag b y the correct answer between "[" "]",
            //we first save with claroline tags ,then when the answer will be parsed, the claroline tags will be replaced

            if ($current_element == 'INLINECHOICEINTERACTION') {
                $current_question_item_body .= "**claroline_start**" . $attributes['RESPONSEIDENTIFIER'] . "**claroline_end**";
            }
            if ($current_element == 'TEXTENTRYINTERACTION') {
                $correct_answer_value = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];
                $current_question_item_body .= "[" . $correct_answer_value . "]";

            }
            if ($current_element == 'BR') {
                $current_question_item_body .= "<br />";
            }
        }
    }

    switch ($current_element) {
        case 'ASSESSMENT':
            // This is the assessment element: we don't care, we just want questions
            if (!empty($attributes['TITLE'])) {
                $exercise_info['name'] = $attributes['TITLE'];
            }
            break;
        case 'ITEM':
            //retrieve current question
            $current_question_ident = $attributes['IDENT'];
            $exercise_info['question'][$current_question_ident] = array();
            $exercise_info['question'][$current_question_ident]['answer'] = array();
            $exercise_info['question'][$current_question_ident]['correct_answers'] = array();
            $exercise_info['question'][$current_question_ident]['tempdir'] = $questionTempDir;
            break;
        case 'SECTION':
            break;
        case 'RESPONSE_LID':
            // Retrieve question type
            if ("multiple" == strtolower($attributes['RCARDINALITY'])) {
                $cardinality = 'multiple';
            }
            if ("single" == strtolower($attributes['RCARDINALITY'])) {
                $cardinality = 'single';
            }
            //needed for FIB
            $current_answer_id = $attributes['IDENT'];
            $current_question_item_body = '';
            break;
        case 'RENDER_CHOICE';
            break;
        case 'RESPONSE_LABEL':
            if (!empty($attributes['IDENT'])) {
                $current_answer_id = $attributes['IDENT'];
                //set the placeholder for the answer to come (in endElementQti1)
                $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id] = '';
            }
            break;
        case 'DECVAR':
            if ($parent_element == 'OUTCOMES' && $grand_parent_element == 'RESPROCESSING') {
                // The following attributes are available
                //$attributes['VARTYPE'];
                //$attributes['DEFAULTVAL'];
                //$attributes['MINVALUE'];
                //$attributes['MAXVALUE'];
            }
            break;
        case 'VAREQUAL':
            if ($parent_element == 'CONDITIONVAR' && $grand_parent_element == 'RESPCONDITION') {
                // The following attributes are available
                //$attributes['RESPIDENT']
            }
            break;
        case 'SETVAR':
            if ($parent_element == 'RESPCONDITION') {
                // The following attributes are available
                //$attributes['ACTION']
            }
            break;
        case 'IMG':
            break;
        case 'MATTEXT':
            if ($parent_element == 'MATERIAL') {
                if ($grand_parent_element == 'PRESENTATION') {
                    $exercise_info['question'][$current_question_ident]['title'] = $current_question_item_body;
                }
            }
    }
}

/**
 * Function used by the SAX xml parser when the parser meets a closing tag for QTI1
 *
 * @param object $parser xml parser created with "xml_parser_create()"
 * @param string $name name of the element
 * @param array $attributes The element attributes
 */
function endElementQti1($parser, $name, $attributes)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $record_item_body;
    global $current_question_item_body;
    global $non_HTML_tag_to_avoid;
    global $cardinality;
    global $lastLabelFieldName;
    global $lastLabelFieldValue;
    global $resourcesLinks;

    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }
    if (sizeof($element_pile) >= 3) {
        $grand_parent_element = $element_pile[sizeof($element_pile) - 3];
    } else {
        $grand_parent_element = "";
    }
    if (sizeof($element_pile) >= 4) {
        $great_grand_parent_element = $element_pile[sizeof($element_pile) - 4];
    } else {
        $great_grand_parent_element = "";
    }

    //treat the record of the full content of itembody tag :

    if ($record_item_body && (!in_array($current_element, $non_HTML_tag_to_avoid))) {
        $current_question_item_body .= "</" . $name . ">";
    }

    switch ($name) {
        case 'MATTEXT':
            if ($parent_element == 'MATERIAL') {
                // For some reason an item in a hierarchy <item><presentation><material><mattext> doesn't seem to
                // catch the grandfather 'presentation', so we check for 'item' as a patch (great-grand-father)
                if ($grand_parent_element == 'PRESENTATION' OR $grand_parent_element == 'ITEM') {
                    $exercise_info['question'][$current_question_ident]['title'] = $current_question_item_body;
                    $current_question_item_body = '';
                } elseif ($grand_parent_element == 'RESPONSE_LABEL') {
                    $last = '';
                    foreach ($exercise_info['question'][$current_question_ident]['answer'] as $key => $value) {
                        $last = $key;
                    }
                    $exercise_info['question'][$current_question_ident]['answer'][$last]['value'] = $current_question_item_body;
                    $current_question_item_body = '';
                }
            }
        case 'RESPONSE_LID':
            // Retrieve question type
            if (!isset($exercise_info['question'][$current_question_ident]['type'])) {
                if ("multiple" == strtolower($attributes['RCARDINALITY'])) {
                    $exercise_info['question'][$current_question_ident]['type'] = MCMA;
                }
                if ("single" == strtolower($attributes['RCARDINALITY'])) {
                    $exercise_info['question'][$current_question_ident]['type'] = MCUA;
                }
            }
            $current_question_item_body = '';
            //needed for FIB
            $current_answer_id = $attributes['IDENT'];
            break;
        case 'ITEMMETADATA':
            $current_question_item_body = '';
            break;
    }
    array_pop($element_pile);
}

/**
 * QTI1 element parser
 * @param $parser
 * @param $data
 */
function elementDataQti1($parser, $data)
{
    global $element_pile;
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $record_item_body;
    global $non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $lastLabelFieldName;
    global $lastLabelFieldValue;
    global $resourcesLinks;

    $current_element = end($element_pile);
    if (sizeof($element_pile) >= 2) {
        $parent_element = $element_pile[sizeof($element_pile) - 2];
    } else {
        $parent_element = "";
    }
    //treat the record of the full content of itembody tag (needed for question statment and/or FIB text:

    if ($record_item_body && (!in_array($current_element, $non_HTML_tag_to_avoid))) {
        $current_question_item_body .= $data;
    }

    switch ($current_element) {
        case 'FIELDLABEL':
            if (!empty($data)) {
                $lastLabelFieldName = $current_element;
                $lastLabelFieldValue = $data;
            }
        case 'FIELDENTRY':
            $current_question_item_body = $data;
            switch ($lastLabelFieldValue) {
                case 'cc_profile':
                    // The following values might be proprietary in MATRIX software. No specific reference
                    // in QTI doc: http://www.imsglobal.org/question/qtiv1p2/imsqti_asi_infov1p2.html#1415855
                    switch ($data) {
                        case 'cc.true_false.v0p1':
                            //this is a true-false question (translated to multiple choice in Chamilo because true-false comes with "I don't know")
                            $exercise_info['question'][$current_question_ident]['type'] = MCUA;
                            break;
                        case 'cc.multiple_choice.v0p1':
                            //this is a multiple choice (unique answer) question
                            $exercise_info['question'][$current_question_ident]['type'] = MCUA;
                            break;
                        case 'cc.multiple_response.v0p1':
                            //this is a multiple choice (unique answer) question
                            $exercise_info['question'][$current_question_ident]['type'] = MCMA;
                            break;
                    }
                    break;
                case 'cc_weighting':
                    //defines the total weight of the question
                    $exercise_info['question'][$current_question_ident]['default_weighting'] = $lastLabelFieldValue;
                    break;
                case 'assessment_question_identifierref':
                    //placeholder - not used yet
                    // Possible values are not defined by qti v1.2
                    break;
            }
            break;
        case 'MATTEXT':
            // Replace relative links by links to the documents in the course
            // $resourcesLinks is only defined by qtiProcessManifest()
            if (isset($resourcesLinks) && isset($resourcesLinks['manifest']) && isset($resourcesLinks['web'])) {
                foreach ($resourcesLinks['manifest'] as $key=>$value) {
                    $data = preg_replace('|' . $value . '|', $resourcesLinks['web'][$key], $data);
                }
            }
            if (!empty($current_question_item_body)) {
                $current_question_item_body .= $data;
            } else {
                $current_question_item_body = $data;
            }
            break;
        case 'VAREQUAL':
            $lastLabelFieldName = 'VAREQUAL';
            $lastLabelFieldValue = $data;
            break;
        case 'SETVAR':
            if ($parent_element == 'RESPCONDITION') {
                // The following attributes are available
                //$attributes['ACTION']
                $exercise_info['question'][$current_question_ident]['correct_answers'][] = $lastLabelFieldValue;
                $exercise_info['question'][$current_question_ident]['weighting'][$lastLabelFieldValue] = $data;
            }
            break;

    }
}

/**
 * Check if a given file is an IMS/QTI question bank file
 * @param string $filePath The absolute filepath
 * @return bool Whether it is an IMS/QTI question bank or not
 */
function isQtiQuestionBank($filePath)
{
    $data = file_get_contents($filePath);
    if (!empty($data)) {
        $match = preg_match('/ims_qtiasiv(\d)p(\d)/', $data);
        if ($match) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a given file is an IMS/QTI manifest file (listing of extra files)
 * @param string $filePath The absolute filepath
 * @return bool Whether it is an IMS/QTI manifest file or not
 */
function isQtiManifest($filePath)
{
    $data = file_get_contents($filePath);
    if (!empty($data)) {
        $match = preg_match('/imsccv(\d)p(\d)/', $data);
        if ($match) {
            return true;
        }
    }

    return false;
}

/**
 * Processes an IMS/QTI manifest file: store links to new files to be able to transform them into the questions text
 * @param string $filePath The absolute filepath
 * @param array $links List of filepaths changes
 * @return bool
 */
function qtiProcessManifest($filePath)
{
    $xml = simplexml_load_file($filePath);
    $course = api_get_course_info();
    $sessionId = api_get_session_id();
    $courseDir = $course['path'];
    $sysPath = api_get_path(SYS_COURSE_PATH);
    $exercisesSysPath = $sysPath . $courseDir . '/document/';
    $webPath = api_get_path(WEB_CODE_PATH);
    $exercisesWebPath = $webPath . 'document/document.php?' . api_get_cidreq() . '&action=download&id=';
    $links = array(
        'manifest' => array(),
        'system' => array(),
        'web' => array(),
    );
    $tableDocuments = Database::get_course_table(TABLE_DOCUMENT);
    $countResources = count($xml->resources->resource->file);
    for ($i=0; $i < $countResources; $i++) {
        $file = $xml->resources->resource->file[$i];
        $href = '';
        foreach ($file->attributes() as $key => $value) {
            if ($key == 'href') {
                if (substr($value, -3, 3) != 'xml') {
                    $href = $value;
                }
            }
        }
        if (!empty($href)) {
            $links['manifest'][] = (string) $href;
            $links['system'][] = $exercisesSysPath . strtolower($href);
            $specialHref = Database::escape_string(preg_replace('/_/', '-', strtolower($href)));
            $specialHref = preg_replace('/(-){2,8}/', '-', $specialHref);

            $sql = "SELECT iid FROM " . $tableDocuments . " WHERE c_id = " . $course['real_id'] . " AND session_id = $sessionId AND path = '/" . $specialHref . "'";
            $result = Database::query($sql);
            $documentId = 0;
            while ($row = Database::fetch_assoc($result)) {
                $documentId = $row['iid'];
            }
            $links['web'][] = $exercisesWebPath . $documentId;
        }
    }
    return $links;
}
