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
 * Read a ZIP entry content as string with best-effort compatibility across PhpZip versions.
 *
 * @param ZipFile $zipFile
 * @param string  $entryName
 *
 * @return string
 */
function getZipEntryContent(ZipFile $zipFile, string $entryName): string
{
    try {
        $data = $zipFile[$entryName];
        if (is_string($data)) {
            return $data;
        }
    } catch (Throwable $e) {
        // Ignore and try alternatives
    }

    // Alternatives depending on PhpZip versions
    try {
        if (method_exists($zipFile, 'getEntryContents')) {
            $data = $zipFile->getEntryContents($entryName);
            return is_string($data) ? $data : '';
        }
    } catch (Throwable $e) {
        // Ignore
    }

    try {
        if (method_exists($zipFile, 'getEntryContent')) {
            $data = $zipFile->getEntryContent($entryName);
            return is_string($data) ? $data : '';
        }
    } catch (Throwable $e) {
        // Ignore
    }

    return '';
}

/**
 * Resolve the saved question ID (iid) in a defensive way.
 *
 * @param object     $question
 * @param int|string $saveResult
 *
 * @return int
 */
function resolveSavedQuestionId(object $question, mixed $saveResult = null): int
{
    if (is_numeric($saveResult) && (int) $saveResult > 0) {
        return (int) $saveResult;
    }

    if (method_exists($question, 'getIid')) {
        $iid = (int) $question->getIid();
        if ($iid > 0) {
            return $iid;
        }
    }

    if (method_exists($question, 'getId')) {
        $id = (int) $question->getId();
        if ($id > 0) {
            return $id;
        }
    }

    if (property_exists($question, 'iid') && (int) $question->iid > 0) {
        return (int) $question->iid;
    }

    if (property_exists($question, 'id') && (int) $question->id > 0) {
        return (int) $question->id;
    }

    return 0;
}

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
    $baseName = basename($file);
    $exerciseInfo['name'] = preg_replace('/\.zip$/i', '', $baseName);
    $exerciseInfo['question'] = [];

    // if file is not a .zip, then we cancel all
    if (!preg_match('/\.zip$/i', $file)) {
        return 'UplZipCorrupt';
    }

    $zipFile = new ZipFile();

    try {
        $zipFile->openFile($file);
    } catch (Throwable $e) {
        Display::addFlash(Display::return_message('QTI import: unable to open ZIP file.', 'error'));
        return 'UplZipCorrupt';
    }

    try {
        $zipContentArray = $zipFile->getEntries();

        $fileFound = false;
        $result = false;
        $resourcesLinks = [];

        foreach ($zipContentArray as $entry) {
            $entryName = $entry->getName();

            if ($entry->isDirectory()) {
                continue;
            }

            $data = getZipEntryContent($zipFile, $entryName);
            if ($data === '') {
                continue;
            }

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

        // Create exercise.
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
        $last_exercise_id = (int) $exercise->getId();
        $courseId = (int) api_get_course_int_id();

        if (!empty($last_exercise_id)) {

            // For each question found...
            foreach ($exerciseInfo['question'] as $qtiIdent => $question_array) {
                if (!isset($question_array['type'])) {
                    continue;
                }

                if (!in_array($question_array['type'], [UNIQUE_ANSWER, MULTIPLE_ANSWER, FREE_ANSWER, FIB], true)) {
                    continue;
                }

                // Create question
                $question = new Ims2Question();
                $question->type = $question_array['type'];

                $question->setAnswer();
                $description = '';
                $question->updateTitle(formatText(strip_tags($question_array['title'] ?? '')));

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

                // Save question (capture potential return id if any)
                $saveResult = null;
                try {
                    $saveResult = $question->save($exercise);
                } catch (Throwable $e) {
                    Display::addFlash(Display::return_message('QTI import: failed to save a question.', 'error'));
                    continue;
                }

                $last_question_id = resolveSavedQuestionId($question, $saveResult);

                if ($last_question_id <= 0) {
                    Display::addFlash(Display::return_message('QTI import: question saved without a valid ID.', 'error'));
                    continue;
                }

                // Special handling for Fill in Blanks (FIB): it stores a single answer row.
                if (FIB == $question->type) {
                    [$fibAnswerString, $fibTotalWeight] = buildFibAnswerString($question_array);
                    $question->updateWeighting($fibTotalWeight);
                    try {
                        // Update question weighting in DB
                        $question->save($exercise);
                    } catch (Throwable $e) {
                        Display::addFlash(Display::return_message('QTI import: failed to update FIB question weighting.', 'error'));
                        continue;
                    }

                    try {
                        $fibAnswer = new Answer($last_question_id, $courseId, $exercise, false);
                        $fibAnswer->createAnswer($fibAnswerString, 0, '', 0, 1);
                        $fibAnswer->save();
                    } catch (Throwable $e) {
                        Display::addFlash(Display::return_message('QTI import: failed to save FIB answer row.', 'error'));
                        continue;
                    }

                    // Done for this question
                    continue;
                }

                // Build answers using correct context (do not read from DB)
                $answer = new Answer($last_question_id, $courseId, $exercise, false);

                $answerList = $question_array['answer'] ?? [];
                $correctAnswersRaw = $question_array['correct_answers'] ?? [];
                $correctAnswerIds = is_array($correctAnswersRaw) ? array_values($correctAnswersRaw) : [$correctAnswersRaw];

                $defaultWeight = isset($question_array['default_weighting']) ? (float) $question_array['default_weighting'] : 0.0;
                $totalCorrectWeight = 0.0;

                // Normalize positions 1..N
                $pos = 1;

                if (!empty($answerList) && is_array($answerList)) {
                    foreach ($answerList as $key => $answers) {
                        // Answer text
                        $answerValue = isset($answers['value']) ? formatText($answers['value']) : '';
                        // Comment must be a string to avoid TypeError in entity setter
                        $answerFeedback = isset($answers['feedback']) ? formatText($answers['feedback']) : '';

                        $isCorrect = in_array($key, $correctAnswerIds, true);

                        $weight = $defaultWeight;
                        if (isset($question_array['weighting']) && is_array($question_array['weighting']) && isset($question_array['weighting'][$key])) {
                            $weight = (float) $question_array['weighting'][$key];
                        }

                        $answer->new_answer[$pos] = $answerValue;
                        $answer->new_comment[$pos] = (string) $answerFeedback; // never null
                        $answer->new_position[$pos] = $pos;
                        $answer->new_correct[$pos] = $isCorrect ? 1 : 0; // never null
                        $answer->new_weighting[$pos] = $weight;

                        if ($isCorrect) {
                            $totalCorrectWeight += $weight;
                        }

                        $pos++;
                    }
                }

                $answer->new_nbrAnswers = $pos - 1;

                if (FREE_ANSWER == $question->type) {
                    $totalCorrectWeight = isset($question_array['weighting'][0]) ? (float) $question_array['weighting'][0] : 0.0;
                }

                $question->updateWeighting($totalCorrectWeight);

                try {
                    $question->save($exercise);
                } catch (Throwable $e) {
                    Display::addFlash(Display::return_message('QTI import: failed to update question weighting.', 'error'));
                    // Continue, but do not attempt answer save if question update failed
                    continue;
                }

                // Save answers (safe even if 0 answers for FREE_ANSWER)
                try {
                    $answer->save();
                } catch (Throwable $e) {
                    Display::addFlash(Display::return_message('QTI import: failed to save answers for a question.', 'error'));
                    continue;
                }
            }

            return $last_exercise_id;
        }

        return false;
    } finally {
        try {
            if (method_exists($zipFile, 'close')) {
                $zipFile->close();
            }
        } catch (Throwable $e) {
            // Ignore
        }
    }
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
        Display::addFlash(Display::return_message(get_lang('Error opening XML file'), 'error'));

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
                get_lang('Unsupported IMS/QTI version.'),
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
                    'subtype' => null,
                    'tempdir' => $questionTempDir,
                    'description' => null,
                    'response_text' => null,
                    'fib_options' => [],
                ];

                break;
            case 'section':
                $title = $node->getAttribute('title');

                if (!empty($title)) {
                    $exerciseInfo['name'] = $title;
                }

                break;
            case 'responseDeclaration':
                $currentAnswerId = $node->getAttribute('identifier');
                $cardinalityAttr = $node->getAttribute('cardinality');
                $cardinality = $cardinalityAttr;

                // Legacy Chamilo/Claroline FIB: responseDeclaration id like "fill_1", "fill_2", ...
                if (!empty($currentAnswerId) && 0 === strpos($currentAnswerId, 'fill_')) {
                    $exerciseInfo['question'][$currentQuestionIdent]['type'] = FIB;
                    $exerciseInfo['question'][$currentQuestionIdent]['subtype'] = 'TEXTFIELD_FILL';
                    // Do NOT force MCUA here even if cardinality is "single"
                    break;
                }

                if ('multiple' === $cardinalityAttr) {
                    $exerciseInfo['question'][$currentQuestionIdent]['type'] = MCMA;
                    $cardinality = 'multiple';
                } elseif ('single' === $cardinalityAttr) {
                    $exerciseInfo['question'][$currentQuestionIdent]['type'] = MCUA;
                    $cardinality = 'single';
                }
                break;
            case 'inlineChoiceInteraction':
                $exerciseInfo['question'][$currentQuestionIdent]['type'] = FIB;
                $exerciseInfo['question'][$currentQuestionIdent]['subtype'] = 'LISTBOX_FILL';
                $currentAnswerId = $node->getAttribute('responseIdentifier');

                break;
            case 'inlineChoice':
                $responseId = '';
                if ($node->parentNode && 'inlineChoiceInteraction' === $node->parentNode->nodeName) {
                    $responseId = $node->parentNode->getAttribute('responseIdentifier');
                }

                if ($responseId === '') {
                    break;
                }

                $correctChoiceId = $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$responseId] ?? null;
                $choiceId = $node->getAttribute('identifier');
                $choiceText = trim((string) $node->nodeValue);

                if (!isset($exerciseInfo['question'][$currentQuestionIdent]['fib_options'][$responseId])) {
                    $exerciseInfo['question'][$currentQuestionIdent]['fib_options'][$responseId] = [
                        'correct' => null,
                        'wrongs' => [],
                    ];
                }

                if ($correctChoiceId !== null && $choiceId === $correctChoiceId) {
                    $exerciseInfo['question'][$currentQuestionIdent]['fib_options'][$responseId]['correct'] = $choiceText;
                } else {
                    $exerciseInfo['question'][$currentQuestionIdent]['fib_options'][$responseId]['wrongs'][] = $choiceText;
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
                $currentQuestionItemBody = '';

                /** @var DOMElement $childNode */
                foreach ($node->childNodes as $childNode) {
                    if ('#text' === $childNode->nodeName) {
                        continue;
                    }

                    if (!in_array($childNode->nodeName, $nonHTMLTagToAvoid, true)) {
                        $currentQuestionItemBody .= '<' . $childNode->nodeName;

                        if ($childNode->attributes) {
                            foreach ($childNode->attributes as $attribute) {
                                $currentQuestionItemBody .= ' ' . $attribute->nodeName . '="' . $attribute->nodeValue . '"';
                            }
                        }

                        // Close with the CHILD tag name, not itemBody
                        $currentQuestionItemBody .= '>' . $childNode->nodeValue . '</' . $childNode->nodeName . '>';

                        continue;
                    }

                    if ('inlineChoiceInteraction' === $childNode->nodeName) {
                        $currentQuestionItemBody .= '**claroline_start**' . $childNode->getAttribute('responseIdentifier')
                            . '**claroline_end**';
                        continue;
                    }

                    if ('textEntryInteraction' === $childNode->nodeName) {
                        $rid = $childNode->getAttribute('responseIdentifier');
                        $currentQuestionItemBody .= '**claroline_start**' . $rid . '**claroline_end**';
                        continue;
                    }

                    if ('br' === $childNode->nodeName) {
                        $currentQuestionItemBody .= '<br>';
                    }
                }

                // Append firstChild text ONLY if it's real (avoid "\n  " from empty itemBody)
                if ($node->firstChild && '#text' === $node->firstChild->nodeName) {
                    $firstText = trim((string) $node->firstChild->nodeValue);
                    if ($firstText !== '') {
                        $currentQuestionItemBody .= $firstText;
                    }
                }

                // Replace relative links by links to the documents in the course
                // $resourcesLinks is only defined by qtiProcessManifest()
                if (isset($resourcesLinks, $resourcesLinks['manifest'], $resourcesLinks['web'])) {
                    foreach ($resourcesLinks['manifest'] as $key => $value) {
                        $currentQuestionItemBody = preg_replace('|'.$value.'|', $resourcesLinks['web'][$key], $currentQuestionItemBody);
                    }
                }

                if (FIB == $exerciseInfo['question'][$currentQuestionIdent]['type']) {
                    $candidate = (string) $currentQuestionItemBody;

                    // Only overwrite response_text if itemBody actually carries meaningful content
                    $hasPlaceholders = (false !== strpos($candidate, '**claroline_start**'));
                    $hasRealContent = (trim(strip_tags($candidate)) !== '');

                    if ($hasPlaceholders || $hasRealContent) {
                        $exerciseInfo['question'][$currentQuestionIdent]['response_text'] = $candidate;
                    }
                    // else: keep the response_text already captured from <correctResponse><value> (your legacy export case)
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

                    // Legacy FIB exports: the whole statement + "::weights:sizes:sep@" is stored here
                    if (!empty($currentAnswerId) && 0 === strpos($currentAnswerId, 'fill_')) {
                        $exerciseInfo['question'][$currentQuestionIdent]['type'] = FIB;
                        $exerciseInfo['question'][$currentQuestionIdent]['subtype'] = $exerciseInfo['question'][$currentQuestionIdent]['subtype'] ?? 'TEXTFIELD_FILL';

                        $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$currentAnswerId] = $nodeValue;

                        // Because <itemBody> is empty in this export, use value as response_text
                        if (empty($exerciseInfo['question'][$currentQuestionIdent]['response_text'])) {
                            $exerciseInfo['question'][$currentQuestionIdent]['response_text'] = $nodeValue;
                        }
                    } else {
                        // Default behavior for other question types
                        if ('single' === $cardinality) {
                            $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][$nodeValue] = $nodeValue;
                        } else {
                            $exerciseInfo['question'][$currentQuestionIdent]['correct_answers'][] = $nodeValue;
                        }
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

    // Post-process FIB placeholders (markers) into Chamilo's "[...]" format.
    foreach ($exerciseInfo['question'] as $ident => &$q) {
        if (!isset($q['type']) || FIB != $q['type']) {
            continue;
        }

        $text = isset($q['response_text']) ? (string) $q['response_text'] : '';
        if ($text === '') {
            continue;
        }

        $subtype = $q['subtype'] ?? null;

        if ('LISTBOX_FILL' === $subtype) {
            $opts = $q['fib_options'] ?? [];
            foreach ($opts as $responseId => $data) {
                $correct = isset($data['correct']) ? (string) $data['correct'] : '';
                $wrongs = isset($data['wrongs']) && is_array($data['wrongs']) ? $data['wrongs'] : [];

                $final = [];
                if ($correct !== '') {
                    $final[] = $correct;
                }
                foreach ($wrongs as $w) {
                    $w = trim((string) $w);
                    if ($w === '' || $w === $correct) {
                        continue;
                    }
                    $final[] = $w;
                }

                $replacement = '['.implode('|', $final).']';
                $marker = '**claroline_start**'.$responseId.'**claroline_end**';
                $text = str_replace($marker, $replacement, $text);
            }
        } else {
            // TEXTFIELD_FILL: use correct answer text
            $corrects = $q['correct_answers'] ?? [];
            if (is_array($corrects)) {
                foreach ($corrects as $responseId => $answerValue) {
                    if (0 !== strpos((string) $responseId, 'fill_')) {
                        continue;
                    }
                    $marker = '**claroline_start**'.$responseId.'**claroline_end**';
                    $text = str_replace($marker, '['.trim((string) $answerValue).']', $text);
                }
            }
        }

        // Replace any leftover markers with empty blanks to avoid broken text.
        $text = preg_replace('/\*\*claroline_start\*\*fill_[^*]+\*\*claroline_end\*\*/', '[]', $text);

        $q['response_text'] = $text;
    }
    unset($q);
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
    if (!is_string($data) || $data === '') {
        return false;
    }

    return (bool) preg_match('/ims_qtiasiv(\d)p(\d)/', $data);
}

