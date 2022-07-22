<?php

/* For licensing terms, see /license.txt */

/**
 * Class MoodleImport.
 *
 * @author  José Loguercio <jose.loguercio@beeznest.com>,
 * @author  Julio Montoya <gugli100@gmail.com>
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
        $debug = false;
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
            case 'application/gzip':
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
                        if ($value['filename'] === 'moodle_backup.xml') {
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

        if (is_dir($documentPath.'/moodle')) {
            chmod($documentPath.'/moodle', 0777);
        }
        // This process will upload all question resource files
        $filesXml = @file_get_contents($destinationDir.'/files.xml');
        $mainFileModuleValues = $this->getAllQuestionFiles($filesXml);
        $currentResourceFilePath = $destinationDir.'/files/';
        $importedFiles = [];
        if ($debug) {
            error_log('loading files');
        }

        $_POST['moodle_import'] = true;
        $_POST['language'] = $courseInfo['language'];

        $modScormFileZips = [];
        foreach ($mainFileModuleValues as $fileInfo) {
            $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
            foreach (new RecursiveIteratorIterator($dirs) as $file) {
                if (!is_file($file) || false === strpos($file, $fileInfo['contenthash'])) {
                    continue;
                }

                if (isset($importedFiles[$fileInfo['filename']])) {
                    continue;
                }

                if ($debug) {
                    error_log($fileInfo['filename']);
                }
                $files = [];
                $files['file']['name'] = $fileInfo['filename'];
                $files['file']['tmp_name'] = $file->getPathname();
                $files['file']['type'] = $fileInfo['mimetype'];
                $files['file']['error'] = 0;
                $files['file']['size'] = $fileInfo['filesize'];
                $files['file']['from_file'] = true;
                $files['file']['move_file'] = true;

                if (isset($fileInfo['modscorm']) && true === $fileInfo['modscorm']) {
                    if ('application/zip' == $fileInfo['mimetype']) {
                        $modScormFileZips[$fileInfo['contenthash']] = $files;
                    }
                    continue;
                }

                $title = isset($fileInfo['title']) ? $fileInfo['title'] : pathinfo($fileInfo['filename'], PATHINFO_FILENAME);

                $data = DocumentManager::upload_document(
                    $files,
                    '/moodle',
                    $title,
                    '',
                    null,
                    'overwrite',
                    true,
                    true,
                    'file',
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

        // It process the sections as learnpaths
        $sections = $this->readSections($xml, $destinationDir);
        $activities = $doc->getElementsByTagName('activity');
        $sectionLpValues = $this->processSections($sections, $activities);

        require_once api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php';

        if ($debug) {
            error_log('Loading activities: '.count($activities));
        }

        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }

            $currentItem = [];
            foreach ($activity->childNodes as $item) {
                $currentItem[$item->nodeName] = $item->nodeValue;
            }

            $moduleName = isset($currentItem['modulename']) ? $currentItem['modulename'] : false;
            if ($debug) {
                error_log('moduleName: '.$moduleName);
            }

            switch ($moduleName) {
                case 'lesson':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readLessonModule($moduleXml);
                    $this->processLesson($moduleValues);
                    break;
                case 'assign':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readAssignModule($moduleXml);
                    $assignId = $this->processAssignment($moduleValues);

                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($assignId)) {
                        $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'student_publication', $assignId, $moduleValues['name']);
                    }
                    break;
                case 'scorm':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readScormModule($moduleXml);
                    $this->processScorm($moduleValues, $modScormFileZips);
                    break;
                case 'glossary':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readGlossaryModule($moduleXml, $currentItem['moduleid']);
                    $this->processGlossary($moduleValues, $currentItem['moduleid']);
                    break;
                case 'label':
                case 'page':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readHtmlModule($moduleXml, $moduleName);
                    $documentId = $this->processHtmlDocument($moduleValues, $moduleName);

                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($documentId)) {
                        $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'document', $documentId, $moduleValues['name']);
                    }
                    break;
                case 'forum':
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

                    $forumId = store_forum($forumValues, $courseInfo, true);
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($forumId)) {
                        $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'forum', $forumId, $moduleValues['name']);
                    }
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
                    if ($debug) {
                        error_log('quiz:'.$moduleValues['name']);
                    }

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
                        $questionsValues = $this->readMainQuestionsXml($questionsXml, $question['questionid']);
                        $moduleValues['question_instances'][$index] = $questionsValues;
                        // Set Question Type from Moodle XML element <qtype>
                        $qType = $questionsValues['qtype'];
                        $questionType = $this->matchMoodleChamiloQuestionTypes($questionsValues);
                        $questionInstance = Question::getInstance($questionType);
                        if (empty($questionInstance)) {
                            continue;
                        }
                        if ($debug) {
                            error_log('question: '.$question['questionid']);
                        }

                        $questionInstance->updateTitle($questionsValues['name']);
                        $questionText = $questionsValues['questiontext'];

                        $questionText = $this->replaceMoodleChamiloCoursePath($questionText);

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
                            $exercise->addToList($questionInstance->iid);
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
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($exercise->iid)) {
                        $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'quiz', $exercise->iid, $title);
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

                        $documentData = DocumentManager::upload_document(
                            $files,
                            '/moodle',
                            $fileInfo['title'],
                            '',
                            null,
                            null,
                            true,
                            true
                        );
                        // It is added as item in Learnpath
                        if (!empty($currentItem['sectionid']) && !empty($documentData['iid'])) {
                            $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'document', $documentData['iid'], $fileInfo['title']);
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

                    $linkId = Link::addlinkcategory('link');
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($linkId)) {
                        $lastLpItemId = $this->processSectionItem($sectionLpValues[$currentItem['sectionid']], 'link', $linkId, $moduleValues['name']);
                    }
                    break;
            }
        }

        if ($debug) {
            error_log('Finish');
        }

        removeDir($destinationDir);
        unlink($filePath);

        return true;
    }

    /**
     * Replace the path from @@PLUGINFILE@@ to a correct chamilo path.
     *
     * @param $text
     *
     * @return string
     */
    public function replaceMoodleChamiloCoursePath($text)
    {
        $coursePath = api_get_course_path();
        $text = str_replace(
            '@@PLUGINFILE@@',
            '/courses/'.$coursePath.'/document/moodle',
            $text
        );

        return $text;
    }

    /**
     * It adds module item by section as learnpath item.
     *
     * @param $lpId
     * @param $itemType
     * @param $itemId
     * @param $itemTitle
     *
     * @return int
     */
    public function processSectionItem($lpId, $itemType, $itemId, $itemTitle)
    {
        $lp = new \learnpath(
            api_get_course_id(),
            $lpId,
            api_get_user_id()
        );

        $lpItemId = $lp->add_item(
            0,
            0,
            $itemType,
            $itemId,
            $itemTitle,
            ''
        );

        return $lpItemId;
    }

    /**
     * It adds the section module as learnpath.
     *
     * @param $sections
     *
     * @return array|false
     */
    public function processSections($sections, $activities)
    {
        if (empty($sections)) {
            return false;
        }

        $i = 1;
        $lpAdded = [];
        foreach ($sections as $sectionId => $section) {
            $countSectionActivities = $this->countSectionActivities($activities, $sectionId);
            if ($countSectionActivities > 0) {
                $lpName = $section['name'];
                if ('$@NULL@$' == $lpName) {
                    $lpName = get_lang('Topic').' '.$i;
                }
                $lpDescription = $section['summary'];
                $lpId = learnpath::add_lp(
                    api_get_course_id(),
                    $lpName,
                    $lpDescription,
                    'chamilo',
                    'manual'
                );
                $lpAdded[$sectionId] = $lpId;
                $i++;
            }
        }

        return $lpAdded;
    }

    /**
     * It counts the activities inside a section module.
     *
     * @param $activities
     * @param $sectionId
     *
     * @return int|void
     */
    public function countSectionActivities($activities, $sectionId)
    {
        $sectionActivities = [];
        $modulesLpTypes = ['url', 'resource', 'quiz', 'forum', 'page', 'label', 'assign'];
        $i = 0;
        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }
            $currentItem = [];
            foreach ($activity->childNodes as $item) {
                $currentItem[$item->nodeName] = $item->nodeValue;
            }
            if (!empty($currentItem['sectionid']) && in_array($currentItem['modulename'], $modulesLpTypes)) {
                $sectionActivities[$currentItem['sectionid']][$i] = $currentItem;
                $i++;
            }
        }

        $countActivities = 0;
        if (isset($sectionActivities[$sectionId])) {
            $countActivities = count($sectionActivities[$sectionId]);
        }

        return $countActivities;
    }

    /**
     * It gets the sections from xml module.
     *
     * @param $xml
     * @param $destinationDir
     *
     * @return array|false
     */
    public function readSections($xml, $destinationDir)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($xml);
        if (empty($res)) {
            return false;
        }

        $sections = [];
        $sectionNodes = $doc->getElementsByTagName('section');
        foreach ($sectionNodes as $section) {
            if (empty($section->childNodes->length)) {
                continue;
            }
            $currentItem = [];
            foreach ($section->childNodes as $item) {
                $currentItem[$item->nodeName] = $item->nodeValue;
            }
            if (!empty($currentItem['directory'])) {
                $sectionDir = $destinationDir.'/'.$currentItem['directory'];
                $sectionInfoXml = @file_get_contents($sectionDir.'/section.xml');
                $sections[$currentItem['sectionid']] = $this->readSectionModule($sectionInfoXml);
            }
        }

        return $sections;
    }

    /**
     * It reads module xml to get section info.
     *
     * @param $sectionInfoXml
     *
     * @return array|false
     */
    public function readSectionModule($sectionInfoXml)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($sectionInfoXml);
        if (empty($res)) {
            return false;
        }

        $sectionInfo = [];
        $sectionNode = $doc->getElementsByTagName('section');
        foreach ($sectionNode as $section) {
            if (empty($section->childNodes->length)) {
                continue;
            }
            foreach ($section->childNodes as $item) {
                $sectionInfo[$item->nodeName] = $item->nodeValue;
            }
        }

        return $sectionInfo;
    }

    /**
     * It gets lesson information from module xml.
     *
     * @param $moduleXml
     *
     * @return array|false
     */
    public function readLessonModule($moduleXml)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($moduleXml);
        if (empty($res)) {
            return false;
        }

        $activities = $doc->getElementsByTagName('lesson');
        $currentItem = [];
        foreach ($activities as $activity) {
            if ($activity->childNodes->length) {
                foreach ($activity->childNodes as $item) {
                    $currentItem[$item->nodeName] = $item->nodeValue;
                }
            }
        }

        $pages = $doc->getElementsByTagName('page');

        $pagesList = [];
        $counter = 0;
        foreach ($pages as $page) {
            if ($page->childNodes->length) {
                foreach ($page->childNodes as $item) {
                        $pagesList[$counter][$item->nodeName] = $item->nodeValue;
                }
                $counter++;
            }
        }
        $currentItem['pages'] = $pagesList;

        return $currentItem;
    }

    /**
     * It gets assignment information from module xml.
     *
     * @param $moduleXml
     *
     * @return array|false
     */
    public function readAssignModule($moduleXml)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($moduleXml);
        if (empty($res)) {
            return false;
        }

        $info = [];
        $entries = $doc->getElementsByTagName('assign');
        foreach ($entries as $entry) {
            if (empty($entry->childNodes->length)) {
                continue;
            }
            foreach ($entry->childNodes as $item) {
                if (in_array($item->nodeName, ['name', 'intro', 'duedate', 'grade'])) {
                    $info[$item->nodeName] = $item->nodeValue;
                }
            }
        }

        return $info;
    }

    /**
     * It gest scorm information from module xml.
     *
     * @param $moduleXml
     *
     * @return array|false
     */
    public function readScormModule($moduleXml)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($moduleXml);
        if (empty($res)) {
            return false;
        }

        $info = [];
        $entries = $doc->getElementsByTagName('scorm');
        $i = 0;
        foreach ($entries as $entry) {
            if (empty($entry->childNodes->length)) {
                continue;
            }
            foreach ($entry->childNodes as $item) {
                if (in_array($item->nodeName, ['name', 'reference', 'sha1hash', 'scormtype'])) {
                    $info[$i][$item->nodeName] = $item->nodeValue;
                }
            }
            $i++;
        }

        return $info;
    }

    /**
     * Get glossary information from module xml.
     *
     * @param $moduleXml
     * @param $moduleId
     *
     * @return array|false
     */
    public function readGlossaryModule($moduleXml, $moduleId)
    {
        $doc = new DOMDocument();
        $res = @$doc->loadXML($moduleXml);
        if (empty($res)) {
            return false;
        }

        $glossaryInfo = [];
        $entries = $doc->getElementsByTagName('entry');
        $i = 0;
        foreach ($entries as $entry) {
            if (empty($entry->childNodes->length)) {
                continue;
            }
            foreach ($entry->childNodes as $item) {
                $glossaryInfo[$moduleId][$i][$item->nodeName] = $item->nodeValue;
            }
            $i++;
        }

        return $glossaryInfo;
    }

    /**
     * It reads item html from module to documents.
     *
     * @param $moduleXml
     * @param $moduleName
     *
     * @return array|false
     */
    public function readHtmlModule($moduleXml, $moduleName)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
        $activities = $moduleDoc->getElementsByTagName($moduleName);
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
     * It saves a learnpath from module xml.
     *
     * @param $moduleValues
     *
     * @return false
     */
    public function processLesson($moduleValues)
    {
        if (!empty($moduleValues['pages'])) {
            $qtypes = [
                20 => 'page',
                10 => 'essay',
                5 => 'matching',
                3 => 'multichoice',
                1 => 'shortanswer',
                2 => 'truefalse'
            ];
            $items = $moduleValues['pages'];
            $lpName = $moduleValues['name'];
            $lpDescription = api_utf8_decode($moduleValues['intro']);
            $lpId = learnpath::add_lp(
                api_get_course_id(),
                $lpName,
                $lpDescription,
                'chamilo',
                'manual'
            );

            $questionList = [];
            foreach ($items as $item) {
                if (in_array($item['qtype'], array_keys($qtypes))) {
                    $qTypeName = $qtypes[$item['qtype']];
                    switch ($qTypeName) {
                        case 'page':
                            $pageValues = [];
                            $pageValues['name'] = $item['title'];
                            $pageValues['content'] = $item['contents'];
                            $documentId = $this->processHtmlDocument($pageValues, 'page');
                            $this->processSectionItem($lpId, 'document', $documentId, $pageValues['name']);
                            break;
                        case 'essay':
                        case 'matching':
                        case 'multichoice':
                        case 'shortanswer':
                        case 'truefalse':
                            break;
                    }
                }
            }
        }

        return false;
    }

    /**
     * It saves a student publication from module xml.
     *
     * @param $assign
     *
     * @return bool|int
     */
    public function processAssignment($assign)
    {
        if (!empty($assign)) {
            require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
            $values = [];
            $values['new_dir'] = $assign['name'];
            $values['enableEndDate'] = 1;
            $values['ends_on'] = date('Y-m-d H:i', $assign['duedate']);
            $values['work_title'] = $assign['name'];
            $values['description'] = api_utf8_decode($assign['intro']);
            $values['qualification'] = '';
            $values['weight'] = $assign['grade'];
            $values['allow_text_assignment'] = 2;

            $assignId = addDir(
                $values,
                api_get_user_id(),
                api_get_course_info(),
                api_get_group_id(),
                api_get_session_id()
            );

            return $assignId;
        }

        return false;
    }

    /**
     * It saves a scorm from module xml.
     *
     * @param $moduleValues
     * @param $modScormFileZips
     *
     * @return bool
     */
    public function processScorm($moduleValues, $modScormFileZips)
    {
        if (!empty($moduleValues)) {
            foreach ($moduleValues as $info) {
                $sha1hash = $info['sha1hash'];
                if (!empty($modScormFileZips[$sha1hash])) {
                    $scormFile = $modScormFileZips[$sha1hash];
                    $oScorm = new scorm();
                    $manifest = $oScorm->import_package(
                        $scormFile['file']
                    );
                    if (!empty($manifest)) {
                        $oScorm->parse_manifest($manifest);
                        $oScorm->import_manifest(
                            api_get_course_id(),
                            1,
                            0,
                            0,
                            $info['name']
                        );
                    }
                    $oScorm->set_proximity($info['scormtype']);
                    $oScorm->set_maker('Scorm');
                    $oScorm->set_jslib('scorm_api.php');
                }
            }

            return true;
        }

        return false;
    }

    /**
     * It saves glossary terms from module xml.
     *
     * @param $moduleValues
     * @param $moduleId
     *
     * @return bool
     */
    public function processGlossary($moduleValues, $moduleId)
    {
        if (!empty($moduleValues[$moduleId])) {
            foreach ($moduleValues[$moduleId] as $entry) {
                $values = [];
                $values['name'] = $entry['concept'];
                $values['description'] = $entry['definition'];
                GlossaryManager::save_glossary($values);
            }

            return true;
        }

        return false;
    }

    /**
     * It process the module as document html.
     *
     * @param $moduleValues
     * @param $moduleName
     *
     * @return false|int
     */
    public function processHtmlDocument($moduleValues, $moduleName)
    {
        $filepath = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document/';
        $title = trim($moduleValues['name']);
        if ('$@NULL@$' == $title) {
            $title = get_lang('Tag');
        }

        // Setting the filename
        $filename = $title;
        $filename = addslashes(trim($filename));
        $filename = Security::remove_XSS($filename);
        $filename = api_replace_dangerous_char($filename);
        $filename = disable_dangerous_file($filename);
        $filename .= DocumentManager::getDocumentSuffix(
            api_get_course_info(),
            api_get_session_id(),
            api_get_group_id()
        );

        $dir = '/';
        $extension = 'html';
        if (file_exists($filepath.$filename.'.'.$extension)) {
            return false;
        }
        $content = ('page' == $moduleName ? $moduleValues['content'] : $moduleValues['intro']);
        $content = api_html_entity_decode($content);
        $content = $this->replaceMoodleChamiloCoursePath($content);

        if ($fp = @fopen($filepath.$filename.'.'.$extension, 'w')) {
            $content = str_replace(
                api_get_path(WEB_COURSE_PATH),
                api_get_configuration_value('url_append').api_get_path(REL_COURSE_PATH),
                $content
            );

            fputs($fp, $content);
            fclose($fp);
            chmod($filepath.$filename.'.'.$extension, api_get_permissions_for_new_files());
            $fileSize = filesize($filepath.$filename.'.'.$extension);
            $saveFilePath = $dir.$filename.'.'.$extension;

            $documentId = add_document(
                api_get_course_info(),
                $saveFilePath,
                'file',
                $fileSize,
                $title
            );

            if ($documentId) {
                api_item_property_update(
                    api_get_course_info(),
                    TOOL_DOCUMENT,
                    $documentId,
                    'DocumentAdded',
                    api_get_user_id(),
                    [],
                    null,
                    null,
                    null,
                    api_get_session_id()
                );
                // Update parent folders
                item_property_update_on_folder(api_get_course_info(), $dir, api_get_user_id());
            }

            return $documentId;
        }

        return false;
    }

    /**
     * Read and validate the forum module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed|array if is a valid xml file, false otherwise
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
     * @return mixed|array if is a valid xml file, false otherwise
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
     * @return mixed|array if is a valid xml file, false otherwise
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
     * @return mixed|array if is a valid xml file, false otherwise
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
     * @return mixed|array if is a valid xml file, false otherwise
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
     * @return mixed|array if is a valid xml file, false otherwise
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
                if ('qtype' === $item->nodeName) {
                    $questionType = $item->nodeValue;
                }

                if ($item->nodeName != 'plugin_qtype_'.$questionType.'_question') {
                    continue;
                }

                $answer = $item->getElementsByTagName($this->getQuestionTypeAnswersTag($questionType));
                $currentItem['plugin_qtype_'.$questionType.'_question'] = [];
                for ($i = 0; $i <= $answer->length - 1; $i++) {
                    $label = 'plugin_qtype_'.$questionType.'_question';
                    $currentItem[$label][$i]['answerid'] = $answer->item($i)->getAttribute('id');
                    foreach ($answer->item($i)->childNodes as $properties) {
                        $currentItem[$label][$i][$properties->nodeName] = $properties->nodeValue;
                    }
                }

                $typeValues = $item->getElementsByTagName($this->getQuestionTypeOptionsTag($questionType));
                for ($i = 0; $i <= $typeValues->length - 1; $i++) {
                    foreach ($typeValues->item($i)->childNodes as $properties) {
                        $currentItem[$questionType.'_values'][$properties->nodeName] = $properties->nodeValue;
                        if ($properties->nodeName !== 'sequence') {
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
     * @param array Result of readMainQuestionsXml
     *
     * @return int Chamilo question type
     */
    public function matchMoodleChamiloQuestionTypes($questionsValues)
    {
        $moodleQuestionType = $questionsValues['qtype'];
        $questionOptions = $moodleQuestionType.'_values';
        // Check <single> located in <plugin_qtype_multichoice_question><multichoice><single><single>
        if (
            'multichoice' === $moodleQuestionType &&
            isset($questionsValues[$questionOptions]) &&
            isset($questionsValues[$questionOptions]['single']) &&
            1 === (int) $questionsValues[$questionOptions]['single']
        ) {
            return UNIQUE_ANSWER;
        }

        switch ($moodleQuestionType) {
            case 'multichoice':
                return MULTIPLE_ANSWER;
            case 'multianswer':
            case 'shortanswer':
            case 'match':
                return FILL_IN_BLANKS;
            case 'essay':
                return FREE_ANSWER;
            case 'truefalse':
                return UNIQUE_ANSWER;
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
                $objAnswer = new Answer($questionInstance->iid);
                $questionWeighting = 0;
                foreach ($questionList as $slot => $answer) {
                    $this->processMultipleAnswer(
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
                $objAnswer = new Answer($questionInstance->iid);
                $coursePath = api_get_course_path();
                $placeholder = $this->replaceMoodleChamiloCoursePath($currentQuestion['questiontext']);
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
                $objAnswer = new Answer($questionInstance->iid);
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
                $objAnswer = new Answer($questionInstance->iid);
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
        $answer = $this->replaceMoodleChamiloCoursePath($answer);
        $comment = $this->replaceMoodleChamiloCoursePath($comment);

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

    public function processMultipleAnswer(
        Answer $objAnswer,
        $answerValues,
        $position,
        &$questionWeighting,
        $importedFiles
    ) {
        $answer = $answerValues['answertext'];
        $comment = $answerValues['feedback'];
        $weighting = $answerValues['fraction'];
        //$weighting = abs($weighting);
        if ($weighting > 0) {
            $questionWeighting += $weighting;
        }
        $goodAnswer = $weighting > 0;

        $this->fixPathInText($importedFiles, $answer);
        $answer = $this->replaceMoodleChamiloCoursePath($answer);
        $comment = $this->replaceMoodleChamiloCoursePath($comment);

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
     * @param Answer $objAnswer
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
        $answer = $this->replaceMoodleChamiloCoursePath($answer);
        $comment = $this->replaceMoodleChamiloCoursePath($comment);

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
                    $answer['questiontext'] = $this->replaceMoodleChamiloCoursePath($answer['questiontext']);

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

                    if ($item->nodeName == 'component' && $item->nodeValue == 'mod_scorm') {
                        $currentItem['modscorm'] = true;
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
