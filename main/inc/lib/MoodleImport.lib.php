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

                        $moduleName = $currentItem['modulename'];
                        switch ($moduleName) {
                            case 'duh!':
                                require_once '../forum/forumfunction.inc.php';
                                $catForumValues = [];

                                // Read the current forum module xml.
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/forum.xml');
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

                                $result = store_forum($forumValues);
                                break;
                            case 'quiz':

                                break;
                            case 'resource':
                                // Read the current resource module xml.
                                $moduleDir = $currentItem['directory'];
                                $moduleXml = @file_get_contents($destinationDir.'/'.$moduleDir.'/resource.xml');
                                $filesXml = @file_get_contents($destinationDir.'/files.xml');
                                $moduleValues = $this->readResourceModule($moduleXml);
                                $fileInfo = $this->readMainFilesXml($filesXml, $moduleValues['contextid']);
                                var_dump($moduleValues);
                                var_dump($fileInfo);

                                break;
                            case 'url':

                                break;
                        }
                    }
                }
            }
        }

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

}