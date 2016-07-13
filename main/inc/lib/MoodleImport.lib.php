<?php
/* For licensing terms, see /license.txt */

/**
 * Class MoodleImport
 *
 * @author José Loguercio <jose.loguercio@beeznest.com>
 * @package chamilo.library
 */

class MoodleImport
{
    /**
     * Read and validate the moodleFile
     *
     * @param resource $uploadedFile *.* mbz file moodle course backup
     * @return bool
     */
    public function readMoodleFile($uploadedFile)
    {
        $file = $uploadedFile['tmp_name'];

        if (is_file($file) && is_readable($file)) {
            $package = new PclZip($file);
            $packageContent = $package->listContent();
            $mainFileKey = 0;
            foreach ($packageContent as $index => $value) {
                if ($value['filename'] == 'moodle_backup.xml') {
                    $mainFileKey = $index;
                    break;
                }
            }

            if (!$mainFileKey) {
                Display::addFlash(Display::return_message(get_lang('FailedToImportThisIsNotAMoodleFile'), 'error'));
            }

            $folder = api_get_unique_id();
            $destinationDir = api_get_path(SYS_ARCHIVE_PATH).$folder;
            $coursePath = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/';
            $courseInfo = api_get_course_info();

            mkdir($destinationDir, api_get_permissions_for_new_directories(), true);

            $package->extract(
                PCLZIP_OPT_PATH,
                $destinationDir
            );

            $xml = @file_get_contents($destinationDir.'/moodle_backup.xml');

            $doc = new DOMDocument();
            $res = @$doc->loadXML($xml);
            if ($res) {
                $activities = $doc->getElementsByTagName('activity');
                foreach ($activities as $activity) {
                    if ($activity->childNodes->length) {
                        $currentItem = [];

                        foreach($activity->childNodes as $item) {
                            $currentItem[$item->nodeName] = $item->nodeValue;
                        }

                        $moduleName = isset($currentItem['modulename']) ? $currentItem['modulename'] : false;
                        switch ($moduleName) {
                            case 'forum':
                                require_once '../forum/forumfunction.inc.php';
                                $catForumValues = [];

                                // Read the current forum module xml.
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                                $moduleValues = $this->readForumModule($moduleXml);

                                // Create a Forum category based on Moodle forum type.
                                $catForumValues['forum_category_title'] = $moduleValues['type'];
                                $catForumValues['forum_category_comment'] = '';
                                $catId = store_forumcategory($catForumValues);
                                $forumValues = [];
                                $forumValues['forum_title'] = $moduleValues['name'];
                                $forumValues['forum_image'] = '';
                                $forumValues['forum_comment'] = $moduleValues['intro'];
                                $forumValues['forum_category'] = $catId;

                                store_forum($forumValues);
                                break;
                            case 'quiz':

                                // Read the current quiz module xml.
                                // The quiz case is the very complicate process of all the import.
                                // Please if you want to review the script, try to see the readingXML functions.
                                // The readingXML functions in this clases do all the mayor work here.

                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                                $questionsXml = @file_get_contents($destinationDir.'/questions.xml');
                                $moduleValues = $this->readQuizModule($moduleXml);

                                // At this point we got all the prepared resources from Moodle file
                                // $moduleValues variable contains all the necesary info to the quiz import
                                // var_dump($moduleValues); // <-- uncomment this to see the final array

                                // Lets do this ...
                                $exercise = new Exercise();
                                $exercise->updateTitle(Exercise::format_title_variable($moduleValues['name']));
                                $exercise->updateDescription($moduleValues['intro']);
                                $exercise->updateAttempts($moduleValues['attempts_number']);
                                $exercise->updateFeedbackType(0);

                                // Match shuffle question with chamilo
                                switch ($moduleValues['shufflequestions']) {
                                    case '0':
                                        $exercise->setRandom(0);
                                        break;
                                    case '1':
                                        $exercise->setRandom(-1);
                                        break;
                                    default:
                                        $exercise->setRandom(0);
                                }
                                $exercise->updateRandomAnswers($moduleValues['shuffleanswers']);
                                // @todo divide to minutes
                                $exercise->updateExpiredTime($moduleValues['timelimit']);

                                if ($moduleValues['questionsperpage'] == 1) {
                                    $exercise->updateType(2);
                                } else {
                                    $exercise->updateType(1);
                                }

                                // Create the new Quiz
                                $exercise->save();

                                // Ok, we got the Quiz and create it, now its time to add the Questions
                                foreach ($moduleValues['question_instances'] as $index => $question) {
                                    $questionsValues = $this->readMainQuestionsXml($questionsXml, $question['questionid']);
                                    $moduleValues['question_instances'][$index] = $questionsValues;
                                    // Set Question Type from Moodle XML element <qtype>
                                    $qType = $moduleValues['question_instances'][$index]['qtype'];
                                    // Add the matched chamilo question type to the array
                                    $moduleValues['question_instances'][$index]['chamilo_qtype'] = $this->matchMoodleChamiloQuestionTypes($qType);
                                    $questionInstance = Question::getInstance($moduleValues['question_instances'][$index]['chamilo_qtype']);
                                    if ($questionInstance) {
                                        $questionInstance->updateTitle($moduleValues['question_instances'][$index]['name']);
                                        $questionInstance->updateDescription($moduleValues['question_instances'][$index]['questiontext']);
                                        $questionInstance->updateLevel(1);
                                        $questionInstance->updateCategory(0);

                                        //Save normal question if NOT media
                                        if ($questionInstance->type != MEDIA_QUESTION) {
                                            $questionInstance->save($exercise->id);

                                            // modify the exercise
                                            $exercise->addToList($questionInstance->id);
                                            $exercise->update_question_positions();
                                        }

                                        $questionList = $moduleValues['question_instances'][$index]['plugin_qtype_'.$qType.'_question'];
                                        $currentQuestion = $moduleValues['question_instances'][$index];

                                        $result = $this->processAnswers($questionList, $qType, $questionInstance, $currentQuestion);
                                    }
                                }

                                break;
                            case 'resource':
                                // Read the current resource module xml.
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                                $filesXml = @file_get_contents($destinationDir.'/files.xml');
                                $moduleValues = $this->readResourceModule($moduleXml);
                                $mainFileModuleValues = $this->readMainFilesXml($filesXml, $moduleValues['contextid']);
                                $fileInfo = array_merge($moduleValues, $mainFileModuleValues, $currentItem);
                                $documentPath = $coursePath.'document/';
                                $currentResourceFilePath = $destinationDir.'/files/';
                                $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
                                foreach(new RecursiveIteratorIterator($dirs) as $file) {
                                    if (is_file($file) && strpos($file, $fileInfo['contenthash']) !== false) {
                                        $files = [];
                                        $files['file']['name'] = $fileInfo['filename'];
                                        $files['file']['tmp_name'] = $file->getPathname();
                                        $files['file']['type'] = $fileInfo['mimetype'];
                                        $files['file']['error'] = 0;
                                        $files['file']['size'] = $fileInfo['filesize'];
                                        $files['file']['from_file'] = true;
                                        $files['file']['move_file'] = true;
                                        $_POST['language'] = $courseInfo['language'];
                                        $_POST['moodle_import'] = true;

                                        DocumentManager::upload_document(
                                            $files,
                                            '/',
                                            $fileInfo['title'],
                                            '',
                                            null,
                                            null,
                                            true,
                                            true
                                        );
                                    }
                                }

                                break;
                            case 'url':
                                // Read the current url module xml.
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                                $moduleValues = $this->readUrlModule($moduleXml);
                                $_POST['title'] = $moduleValues['name'];
                                $_POST['url'] = $moduleValues['externalurl'];
                                $_POST['description'] = $moduleValues['intro'];
                                $_POST['category_id'] = 0;
                                $_POST['target'] = '_blank';

                                Link::addlinkcategory("link");
                                break;
                        }
                    }
                }
            } else {
                return false;
            }
        } else {
            return false;
        }

        removeDir($destinationDir);
        return $packageContent[$mainFileKey];
    }

