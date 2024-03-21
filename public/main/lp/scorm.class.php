<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use PhpZip\ZipFile;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Defines the scorm class, which is meant to contain the scorm items (nuclear elements).
 *
 * @author    Yannick Warnier <ywarnier@beeznest.org>
 */
class scorm extends learnpath
{
    public $manifest = [];
    public $resources = [];
    public $resources_att = [];
    public $organizations = [];
    public $organizations_att = [];
    public $metadata = [];
    // Will hold the references to resources for each item ID found.
    public $idrefs = [];
    // For each resource found, stores the file url/uri.
    public $refurls = [];
    /*  Path between the scorm/ directory and the imsmanifest.xml e.g.
    maritime_nav/maritime_nav. This is the path that will be used in the
    lp_path when importing a package. */
    public $subdir;
    public $manifestToString;
    public $items;
    // Keeps the zipfile safe for the object's life so that we can use it if no title avail.
    public $zipname = '';
    // Keeps an index of the number of uses of the zipname so far.
    public $lastzipnameindex = 0;
    public $manifest_encoding = 'UTF-8';
    public $asset = true;
    public $debug = true;

    /**
     * Class constructor. Based on the parent constructor.
     *
     * @param    string    Course code
     * @param    int    Learnpath ID in DB
     * @param    int    User ID
     */
    public function __construct($entity = null, $course_info = null, $user_id = null)
    {
        $this->items = [];
        $this->subdir = '';
        $this->manifestToString = '';
        parent::__construct($entity, $course_info, $user_id);
    }

    /**
     * Opens a resource.
     *
     * @param int $id Database ID of the resource
     */
    public function open($id)
    {
        if ($this->debug > 0) {
            error_log('scorm::open() - In scorm::open method', 0);
        }
    }

