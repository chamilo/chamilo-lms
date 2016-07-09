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
                            case 'forum1':
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
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                                $questionsXml = @file_get_contents($destinationDir.'/questions.xml');
                                $moduleValues = $this->readQuizModule($moduleXml);
                                foreach ($moduleValues['question_instances'] as $index => $question) {
                                    $questionsValues = $this->readMainQuestionsXml($questionsXml, $question['questionid']);
                                    //$moduleValues['question_instances'][$index] =
                                }
                                exit;

                                break;
                            case 'resource1':
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
                            case 'url1':
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
            }
        }

        //removeDir($destinationDir);
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
                                $answer = $item->getElementsByTagName('answer');
                                $currentItem['plugin_qtype_'.$questionType.'_question'] = [];
                                for ($i = 0; $i <= $answer->length - 1; $i++) {
                                    $currentItem['plugin_qtype_'.$questionType.'_question'][$i]['answerid'] = $answer->item($i)->getAttribute('id');
                                    foreach ($answer->item($i)->childNodes as $properties) {
                                        $currentItem['plugin_qtype_'.$questionType.'_question'][$i][$properties->nodeName] = $properties->nodeValue;
                                    }
                                }

                                $typeValues = $item->getElementsByTagName($questionType);
                                for ($i = 0; $i <= $typeValues->length - 1; $i++) {
                                    foreach ($typeValues->item($i)->childNodes as $properties) {
                                        $currentItem[$questionType.'_values'][$properties->nodeName] = $properties->nodeValue;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            var_dump($currentItem);
            return $currentItem;
        }

        return false;
    }

}