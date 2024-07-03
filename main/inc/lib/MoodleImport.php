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
        $allFiles = [];
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
                $allFiles[$fileInfo['contextid']][] = $files;
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
        $n = 1;
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
                    $this->processLesson($moduleValues, $allFiles);
                    break;
                case 'assign':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readAssignModule($moduleXml);
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $assignId = $this->processAssignment($moduleValues, $allFiles, $sectionPath);

                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($assignId)) {
                        $this->processSectionItem($sectionLpValues[$currentItem['sectionid']]['lpId'], 'student_publication', $assignId, $moduleValues['name'], $n);
                        $n++;
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
                    $this->processGlossary($moduleValues, $currentItem['moduleid'], $allFiles, '');
                    break;
                case 'label':
                case 'page':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readHtmlModule($moduleXml, $moduleName);
                    $sectionPath = isset($currentItem['sectionid']) ? '/'.$sectionLpValues[$currentItem['sectionid']]['sectionPath'].'/' : '/';
                    $contextId = $moduleValues['attributes']['contextid'];
                    if (isset($allFiles[$contextId])) {
                        $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
                    }
                    $documentId = $this->processHtmlDocument($moduleValues, $moduleName, $importedFiles, $sectionPath);

                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($documentId)) {
                        $this->processSectionItem($sectionLpValues[$currentItem['sectionid']]['lpId'], 'document', $documentId, $moduleValues['name'], $n);
                        $n++;
                    }
                    break;
                case 'forum':
                    $catForumValues = [];
                    // Read the current forum module xml.
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readForumModule($moduleXml);
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $contextId = $moduleValues['attributes']['contextid'];
                    if (isset($allFiles[$contextId])) {
                        $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
                    }

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
                    $moduleValues['intro'] = $this->replaceMoodleChamiloCoursePath($moduleValues['intro'], $sectionPath);
                    if ($importedFiles) {
                        $this->fixPathInText($importedFiles, $moduleValues['intro']);
                    }
                    $forumValues['forum_comment'] = $moduleValues['intro'];
                    $forumValues['forum_category'] = $catId;
                    $forumValues['moderated'] = 0;

                    $forumId = store_forum($forumValues, $courseInfo, true);
                    if (!empty($moduleValues['discussions'])) {
                        $forum = get_forums($forumId);
                        foreach ($moduleValues['discussions'] as $discussion) {
                            $moduleValues['intro'] = $this->replaceMoodleChamiloCoursePath($moduleValues['intro'], $sectionPath);
                            if ($importedFiles) {
                                $this->fixPathInText($importedFiles, $moduleValues['intro']);
                            }
                            $postText = '';
                            if (!empty($discussion['posts'])) {
                                $postText = $discussion['posts'][0]['message'];
                                $postText = $this->replaceMoodleChamiloCoursePath($postText, $sectionPath);
                                if ($importedFiles) {
                                    $this->fixPathInText($importedFiles, $postText);
                                }
                            }
                            store_thread(
                                $forum,
                                [
                                    'forum_id' => $forumId,
                                    'thread_id' => 0,
                                    'gradebook' => 0,
                                    'post_title' => $discussion['name'],
                                    'post_text' => $postText,
                                    'category_id' => 1,
                                    'numeric_calification' => 0,
                                    'calification_notebook_title' => 0,
                                    'weight_calification' => 0.00,
                                    'thread_peer_qualify' => 0,
                                    'lp_item_id' => 0,
                                ],
                                [],
                                false
                            );
                        }
                    }
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($forumId)) {
                        $this->processSectionItem($sectionLpValues[$currentItem['sectionid']]['lpId'], 'forum', $forumId, $moduleValues['name'], $n);
                        $n++;
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
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $contextId = $moduleValues['attributes']['contextid'];
                    if (isset($allFiles[$contextId])) {
                        $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
                    }
                    // At this point we got all the prepared resources from Moodle file
                    // $moduleValues variable contains all the necesary info to the quiz import
                    // var_dump($moduleValues); // <-- uncomment this to see the final array

                    $exercise = new Exercise($courseInfo['real_id']);
                    if ($debug) {
                        error_log('quiz:'.$moduleValues['name']);
                    }

                    $title = Exercise::format_title_variable($moduleValues['name']);
                    $exercise->updateTitle($title);
                    $introText = $this->replaceMoodleChamiloCoursePath($moduleValues['intro'], $sectionPath);
                    $exercise->updateDescription($introText);
                    $exercise->updateAttempts($moduleValues['attempts_number']);
                    $feedbackType = 2;
                    if (in_array($moduleValues['preferredbehaviour'], ['adaptive', 'adaptivenopenalty'])) {
                        $feedbackType = 1;
                    } elseif (in_array($moduleValues['preferredbehaviour'], ['immediatefeedback', 'immediatecbm'])) {
                        $feedbackType = 3;
                    } elseif ('deferredfeedback' === $moduleValues['preferredbehaviour']) {
                        $feedbackType = 0;
                    }
                    $exercise->updateFeedbackType($feedbackType);

                    // Match shuffle question with chamilo
                    if (isset($moduleValues['shufflequestions']) &&
                        (int) $moduleValues['shufflequestions'] === 1
                    ) {
                        $exercise->setRandom(-1);
                    } else {
                        $exercise->setRandom(0);
                    }
                    $exercise->updateRandomAnswers(!empty($moduleValues['shuffleanswers']));
                    $limeLimit = 0;
                    if (!empty($moduleValues['timelimit'])) {
                        $limeLimit = round($moduleValues['timelimit'] / 60);
                    }
                    $exercise->updateExpiredTime((int) $limeLimit);
                    $gradesXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/grades.xml');
                    $gradeQuizValues = $this->readQuizGradeModule($gradesXml, $moduleValues['quiz_id']);
                    $exercise->pass_percentage = 0;
                    if (!empty($gradeQuizValues['grademax'])) {
                        $gradeMax = (int) $gradeQuizValues['grademax'];
                        $gradePass = (int) $gradeQuizValues['gradepass'];
                        $exercise->pass_percentage = round(($gradePass * 100) / $gradeMax);
                    }

                    if ($moduleValues['questionsperpage'] == 1) {
                        $exercise->updateType(2);
                    } else {
                        $exercise->updateType(1);
                    }
                    $exercise->start_time = null;
                    if (!empty($moduleValues['timeopen'])) {
                        $exercise->start_time = api_get_utc_datetime($moduleValues['timeopen']);
                    }
                    $exercise->end_time = null;
                    if (!empty($moduleValues['timeclose'])) {
                        $exercise->end_time = api_get_utc_datetime($moduleValues['timeclose']);
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

                        $questionText = $this->replaceMoodleChamiloCoursePath($questionText, $sectionPath);

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
                            $importedFiles,
                            $sectionPath
                        );
                    }
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($exercise->iid)) {
                        $this->processSectionItem($sectionLpValues[$currentItem['sectionid']]['lpId'], 'quiz', $exercise->iid, $title, $n);
                        $n++;
                    }
                    break;
                case 'folder':
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $filesXml = @file_get_contents($destinationDir.'/files.xml');
                    $moduleValues = $this->readFolderModule($moduleXml);
                    $mainFileModuleValues = $this->readFolderModuleFilesXml(
                        $filesXml,
                        $moduleValues['contextid']
                    );
                    $resourcesFiles = [];
                    $currentResourceFilePath = $destinationDir.'/files/';
                    $dirs = new RecursiveDirectoryIterator($currentResourceFilePath);
                    foreach (new RecursiveIteratorIterator($dirs) as $file) {
                        foreach ($mainFileModuleValues['files'] as $info) {
                            if (!is_file($file) || false === strpos($file, $info['contenthash'])) {
                                continue;
                            }
                            $files = [];
                            $files['file']['name'] = $info['filename'];
                            $files['file']['tmp_name'] = $file->getPathname();
                            $files['file']['type'] = $info['mimetype'];
                            $files['file']['error'] = 0;
                            $files['file']['size'] = $info['filesize'];
                            $files['file']['from_file'] = true;
                            $files['file']['move_file'] = true;
                            $files['file']['filepath'] = $info['filepath'];
                            $resourcesFiles[] = $files;
                        }
                    }
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $lpId = (int) $sectionLpValues[$currentItem['sectionid']]['lpId'];
                    $this->processSectionFolderModule($mainFileModuleValues, $sectionPath, $moduleValues['name'], $resourcesFiles, $lpId, $n);
                    $n++;

                    break;
                case 'resource':
                    // Read the current resource module xml.
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $filesXml = @file_get_contents($destinationDir.'/files.xml');
                    $moduleValues = $this->readResourceModule($moduleXml);
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $mainFileModuleValues = $this->readMainFilesXml(
                        $filesXml,
                        $moduleValues['contextid']
                    );
                    $resourcesFiles = [];
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

                        $resourcesFiles[] = $files;
                    }
                    if (!empty($resourcesFiles)) {
                        $lpId = 0;
                        if (!empty($currentItem['sectionid'])) {
                            $lpId = $sectionLpValues[$currentItem['sectionid']]['lpId'];
                        }
                        $importedFiles = $this->processSectionMultimedia($resourcesFiles, $sectionPath, $lpId, $n);
                        $n++;
                    }

                    break;
                case 'url':
                    // Read the current url module xml.
                    $moduleDir = $currentItem['directory'];
                    $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/'.$moduleName.'.xml');
                    $moduleValues = $this->readUrlModule($moduleXml);
                    $sectionPath = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionPath'] : '';
                    $sectionName = isset($sectionLpValues[$currentItem['sectionid']]) ? $sectionLpValues[$currentItem['sectionid']]['sectionName'] : '';
                    $categoryId = 0;
                    if (!empty($sectionName)) {
                        $category = Link::getCategoryByName($sectionName);
                        if (!empty($category)) {
                            $categoryId = $category['iid'];
                        } else {
                            $_POST['category_title'] = $sectionName;
                            $_POST['description'] = '';
                            $categoryId = Link::addlinkcategory('category');
                        }
                    }
                    $contextId = $moduleValues['attributes']['contextid'];
                    if (isset($allFiles[$contextId])) {
                        $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
                    }
                    $_POST['title'] = $moduleValues['name'];
                    $_POST['url'] = $moduleValues['externalurl'];
                    $moduleValues['intro'] = $this->replaceMoodleChamiloCoursePath($moduleValues['intro'], $sectionPath);
                    if ($importedFiles) {
                        $this->fixPathInText($importedFiles, $moduleValues['intro']);
                    }
                    $_POST['description'] = strip_tags($moduleValues['intro']);
                    $_POST['category_id'] = $categoryId;
                    $_POST['target'] = '_blank';

                    $linkId = Link::addlinkcategory('link');
                    // It is added as item in Learnpath
                    if (!empty($currentItem['sectionid']) && !empty($linkId)) {
                        $this->processSectionItem($sectionLpValues[$currentItem['sectionid']]['lpId'], 'link', $linkId, $moduleValues['name'], $n);
                        $n++;
                    }
                    break;
            }
        }

        if (!empty($sectionLpValues)) {
            foreach ($sectionLpValues as $section) {
                if (!empty($section['sectionPath'])) {
                    $documentPath = '/'.$section['sectionPath'];
                    $baseWorkDir = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
                    $checkDir = $baseWorkDir.$documentPath;
                    if ($this->isEmptyDir($checkDir)) {
                        $document = DocumentManager::getDocumentByPathInCourse($courseInfo, $documentPath);
                        my_delete($checkDir);
                        // Hard delete.
                        DocumentManager::deleteDocumentFromDb(
                            $document[0]['iid'],
                            $courseInfo,
                            api_get_session_id(),
                            true
                        );
                    }
                }
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
     * @param string $sectionPath
     *
     * @return string
     */
    public function replaceMoodleChamiloCoursePath($text, $sectionPath = '')
    {
        if (!empty($sectionPath)) {
            $sectionPath = '/'.$sectionPath;
        }
        $multimediaPath = $sectionPath.'/Multimedia';
        $coursePath = api_get_course_path();
        $text = str_replace(
            '@@PLUGINFILE@@',
            '/courses/'.$coursePath.'/document'.$multimediaPath,
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
     * @param int $dspOrder
     *
     * @return void
     */
    public function processSectionItem($lpId, $itemType, $itemId, $itemTitle, $dspOrder = 0)
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
            '',
            0,
            0,
            0,
            $dspOrder
        );
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

        $courseInfo = api_get_course_info();
        $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
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
                $dirName = api_replace_dangerous_char($lpName);
                create_unexisting_directory(
                    $courseInfo,
                    api_get_user_id(),
                    api_get_session_id(),
                    api_get_group_id(),
                    null,
                    $documentPath,
                    '/'.$dirName,
                    $lpName,
                    0
                );
                $lpAdded[$sectionId] = [
                    'lpId' => $lpId,
                    'sectionPath' => $dirName,
                    'sectionName' => $lpName,
                ];
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
                $i = 0;
                $answerNodes = $page->getElementsByTagName("answer");
                $answers = [];
                foreach ($answerNodes as $answer) {
                    foreach ($answer->childNodes as $n) {
                        $answers[$i][$n->nodeName] = $n->nodeValue;
                    }
                    $i++;
                }
                $pagesList[$counter]['answers'] = $answers;
                $counter++;
            }
        }
        $currentItem['pages'] = $pagesList;
        $attributes = $this->getDocActivityAttributes($doc);
        $currentItem['attributes'] = $attributes;

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
                if (in_array($item->nodeName, ['name', 'intro', 'duedate', 'cutoffdate', 'grade'])) {
                    $info[$item->nodeName] = $item->nodeValue;
                }
            }
        }

        $configOpts = $doc->getElementsByTagName('plugin_config');
        $config = [];
        $counter = 0;
        foreach ($configOpts as $opt) {
            if (empty($opt->childNodes->length)) {
                continue;
            }
            $pluginName = '';
            foreach ($opt->childNodes as $item) {
                if ('#text' == $item->nodeName) {
                    continue;
                }
                if ('plugin' == $item->nodeName && !in_array($item->nodeValue, ['onlinetext', 'file'])) {
                    break;
                }
                if ('subtype' == $item->nodeName && 'assignsubmission' != $item->nodeValue) {
                    break;
                }
                if ('name' == $item->nodeName && 'enabled' != $item->nodeValue) {
                    break;
                }
                if ('plugin' == $item->nodeName) {
                    $pluginName = $item->nodeValue;
                }
                if ('value' == $item->nodeName) {
                    $config[$pluginName]['enabled'] = (int) $item->nodeValue;
                }
            }
            $counter++;
        }
        $info['config'] = $config;
        $attributes = $this->getDocActivityAttributes($doc);
        $info['attributes'] = $attributes;

        return $info;
    }

    /**
     * It gets the attributes of each activity from module xml.
     *
     * @param $doc
     *
     * @return array
     */
    public function getDocActivityAttributes($doc)
    {
        $activityAttr = [];
        $searchActivity = $doc->getElementsByTagName('activity');
        foreach ($searchActivity as $searchNode) {
            $activityAttr['contextid'] = $searchNode->getAttribute('contextid');
            $activityAttr['modulename'] = $searchNode->getAttribute('modulename');
            $activityAttr['moduleid'] = $searchNode->getAttribute('moduleid');
        }

        return $activityAttr;
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
        $attributes = $this->getDocActivityAttributes($doc);
        $glossaryInfo['attributes'] = $attributes;

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
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

        return $currentItem;
    }

    /**
     * It addes the files from a resources type folder.
     *
     * @param     $files
     * @param     $mainFolderName
     * @param     $sectionPath
     * @param int $lpId
     * @param int $n
     */
    public function processSectionFolderModule($mainFileModuleValues, $sectionPath, $mainFolderName, $resourcesFiles, $lpId = 0, $dspOrder = 0)
    {
        if (!empty($mainFileModuleValues['folder'])) {
            $courseInfo = api_get_course_info();
            $chapters = [];
            $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
            // It creates the first lp chapter (module folder name)
            $safeMainFolderName = api_replace_dangerous_char($mainFolderName);
            $documentData = create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                api_get_session_id(),
                api_get_group_id(),
                null,
                $documentPath,
                '/'.$sectionPath.'/'.$safeMainFolderName,
                $mainFolderName,
                0
            );
            if (!empty($lpId) && !empty($documentData['iid'])) {
                $lp = new \learnpath(
                    api_get_course_id(),
                    $lpId,
                    api_get_user_id()
                );
                $lpItemId = $lp->add_item(
                    0,
                    0,
                    'dir',
                    $documentData['iid'],
                    $mainFolderName,
                    '',
                    0,
                    0,
                    0,
                    $dspOrder
                );
                $chapters['/'] = $lpItemId;
            }
            // It checks the subfolder for second level.
            foreach ($mainFileModuleValues['folder'] as $folder) {
                if ('/' == $folder['filepath']) {
                    continue;
                }
                $folder['filepath'] = trim($folder['filepath'], '/');
                $arrFolderPath = explode('/', $folder['filepath']);
                if (1 == count($arrFolderPath)) {
                    $folderName = $arrFolderPath[0];
                    $safeFolderName = api_replace_dangerous_char($folderName);
                    $documentSubData = create_unexisting_directory(
                        $courseInfo,
                        api_get_user_id(),
                        api_get_session_id(),
                        api_get_group_id(),
                        null,
                        $documentPath,
                        '/'.$sectionPath.'/'.$safeMainFolderName.'/'.$safeFolderName,
                        $folderName,
                        0
                    );
                    if (!empty($lpId) && !empty($documentSubData['iid'])) {
                        $lp = new \learnpath(
                            api_get_course_id(),
                            $lpId,
                            api_get_user_id()
                        );
                        $lpItemId = $lp->add_item(
                            $chapters['/'],
                            0,
                            'dir',
                            $documentSubData['iid'],
                            $folderName,
                            '',
                            0,
                            0,
                            0
                        );
                        $chapters["/$folderName/"] = $lpItemId;
                    }
                }
            }
            if (!empty($resourcesFiles)) {
                foreach ($resourcesFiles as $file) {
                    $title = pathinfo($file['file']['name'], PATHINFO_FILENAME);
                    $path = $file['file']['filepath'];
                    if (1 == count(explode('/', trim($path, '/')))) {
                        $safePath = api_replace_dangerous_char($path);
                        $newSafePath = !empty($safePath) ? '/'.$sectionPath.'/'.$safeMainFolderName.'/'.$safePath : '/'.$sectionPath.'/'.$safeMainFolderName;
                        $data = DocumentManager::upload_document(
                            $file,
                            $newSafePath,
                            $title,
                            '',
                            null,
                            'overwrite',
                            true,
                            true,
                            'file',
                            false
                        );
                        if (!empty($lpId) && !empty($data['iid'])) {
                            // It is added as item in Learnpath
                            $lp = new \learnpath(
                                api_get_course_id(),
                                $lpId,
                                api_get_user_id()
                            );
                            $lpItemId = $lp->add_item(
                                $chapters[$path],
                                0,
                                'document',
                                $data['iid'],
                                $title,
                                '',
                                0,
                                0,
                                0
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * It reorganizes files imported to documents.
     *
     * @param $files
     * @param $sectionPath
     *
     * @return array
     */
    public function processSectionMultimedia($files, $sectionPath, $lpId = 0, $n = 0)
    {
        $importedFiles = [];
        if (!empty($files)) {
            $courseInfo = api_get_course_info();
            if (!empty($sectionPath)) {
                $sectionPath = '/'.trim($sectionPath, '/\\');
            }
            $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
            $multimediaPath = $documentPath.$sectionPath.'/Multimedia';
            if (!is_dir($multimediaPath)) {
                create_unexisting_directory(
                    $courseInfo,
                    api_get_user_id(),
                    api_get_session_id(),
                    api_get_group_id(),
                    null,
                    $documentPath,
                    $sectionPath.'/Multimedia',
                    'Multimedia',
                    0
                );
            }
            foreach ($files as $file) {
                $title = pathinfo($file['file']['name'], PATHINFO_FILENAME);
                $path = !empty($lpId) ? $sectionPath : $sectionPath.'/Multimedia';
                $data = DocumentManager::upload_document(
                    $file,
                    $path,
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
                    $importedFiles[$file['file']['name']] = basename($data['path']);
                    // It is added as item in Learnpath
                    if (!empty($lpId) && !empty($data['iid'])) {
                        $this->processSectionItem($lpId, 'document', $data['iid'], $title, $n);
                    }
                }
            }
        }

        return $importedFiles;
    }

    /**
     * It saves a learnpath from module xml.
     *
     * @param $moduleValues
     *
     * @return false
     */
    public function processLesson($moduleValues, $allFiles = [])
    {
        if (!empty($moduleValues['pages'])) {
            $qtypes = [
                20 => 'page',
                10 => 'essay',
                5 => 'matching',
                3 => 'multichoice',
                1 => 'shortanswer',
                2 => 'truefalse',
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

            $dirName = api_replace_dangerous_char($lpName);
            $courseInfo = api_get_course_info();
            $documentPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
            create_unexisting_directory(
                $courseInfo,
                api_get_user_id(),
                api_get_session_id(),
                api_get_group_id(),
                null,
                $documentPath,
                '/'.$dirName,
                $lpName,
                0
            );

            $importedFiles = [];
            $contextId = $moduleValues['attributes']['contextid'];
            if (isset($allFiles[$contextId])) {
                $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $dirName);
            }

            $questionList = [];
            foreach ($items as $item) {
                if (in_array($item['qtype'], array_keys($qtypes))) {
                    $qTypeName = $qtypes[$item['qtype']];
                    switch ($qTypeName) {
                        case 'page':
                            $pageValues = [];
                            $pageValues['name'] = $item['title'];
                            $pageValues['content'] = $item['contents'];
                            $sectionPath = '/'.$dirName.'/';
                            $documentId = $this->processHtmlDocument($pageValues, 'page', $importedFiles, $sectionPath);
                            $this->processSectionItem($lpId, 'document', $documentId, $pageValues['name']);
                            break;
                        case 'essay':
                        case 'match':
                        case 'multichoice':
                        case 'shortanswer':
                        case 'truefalse':
                            $qType = $qtypes[$item['qtype']];
                            $question = [];
                            $question['qtype'] = $qType;
                            $question['name'] = $item['title'];
                            $question['questiontext'] = api_utf8_decode($item['contents']);
                            $question[$qType.'_values']['single'] = 1;
                            $question['questionType'] = $this->matchMoodleChamiloQuestionTypes($question);
                            $answers = [];
                            if (!empty($item['answers'])) {
                                $defaultmark = 0;
                                foreach ($item['answers'] as $answer) {
                                    $answerValue = [];
                                    $answerValue['answertext'] = api_utf8_decode($answer['answer_text']);
                                    $answerValue['feedback'] = api_utf8_decode($answer['response']);
                                    $answerValue['fraction'] = $answer['score'];
                                    $defaultmark += $answer['score'];
                                    $answers[] = $answerValue;
                                }
                                $question['defaultmark'] = $defaultmark;
                            }
                            $question['answers'] = $answers;
                            $questionList[] = $question;
                            break;
                    }
                }
            }

            if (!empty($questionList)) {
                $courseInfo = api_get_course_info();
                // It creates a quiz for those questions.
                $exercise = new Exercise($courseInfo['real_id']);
                $quizLpName = $lpName.' - '.get_lang('Quiz');
                $title = Exercise::format_title_variable($quizLpName);
                $exercise->updateTitle($title);
                $moduleValues['intro'] = $this->replaceMoodleChamiloCoursePath($moduleValues['intro'], $dirName);
                $exercise->updateDescription(api_utf8_decode($moduleValues['intro']));
                $exercise->updateAttempts(0);
                $exercise->updateFeedbackType(0);
                $exercise->setRandom(0);
                $exercise->updateRandomAnswers(false);
                $exercise->updateExpiredTime(0);
                $exercise->updateType(2);
                $exercise->updateResultsDisabled(0);

                // Create the new Quiz
                $exercise->save();

                $this->processSectionItem($lpId, 'quiz', $exercise->iid, $quizLpName);

                // Ok, we got the Quiz and create it, now its time to add the Questions
                foreach ($questionList as $question) {
                    $questionInstance = Question::getInstance($question['questionType']);
                    if (empty($questionInstance)) {
                        continue;
                    }
                    $questionInstance->updateTitle($question['name']);
                    $questionText = $question['questiontext'];
                    $questionText = $this->replaceMoodleChamiloCoursePath($questionText, $dirName);
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
                    $this->processAnswers(
                        $exercise,
                        $question['answers'],
                        $question['qtype'],
                        $questionInstance,
                        $question,
                        $importedFiles,
                        $dirName
                    );
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
    public function processAssignment($assign, $allFiles = [], $sectionPath = '')
    {
        if (!empty($assign)) {
            require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

            $importedFiles = [];
            $contextId = $assign['attributes']['contextid'];
            if (isset($allFiles[$contextId])) {
                $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
            }

            $values = [];
            $values['new_dir'] = $assign['name'];
            if (!empty($assign['cutoffdate'])) {
                $values['enableEndDate'] = 1;
                $values['ends_on'] = date('Y-m-d H:i', $assign['cutoffdate']);
            }
            if (!empty($assign['duedate'])) {
                $values['enableExpiryDate'] = 1;
                $values['expires_on'] = date('Y-m-d H:i', $assign['duedate']);
            }
            $values['work_title'] = $assign['name'];
            $assign['intro'] = $this->replaceMoodleChamiloCoursePath($assign['intro'], $sectionPath);
            if ($importedFiles) {
                $this->fixPathInText($importedFiles, $assign['intro']);
            }

            $values['description'] = api_utf8_decode($assign['intro']);
            $values['qualification'] = (int) $assign['grade'];
            $values['weight'] = (int) $assign['grade'];
            $values['allow_text_assignment'] = 2;
            if (!empty($assign['config'])) {
                if (1 == (int) $assign['config']['onlinetext']['enabled'] && 1 == (int) $assign['config']['file']['enabled']) {
                    $values['allow_text_assignment'] = 0;
                } elseif (1 == (int) $assign['config']['onlinetext']['enabled'] && empty($assign['config']['file']['enabled'])) {
                    $values['allow_text_assignment'] = 1;
                } elseif (empty($assign['config']['onlinetext']['enabled']) && 1 == (int) $assign['config']['file']['enabled']) {
                    $values['allow_text_assignment'] = 2;
                }
            }

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
    public function processGlossary($moduleValues, $moduleId, $allFiles = [], $sectionPath = '/')
    {
        $importedFiles = [];
        $contextId = $moduleValues['attributes']['contextid'];
        if (isset($allFiles[$contextId])) {
            $importedFiles = $this->processSectionMultimedia($allFiles[$contextId], $sectionPath);
        }

        if (!empty($moduleValues[$moduleId])) {
            foreach ($moduleValues[$moduleId] as $entry) {
                $values = [];
                $values['name'] = $entry['concept'];
                $entry['definition'] = $this->replaceMoodleChamiloCoursePath($entry['definition'], $sectionPath);
                if ($importedFiles) {
                    $this->fixPathInText($importedFiles, $entry['definition']);
                }
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
    public function processHtmlDocument($moduleValues, $moduleName, $importedFiles = [], $sectionPath = '/')
    {
        $dir = $sectionPath;
        $filepath = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/document'.$dir;
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

        $extension = 'html';
        $content = ('page' == $moduleName ? $moduleValues['content'] : $moduleValues['intro']);
        $content = api_html_entity_decode($content);
        $cleanSectionPath = trim($sectionPath, '/\\');
        $content = $this->replaceMoodleChamiloCoursePath($content, $cleanSectionPath);

        if ($importedFiles) {
            $this->fixPathInText($importedFiles, $content);
        }

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
                    if (in_array($item->nodeName, ['type', 'name', 'intro', 'grade_forum'])) {
                        $currentItem[$item->nodeName] = $item->nodeValue;
                    }
                }
            }
        }

        $discussions = $moduleDoc->getElementsByTagName('discussion');

        $discussionsList = [];
        $counter = 0;
        foreach ($discussions as $discussion) {
            if ($discussion->childNodes->length) {
                foreach ($discussion->childNodes as $item) {
                    $discussionsList[$counter][$item->nodeName] = $item->nodeValue;
                }
                $i = 0;
                $postsNodes = $discussion->getElementsByTagName("post");
                $posts = [];
                foreach ($postsNodes as $post) {
                    foreach ($post->childNodes as $n) {
                        $posts[$i][$n->nodeName] = $n->nodeValue;
                    }
                    $i++;
                }
                $discussionsList[$counter]['posts'] = $posts;
                $counter++;
            }
        }
        $currentItem['discussions'] = $discussionsList;
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

        return $currentItem;
    }

    /**
     * Read and validate the folder module XML.
     *
     * @param resource $moduleXml XML file
     *
     * @return mixed|array if is a valid xml file, false otherwise
     */
    public function readFolderModule($moduleXml)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($moduleXml);
        if (empty($moduleRes)) {
            return false;
        }
        $activities = $moduleDoc->getElementsByTagName('folder');
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
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

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
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

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
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

        return $currentItem;
    }

    /**
     * It gets the grade values about a quiz imported.
     *
     * @param $gradeXml
     * @param $quizId
     *
     * @return false|mixed
     */
    public function readQuizGradeModule($gradeXml, $quizId)
    {
        $doc = new DOMDocument();
        $gradeRes = @$doc->loadXML($gradeXml);
        if (empty($gradeRes)) {
            return false;
        }
        $entries = $doc->getElementsByTagName('grade_item');
        $info = [];
        $i = 0;
        foreach ($entries as $entry) {
            if (empty($entry->childNodes->length)) {
                continue;
            }
            foreach ($entry->childNodes as $item) {
                if (in_array($item->nodeName, ['iteminstance', 'grademax', 'grademin', 'gradepass'])) {
                    $info[$i][$item->nodeName] = $item->nodeValue;
                }
            }
            $i++;
        }
        $grades = [];
        if (!empty($info)) {
            foreach ($info as $res) {
                $grades[$res['iteminstance']] = $res;
            }

            return $grades[$quizId];
        }

        return false;
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
                $currentItem['quiz_id'] = $activity->getAttribute('id');
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
        $attributes = $this->getDocActivityAttributes($moduleDoc);
        $currentItem['attributes'] = $attributes;

        return $currentItem;
    }

    /**
     * Search the files of a resource type folder in main Files XML.
     *
     * @param resource $filesXml  XML file
     * @param int      $contextId
     *
     * @return mixed|array if is a valid xml file, false otherwise
     */
    public function readFolderModuleFilesXml($filesXml, $contextId = null)
    {
        $moduleDoc = new DOMDocument();
        $moduleRes = @$moduleDoc->loadXML($filesXml);

        if (empty($moduleRes)) {
            return false;
        }
        $activities = $moduleDoc->getElementsByTagName('file');
        $filesInfo = [];
        $i = 0;
        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }
            foreach ($activity->childNodes as $item) {
                if (in_array($item->nodeName, ['filename', 'filesize', 'contenthash', 'contextid', 'filepath', 'filesize', 'mimetype'])) {
                    $filesInfo[$i][$item->nodeName] = $item->nodeValue;
                }
            }
            $i++;
        }
        $currentItem = [];
        if (!empty($filesInfo)) {
            foreach ($filesInfo as $info) {
                if (!empty($info['filesize'])) {
                    $currentItem[$info['contextid']]['files'][] = $info;
                } else {
                    $currentItem[$info['contextid']]['folder'][] = $info;
                }
            }
        }
        $files = isset($contextId) ? $currentItem[$contextId] : $currentItem;

        return $files;
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
        $filesInfo = [];
        $i = 0;
        foreach ($activities as $activity) {
            if (empty($activity->childNodes->length)) {
                continue;
            }
            foreach ($activity->childNodes as $item) {
                if (in_array($item->nodeName, ['filename', 'filesize', 'contenthash', 'contextid', 'filesize', 'mimetype'])) {
                    $filesInfo[$i][$item->nodeName] = $item->nodeValue;
                }
            }
            $i++;
        }
        $currentItem = [];
        if (!empty($filesInfo)) {
            foreach ($filesInfo as $info) {
                if (!empty($info['filesize'])) {
                    $currentItem[$info['contextid']] = $info;
                }
            }
        }

        return $currentItem[$contextId];
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
            case 'match':
                return FILL_IN_BLANKS;
            case 'essay':
            case 'shortanswer':
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
        $importedFiles,
        $sectionPath = ''
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
                        $importedFiles,
                        $sectionPath
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
                $placeholder = $this->replaceMoodleChamiloCoursePath($currentQuestion['questiontext'], $sectionPath);
                $optionsValues = [];
                foreach ($questionList as $slot => $subQuestion) {
                    $qtype = $subQuestion['qtype'];
                    $optionsValues[] = $this->processFillBlanks(
                        $objAnswer,
                        $qtype,
                        $subQuestion['plugin_qtype_'.$qtype.'_question'],
                        $placeholder,
                        $slot + 1
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
                    $sectionPath
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
            case 'ddmatch':
                $questionWeighting = $currentQuestion['defaultmark'];
                $questionInstance->updateWeighting($questionWeighting);
                $questionInstance->updateDescription(get_lang('ThisQuestionIsNotSupportedYet'));
                $questionInstance->save($exercise);

                return false;
            case 'shortanswer':
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
                        $importedFiles,
                        $sectionPath
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
        $importedFiles,
        $sectionPath = ''
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
        $answer = $this->replaceMoodleChamiloCoursePath($answer, $sectionPath);
        $comment = $this->replaceMoodleChamiloCoursePath($comment, $sectionPath);

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
        $importedFiles,
        $sectionPath = ''
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
        $answer = $this->replaceMoodleChamiloCoursePath($answer, $sectionPath);
        $comment = $this->replaceMoodleChamiloCoursePath($comment, $sectionPath);

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
        $importedFiles,
        $sectionPath = ''
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
        $answer = $this->replaceMoodleChamiloCoursePath($answer, $sectionPath);
        $comment = $this->replaceMoodleChamiloCoursePath($comment, $sectionPath);

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
     *
     * @return int db response
     */
    public function processFillBlanks(
        $objAnswer,
        $questionType,
        $answerValues,
        &$placeholder,
        $position,
        $sectionPath = ''
    ) {
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
                    $answer['questiontext'] = $this->replaceMoodleChamiloCoursePath($answer['questiontext'], $sectionPath);

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

                    if ($item->nodeName == 'contextid') {
                        $currentItem['contextid'] = $item->nodeValue;
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

    /**
     * Check if folder is empty or not.
     *
     * @param $dir
     *
     * @return bool
     */
    private function isEmptyDir($dir)
    {
        $iterator = new FilesystemIterator($dir);
        $isDirEmpty = !$iterator->valid();

        return $isDirEmpty;
    }
}