    /**
     * Possible SCO status: see CAM doc 2.3.2.5.1: passed, completed, browsed, failed, not attempted, incomplete.
     * Prerequisites: see CAM doc 2.3.2.5.1 for pseudo-code.
     *
     * Parses an imsmanifest.xml file and puts everything into the $manifest array.
     *
     * @param	string	Path to the imsmanifest.xml file on the system.
     * If not defined, uses the base path of the course's scorm dir
     *
     * @return array Structured array representing the imsmanifest's contents
     */
    public function parse_manifest()
    {
        if ($this->manifestToString) {
            $xml = $this->manifestToString;
            // $this->manifest_encoding = api_detect_encoding_xml($xml);
            // This is the usual way for reading the encoding.
            // This method reads the encoding, it tries to be correct even in cases
            // of wrong or missing encoding declarations.
            $this->manifest_encoding = self::detect_manifest_encoding($xml);

            // UTF-8 is supported by DOMDocument class, this is for sure.
            $xml = api_utf8_encode_xml($xml, $this->manifest_encoding);
            $crawler = new Crawler();
            $crawler->addXmlContent($xml);
            $xmlErrors = libxml_get_errors();

            if (!empty($xmlErrors)) {
                if ($this->debug > 0) {
                    error_log('In scorm::parse_manifest() - Exception thrown when loading DOMDocument');
                }
                // Throw exception?
                return null;
            }

            if ($this->debug > 1) {
                error_log('Called  (encoding:'.$this->manifest_encoding.' - saved: '.$this->manifest_encoding.')', 0);
            }

            $root = $crawler->getNode(0);
            if ($root->hasAttributes()) {
                $attributes = $root->attributes;
                if (0 !== $attributes->length) {
                    foreach ($attributes as $attrib) {
                        // <manifest> element attributes
                        $this->manifest[$attrib->name] = $attrib->value;
                    }
                }
            }
            $this->manifest['name'] = $root->tagName;
            if ($root->hasChildNodes()) {
                $children = $root->childNodes;
                if (0 !== $children->length) {
                    foreach ($children as $child) {
                        // <manifest> element children (can be <metadata>, <organizations> or <resources> )
                        if (XML_ELEMENT_NODE == $child->nodeType) {
                            switch ($child->tagName) {
                                case 'metadata':
                                    // Parse items from inside the <metadata> element.
                                    $this->metadata = new scormMetadata('manifest', $child);
                                    break;
                                case 'organizations':
                                    // Contains the course structure - this element appears 1 and only 1 time in a package imsmanifest.
                                    // It contains at least one 'organization' sub-element.
                                    $orgs_attribs = $child->attributes;
                                    foreach ($orgs_attribs as $orgs_attrib) {
                                        // Attributes of the <organizations> element.
                                        if (XML_ATTRIBUTE_NODE == $orgs_attrib->nodeType) {
                                            $this->manifest['organizations'][$orgs_attrib->name] = $orgs_attrib->value;
                                        }
                                    }
                                    $orgs_nodes = $child->childNodes;
                                    $i = 0;
                                    $found_an_org = false;
                                    foreach ($orgs_nodes as $orgnode) {
                                        // <organization> elements - can contain <item>, <metadata> and <title>
                                        // Here we are at the 'organization' level. There might be several organization tags but
                                        // there is generally only one.
                                        // There are generally three children nodes we are looking for inside and organization:
                                        // -title
                                        // -item (may contain other item tags or may appear several times inside organization)
                                        // -metadata (relative to the organization)
                                        $found_an_org = false;
                                        switch ($orgnode->nodeType) {
                                            case XML_TEXT_NODE:
                                                // Ignore here.
                                                break;
                                            case XML_ATTRIBUTE_NODE:
                                                // Just in case there would be interesting attributes inside the organization tag.
                                                // There shouldn't as this is a node-level, not a data level.
                                                //$manifest['organizations'][$i][$orgnode->name] = $orgnode->value;
                                                //$found_an_org = true;
                                                break;
                                            case XML_ELEMENT_NODE:
                                                // <item>, <metadata> or <title> (or attributes)
                                                $organizations_attributes = $orgnode->attributes;
                                                foreach ($organizations_attributes as $orgs_attr) {
                                                    $this->organizations_att[$orgs_attr->name] = $orgs_attr->value;
                                                }
                                                $oOrganization = new scormOrganization(
                                                    'manifest',
                                                    $orgnode,
                                                    $this->manifest_encoding
                                                );
                                                if ('' != $oOrganization->identifier) {
                                                    $name = $oOrganization->get_name();
                                                    if (empty($name)) {
                                                        // If the org title is empty, use zip file name.
                                                        $myname = $this->zipname;
                                                        if (0 != $this->lastzipnameindex) {
                                                            $myname = $myname + $this->lastzipnameindex;
                                                            $this->lastzipnameindex++;
                                                        }
                                                        $oOrganization->set_name($this->zipname);
                                                    }
                                                    $this->organizations[$oOrganization->identifier] = $oOrganization;
                                                }
                                                break;
                                        }
                                    }
                                    break;
                                case 'resources':
                                    if ($child->hasAttributes()) {
                                        $resources_attribs = $child->attributes;
                                        foreach ($resources_attribs as $res_attr) {
                                            if (XML_ATTRIBUTE_NODE == $res_attr->type) {
                                                $this->manifest['resources'][$res_attr->name] = $res_attr->value;
                                            }
                                        }
                                    }
                                    if ($child->hasChildNodes()) {
                                        $resources_nodes = $child->childNodes;
                                        $i = 0;
                                        foreach ($resources_nodes as $res_node) {
                                            $oResource = new scormResource('manifest', $res_node);
                                            if ('' != $oResource->identifier) {
                                                $this->resources[$oResource->identifier] = $oResource;
                                                $i++;
                                            }
                                        }
                                    }
                                    // Contains links to physical resources.
                                    break;
                                case 'manifest':
                                    // Only for sub-manifests.
                                    break;
                            }
                        }
                    }
                }
            }
            // End parsing using PHP5 DOMXML methods.
        } else {
            $this->set_error_msg("File could not be read");

            return null;
        }

        // TODO: Close the DOM handler.
        return $this->manifest;
    }

