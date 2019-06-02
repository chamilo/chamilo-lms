<?php
/* For licensing terms, see /license.txt */

/**
 * Class MoodleImport.
 *
 * @author  José Loguercio <jose.loguercio@beeznest.com>,
 * @author  Julio Montoya <gugli100@gmail.com>
 *
 * @package chamilo.library
 */
class MoodleImport
{
    /**
     * Import moodle file.
     *
     * @param resource $uploadedFile *.* mbz file moodle course backup
     *
     * @throws Exception
     *
     * @return bool
     */
    public function import($uploadedFile)
    {
        if (UPLOAD_ERR_OK !== $uploadedFile['error']) {
            throw new Exception(get_lang('UploadError'));
        }

        $cachePath = api_get_path(SYS_ARCHIVE_PATH);
        $tempPath = $uploadedFile['tmp_name'];
        $nameParts = explode('.', $uploadedFile['name']);
        $extension = array_pop($nameParts);
        $name = basename($tempPath).".$extension";

        if (!move_uploaded_file($tempPath, api_get_path(SYS_ARCHIVE_PATH).$name)) {
            throw new Exception(get_lang('UploadError'));
        }

        $filePath = $cachePath.$name;

        if (!is_readable($filePath)) {
            throw new Exception(get_lang('UploadError'));
        }

        $mimeType = mime_content_type($filePath);

        $folder = api_get_unique_id();
        $destinationDir = api_get_path(SYS_ARCHIVE_PATH).$folder;

        mkdir($destinationDir, api_get_permissions_for_new_directories(), true);

        switch ($mimeType) {
            case 'application/x-gzip':
                $backUpFile = new PharData($filePath);

                if (false === $backUpFile->extractTo($destinationDir)) {
                    throw new Exception(get_lang('ErrorImportingFile'));
                }

                if (!file_exists($destinationDir.'/moodle_backup.xml')) {
                    throw new Exception(get_lang('FailedToImportThisIsNotAMoodleFile'));
                }

                break;
            case 'application/zip':
                $package = new PclZip($filePath);
                $mainFileKey = 0;
                $packageContent = $package->listContent();

                if (!empty($packageContent)) {
                    foreach ($packageContent as $index => $value) {
                        if ($value['filename'] == 'moodle_backup.xml') {
                            $mainFileKey = $index;
                            break;
                        }
                    }
                }

                if (!$mainFileKey) {
                    throw new Exception(get_lang('FailedToImportThisIsNotAMoodleFile'));
                }

                $package->extract(PCLZIP_OPT_PATH, $destinationDir);

                break;
        }

        $courseInfo = api_get_course_info();
        $sessionId = api_get_session_id();
        $groupId = api_get_group_id();
        $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';

        create_unexisting_directory(
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

        // This process will upload all question resource files
        $filesXml = @file_get_contents($destinationDir.'/files.xml');
        $mainFileModuleValues = $this->getAllQuestionFiles($filesXml);
        $currentResourceFilePath = $destinationDir.'/files/';
        $importedFiles = [];
        foreach ($mainFileModuleValues as $fileInfo) {
            $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
            foreach (new RecursiveIteratorIterator($dirs) as $file) {
                if (!is_file($file) || false === strpos($file, $fileInfo['contenthash'])) {
                    continue;
                }

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

                $data = DocumentManager::upload_document(
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

                if ($data) {
                    $importedFiles[$fileInfo['filename']] = basename($data['path']);
                }
            }
        }

        $xml = @file_get_contents($destinationDir.'/moodle_backup.xml');

        $doc = new DOMDocument();
        $res = @$doc->loadXML($xml);

        if (empty($res)) {
            removeDir($destinationDir);
            unlink($filePath);

            throw new Exception(get_lang('FailedToImportThisIsNotAMoodleFile'));
        }
        $activities = $doc->getElementsByTagName('activity');
        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }

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
                    $catId = store_forumcategory(
                        $catForumValues,
                        $courseInfo,
                        false
                    );
                    $forumValues = [];
                    $forumValues['forum_title'] = $moduleValues['name'];
                    $forumValues['forum_image'] = '';
                    $forumValues['forum_comment'] = $moduleValues['intro'];
                    $forumValues['forum_category'] = $catId;
                    $forumValues['moderated'] = 0;

                    store_forum($forumValues, $courseInfo);
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

                    $exercise = new Exercise($courseInfo['real_id']);
                    $title = Exercise::format_title_variable($moduleValues['name']);
                    $exercise->updateTitle($title);
                    $exercise->updateDescription($moduleValues['intro']);
                    $exercise->updateAttempts($moduleValues['attempts_number']);
                    $exercise->updateFeedbackType(0);

                    // Match shuffle question with chamilo
                    if (isset($moduleValues['shufflequestions']) &&
                        (int) $moduleValues['shufflequestions'] === 1
                    ) {
                        $exercise->setRandom(-1);
                    } else {
                        $exercise->setRandom(0);
                    }
                    $exercise->updateRandomAnswers(!empty($moduleValues['shuffleanswers']));
                    // @todo divide to minutes
                    $exercise->updateExpiredTime((int) $moduleValues['timelimit']);

                    if ($moduleValues['questionsperpage'] == 1) {
                        $exercise->updateType(2);
                    } else {
                        $exercise->updateType(1);
                    }

                    // Create the new Quiz
                    $exercise->save();

                    // Ok, we got the Quiz and create it, now its time to add the Questions
                    foreach ($moduleValues['question_instances'] as $index => $question) {
                        $questionsValues = $this->readMainQuestionsXml(
                            $questionsXml,
                            $question['questionid']
                        );
                        $moduleValues['question_instances'][$index] = $questionsValues;
                        // Set Question Type from Moodle XML element <qtype>
                        $qType = $moduleValues['question_instances'][$index]['qtype'];
                        // Add the matched chamilo question type to the array
                        $moduleValues['question_instances'][$index]['chamilo_qtype'] =
                            $this->matchMoodleChamiloQuestionTypes($qType);
                        $questionInstance = Question::getInstance(
                            $moduleValues['question_instances'][$index]['chamilo_qtype']
                        );

                        if (empty($questionInstance)) {
                            continue;
                        }

                        $questionInstance->updateTitle(
                            $moduleValues['question_instances'][$index]['name']
                        );
                        $questionText = $moduleValues['question_instances'][$index]['questiontext'];

                        // Replace the path from @@PLUGINFILE@@ to a correct chamilo path
                        $questionText = str_replace(
                            '@@PLUGINFILE@@',
                            '/courses/'.$courseInfo['path'].'/document/moodle',
                            $questionText
                        );

                        if ($importedFiles) {
                            $this->fixPathInText($importedFiles, $questionText);
                        }

                        $questionInstance->updateDescription($questionText);
                        $questionInstance->updateLevel(1);
                        $questionInstance->updateCategory(0);

                        //Save normal question if NOT media
                        if ($questionInstance->type != MEDIA_QUESTION) {
                            $questionInstance->save($exercise);

                            // modify the exercise
                            $exercise->addToList($questionInstance->id);
                            $exercise->update_question_positions();
                        }

                        $questionList = $moduleValues['question_instances'][$index]['plugin_qtype_'.$qType.'_question'];
                        $currentQuestion = $moduleValues['question_instances'][$index];

                        $this->processAnswers(
                            $exercise,
                            $questionList,
                            $qType,
                            $questionInstance,
                            $currentQuestion,
                            $importedFiles
                        );
                    }
                    break;
                case 'resource':
                    // Read the current resource module xml.
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $filesXml = @file_get_contents($destinationDir.'/files.xml');
                    $moduleValues = $this->readResourceModule($moduleXml);
                    $mainFileModuleValues = $this->readMainFilesXml(
                        $filesXml,
                        $moduleValues['contextid']
                    );
                    $fileInfo = array_merge($moduleValues, $mainFileModuleValues, $currentItem);
                    $currentResourceFilePath = $destinationDir.'/files/';
                    $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
                    foreach (new RecursiveIteratorIterator($dirs) as $file) {
                        if (!is_file($file) || false === strpos($file, $fileInfo['contenthash'])) {
                            continue;
                        }

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

        removeDir($destinationDir);
        unlink($filePath);

        return true;
    }

    /**
     * Read and validate the forum module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readForumModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
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

    /**
     * Read and validate the resource module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readResourceModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
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

    /**
     * Read and validate the url module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readUrlModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
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

    /**
     * Read and validate the quiz module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readQuizModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
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

    /**
     * Search the current file resource in main Files XML.
     *
     * @param resource $filesXml  XML file
     * @param int      $contextId
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readMainFilesXml($filesXml, $contextId)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($filesXml);

        if (empty($moduleRes)) {
            return false;
        }

        $activities = $moduleDoc->getElementsByTagName('file');
        $currentItem = [];
        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }
            $isThisItemThatIWant = false;
            foreach ($activity->childNodes as $item) {
                if (!$isThisItemThatIWant && $item->nodeName == 'contenthash') {
                    $currentItem['contenthash'] = $item->nodeValue;
                }
                if ($item->nodeName == 'contextid' &&
                    (int) $item->nodeValue == (int) $contextId &&
                    !$isThisItemThatIWant
                ) {
                    $isThisItemThatIWant = true;
                    continue;
                }

                if ($isThisItemThatIWant && $item->nodeName == 'filename') {
                    $currentItem['filename'] = $item->nodeValue;
                }

                if ($isThisItemThatIWant && $item->nodeName == 'filesize') {
                    $currentItem['filesize'] = $item->nodeValue;
                }

                if ($isThisItemThatIWant && $item->nodeName == 'mimetype' &&
                    $item->nodeValue == 'document/unknown'
                ) {
                    break;
                }

                if ($isThisItemThatIWant && $item->nodeName == 'mimetype' &&
                    $item->nodeValue !== 'document/unknown'
                ) {
                    $currentItem['mimetype'] = $item->nodeValue;
                    break 2;
                }
            }
        }

        return $currentItem;
    }

    /**
     * Search the current question resource in main Questions XML.
     *
     * @param resource $questionsXml XML file
     * @param int      $questionId
     *
     * @return mixed | array if is a valid xml file, false otherwise
     */
    public function readMainQuestionsXml($questionsXml, $questionId)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($questionsXml);
        if (empty($moduleRes)) {
            return false;
        }

        $questions = $moduleDoc->getElementsByTagName('question');
        $currentItem = [];
        foreach ($questions as $question) {
            if ((int) $question->getAttribute('id') !== (int) $questionId) {
                continue;
            }

            if (empty($question->childNodes->length)) {
                continue;
            }

            $currentItem['questionid'] = $questionId;
            $questionType = '';
            foreach ($question->childNodes as $item) {
                $currentItem[$item->nodeName] = $item->nodeValue;
                if ($item->nodeName == 'qtype') {
                    $questionType = $item->nodeValue;
                }

                if ($item->nodeName != 'plugin_qtype_'.$questionType.'_question') {
                    continue;
                }

                $answer = $item->getElementsByTagName($this->getQuestionTypeAnswersTag($questionType));
                $currentItem['plugin_qtype_'.$questionType.'_question'] = [];
                for ($i = 0; $i <= $answer->length - 1; $i++) {
                    $currentItem['plugin_qtype_'.$questionType.'_question'][$i]['answerid'] =
                        $answer->item($i)->getAttribute('id');
                    foreach ($answer->item($i)->childNodes as $properties) {
                        $currentItem['plugin_qtype_'.$questionType.'_question'][$i][$properties->nodeName] =
                            $properties->nodeValue;
                    }
                }

                $typeValues = $item->getElementsByTagName($this->getQuestionTypeOptionsTag($questionType));
                for ($i = 0; $i <= $typeValues->length - 1; $i++) {
                    foreach ($typeValues->item($i)->childNodes as $properties) {
                        $currentItem[$questionType.'_values'][$properties->nodeName] = $properties->nodeValue;

                        if ($properties->nodeName != 'sequence') {
                            continue;
                        }

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

        $this->traverseArray($currentItem, ['#text', 'question_hints', 'tags']);

        return $currentItem;
    }

    /**
     * return the correct question type options tag.
     *
     * @param string $questionType name
     *
     * @return string question type tag
     */
    public function getQuestionTypeOptionsTag($questionType)
    {
        switch ($questionType) {
            case 'match':
            case 'ddmatch':
                return 'matchoptions';
            default:
                return $questionType;
        }
    }

    /**
     * return the correct question type answers tag.
     *
     * @param string $questionType name
     *
     * @return string question type tag
     */
    public function getQuestionTypeAnswersTag($questionType)
    {
        switch ($questionType) {
            case 'match':
            case 'ddmatch':
                return 'match';
            default:
                return 'answer';
        }
    }

    /**
     * @param string $moodleQuestionType
     *
     * @return int Chamilo question type
     */
    public function matchMoodleChamiloQuestionTypes($moodleQuestionType)
    {
        switch ($moodleQuestionType) {
            case 'multichoice':
                return UNIQUE_ANSWER;
            case 'multianswer':
            case 'shortanswer':
            case 'match':
                return FILL_IN_BLANKS;
            case 'essay':
                return FREE_ANSWER;
            case 'truefalse':
                return UNIQUE_ANSWER_NO_OPTION;
        }
    }

    /**
     * Fix moodle files that contains spaces.
     *
     * @param array  $importedFiles
     * @param string $text
     *
     * @return mixed
     */
    public function fixPathInText($importedFiles, &$text)
    {
        if ($importedFiles) {
            foreach ($importedFiles as $old => $new) {
                // Ofaj fix moodle file names
                // In some questions moodle text contains file with name like:
                // Bild%20Check-In-Formular%20Ausfu%CC%88llen.jpg"
                // rawurlencode function transforms '' (whitespace) to %20 and so on
                $text = str_replace(rawurlencode($old), $new, $text);
            }
        }

        return $text;
    }

    /**
     * Process Moodle Answers to Chamilo.
     *
     * @param Exercise $exercise
     * @param array    $questionList
     * @param string   $questionType
     * @param Question $questionInstance Question/Answer instance
     * @param array    $currentQuestion
     * @param array    $importedFiles
     *
     * @return int db response
     */
    public function processAnswers(
        $exercise,
        $questionList,
        $questionType,
        $questionInstance,
        $currentQuestion,
        $importedFiles
    ) {
        switch ($questionType) {
            case 'multichoice':
                $objAnswer = new Answer($questionInstance->id);
                $questionWeighting = 0;
                foreach ($questionList as $slot => $answer) {
                    $this->processUniqueAnswer(
                        $objAnswer,
                        $answer,
                        $slot + 1,
                        $questionWeighting,
                        $importedFiles
                    );
                }

                // saves the answers into the data base
                $objAnswer->save();
                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save($exercise);

                return true;
            case 'multianswer':
                $objAnswer = new Answer($questionInstance->id);
                $coursePath = api_get_course_path();
                $placeholder = str_replace(
                    '@@PLUGINFILE@@',
                    '/courses/'.$coursePath.'/document/moodle',
                    $currentQuestion['questiontext']
                );
                $optionsValues = [];
                foreach ($questionList as $slot => $subQuestion) {
                    $qtype = $subQuestion['qtype'];
                    $optionsValues[] = $this->processFillBlanks(
                        $objAnswer,
                        $qtype,
                        $subQuestion['plugin_qtype_'.$qtype.'_question'],
                        $placeholder,
                        $slot + 1,
                        $importedFiles
                    );
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
                $questionInstance->save($exercise);
                $this->fixPathInText($importedFiles, $placeholder);

                // saves the answers into the data base
                $objAnswer->createAnswer($placeholder, 0, '', 0, 1);
                $objAnswer->save();

                return true;
            case 'match':
                $objAnswer = new Answer($questionInstance->id);
                $placeholder = '';

                $optionsValues = $this->processFillBlanks(
                    $objAnswer,
                    'match',
                    $questionList,
                    $placeholder,
                    0,
                    $importedFiles
                );

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
                $questionInstance->save($exercise);
                // saves the answers into the database
                $this->fixPathInText($importedFiles, $placeholder);
                $objAnswer->createAnswer($placeholder, 0, '', 0, 1);
                $objAnswer->save();

                return true;
            case 'shortanswer':
            case 'ddmatch':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->updateDescription(get_lang('ThisQuestionIsNotSupportedYet'));
                $questionInstance->save($exercise);

                return false;
            case 'essay':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save($exercise);

                return true;
            case 'truefalse':
                $objAnswer = new Answer($questionInstance->id);
                $questionWeighting = 0;
                foreach ($questionList as $slot => $answer) {
                    $this->processTrueFalse(
                        $objAnswer,
                        $answer,
                        $slot + 1,
                        $questionWeighting,
                        $importedFiles
                    );
                }

                // saves the answers into the data base
                $objAnswer->save();
                // sets the total weighting of the question
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->save($exercise);

                return false;
            default:
                return false;
        }
    }

    /**
     * Process Chamilo Unique Answer.
     *
     * @param object $objAnswer
     * @param array  $answerValues
     * @param int    $position
     * @param int    $questionWeighting
     * @param array  $importedFiles
     *
     * @return int db response
     */
    public function processUniqueAnswer(
        $objAnswer,
        $answerValues,
        $position,
        &$questionWeighting,
        $importedFiles
    ) {
        $correct = (int) $answerValues['fraction'] ? (int) $answerValues['fraction'] : 0;
        $answer = $answerValues['answertext'];
        $comment = $answerValues['feedback'];
        $weighting = $answerValues['fraction'];
        $weighting = abs($weighting);
        if ($weighting > 0) {
            $questionWeighting += $weighting;
        }
        $goodAnswer = $correct ? true : false;

        $this->fixPathInText($importedFiles, $answer);

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
     * Process Chamilo True False.
     *
     * @param object $objAnswer
     * @param array  $answerValues
     * @param int    $position
     * @param int    $questionWeighting
     * @param array  $importedFiles
     *
     * @return int db response
     */
    public function processTrueFalse(
        $objAnswer,
        $answerValues,
        $position,
        &$questionWeighting,
        $importedFiles
    ) {
        $correct = (int) $answerValues['fraction'] ? (int) $answerValues['fraction'] : 0;
        $answer = $answerValues['answertext'];
        $comment = $answerValues['feedback'];
        $weighting = $answerValues['fraction'];
        $weighting = abs($weighting);
        if ($weighting > 0) {
            $questionWeighting += $weighting;
        }
        $goodAnswer = $correct ? true : false;

        $this->fixPathInText($importedFiles, $answer);

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
     * Process Chamilo FillBlanks.
     *
     * @param object $objAnswer
     * @param array  $questionType
     * @param array  $answerValues
     * @param string $placeholder
     * @param int    $position
     * @param array  $importedFiles
     *
     * @return int db response
     */
    public function processFillBlanks(
        $objAnswer,
        $questionType,
        $answerValues,
        &$placeholder,
        $position,
        $importedFiles
    ) {
        $coursePath = api_get_course_path();

        switch ($questionType) {
            case 'multichoice':
                $optionsValues = [];
                $correctAnswer = '';
                $othersAnswer = '';
                foreach ($answerValues as $answer) {
                    $correct = (int) $answer['fraction'];
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
            case 'shortanswer':
                $optionsValues = [];
                $correctAnswer = '';
                foreach ($answerValues as $answer) {
                    $correct = (int) $answer['fraction'];
                    if ($correct) {
                        $correctAnswer .= $answer['answertext'];
                        $optionsValues['weight'] = $answer['fraction'];
                        $optionsValues['size'] = '200';
                    }
                }

                $currentAnswers = '['.$correctAnswer.']';
                $placeholder = str_replace("{#$position}", $currentAnswers, $placeholder);

                return $optionsValues;
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

                    $currentAnswers = htmlentities($correctAnswer.$othersAnswers);
                    $currentAnswers = '['.substr($currentAnswers, 0, -1).'] ';
                    $answer['questiontext'] = str_replace(
                        '@@PLUGINFILE@@',
                        '/courses/'.$coursePath.'/document/moodle',
                        $answer['questiontext']
                    );

                    $placeholder .= '<p> '.strip_tags($answer['questiontext']).' '.$currentAnswers.' </p>';
                }

                return $optionsValues;
            default:
                return false;
        }
    }

    /**
     * get All files associated with a question.
     *
     * @param $filesXml
     *
     * @return array
     */
    public function getAllQuestionFiles($filesXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($filesXml);

        if (empty($moduleRes)) {
            return [];
        }

        $allFiles = [];
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

        return $allFiles;
    }

    /**
     * Litle utility to delete the unuseful tags.
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