    /**
     * Read and validate the forum module XML
     *
     * @param resource $moduleXml XML file
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readForumModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('forum');
            $currentItem = [];
            foreach ($activities as $activity) {
                if ($activity->childNodes->length) {
                    foreach ($activity->childNodes as $item) {
                        $currentItem[$item->nodeName] = $item->nodeValue;
                    }
                }
            }

            return $currentItem;
        }

        return false;
    }

    /**
     * Read and validate the resource module XML
     *
     * @param resource $moduleXml XML file
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readResourceModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('resource');
            $mainActivity = $moduleDoc->getElementsByTagName('activity');
            $contextId = $mainActivity->item(0)->getAttribute('contextid');
            $currentItem = [];
            foreach ($activities as $activity) {
                if ($activity->childNodes->length) {
                    foreach($activity->childNodes as $item) {
                        $currentItem[$item->nodeName] = $item->nodeValue;
                    }
                }
            }

            $currentItem['contextid'] = $contextId;
            return $currentItem;
        }

        return false;
    }

    /**
     * Read and validate the url module XML
     *
     * @param resource $moduleXml XML file
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readUrlModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('url');
            $currentItem = [];
            foreach ($activities as $activity) {
                if ($activity->childNodes->length) {
                    foreach ($activity->childNodes as $item) {
                        $currentItem[$item->nodeName] = $item->nodeValue;
                    }
                }
            }

            return $currentItem;
        }

        return false;
    }

    /**
     * Read and validate the quiz module XML
     *
     * @param resource $moduleXml XML file
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readQuizModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('quiz');
            $currentItem = [];
            foreach ($activities as $activity) {
                if ($activity->childNodes->length) {
                    foreach ($activity->childNodes as $item) {
                        $currentItem[$item->nodeName] = $item->nodeValue;
                    }
                }
            }

            $questions = $moduleDoc->getElementsByTagName('question_instance');

            $questionList = [];
            $counter = 0;
            foreach ($questions as $question) {
                if ($question->childNodes->length) {
                    foreach ($question->childNodes as $item) {
                        $questionList[$counter][$item->nodeName] = $item->nodeValue;
                    }
                    $counter++;
                }

            }
            $currentItem['question_instances'] = $questionList;
            return $currentItem;
        }

        return false;
    }

    /**
     * Search the current file resource in main Files XML
     *
     * @param resource $filesXml XML file
     * @param int $contextId
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readMainFilesXml($filesXml, $contextId)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($filesXml);
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('file');
            $currentItem = [];
            foreach ($activities as $activity) {
                if ($activity->childNodes->length) {
                    $isThisItemThatIWant = false;
                    foreach($activity->childNodes as $item) {
                        if (!$isThisItemThatIWant && $item->nodeName == 'contenthash') {
                            $currentItem['contenthash'] = $item->nodeValue;
                        }
                        if ($item->nodeName == 'contextid' && intval($item->nodeValue) == intval($contextId) && !$isThisItemThatIWant) {
                            $isThisItemThatIWant = true;
                            continue;
                        }

                        if ($isThisItemThatIWant && $item->nodeName == 'filename') {
                            $currentItem['filename'] = $item->nodeValue;
                        }

                        if ($isThisItemThatIWant && $item->nodeName == 'filesize') {
                            $currentItem['filesize'] = $item->nodeValue;
                        }

                        if ($isThisItemThatIWant && $item->nodeName == 'mimetype' && $item->nodeValue == 'document/unknown') {
                            break;
                        }

                        if ($isThisItemThatIWant && $item->nodeName == 'mimetype' && $item->nodeValue !== 'document/unknown') {
                            $currentItem['mimetype'] = $item->nodeValue;
                            break 2;
                        }
                    }
                }
            }

            return $currentItem;
        }

        return false;
    }

    /**
     * Search the current quiestion resource in main Questions XML
     *
     * @param resource $questionsXml XML file
     * @param int $questionId
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readMainQuestionsXml($questionsXml, $questionId)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($questionsXml);
        if ($moduleRes) {
            $questions = $moduleDoc->getElementsByTagName('question');
            $currentItem = [];
            foreach ($questions as $question) {
                if (intval($question->getAttribute('id')) == $questionId) {
                    if ($question->childNodes->length) {
                        $currentItem['questionid'] = $questionId;
                        $questionType = '';
                        foreach($question->childNodes as $item) {
                            $currentItem[$item->nodeName] = $item->nodeValue;
                            if ($item->nodeName == 'qtype') {
                                $questionType = $item->nodeValue;
                            }

                            if ($item->nodeName == 'plugin_qtype_'.$questionType.'_question') {
                                $answer = $item->getElementsByTagName($this->getQuestionTypeAnswersTag($questionType));
                                $currentItem['plugin_qtype_'.$questionType.'_question'] = [];
                                for ($i = 0; $i <= $answer->length - 1; $i++) {
                                    $currentItem['plugin_qtype_'.$questionType.'_question'][$i]['answerid'] = $answer->item($i)->getAttribute('id');
                                    foreach ($answer->item($i)->childNodes as $properties) {
                                        $currentItem['plugin_qtype_'.$questionType.'_question'][$i][$properties->nodeName] = $properties->nodeValue;
                                    }
                                }

                                $typeValues = $item->getElementsByTagName($this->getQuestionTypeOptionsTag($questionType));
                                for ($i = 0; $i <= $typeValues->length - 1; $i++) {
                                    foreach ($typeValues->item($i)->childNodes as $properties) {
                                        $currentItem[$questionType.'_values'][$properties->nodeName] = $properties->nodeValue;
                                        if ($properties->nodeName == 'sequence') {
                                            $sequence = $properties->nodeValue;
                                            $sequenceIds = explode(',', $sequence);
                                            foreach ($sequenceIds as $qId) {
                                                $questionMatch = $this->readMainQuestionsXml($questionsXml, $qId);
                                                $currentItem['plugin_qtype_'.$questionType.'_question'][] = $questionMatch;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $this->traverseArray($currentItem, ['#text', 'question_hints', 'tags']);
            return $currentItem;
        }

        return false;
    }

    /**
     * return the correct question type options tag
     *
     * @param string $questionType name
     * @return string question type tag
     */
    public function getQuestionTypeOptionsTag($questionType)
    {
        switch ($questionType) {
            case 'match':
            case 'ddmatch':
                return 'matchoptions';
                break;
            default:
                return $questionType;
                break;
        }
    }