    /**
     * Import the scorm object (as a result from the parse_manifest function) into the database structure.
     *
     * @param int $courseId
     * @param int $userMaxScore
     * @param int $sessionId
     *
     * @return CLp|null
     */
    public function import_manifest($courseId, $userMaxScore = 1, $sessionId = 0)
    {
        if ($this->debug > 0) {
            error_log('Entered import_manifest('.$courseId.')', 0);
        }

        $course = api_get_course_entity($courseId);

        // Get table names.
        $lpItemTable = Database::get_course_table(TABLE_LP_ITEM);
        $userMaxScore = (int) $userMaxScore;

        $repo = Container::getLpRepository();
        $lpItemRepo = Container::getLpItemRepository();
        $lp = null;
        foreach ($this->organizations as $id => $dummy) {
            /** @var scormOrganization $oOrganization */
            $oOrganization = &$this->organizations[$id];
            // Prepare and execute insert queries:
            // -for learnpath
            // -for items
            // -for views?
            /*$get_max = "SELECT MAX(display_order) FROM $lpTable WHERE c_id = $courseId ";
            $res_max = Database::query($get_max);
            $dsp = 1;
            if (Database::num_rows($res_max) > 0) {
                $row = Database::fetch_array($res_max);
                $dsp = $row[0] + 1;
            }*/

            $name = $oOrganization->get_name();
            $lp = (new CLp())
                ->setLpType(CLp::SCORM_TYPE)
                ->setTitle($name)
                ->setRef($oOrganization->get_ref())
                ->setPath($this->subdir)
                ->setDefaultEncoding($this->manifest_encoding)
                ->setJsLib('scorm_api.php')
                ->setUseMaxScore($userMaxScore)
                ->setAsset($this->asset)
                ->setParent($course)
                ->addCourseLink($course, api_get_session_entity($sessionId))
            ;

            $repo->createLp($lp);

            $lp_id = $lp->getIid();

            // Now insert all elements from inside that learning path.
            // Make sure we also get the href and sco/asset from the resources.
            $list = $oOrganization->get_flat_items_list();
            $parents_stack = [0];
            $parent = 0;
            $previous = 0;
            $level = 0;
            foreach ($list as $item) {
                if ($item['level'] > $level) {
                    // Push something into the parents array.
                    array_push($parents_stack, $previous);
                    $parent = $previous;
                } elseif ($item['level'] < $level) {
                    $diff = $level - $item['level'];
                    // Pop something out of the parents array.
                    for ($j = 1; $j <= $diff; $j++) {
                        $outdated_parent = array_pop($parents_stack);
                    }
                    $parent = array_pop($parents_stack); // Just save that value, then add it back.
                    array_push($parents_stack, $parent);
                }
                $path = '';
                $type = 'dir';
                if (isset($this->resources[$item['identifierref']])) {
                    $oRes = &$this->resources[$item['identifierref']];
                    $path = @$oRes->get_path();
                    if (!empty($path)) {
                        $temptype = $oRes->get_scorm_type();
                        if (!empty($temptype)) {
                            $type = $temptype;
                        }
                    }
                }
                $level = $item['level'];
                $title = $item['title'];
                $title = api_utf8_decode($title);
                $max_score = (int) $item['max_score'];
                if (0 === $max_score) {
                    // If max score is not set The use_max_score parameter
                    // is check in order to use 100 (chamilo style) or '' (strict scorm)
                    $max_score = 'NULL';
                    if ($userMaxScore) {
                        $max_score = 100;
                    }
                } else {
                    // Otherwise save the max score.
                    $max_score = "'$max_score'";
                }

                if (empty($title)) {
                    $title = get_lang('Untitled');
                }

                $parentEntity = $lpItemRepo->getRootItem($lp_id);
                if (!empty($parent)) {
                    $parentEntity = $lpItemRepo->find($parent);
                }

                $lpItem = (new CLpItem())
                    ->setTitle($title)
                    ->setItemType($type)
                    ->setRef($item['identifier'])
                    ->setPath($path)
                    ->setMinScore(0)
                    ->setMaxScore($max_score)
                    ->setParent($parentEntity)
                    //->setPreviousItemId($previous)
                    //->setNextItemId(0)
                    ->setPrerequisite($item['prerequisites'])
                    //->setDisplayOrder($item['rel_order'])
                    ->setLaunchData($item['datafromlms'])
                    ->setParameters($item['parameters'])
                    ->setLp($lp)
                ;

                if (!empty($item['masteryscore'])) {
                    $lpItem->setMasteryScore($item['masteryscore']);
                }

                if (!empty($item['maxtimeallowed'])) {
                    $lpItem->setMaxTimeAllowed($item['maxtimeallowed']);
                }
                $lpItemRepo->create($lpItem);

                $item_id = $lpItem->getIid();
                /*if ($item_id) {
                    // Now update previous item to change next_item_id.
                    $upd = "UPDATE $lpItemTable SET next_item_id = $item_id
                            WHERE iid = $previous";
                    Database::query($upd);
                    // Update previous item id.
                    $previous = $item_id;
                }*/

                // Code for indexing, now only index specific fields like terms and the title.
                /*if (!empty($_POST['index_document'])) {
                    $di = new ChamiloIndexer();
                    isset($_POST['language']) ? $lang = Database::escape_string($_POST['language']) : $lang = 'english';
                    $di->connectDb(null, null, $lang);
                    $ic_slide = new IndexableChunk();
                    $ic_slide->addValue('title', $title);
                    $specific_fields = get_specific_field_list();
                    $all_specific_terms = '';
                    foreach ($specific_fields as $specific_field) {
                        if (isset($_REQUEST[$specific_field['code']])) {
                            $sterms = trim($_REQUEST[$specific_field['code']]);
                            $all_specific_terms .= ' '.$sterms;
                            if (!empty($sterms)) {
                                $sterms = explode(',', $sterms);
                                foreach ($sterms as $sterm) {
                                    $ic_slide->addTerm(trim($sterm), $specific_field['code']);
                                }
                            }
                        }
                    }
                    $body_to_index = $all_specific_terms.' '.$title;
                    $ic_slide->addValue("content", $body_to_index);
                    // TODO: Add a comment to say terms separated by commas.
                    $ic_slide->addCourseId($courseId);
                    $ic_slide->addToolId(TOOL_LEARNPATH);

                    // TODO: Unify with other lp types.
                    $xapian_data = [
                        SE_COURSE_ID => $courseId,
                        SE_TOOL_ID => TOOL_LEARNPATH,
                        SE_DATA => ['lp_id' => $lp_id, 'lp_item' => $previous, 'document_id' => ''],
                        SE_USER => api_get_user_id(),
                    ];
                    $ic_slide->xapian_data = serialize($xapian_data);
                    $di->addChunk($ic_slide);
                    // Index and return search engine document id.
                    $did = $di->index();
                    if ($did) {
                        // Save it to db.
                        $tbl_se_ref = Database::get_main_table(TABLE_MAIN_SEARCH_ENGINE_REF);
                        $sql = 'INSERT INTO %s (id, course_code, tool_id, ref_id_high_level, ref_id_second_level, search_did)
                                VALUES (NULL , \'%s\', \'%s\', %s, %s, %s)';
                        $sql = sprintf($sql, $tbl_se_ref, $course->getCode(), TOOL_LEARNPATH, $lp_id, $previous, $did);
                        Database::query($sql);
                    }
                }*/
            }
        }

        return $lp;
    }

