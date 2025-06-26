<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use PhpZip\ZipFile;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 * @author claro team <cvs@claroline.net>
 * @author Guillaume Lederer <guillaume@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */

/**
 * Imports an exercise in QTI format if the XML structure can be found in it.
 *
 * @param string $file
 *
 * @return string|array as a backlog of what was really imported, and error or debug messages to display
 */
function import_exercise($file)
{
    global $exerciseInfo;
    global $resourcesLinks;

    // set some default values for the new exercise
    $exerciseInfo = [];
    $exerciseInfo['name'] = preg_replace('/.zip$/i', '', $file);
    $exerciseInfo['question'] = [];

    // if file is not a .zip, then we cancel all
    if (!preg_match('/.zip$/i', $file)) {
        return 'UplZipCorrupt';
    }

    $zipFile = new ZipFile();
    $zipFile->openFile($file);
    $zipContentArray = $zipFile->getEntries();

    $fileFound = false;
    $result = false;
    $filePath = null;
    $resourcesLinks = [];

    // parse every subdirectory to search xml question files and other assets to be imported
    // The assets-related code is a bit fragile as it has to deal with files renamed by Chamilo and it only works if
    // the imsmanifest.xml file is read.
    foreach ($zipContentArray as $item) {
        $file = $item->getName();

        if ($item->isDirectory()) {
            continue;
        }

        $data = $item->getData();

        $isQti = isQtiQuestionBank($data);
        if ($isQti) {
            $result = qti_parse_file($data);
            $fileFound = true;
        } else {
            $isManifest = isQtiManifest($data);
            if ($isManifest) {
                $resourcesLinks = qtiProcessManifest($data);
            }
        }
    }

    if (!$fileFound) {
        return 'NoXMLFileFoundInTheZip';
    }

    if (false == $result) {
        return false;
    }

    // 1. Create exercise.
    $exercise = new Exercise();
    $exercise->exercise = $exerciseInfo['name'];

    // Random QTI support
    if (isset($exerciseInfo['order_type'])) {
        if ('Random' === $exerciseInfo['order_type']) {
            $exercise->setQuestionSelectionType(2);
            $exercise->random = -1;
        }
    }

    if (!empty($exerciseInfo['description'])) {
        $exercise->updateDescription(formatText(strip_tags($exerciseInfo['description'])));
    }

    $exercise->save();
    $last_exercise_id = $exercise->getId();
    $courseId = api_get_course_int_id();
    if (!empty($last_exercise_id)) {
        // For each question found...
        foreach ($exerciseInfo['question'] as $question_array) {
            if (!in_array($question_array['type'], [UNIQUE_ANSWER, MULTIPLE_ANSWER, FREE_ANSWER])) {
                continue;
            }
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

            $last_question_id = $question->getId();
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

            if (FREE_ANSWER == $question->type) {
                $totalCorrectWeight = $question_array['weighting'][0];
            }

            $question->updateWeighting($totalCorrectWeight);
            $question->save($exercise);
            $answer->save();
        }

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
function qti_parse_file($data)
{
    global $record_item_body;
    global $questionTempDir;

    if (empty($data)) {
        Display::addFlash(Display::return_message(get_lang('Error opening question\'s XML file'), 'error'));

        return false;
    }

    //parse XML question file
    //$data = str_replace(array('<p>', '</p>', '<front>', '</front>'), '', $data);
    $data = ChamiloHelper::stripGivenTags($data, ['p', 'front']);
    $qtiVersion = [];
    $match = preg_match('/ims_qtiasiv(\d)p(\d)/', $data, $qtiVersion);
    $qtiMainVersion = 2; //by default, assume QTI version 2
    if ($match) {
        $qtiMainVersion = $qtiVersion[1];
    }

    //used global variable start values declaration:
    $record_item_body = false;

    if (2 != $qtiMainVersion) {
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
 * Function used to parser a QTI2 xml file.
 *
 * @param string $xmlData
 */
function parseQti2($xmlData)
{
    global $exerciseInfo;
    global $questionTempDir;
    global $resourcesLinks;

    $crawler = new Crawler($xmlData);
    $nodes = $crawler->filter('*');

    $currentQuestionIdent = '';
    $currentAnswerId = '';
    $currentQuestionItemBody = '';
    $cardinality = '';
    $nonHTMLTagToAvoid = [
        'prompt',
        'simpleChoice',
        'choiceInteraction',
        'inlineChoiceInteraction',
        'inlineChoice',
        'soMPLEMATCHSET',
        'simpleAssociableChoice',
        'textEntryInteraction',
        'feedbackInline',
        'matchInteraction',
        'extendedTextInteraction',
        'itemBody',
        'br',
        'img',
    ];
    $currentMatchSet = null;

    /** @var DOMElement $node */
    foreach ($nodes as $node) {
        if ('#text' === $node->nodeName) {
            continue;
        }

        switch ($node->nodeName) {
            case 'assessmentItem':
                $currentQuestionIdent = $node->getAttribute('identifier');

                $exerciseInfo['question'][$currentQuestionIdent] = [
                    'answer' => [],
                    'correct_answers' => [],
                    'title' => $node->getAttribute('title'),
                    'category' => $node->getAttribute('category'),
                    'type' => '',
                    'tempdir' => $questionTempDir,
                    'description' => null,
                ];

                break;
            case 'section':
                $title = $node->getAttribute('title');

                if (!empty($title)) {
                    $exerciseInfo['name'] = $title;
                }

                break;
            case 'responseDeclaration':
                if ('multiple' === $node->getAttribute('cardinality')) {
                    $exerciseInfo['question'][$currentQuestionIdent]['type'] = MCMA;
                    $cardinality = 'multiple';
                }

                if ('single' === $node->getAttribute('cardinality')) {
                    $exerciseInfo['question'][$currentQuestionIdent]['type'] = MCUA;
                    $cardinality = 'single';
                }

                $currentAnswerId = $node->getAttribute('identifier');

                break;
            case 'inlineChoiceInteraction':
                $exerciseInfo['question'][$currentQuestionIdent]['type'] = FIB;
                $exerciseInfo['question'][$currentQuestionIdent]['subtype'] = 'LISTBOX_FILL';
                $currentAnswerId = $node->getAttribute('responseIdentifier');

                break;
            case 'inlineChoice':
                $answerIdentifier = $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$currentAnswerId];

                if ($node->getAttribute('identifier') == $answerIdentifier) {
                    $currentQuestionItemBody = str_replace(
                        '**claroline_start**'.$currentAnswerId.'**claroline_end**',
                        '['.$node->nodeValue.']',
                        $currentQuestionItemBody
                    );
                } else {
                    if (!isset($exerciseInfo['question'][$currentQuestionIdent]['wrong_answers'])) {
                        $exerciseInfo['question'][$currentQuestionIdent]['wrong_answers'] = [];
                    }

                    $exerciseInfo['question'][$currentQuestionIdent]['wrong_answers'][] = $node->nodeValue;
                }

                break;
            case 'textEntryInteraction':
                $exerciseInfo['question'][$currentQuestionIdent]['type'] = FIB;
                $exerciseInfo['question'][$currentQuestionIdent]['subtype'] = 'TEXTFIELD_FILL';
                $exerciseInfo['question'][$currentQuestionIdent]['response_text'] = $currentQuestionItemBody;

                break;
            case 'matchInteraction':
                $exerciseInfo['question'][$currentQuestionIdent]['type'] = MATCHING;

                break;
            case 'extendedTextInteraction':
                $exerciseInfo['question'][$currentQuestionIdent]['type'] = FREE_ANSWER;
                $exerciseInfo['question'][$currentQuestionIdent]['description'] = $node->nodeValue;

                break;
            case 'simpleMatchSet':
                if (!isset($currentMatchSet)) {
                    $currentMatchSet = 1;
                } else {
                    $currentMatchSet++;
                }
                $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentMatchSet] = [];

                break;
            case 'simpleAssociableChoice':
                $currentAssociableChoice = $node->getAttribute('identifier');

                $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentMatchSet][$currentAssociableChoice] = trim($node->nodeValue);

                break;
            case 'simpleChoice':
                $currentAnswerId = $node->getAttribute('identifier');
                if (!isset($exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId])) {
                    $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId] = [];
                }

                //$simpleChoiceValue = $node->nodeValue;
                $simpleChoiceValue = '';
                /** @var DOMElement $childNode */
                foreach ($node->childNodes as $childNode) {
                    if ('feedbackInline' === $childNode->nodeName) {
                        continue;
                    }
                    $simpleChoiceValue .= $childNode->nodeValue;
                }
                $simpleChoiceValue = trim($simpleChoiceValue);
                if (!isset($exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['value'])) {
                    $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['value'] = $simpleChoiceValue;
                } else {
                    $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['value'] .= $simpleChoiceValue;
                }

                break;
            case 'mapEntry':
                if (in_array($node->parentNode->nodeName, ['mapping', 'mapEntry'])) {
                    $answer_id = $node->getAttribute('mapKey');

                    if (!isset($exerciseInfo['question'][$currentQuestionIdent]['weighting'])) {
                        $exerciseInfo['question'][$currentQuestionIdent]['weighting'] = [];
                    }

                    $exerciseInfo['question'][$currentQuestionIdent]['weighting'][$answer_id] = $node->getAttribute(
                        'mappedValue'
                    );
                }

                break;
            case 'mapping':
                $defaultValue = $node->getAttribute('defaultValue');
                if (!empty($defaultValue)) {
                    $exerciseInfo['question'][$currentQuestionIdent]['default_weighting'] = $defaultValue;
                }
                // no break ?
            case 'itemBody':
                $nodeValue = $node->nodeValue;
                $currentQuestionItemBody = '';

                /** @var DOMElement $childNode */
                foreach ($node->childNodes as $childNode) {
                    if ('#text' === $childNode->nodeName) {
                        continue;
                    }

                    if (!in_array($childNode->nodeName, $nonHTMLTagToAvoid)) {
                        $currentQuestionItemBody .= '<'.$childNode->nodeName;

                        if ($childNode->attributes) {
                            foreach ($childNode->attributes as $attribute) {
                                $currentQuestionItemBody .= ' '.$attribute->nodeName.'="'.$attribute->nodeValue.'"';
                            }
                        }

                        $currentQuestionItemBody .= '>'.$childNode->nodeValue.'</'.$node->nodeName.'>';

                        continue;
                    }

                    if ('inlineChoiceInteraction' === $childNode->nodeName) {
                        $currentQuestionItemBody .= '**claroline_start**'.$childNode->getAttribute('responseIdentifier')
                            .'**claroline_end**';

                        continue;
                    }

                    if ('textEntryInteraction' === $childNode->nodeName) {
                        $correct_answer_value = $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$currentAnswerId];
                        $currentQuestionItemBody .= '['.$correct_answer_value.']';

                        continue;
                    }

                    if ('br' === $childNode->nodeName) {
                        $currentQuestionItemBody .= '<br>';
                    }
                }

                // Replace relative links by links to the documents in the course
                // $resourcesLinks is only defined by qtiProcessManifest()
                if (isset($resourcesLinks) && isset($resourcesLinks['manifest']) && isset($resourcesLinks['web'])) {
                    foreach ($resourcesLinks['manifest'] as $key => $value) {
                        $nodeValue = preg_replace('|'.$value.'|', $resourcesLinks['web'][$key], $nodeValue);
                    }
                }

                $currentQuestionItemBody .= $node->firstChild->nodeValue;

                if (FIB == $exerciseInfo['question'][$currentQuestionIdent]['type']) {
                    $exerciseInfo['question'][$currentQuestionIdent]['response_text'] = $currentQuestionItemBody;
                } else {
                    if (FREE_ANSWER == $exerciseInfo['question'][$currentQuestionIdent]['type']) {
                        $currentQuestionItemBody = trim($currentQuestionItemBody);

                        if (!empty($currentQuestionItemBody)) {
                            $exerciseInfo['question'][$currentQuestionIdent]['description'] = $currentQuestionItemBody;
                        }
                    } else {
                        $exerciseInfo['question'][$currentQuestionIdent]['statement'] = $currentQuestionItemBody;
                    }
                }

                break;
            case 'img':
                $exerciseInfo['question'][$currentQuestionIdent]['attached_file_url'] = $node->getAttribute('src');

                break;
            case 'order':
                $orderType = $node->getAttribute('order_type');

                if (!empty($orderType)) {
                    $exerciseInfo['order_type'] = $orderType;
                }

                break;
            case 'feedbackInline':
                if (!isset($exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['feedback'])) {
                    $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['feedback'] = trim(
                        $node->nodeValue
                    );
                } else {
                    $exerciseInfo['question'][$currentQuestionIdent]['answer'][$currentAnswerId]['feedback'] .= trim(
                        $node->nodeValue
                    );
                }

                break;
            case 'value':
                if ('correctResponse' === $node->parentNode->nodeName) {
                    $nodeValue = trim($node->nodeValue);

                    if ('single' === $cardinality) {
                        $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$nodeValue] = $nodeValue;
                    } else {
                        $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][] = $nodeValue;
                    }
                }

                if ('outcomeDeclaration' === $node->parentNode->parentNode->nodeName) {
                    $nodeValue = trim($node->nodeValue);

                    if (!empty($nodeValue)) {
                        $exerciseInfo['question'][$currentQuestionIdent]['weighting'][0] = $nodeValue;
                    }
                }

                break;
            case 'mattext':
                if ('flow_mat' === $node->parentNode->parentNode->nodeName &&
                    ('presentation_material' === $node->parentNode->parentNode->parentNode->nodeName ||
                        'section' === $node->parentNode->parentNode->parentNode->nodeName
                    )
                ) {
                    $nodeValue = trim($node->nodeValue);

                    if (!empty($nodeValue)) {
                        $exerciseInfo['description'] = $node->nodeValue;
                    }
                }
                break;
            case 'prompt':
                $description = trim($node->nodeValue);
                $description = htmlspecialchars_decode($description);
                $description = Security::remove_XSS($description);

                if (!empty($description)) {
                    $exerciseInfo['question'][$currentQuestionIdent]['description'] = $description;
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
function isQtiQuestionBank($data)
{
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
function isQtiManifest($data)
{
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
 *
 * @return bool
 */
function qtiProcessManifest($data)
{
    $xml = simplexml_load_string($data);
    $course = api_get_course_info();
    $sessionId = api_get_session_id();
    $exercisesSysPath = '/';
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
            if ('href' == $key) {
                if ('xml' != substr($value, -3, 3)) {
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