    /**
     * return the correct question type answers tag
     *
     * @param string $questionType name
     * @return string question type tag
     */
    public function getQuestionTypeAnswersTag($questionType)
    {
        switch ($questionType) {
            case 'match':
            case 'ddmatch':
                return 'match';
                break;
            default:
                return 'answer';
                break;
        }
    }

    /**
     *
     * @param string $moodleQuestionType
     * @return integer Chamilo question type
     */
    public function matchMoodleChamiloQuestionTypes($moodleQuestionType)
    {
        switch ($moodleQuestionType) {
            case 'multichoice':
                return UNIQUE_ANSWER;
                break;
            case 'multianswer':
            case 'shortanswer':
            case 'match':
                return FILL_IN_BLANKS;
                break;
            case 'essay':
                return FREE_ANSWER;
                break;
            case 'truefalse':
                return MULTIPLE_ANSWER_TRUE_FALSE;
            break;
        }
    }

    /**
     * Process Moodle Answers to Chamilo
     *
     * @param array $questionList
     * @param string $questionType
     * @param object $questionInstance Question/Answer instance
     * @param array $currentQuestion
     * @return integer db response
     */
    public function processAnswers($questionList, $questionType, $questionInstance, $currentQuestion)
    {
        switch ($questionType) {
            case 'multichoice':
                $objAnswer = new Answer($questionInstance->id);
                $questionWeighting = 0;
                foreach ($questionList as $slot => $answer) {
                    $this->processUniqueAnswer($objAnswer, $answer, $slot + 1, $questionWeighting);
                }

                // saves the answers into the data base
                $objAnswer->save();
                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();

                return true;
                break;
            case 'multianswer':
                $objAnswer = new Answer($questionInstance->id);

                $placeholder = $currentQuestion['questiontext'];

                $optionsValues = [];

                foreach ($questionList as $slot => $subQuestion) {
                    $qtype = $subQuestion['qtype'];
                    $optionsValues[] = $this->processFillBlanks($objAnswer, $subQuestion, $subQuestion['plugin_qtype_'.$qtype.'_question'], $placeholder, $slot + 1);
                }

                $answerOptionsWeight = '::';
                $answerOptionsSize = '';
                $questionWeighting = 0;
                foreach ($optionsValues as $index => $value) {
                    $questionWeighting += $value['weight'];
                    $answerOptionsWeight .= $value['weight'].',';
                    $answerOptionsSize .= $value['size'].',';
                }

                $answerOptionsWeight = substr($answerOptionsWeight, 0, -1);
                $answerOptionsSize = substr($answerOptionsSize, 0, -1);

                $suffleAnswers = isset($subQuestion[$qtype.'_values']['shuffleanswers']) ? $subQuestion[$qtype.'_values']['shuffleanswers'] : false;

                if ($suffleAnswers) {
                    $answerOptions = $answerOptionsWeight.':'.$answerOptionsSize.':0@'.$suffleAnswers;
                } else {
                    $answerOptions = $answerOptionsWeight.':'.$answerOptionsSize.':0@';
                }

                $placeholder = $placeholder.$answerOptions;

                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->updateDescription('');
                $questionInstance->save();
                $objAnswer->createAnswer($placeholder, 0, '', 0, 1);
                $objAnswer->save();
            case 'shortanswer':
            case 'match':
            case 'ddmatch':
                // Not Supported Yet
                return false;
                break;
            case 'essay':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();
                break;
            case 'truefalse':
                // Not Supported Yet
                return false;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Process Chamilo Unique Answer
     *
     * @param object $objAnswer
     * @param array $answerValues
     * @param integer $position
     * @param integer $questionWeighting
     * @return integer db response
     */
    public function processUniqueAnswer($objAnswer, $answerValues, $position, &$questionWeighting)
    {
        $correct = intval($answerValues['fraction']) ? intval($answerValues['fraction']) : 0;
        $answer = $answerValues['answertext'];
        $comment = $answerValues['feedback'];
        $weighting = $answerValues['fraction'];
        $weighting = abs($weighting);
        if ($weighting > 0) {
            $questionWeighting += $weighting;
        }
        $goodAnswer =  $correct ? true : false;

        $objAnswer->createAnswer(
            $answer,
            $goodAnswer,
            $comment,
            $weighting,
            $position,
            null,
            null,
            ''
        );
    }

    /**
     * Process Chamilo FillBlanks
     *
     * @param object $objAnswer
     * @param array $question
     * @param array $answerValues
     * @param string $placeholder
     * @param integer $position
     * @return integer db response
     */
    public function processFillBlanks($objAnswer, $question, $answerValues, &$placeholder, $position)
    {
        switch ($question['qtype']) {
            case 'multichoice':
                $optionsValues = [];

                $correctAnswer = '';
                $othersAnswer = '';
                foreach ($answerValues as $answer) {
                    $correct = intval($answer['fraction']);
                    if ($correct) {
                        $correctAnswer .= $answer['answertext'].'|';
                        $optionsValues['weight'] = $answer['fraction'];
                        $optionsValues['size'] = '200';
                    } else {
                        $othersAnswer .= $answer['answertext'].'|';
                    }
                }
                $currentAnswers = $correctAnswer.$othersAnswer;
                $currentAnswers = '['.substr($currentAnswers, 0, -1).']';
                $placeholder = str_replace("{#$position}", $currentAnswers, $placeholder);

                return $optionsValues;

                break;
            case 'shortanswer':
                $optionsValues = [];

                $correctAnswer = '';

                foreach ($answerValues as $answer) {
                    $correct = intval($answer['fraction']);
                    if ($correct) {
                        $correctAnswer .= $answer['answertext'];
                        $optionsValues['weight'] = $answer['fraction'];
                        $optionsValues['size'] = '200';
                    }
                }

                $currentAnswers = '['.$correctAnswer.']';
                $placeholder = str_replace("{#$position}", $currentAnswers, $placeholder);

                return $optionsValues;

                break;
            default:
                return false;
                break;
        }
    }


    /**
     * Litle utility to delete the unuseful tags
     *
     * @param $array
     * @param $keys
     */
    public function traverseArray(&$array, $keys) {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->traverseArray($value, $keys);
            } else {
                if (in_array($key, $keys)){
                    unset($array[$key]);
                }
            }
        }
    }

}