    /**
     * Intermediate to import_package only to allow import from local zip files.
     *
     * @param  string    Path to the zip file, from the sys root
     * @param  string    Current path (optional)
     *
     * @return string Absolute path to the imsmanifest.xml file or empty string on error
     */
    public function import_local_package($file_path, $currentDir = '')
    {
        // TODO: Prepare info as given by the $_FILES[''] vector.
        $fileInfo = [];
        $fileInfo['tmp_name'] = $file_path;
        $fileInfo['name'] = basename($file_path);
        // Call the normal import_package function.
        return $this->import_package($fileInfo, $currentDir);
    }

    /**
     * Imports a zip file into the Chamilo structure.
     *
     * @param string    $zipFileInfo       Zip file info as given by $_FILES['userFile']
     * @param string    $currentDir
     * @param array     $courseInfo
     * @param bool      $updateDirContents
     * @param learnpath $lpToCheck
     * @param bool      $allowHtaccess
     *
     * @return string $current_dir Absolute path to the imsmanifest.xml file or empty string on error
     */
    public function import_package(
        $zipFileInfo,
        $currentDir = '',
        $courseInfo = [],
        $updateDirContents = false,
        $lpToCheck = null,
        $allowHtaccess = false
    ) {
        $this->debug = 100;
        if ($this->debug) {
            error_log(
                'In scorm::import_package('.print_r($zipFileInfo, true).',"'.$currentDir.'") method'
            );
        }

        $zipFilePath = $zipFileInfo['tmp_name'];
        $zipFileName = $zipFileInfo['name'];

        $currentDir = api_replace_dangerous_char(trim($currentDir)); // Current dir we are in, inside scorm/

        if ($this->debug > 1) {
            error_log('import_package() - current_dir = '.$currentDir, 0);
        }

        // Get name of the zip file without the extension.
        $fileInfo = pathinfo($zipFileName);
        $filename = $fileInfo['basename'];
        $extension = $fileInfo['extension'];
        $fileBaseName = str_replace('.'.$extension, '', $filename); // Filename without its extension.
        $this->zipname = $fileBaseName; // Save for later in case we don't have a title.
        $newDir = api_replace_dangerous_char(trim($fileBaseName));
        $this->subdir = $newDir;
        if ($this->debug) {
            error_log('$zipFileName: '.$zipFileName);
            error_log('Received zip file name: '.$zipFilePath);
            error_log("subdir is first set to : ".$this->subdir);
            error_log("base file name is : ".$fileBaseName);
        }

        $zipFile = new ZipFile();
        $zipFile->openFile($zipFilePath);
        $zipContentArray = $zipFile->getEntries();
        $packageType = '';
        $manifestList = [];
        // The following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?).
        $realFileSize = 0;
        foreach ($zipContentArray as $thisContent) {
            $fileName = $thisContent->getName();
            $size = $thisContent->getUncompressedSize();
            if (preg_match('~.(php.*|phtml)$~i', $fileName)) {
                $file = $fileName;
                $this->set_error_msg("File $file contains a PHP script");
            } elseif (stristr($fileName, 'imsmanifest.xml')) {
                if ($fileName == basename($fileName)) {
                } else {
                    if ($this->debug) {
                        error_log("subdir is now ".$this->subdir);
                    }
                }
                $packageType = 'scorm';
                $manifestList[] = $fileName;
            }
            $realFileSize += $size;
        }

        // Now get the shortest path (basically, the imsmanifest that is the closest to the root).
        $shortestPath = $manifestList[0];
        $slashCount = substr_count($shortestPath, '/');
        foreach ($manifestList as $manifestPath) {
            $tmpSlashCount = substr_count($manifestPath, '/');
            if ($tmpSlashCount < $slashCount) {
                $shortestPath = $manifestPath;
                $slashCount = $tmpSlashCount;
            }
        }

        $firstDir = $this->subdir;
        $this->subdir .= '/'.dirname($shortestPath); // Do not concatenate because already done above.
        if ($this->debug) {
            error_log("subdir is now (2): ".$this->subdir);
        }
        $this->manifestToString = $zipFile->getEntryContents($shortestPath);

        if ($this->debug) {
            error_log("Package type is now: '$packageType'");
        }

        if ('' === $packageType) {
            Display::addFlash(
                Display::return_message(get_lang('This is not a valid SCORM ZIP file !'))
            );

            return false;
        }

        // Todo check filesize
        /*if (!enough_size($realFileSize, $courseSysDir, $maxFilledSpace)) {
            if ($this->debug > 1) {
                error_log('Not enough space to store package');
            }
            Display::addFlash(
                Display::return_message(
                    get_lang(
                        'The upload has failed. Either you have exceeded your maximum quota, or there is not enough disk space.'
                    )
                )
            );

            return false;
        }*/

        /*if ($updateDirContents && $lpToCheck) {
            $originalPath = str_replace('/.', '', $lpToCheck->path);
            if ($originalPath != $newDir) {
                Display::addFlash(Display::return_message(get_lang('The file to upload is not valid.')));

                return false;
            }
        }

        // It happens on Linux that $newDir sometimes doesn't start with '/'
        if ('/' !== $newDir[0]) {
            $newDir = '/'.$newDir;
        }

        if ('/' === $newDir[strlen($newDir) - 1]) {
            $newDir = substr($newDir, 0, -1);
        }*/

        /* Uncompressing phase */
        /*
            We need to process each individual file in the zip archive to
            - add it to the database
            - parse & change relative html links
            - make sure the filenames are secure (filter funny characters or php extensions)
        */

        // 1. Upload zip file
        $request = Container::getRequest();
        $uploadFile = null;
        if ($request->files->has('user_file')) {
            $uploadFile = $request->files->get('user_file');
        }

        $repo = Container::getAssetRepository();
        $asset = (new Asset())
            ->setCategory(Asset::SCORM)
            ->setTitle($zipFileName)
            ->setFile($uploadFile)
            ->setCompressed(true)
        ;
        $repo->update($asset);

        // 2. Unzip file
        $repo->unZipFile($asset, $firstDir);
        $this->asset = $asset;

        return $asset;
    }