/**
 * Check if a given file is an IMS/QTI manifest file (listing of extra files).
 *
 * @param string $filePath The absolute filepath
 *
 * @return bool Whether it is an IMS/QTI manifest file or not
 */
function isQtiManifest($data): bool
{
    if (!is_string($data) || $data === '') {
        return false;
    }
    return (bool) preg_match('/imsccv(\d)p(\d)/', $data);
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

function parseExistingFibMeta(string $text): ?array
{
    $text = trim($text);

    $pos = strrpos($text, '::');
    if (false === $pos) {
        return null;
    }

    $tail = substr($text, $pos + 2);

    // Accept both "...:0@" and "...:0@0"
    if (preg_match('/^([0-9]+(?:\.[0-9]+)?(?:,[0-9]+(?:\.[0-9]+)?)*)\:([0-9]+(?:,[0-9]+)*)\:(\d+)@(\d+)$/', $tail, $m)) {
        $weights = array_map('floatval', explode(',', $m[1]));
        return [$text, array_sum($weights)];
    }

    if (preg_match('/^([0-9]+(?:\.[0-9]+)?(?:,[0-9]+(?:\.[0-9]+)?)*)\:([0-9]+(?:,[0-9]+)*)\:(\d+)@$/', $tail, $m)) {
        // Missing switchable flag -> normalize to @0
        $weights = array_map('floatval', explode(',', $m[1]));
        return [$text.'0', array_sum($weights)];
    }

    return null;
}

/**
 * Build Chamilo FillBlanks answer string (single row in c_quiz_answer).
 *
 * Format:
 *   <html with [blank]...>::w1,w2:sz1,sz2:separator@switchable
 *
 * @return array{0:string,1:float} [answerString, totalWeight]
 */
function buildFibAnswerString(array $questionArray): array
{
    $text = (string) ($questionArray['response_text'] ?? '');
    $text = trim($text);
    $looksEmptyOrWrong =
        ($text === '') ||
        (false === strpos($text, '[') && false === strpos($text, '::') && false === strpos($text, '**claroline_start**'));

    if ($looksEmptyOrWrong && isset($questionArray['correct_answers']) && is_array($questionArray['correct_answers'])) {
        foreach ($questionArray['correct_answers'] as $k => $v) {
            if (0 === strpos((string) $k, 'fill_')) {
                $text = trim((string) $v);
                break;
            }
        }
    }

    $text = formatText($text);
    $text = api_preg_replace("/\xc2\xa0/", ' ', $text);
    $text = trim($text);

    $existing = parseExistingFibMeta($text);
    if (null !== $existing) {
        return $existing;
    }

    // Only now do the safe cleanup for normal builds
    $text = str_replace('::', '', $text);

    $text = api_preg_replace("/\xc2\xa0/", ' ', $text);

    $weightsMap = $questionArray['weighting'] ?? [];
    if (!is_array($weightsMap)) {
        $weightsMap = [];
    }

    $nb = preg_match_all('/\[[^\]]*\]/', $text, $m);
    $weights = [];
    $sizes = [];
    $total = 0.0;

    if ($nb && !empty($m[0])) {
        foreach ($m[0] as $blankRaw) {
            $inside = trim((string) $blankRaw, '[]');

            // For menu blanks: "Correct|Wrong1|Wrong2" (correct must be first)
            // For several answers: "Correct||Alt1||Alt2" (first is the reference)
            $correct = $inside;
            if (false !== strpos($correct, '||')) {
                $parts = explode('||', $correct);
                $correct = $parts[0] ?? $correct;
            } elseif (false !== strpos($correct, '|')) {
                $parts = explode('|', $correct);
                $correct = $parts[0] ?? $correct;
            }
            $correct = trim((string) $correct);

            $w = 1.0;
            if ($correct !== '' && array_key_exists($correct, $weightsMap)) {
                $w = (float) $weightsMap[$correct];
            }

            $weights[] = $w;
            $sizes[] = 200; // default input size
            $total += $w;
        }

        $text .= '::'.implode(',', $weights).':'.implode(',', $sizes);
    }

    // Separator 0 means "[...]" and switchable defaults to 0
    $text .= ':0@0';

    return [$text, $total];
}
