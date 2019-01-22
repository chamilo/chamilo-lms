<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @package chamilo.exercise
 *
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

/**
 * Unzip the exercise in the temp folder.
 *
 * @param string $baseWorkDir The path of the temporary directory where the exercise was uploaded and unzipped
 * @param string $uploadPath
 *
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

    if (preg_match('/.zip$/i', $_FILES['userFile']['name'])) {
        $result = handle_uploaded_document(
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
            true,
            null,
            null,
            false
        );

        return $result;
    }

    return false;
}

/**
 * Imports an exercise in QTI format if the XML structure can be found in it.
 *
 * @param array $file
 *
 * @return string|array as a backlog of what was really imported, and error or debug messages to display
 */
function import_exercise($file)
{
    global $exercise_info;
    global $element_pile;
    global $resourcesLinks;

    $baseWorkDir = api_get_path(SYS_ARCHIVE_PATH).'qti2/';
    if (!is_dir($baseWorkDir)) {
        mkdir($baseWorkDir, api_get_permissions_for_new_directories(), true);
    }

    $uploadPath = api_get_unique_id().'/';

    if (!is_dir($baseWorkDir.$uploadPath)) {
        mkdir($baseWorkDir.$uploadPath, api_get_permissions_for_new_directories(), true);
    }

    // set some default values for the new exercise
    $exercise_info = [];
    $exercise_info['name'] = preg_replace('/.zip$/i', '', $file);
    $exercise_info['question'] = [];
    $element_pile = [];
    // create parser and array to retrieve info from manifest
    $element_pile = []; //pile to known the depth in which we are

    // if file is not a .zip, then we cancel all
    if (!preg_match('/.zip$/i', $file)) {
        return 'UplZipCorrupt';
    }

    // unzip the uploaded file in a tmp directory
    if (!get_and_unzip_uploaded_exercise($baseWorkDir, $uploadPath)) {
        return 'UplZipCorrupt';
    }

    $baseWorkDir = $baseWorkDir.$uploadPath;

    // find the different manifests for each question and parse them.
    $exerciseHandle = opendir($baseWorkDir);
    $file_found = false;
    $result = false;
    $filePath = null;
    $resourcesLinks = [];

    // parse every subdirectory to search xml question files and other assets to be imported
    // The assets-related code is a bit fragile as it has to deal with files renamed by Chamilo and it only works if
    // the imsmanifest.xml file is read.
    while (false !== ($file = readdir($exerciseHandle))) {
        if (is_dir($baseWorkDir.'/'.$file) && $file != "." && $file != "..") {
            // Find each manifest for each question repository found
            $questionHandle = opendir($baseWorkDir.'/'.$file);
            // Only analyse one level of subdirectory - no recursivity here
            while (false !== ($questionFile = readdir($questionHandle))) {
                if (preg_match('/.xml$/i', $questionFile)) {
                    $isQti = isQtiQuestionBank($baseWorkDir.'/'.$file.'/'.$questionFile);
                    if ($isQti) {
                        $result = qti_parse_file($baseWorkDir, $file, $questionFile);
                        $filePath = $baseWorkDir.$file;
                        $file_found = true;
                    } else {
                        $isManifest = isQtiManifest($baseWorkDir.'/'.$file.'/'.$questionFile);
                        if ($isManifest) {
                            $resourcesLinks = qtiProcessManifest($baseWorkDir.'/'.$file.'/'.$questionFile);
                        }
                    }
                }
            }
        } elseif (preg_match('/.xml$/i', $file)) {
            $isQti = isQtiQuestionBank($baseWorkDir.'/'.$file);
            if ($isQti) {
                $result = qti_parse_file($baseWorkDir, '', $file);
                $filePath = $baseWorkDir.'/'.$file;
                $file_found = true;
            } else {
                $isManifest = isQtiManifest($baseWorkDir.'/'.$file);
                if ($isManifest) {
                    $resourcesLinks = qtiProcessManifest($baseWorkDir.'/'.$file);
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

    // Random QTI support
    if (isset($exercise_info['order_type'])) {
        if ($exercise_info['order_type'] == 'Random') {
            $exercise->setQuestionSelectionType(2);
            $exercise->random = -1;
        }
    }

    if (!empty($exercise_info['description'])) {
        $exercise->updateDescription(formatText(strip_tags($exercise_info['description'])));
    }

    $exercise->save();
    $last_exercise_id = $exercise->selectId();
    $courseId = api_get_course_int_id();
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
            $question->updateTitle(formatText(strip_tags($question_array['title'])));

            if (isset($question_array['category'])) {
                $category = formatText(strip_tags($question_array['category']));
                if (!empty($category)) {
                    $categoryId = TestCategory::get_category_id_for_title(
                        $category,
                        $courseId
                    );

                    if (empty($categoryId)) {
                        $cat = new TestCategory();
                        $cat->name = $category;
                        $cat->description = '';
                        $categoryId = $cat->save($courseId);
                        if ($categoryId) {
                            $question->category = $categoryId;
                        }
                    } else {
                        $question->category = $categoryId;
                    }
                }
            }

            if (!empty($question_array['description'])) {
                $description .= $question_array['description'];
            }

            $question->updateDescription($description);
            $question->save($exercise);

            $last_question_id = $question->selectId();
            //3. Create answer
            $answer = new Answer($last_question_id);
            $answerList = $question_array['answer'];
            $answer->new_nbrAnswers = count($answerList);
            $totalCorrectWeight = 0;
            $j = 1;
            $matchAnswerIds = [];
            if (!empty($answerList)) {
                foreach ($answerList as $key => $answers) {
                    if (preg_match('/_/', $key)) {
                        $split = explode('_', $key);
                        $i = $split[1];
                    } else {
                        $i = $j;
                        $j++;
                        $matchAnswerIds[$key] = $j;
                    }

                    // Answer
                    $answer->new_answer[$i] = isset($answers['value']) ? formatText($answers['value']) : '';
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

                    $answer->new_weighting[$i] = 0;
                    if (isset($question_array['weighting'][$key])) {
                        $answer->new_weighting[$i] = $question_array['weighting'][$key];
                    }
                    if ($answer->new_correct[$i]) {
                        $totalCorrectWeight += $answer->new_weighting[$i];
                    }
                }
            }

            if ($question->type == FREE_ANSWER) {
                $totalCorrectWeight = $question_array['weighting'][0];
            }

            $question->updateWeighting($totalCorrectWeight);
            $question->save($exercise);
            $answer->save();
        }
        // delete the temp dir where the exercise was unzipped
        my_delete($baseWorkDir.$uploadPath);

        return $last_exercise_id;
    }

    return false;
}

/**
 * We assume the file charset is UTF8.
 */
function formatText($text)
{
    return api_html_entity_decode($text);
}

/**
 * Parses a given XML file and fills global arrays with the elements.
 *
 * @param string $exercisePath
 * @param string $file
 * @param string $questionFile
 *
 * @return bool
 */
function qti_parse_file($exercisePath, $file, $questionFile)
{
    global $non_HTML_tag_to_avoid;
    global $record_item_body;
    global $questionTempDir;

    $questionTempDir = $exercisePath.'/'.$file.'/';
    $questionFilePath = $questionTempDir.$questionFile;

    if (!($fp = fopen($questionFilePath, 'r'))) {
        Display::addFlash(Display::return_message(get_lang('Error opening question\'s XML file'), 'error'));

        return false;
    }

    $data = fread($fp, filesize($questionFilePath));

    //close file
    fclose($fp);

    //parse XML question file
    //$data = str_replace(array('<p>', '</p>', '<front>', '</front>'), '', $data);
    $data = ChamiloApi::stripGivenTags($data, ['p', 'front']);
    $qtiVersion = [];
    $match = preg_match('/ims_qtiasiv(\d)p(\d)/', $data, $qtiVersion);
    $qtiMainVersion = 2; //by default, assume QTI version 2
    if ($match) {
        $qtiMainVersion = $qtiVersion[1];
    }

    //used global variable start values declaration:
    $record_item_body = false;
    $non_HTML_tag_to_avoid = [
        "simpleChoice",
        "choiceInteraction",
        "inlineChoiceInteraction",
        "inlineChoice",
        "soMPLEMATCHSET",
        "simpleAssociableChoice",
        "textEntryInteraction",
        "feedbackInline",
        "matchInteraction",
        'extendedTextInteraction',
        "itemBody",
        "br",
        "img",
    ];

    if ($qtiMainVersion != 2) {
        Display::addFlash(
            Display::return_message(
                get_lang('UnsupportedQtiVersion'),
                'error'
            )
        );

        return false;
    }

    parseQti2($data);

    return true;
}

/**
 * Function used to parser a QTI2 xml file
 *
 * @param string $xmlData
 */
function parseQti2($xmlData) {
    global $exercise_info;
    global $current_question_ident;
    global $current_answer_id;
    global $current_match_set;
    global $currentAssociableChoice;
    global $current_question_item_body;
    global $non_HTML_tag_to_avoid;
    global $current_inlinechoice_id;
    global $cardinality;
    global $questionTempDir;
    global $resourcesLinks;

    $crawler = new Crawler($xmlData);
    $nodes = $crawler->filter('*');

    /** @var DOMElement $node */
    foreach ($nodes as $node) {
        if ('#text' === $node->nodeName) {
            continue;
        }

        switch ($node->nodeName) {
            case 'assessmentItem':
                $current_question_ident = $node->getAttribute('identifier');

                $exercise_info['question'][$current_question_ident] = [
                    'answer' => [],
                    'correct_answers' => [],
                    'title' => $node->getAttribute('title'),
                    'category' => $node->getAttribute('category'),
                    'type' => '',
                    'tempdir' => $questionTempDir,
                ];
                break;

            case 'section':
                $title = $node->getAttribute('title');

                if (!empty($title)) {
                    $exercise_info['name'] = $title;
                }
                break;

            case 'responseDeclaration':
                if ('multiple' === $node->getAttribute('cardinality')) {
                    $exercise_info['question'][$current_question_ident]['type'] = MCMA;
                    $cardinality = 'multiple';
                }

                if ('single' === $node->getAttribute('cardinality')) {
                    $exercise_info['question'][$current_question_ident]['type'] = MCUA;
                    $cardinality = 'single';
                }

                $current_answer_id = $node->getAttribute('identifier');
                break;

            case 'inlineChoiceInteraction':
                $exercise_info['question'][$current_question_ident]['type'] = FIB;
                $exercise_info['question'][$current_question_ident]['subtype'] = 'LISTBOX_FILL';
                $current_answer_id = $node->getAttribute('responseIdentifier');
                break;

            case 'inlineChoice':
                $current_inlinechoice_id = $node->getAttribute('identifier');

                $answer_identifier = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];

                if ($current_inlinechoice_id == $answer_identifier) {
                    $current_question_item_body = str_replace(
                        "**claroline_start**".$current_answer_id."**claroline_end**",
                        "[".$node->nodeValue."]",
                        $current_question_item_body
                    );
                } else {
                    if (!isset($exercise_info['question'][$current_question_ident]['wrong_answers'])) {
                        $exercise_info['question'][$current_question_ident]['wrong_answers'] = [];
                    }

                    $exercise_info['question'][$current_question_ident]['wrong_answers'][] = $node->nodeValue;
                }
                break;

            case 'textEntryInteraction':
                $exercise_info['question'][$current_question_ident]['type'] = FIB;
                $exercise_info['question'][$current_question_ident]['subtype'] = 'TEXTFIELD_FILL';
                $exercise_info['question'][$current_question_ident]['response_text'] = $current_question_item_body;
                break;

            case 'matchInteraction':
                $exercise_info['question'][$current_question_ident]['type'] = MATCHING;
                break;

            case 'extendedTextInteraction':
                $exercise_info['question'][$current_question_ident]['type'] = FREE_ANSWER;
                $exercise_info['question'][$current_question_ident]['description'] = $node->nodeValue;
                break;

            case 'simpleMatchSet':
                if (!isset($current_match_set)) {
                    $current_match_set = 1;
                } else {
                    $current_match_set++;
                }
                $exercise_info['question'][$current_question_ident]['answer'][$current_match_set] = [];
                break;

            case 'simpleAssociableChoice':
                $currentAssociableChoice = $node->getAttribute('identifier');

                $exercise_info['question'][$current_question_ident]['answer'][$current_match_set][$currentAssociableChoice] = trim(
                    $node->nodeValue
                );
                break;

            case 'simpleChoice':
                $current_answer_id = $node->getAttribute('identifier');
                if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id])) {
                    $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id] = [];
                }

                if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'])) {
                    $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] = trim(
                        $node->nodeValue
                    );
                } else {
                    $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['value'] .= ''
                        .trim(
                            $node->nodeValue
                        );
                }
                break;

            case 'mapEntry':
                if (in_array($node->parentNode->nodeName, ['mapping', 'mapEntry'])) {
                    $answer_id = $node->getAttribute('mapKey');

                    if (!isset($exercise_info['question'][$current_question_ident]['weighting'])) {
                        $exercise_info['question'][$current_question_ident]['weighting'] = [];
                    }

                    $exercise_info['question'][$current_question_ident]['weighting'][$answer_id] = $node->getAttribute('mappedValue');
                }
                break;

            case 'mapping':
                $defaultValue = $node->getAttribute('defaultValue');

                if (!empty($defaultValue)) {
                    $exercise_info['question'][$current_question_ident]['default_weighting'] = $defaultValue;
                }
                // no break ?

            case 'itemBody':
                $nodeValue = $node->nodeValue;

                $current_question_item_body = '';

                /** @var DOMElement $childNode */
                foreach ($node->childNodes as $childNode) {
                    if ('#text' === $childNode->nodeName) {
                        continue;
                    }

                    if (!in_array($childNode->nodeName, $non_HTML_tag_to_avoid)) {
                        $current_question_item_body .= '<'.$childNode->nodeName;

                        if ($childNode->attributes) {
                            foreach ($childNode->attributes as $attribute) {
                                $current_question_item_body .= ' '.$attribute->nodeName.'="'.$attribute->nodeValue.'"';
                            }
                        }

                        $current_question_item_body .= '>'
                            .$childNode->nodeValue
                            .'</'.$node->nodeName.'>';

                        continue;
                    }

                    if ('inlineChoiceInteraction' === $childNode->nodeName) {
                        $current_question_item_body .= "**claroline_start**"
                            .$childNode->attr('responseIdentifier')
                            ."**claroline_end**";

                        continue;
                    }

                    if ('textEntryInteraction' === $childNode->nodeName) {
                        $correct_answer_value = $exercise_info['question'][$current_question_ident]['correct_answers'][$current_answer_id];
                        $current_question_item_body .= "[".$correct_answer_value."]";

                        continue;
                    }

                    if ('br' === $childNode->nodeName) {
                        $current_question_item_body .= '<br>';
                    }
                }

                // Replace relative links by links to the documents in the course
                // $resourcesLinks is only defined by qtiProcessManifest()
                if (isset($resourcesLinks) && isset($resourcesLinks['manifest']) && isset($resourcesLinks['web'])) {
                    foreach ($resourcesLinks['manifest'] as $key => $value) {
                        $nodeValue = preg_replace('|'.$value.'|', $resourcesLinks['web'][$key], $nodeValue);
                    }
                }

                $current_question_item_body .= $node->firstChild->nodeValue;

                if ($exercise_info['question'][$current_question_ident]['type'] == FIB) {
                    $exercise_info['question'][$current_question_ident]['response_text'] = $current_question_item_body;
                } else {
                    if ($exercise_info['question'][$current_question_ident]['type'] == FREE_ANSWER) {
                        $current_question_item_body = trim($current_question_item_body);

                        if (!empty($current_question_item_body)) {
                            $exercise_info['question'][$current_question_ident]['description'] = $current_question_item_body;
                        }
                    } else {
                        $exercise_info['question'][$current_question_ident]['statement'] = $current_question_item_body;
                    }
                }
                break;

            case 'img':
                $exercise_info['question'][$current_question_ident]['attached_file_url'] = $node->getAttribute('src');
                break;

            case 'order':
                $orderType = $node->getAttribute('order_type');

                if (!empty($orderType)) {
                    $exercise_info['order_type'] = $orderType;
                }
                break;

            case 'feedbackInline':
                if (!isset($exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'])) {
                    $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id] = trim(
                        $node->nodeValue
                    );
                } else {
                    $exercise_info['question'][$current_question_ident]['answer'][$current_answer_id]['feedback'] .= ''
                        .trim(
                            $node->nodeValue
                        );
                }
                break;

            case 'value':
                if ('correctResponse' === $node->parentNode->nodeName) {
                    $nodeValue = trim($node->nodeValue);

                    if ('single' === $cardinality) {
                        $exercise_info['question'][$current_question_ident]['correct_answers'][$nodeValue] = $nodeValue;
                    } else {
                        $exercise_info['question'][$current_question_ident]['correct_answers'][] = $nodeValue;
                    }
                }

                if ('outcomeDeclaration' === $node->parentNode->parentNode->nodeName) {
                    $nodeValue = trim($node->nodeValue);

                    if (!empty($nodeValue)) {
                        $exercise_info['question'][$current_question_ident]['weighting'][0] = $nodeValue;
                    }
                }
                break;

            case 'mattext':
                if ('flow_mat' === $node->parentNode->parentNode->nodeName &&
                    ('presentation_material' === $node->parentNode->parentNode->parentNode->nodeName ||
                        'section' === $node->parentNode->parentNode->parentNode->nodeName)
                ) {
                    $nodeValue = trim($node->nodeValue);

                    if (!empty($nodeValue)) {
                        $exercise_info['description'] = $node->nodeValue;
                    }
                }
                break;
        }
    }
}