    /**
     * Exports the current SCORM object's files as a zip.
     * Excerpts taken from learnpath_functions.inc.php::exportpath().
     *
     * @param int    Learnpath ID (optional, taken from object context if not defined)
     *
     * @return bool
     */
    public function export_zip($lp_id = null)
    {
        if ($this->debug > 0) {
            error_log('In scorm::export_zip method('.$lp_id.')');
        }
        if (empty($lp_id)) {
            if (!is_object($this)) {
                return false;
            } else {
                $id = $this->get_id();
                if (empty($id)) {
                    return false;
                } else {
                    $lp_id = $this->get_id();
                }
            }
        }
        //zip everything that is in the corresponding scorm dir
        //write the zip file somewhere (might be too big to return)

        $_course = api_get_course_info();
        $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT * FROM $tbl_lp WHERE iid = $lp_id";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);
        $LPname = $row['path'];
        $list = explode('/', $LPname);
        $LPnamesafe = $list[0];
        $zipfoldername = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/temp/'.$LPnamesafe;
        $scormfoldername = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/scorm/'.$LPnamesafe;
        $zipfilename = $zipfoldername.'/'.$LPnamesafe.'.zip';

        // Get a temporary dir for creating the zip file.
        mkdir($zipfoldername, api_get_permissions_for_new_directories());

        // Create zipfile of given directory.
        // @todo use ZipFile
        $zip_folder = new PclZip($zipfilename);
        $zip_folder->create($scormfoldername.'/', PCLZIP_OPT_REMOVE_PATH, $scormfoldername.'/');

        //This file sending implies removing the default mime-type from php.ini
        //DocumentManager::file_send_for_download($zipfilename, true, $LPnamesafe.'.zip');
        DocumentManager::file_send_for_download($zipfilename, true);

        return true;
    }

