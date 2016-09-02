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
            $destinationDir = api_get_path(SYS_ARCHIVE_PATH) . $folder;
            $coursePath = api_get_course_path();
            $sessionId = api_get_session_id();
            $groupId = api_get_group_id();
            $documentPath = api_get_path(SYS_COURSE_PATH) . $coursePath . '/document';
            $courseInfo = api_get_course_info();

            mkdir($destinationDir, api_get_permissions_for_new_directories(), true);

            $newFolderData = create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                $sessionId,
                $groupId,
                null,
                $documentPath,
                '/moodle',
                'Moodle Docs',
                0
            );

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

                        foreach ($activity->childNodes as $item) {
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
                                $catId = store_forumcategory($catForumValues, [], false);
                                $forumValues = [];
                                $forumValues['forum_title'] = $moduleValues['name'];
                                $forumValues['forum_image'] = '';
                                $forumValues['forum_comment'] = $moduleValues['intro'];
                                $forumValues['forum_category'] = $catId;
                                $forumValues['moderated'] = 0;

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

                                        // Replace the path from @@PLUGINFILE@@ to a correct chamilo path
                                        $moduleValues['question_instances'][$index]['questiontext'] = str_replace('@@PLUGINFILE@@', '/courses/' . $coursePath . '/document/moodle', $moduleValues['question_instances'][$index]['questiontext']);
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
                                $currentResourceFilePath = $destinationDir.'/files/';
                                $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
                                foreach (new RecursiveIteratorIterator($dirs) as $file) {
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
                                            '/moodle',
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

                // This process will upload all question resource files
                $filesXml = @file_get_contents($destinationDir.'/files.xml');
                $mainFileModuleValues = $this->getAllQuestionFiles($filesXml);
                $currentResourceFilePath = $destinationDir.'/files/';

                foreach ($mainFileModuleValues as $fileInfo) {
                    $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
                    foreach (new RecursiveIteratorIterator($dirs) as $file) {
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
                                '/moodle',
                                isset($fileInfo['title']) ? $fileInfo['title'] : pathinfo($fileInfo['filename'], PATHINFO_FILENAME),
                                '',
                                null,
                                null,
                                true,
                                true,
                                'file',
                                // This is to validate spaces as hyphens
                                false
                            );
                        }
                    }
                }

            } else {
                removeDir($destinationDir);
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
                    foreach ($activity->childNodes as $item) {
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
                    foreach ($activity->childNodes as $item) {
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
                        foreach ($question->childNodes as $item) {
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
                return UNIQUE_ANSWER_NO_OPTION;
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

                $coursePath = api_get_course_path();

                $placeholder = str_replace('@@PLUGINFILE@@', '/courses/' . $coursePath . '/document/moodle', $currentQuestion['questiontext']);

                $optionsValues = [];

                foreach ($questionList as $slot => $subQuestion) {
                    $qtype = $subQuestion['qtype'];
                    $optionsValues[] = $this->processFillBlanks($objAnswer, $qtype, $subQuestion['plugin_qtype_'.$qtype.'_question'], $placeholder, $slot + 1);
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
                $answerOptions = $answerOptionsWeight.':'.$answerOptionsSize.':0@';
                $placeholder = $placeholder.PHP_EOL.$answerOptions;

                // This is a minor trick to clean the question description that in a multianswer is the main placeholder
                $questionInstance->updateDescription('');
                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();
                // saves the answers into the data base
                $objAnswer->createAnswer($placeholder, 0, '', 0, 1);
                $objAnswer->save();

                return true;
            case 'match':
                $objAnswer = new Answer($questionInstance->id);
                $placeholder = '';

                $optionsValues = $this->processFillBlanks($objAnswer, 'match', $questionList, $placeholder, 0);

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
                $answerOptions = $answerOptionsWeight.':'.$answerOptionsSize.':0@';
                $placeholder = $placeholder.PHP_EOL.$answerOptions;

                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();
                // saves the answers into the data base
                $objAnswer->createAnswer($placeholder, 0, '', 0, 1);
                $objAnswer->save();

                return true;
                break;
            case 'shortanswer':
            case 'ddmatch':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->updateDescription(get_lang('ThisQuestionIsNotSupportedYet'));
                $questionInstance->save();
                return false;
                break;
            case 'essay':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();
                return true;
                break;
            case 'truefalse':
                $objAnswer = new Answer($questionInstance->id);
                $questionWeighting = 0;
                foreach ($questionList as $slot => $answer) {
                    $this->processTrueFalse($objAnswer, $answer, $slot + 1, $questionWeighting);
                }

                // saves the answers into the data base
                $objAnswer->save();
                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save();
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
     * Process Chamilo True False
     *
     * @param object $objAnswer
     * @param array $answerValues
     * @param integer $position
     * @param integer $questionWeighting
     * @return integer db response
     */
    public function processTrueFalse($objAnswer, $answerValues, $position, &$questionWeighting)
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
     * @param array $questionType
     * @param array $answerValues
     * @param string $placeholder
     * @param integer $position
     * @return integer db response
     */
    public function processFillBlanks($objAnswer, $questionType, $answerValues, &$placeholder, $position)
    {

        $coursePath = api_get_course_path();

        switch ($questionType) {
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
            case 'match':
                $answers = [];
                // Here first we need to extract all the possible answers
                foreach ($answerValues as $slot => $answer) {
                    $answers[$slot] = $answer['answertext'];
                }

                // Now we set the order of the values matching the correct answer and set it to the first element
                $optionsValues = [];
                foreach ($answerValues as $slot => $answer) {
                    $correctAnswer = '';
                    $othersAnswers = '';
                    $correctAnswer .= $answer['answertext'].'|';

                    foreach ($answers as $other) {
                        if ($other !== $answer['answertext']) {
                            $othersAnswers .= $other.'|';
                        }
                    }

                    $optionsValues[$slot]['weight'] = 1;
                    $optionsValues[$slot]['size'] = '200';

                    $currentAnswers = $correctAnswer.$othersAnswers;
                    $currentAnswers = '['.substr($currentAnswers, 0, -1).'] ';
                    $answer['questiontext'] = str_replace('@@PLUGINFILE@@', '/courses/' . $coursePath . '/document/moodle', $answer['questiontext']);

                    $placeholder .= '<p> ' . strip_tags($answer['questiontext']).' '.$currentAnswers . ' </p>';
                }

                return $optionsValues;

                break;
            default:
                return false;
                break;
        }
    }

    /**
     * get All files associated with a question
     *
     * @param $filesXml
     * @return array
     */
    public function getAllQuestionFiles($filesXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($filesXml);
        $allFiles = [];
        if ($moduleRes) {
            $activities = $moduleDoc->getElementsByTagName('file');
            foreach ($activities as $activity) {

                $currentItem = [];
                $thisIsAnInvalidItem = false;

                if ($activity->childNodes->length) {
                    foreach ($activity->childNodes as $item) {
                        if ($item->nodeName == 'component' && $item->nodeValue == 'mod_resource') {
                            $thisIsAnInvalidItem = true;
                        }

                        if ($item->nodeName == 'contenthash') {
                            $currentItem['contenthash'] = $item->nodeValue;
                        }

                        if ($item->nodeName == 'filename') {
                            $currentItem['filename'] = $item->nodeValue;
                        }

                        if ($item->nodeName == 'filesize') {
                            $currentItem['filesize'] = $item->nodeValue;
                        }

                        if ($item->nodeName == 'mimetype' && $item->nodeValue == 'document/unknown') {
                            $thisIsAnInvalidItem = true;
                        }

                        if ($item->nodeName == 'mimetype' && $item->nodeValue !== 'document/unknown') {
                            $currentItem['mimetype'] = $item->nodeValue;
                        }
                    }
                }

                if (!$thisIsAnInvalidItem) {
                    $allFiles[] = $currentItem;
                }
            }
        }

        return $allFiles;
    }


    /**
     * Litle utility to delete the unuseful tags
     *
     * @param $array
     * @param $keys
     */
    public function traverseArray(&$array, $keys)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->traverseArray($value, $keys);
            } else {
                if (in_array($key, $keys)) {
                    unset($array[$key]);
                }
            }
        }
    }

}