/**
 * Check if a given file is an IMS/QTI question bank file.
 *
 * @param string $filePath The absolute filepath
 *
 * @return bool Whether it is an IMS/QTI question bank or not
 */
function isQtiQuestionBank($filePath)
{
    $data = file_get_contents($filePath);
    if (!empty($data)) {
        $match = preg_match('/ims_qtiasiv(\d)p(\d)/', $data);
        // @todo allow other types
        //$match2 = preg_match('/imsqti_v(\d)p(\d)/', $data);

        if ($match) {
            return true;
        }
    }

    return false;
}

/**
 * Check if a given file is an IMS/QTI manifest file (listing of extra files).
 *
 * @param string $filePath The absolute filepath
 *
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
 * Processes an IMS/QTI manifest file: store links to new files
 * to be able to transform them into the questions text.
 *
 * @param string $filePath The absolute filepath
 * @param array  $links    List of filepaths changes
 *
 * @return bool
 */
function qtiProcessManifest($filePath)
{
    $xml = simplexml_load_file($filePath);
    $course = api_get_course_info();
    $sessionId = api_get_session_id();
    $courseDir = $course['path'];
    $sysPath = api_get_path(SYS_COURSE_PATH);
    $exercisesSysPath = $sysPath.$courseDir.'/document/';
    $webPath = api_get_path(WEB_CODE_PATH);
    $exercisesWebPath = $webPath.'document/document.php?'.api_get_cidreq().'&action=download&id=';
    $links = [
        'manifest' => [],
        'system' => [],
        'web' => [],
    ];
    $tableDocuments = Database::get_course_table(TABLE_DOCUMENT);
    $countResources = count($xml->resources->resource->file);
    for ($i = 0; $i < $countResources; $i++) {
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
            $links['system'][] = $exercisesSysPath.strtolower($href);
            $specialHref = Database::escape_string(preg_replace('/_/', '-', strtolower($href)));
            $specialHref = preg_replace('/(-){2,8}/', '-', $specialHref);

            $sql = "SELECT iid FROM $tableDocuments 
                    WHERE
                        c_id = ".$course['real_id']." AND 
                        session_id = $sessionId AND 
                        path = '/".$specialHref."'";
            $result = Database::query($sql);
            $documentId = 0;
            while ($row = Database::fetch_assoc($result)) {
                $documentId = $row['iid'];
            }
            $links['web'][] = $exercisesWebPath.$documentId;
        }
    }

    return $links;
}