    /**
     * Gets a resource's path if available, otherwise return empty string.
     *
     * @param	string	Resource ID as used in resource array
     *
     * @return string The resource's path as declared in imsmanifest.xml
     */
    public function get_res_path($id)
    {
        if ($this->debug > 0) {
            error_log('In scorm::get_res_path('.$id.') method');
        }
        $path = '';
        if (isset($this->resources[$id])) {
            $oRes = &$this->resources[$id];
            $path = @$oRes->get_path();
        }

        return $path;
    }

    /**
     * Gets a resource's type if available, otherwise return empty string.
     *
     * @param  string    Resource ID as used in resource array
     *
     * @return string The resource's type as declared in imsmanifest.xml
     */
    public function get_res_type($id)
    {
        if ($this->debug > 0) {
            error_log('In scorm::get_res_type('.$id.') method');
        }
        $type = '';
        if (isset($this->resources[$id])) {
            $oRes = &$this->resources[$id];
            $temptype = $oRes->get_scorm_type();
            if (!empty($temptype)) {
                $type = $temptype;
            }
        }

        return $type;
    }

    /**
     * Gets the default organisation's title.
     *
     * @return string The organization's title
     */
    public function get_title()
    {
        if ($this->debug > 0) {
            error_log('In scorm::get_title() method');
        }
        $title = '';
        if (isset($this->manifest['organizations']['default'])) {
            $title = $this->organizations[$this->manifest['organizations']['default']]->get_name();
        } elseif (1 == count($this->organizations)) {
            // This will only get one title but so we don't need to know the index.
            foreach ($this->organizations as $id => $value) {
                $title = $this->organizations[$id]->get_name();
                break;
            }
        }

        return $title;
    }

    /**
     * // TODO @TODO Implement this function to restore items data from an imsmanifest,
     * updating the existing table... This will prove very useful in case initial data
     * from imsmanifest were not imported well enough.
     *
     * @param string $courseCode
     * @param int	LP ID (in database)
     * @param string	Manifest file path (optional if lp_id defined)
     *
     * @return int New LP ID or false on failure
     *             TODO @TODO Implement imsmanifest_path parameter
     */
    public function reimport_manifest($courseCode, $lp_id = null, $imsmanifest_path = '')
    {
        if ($this->debug > 0) {
            error_log('In scorm::reimport_manifest() method', 0);
        }

        $courseInfo = api_get_course_info($courseCode);
        if (empty($courseInfo)) {
            $this->error = 'Course code does not exist in database';

            return false;
        }

        $this->cc = $courseInfo['code'];

        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $lp_id = intval($lp_id);
        $sql = "SELECT * FROM $lp_table WHERE iid = $lp_id";
        if ($this->debug > 2) {
            error_log('scorm::reimport_manifest() '.__LINE__.' - Querying lp: '.$sql);
        }
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            $this->lp_id = $lp_id;
            $row = Database::fetch_array($res);
            $this->type = $row['lp_type'];
            $this->name = stripslashes($row['name']);
            $this->encoding = $row['default_encoding'];
            $this->proximity = $row['content_local'];
            $this->maker = $row['content_maker'];
            $this->prevent_reinit = $row['prevent_reinit'];
            $this->license = $row['content_license'];
            $this->scorm_debug = $row['debug'];
            $this->js_lib = $row['js_lib'];
            $this->path = $row['path'];
            if (2 == $this->type) {
                if (1 == $row['force_commit']) {
                    $this->force_commit = true;
                }
            }
            $this->mode = $row['default_view_mod'];
            $this->subdir = $row['path'];
        }
        // Parse the manifest (it is already in this lp's details).
        $manifest_file = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/scorm/'.$this->subdir.'/imsmanifest.xml';
        if ('' == $this->subdir) {
            $manifest_file = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/scorm/imsmanifest.xml';
        }
        echo $manifest_file;
        if (is_file($manifest_file) && is_readable($manifest_file)) {
            // Re-parse the manifest file.
            if ($this->debug > 1) {
                error_log('In scorm::reimport_manifest() - Parsing manifest '.$manifest_file);
            }
            $manifest = $this->parse_manifest($manifest_file);
            // Import new LP in DB (ignore the current one).
            if ($this->debug > 1) {
                error_log('In scorm::reimport_manifest() - Importing manifest '.$manifest_file);
            }
            $this->import_manifest(api_get_course_int_id());
        } else {
            if ($this->debug > 0) {
                error_log('In scorm::reimport_manifest() - Could not find manifest file at '.$manifest_file);
            }
        }

        return false;
    }

    /**
     * Detects the encoding of a given manifest (a xml-text).
     * It is possible the encoding of the manifest to be wrongly declared or
     * not to be declared at all. The proposed method tries to resolve these problems.
     *
     * @param string $xml the input xml-text
     *
     * @return string the detected value of the input xml
     */
    private function detect_manifest_encoding(&$xml)
    {
        if (api_is_valid_utf8($xml)) {
            return 'UTF-8';
        }

        $declared_encoding = '';
        if (preg_match(_PCRE_XML_ENCODING, $xml, $matches)) {
            $declared_encoding = api_refine_encoding_id($matches[1]);
        }

        if (!empty($declared_encoding)) {
            return $declared_encoding;
        }

        $test_string = '';
        if (preg_match_all('/<langstring[^>]*>(.*)<\/langstring>/m', $xml, $matches)) {
            $test_string = implode("\n", $matches[1]);
            unset($matches);
        }
        if (preg_match_all('/<title[^>]*>(.*)<\/title>/m', $xml, $matches)) {
            $test_string .= "\n".implode("\n", $matches[1]);
            unset($matches);
        }
        if (empty($test_string)) {
            $test_string = $xml;
        }

        return api_detect_encoding($test_string);
    }
}
