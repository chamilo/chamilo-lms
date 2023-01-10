<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CLp;

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
    public $subdir = '';
    public $items = [];
    // Keeps the zipfile safe for the object's life so that we can use it if no title avail.
    public $zipname = '';
    // Keeps an index of the number of uses of the zipname so far.
    public $lastzipnameindex = 0;
    public $manifest_encoding = 'UTF-8';
    public $debug = false;

    /**
     * Class constructor. Based on the parent constructor.
     *
     * @param    string    Course code
     * @param    int    Learnpath ID in DB
     * @param    int    User ID
     */
    public function __construct($course_code = null, $resource_id = null, $user_id = null)
    {
        if ($this->debug > 0) {
            error_log('New LP - scorm::scorm('.$course_code.','.$resource_id.','.$user_id.') - In scorm constructor');
        }

        parent::__construct($course_code, $resource_id, $user_id);
    }

    /**
     * Opens a resource.
     *
     * @param int $id Database ID of the resource
     */
    public function open($id)
    {
        if ($this->debug > 0) {
            error_log('New LP - scorm::open() - In scorm::open method', 0);
        }
        // redefine parent method
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
    public function parse_manifest($file = '')
    {
        if ($this->debug > 0) {
            error_log('In scorm::parse_manifest('.$file.')', 0);
        }
        if (empty($file)) {
            // Get the path of the imsmanifest file.
        }

        if (is_file($file) && is_readable($file) && ($xml = @file_get_contents($file))) {
            // Parsing using PHP5 DOMXML methods.
            if ($this->debug > 0) {
                error_log('In scorm::parse_manifest() - Parsing using PHP5 method');
            }
            // $this->manifest_encoding = api_detect_encoding_xml($xml);
            // This is the usual way for reading the encoding.
            // This method reads the encoding, it tries to be correct even in cases
            // of wrong or missing encoding declarations.
            $this->manifest_encoding = self::detect_manifest_encoding($xml);

            // UTF-8 is supported by DOMDocument class, this is for sure.
            $xml = api_utf8_encode_xml($xml, $this->manifest_encoding);

            $crawler = Import::xmlFromString($xml);

            $xmlErrors = libxml_get_errors();

            if (!empty($xmlErrors)) {
                if ($this->debug > 0) {
                    error_log('New LP - In scorm::parse_manifest() - Exception thrown when loading '.$file.' in DOMDocument');
                }
                // Throw exception?
                return null;
            }

            if ($this->debug > 1) {
                error_log('New LP - Called  (encoding:'.$this->manifest_encoding.' - saved: '.$this->manifest_encoding.')', 0);
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
                                                if ($oOrganization->identifier != '') {
                                                    $name = $oOrganization->get_name();
                                                    if (empty($name)) {
                                                        // If the org title is empty, use zip file name.
                                                        $myname = $this->zipname;
                                                        if ($this->lastzipnameindex != 0) {
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
                                            if ($res_attr->type == XML_ATTRIBUTE_NODE) {
                                                $this->manifest['resources'][$res_attr->name] = $res_attr->value;
                                            }
                                        }
                                    }
                                    if ($child->hasChildNodes()) {
                                        $resources_nodes = $child->childNodes;
                                        $i = 0;
                                        foreach ($resources_nodes as $res_node) {
                                            $oResource = new scormResource('manifest', $res_node);
                                            if ($oResource->identifier != '') {
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
            if ($this->debug > 1) {
                error_log('New LP - Could not open/read file '.$file);
            }
            $this->set_error_msg("File $file could not be read");

            return null;
        }

        $fixTemplate = api_get_configuration_value('learnpath_fix_xerte_template');
        $proxyPath = api_get_configuration_value('learnpath_proxy_url');
        if ($fixTemplate && !empty($proxyPath)) {
            // Check organisations:
            if (isset($this->manifest['organizations'])) {
                foreach ($this->manifest['organizations'] as $data) {
                    if (strpos(strtolower($data), 'xerte') !== false) {
                        // Check if template.xml exists:
                        $templatePath = str_replace('imsmanifest.xml', 'template.xml', $file);
                        if (file_exists($templatePath) && is_file($templatePath)) {
                            $templateContent = file_get_contents($templatePath);
                            $find = [
                                'href="www.',
                                'href="https://',
                                'href="http://',
                                'url="www.',
                                'pdfs/download.php?',
                            ];

                            $replace = [
                                'href="http://www.',
                                'target = "_blank" href="'.$proxyPath.'?type=link&src=https://',
                                'target = "_blank" href="'.$proxyPath.'?type=link&src=http://',
                                'url="http://www.',
                                'pdfs/download.php&',
                            ];
                            $templateContent = str_replace($find, $replace, $templateContent);
                            file_put_contents($templatePath, $templateContent);
                        }

                        // Fix link generation:
                        $linkPath = str_replace('imsmanifest.xml', 'models_html5/links.html', $file);
                        if (file_exists($linkPath) && is_file($linkPath)) {
                            $linkContent = file_get_contents($linkPath);
                            $find = [
                                ':this.getAttribute("url")',
                            ];
                            $replace = [
                                ':"'.$proxyPath.'?type=link&src=" + this.getAttribute("url")',
                            ];
                            $linkContent = str_replace($find, $replace, $linkContent);
                            file_put_contents($linkPath, $linkContent);
                        }

                        // Fix iframe generation
                        $framePath = str_replace('imsmanifest.xml', 'models_html5/embedDiv.html', $file);

                        if (file_exists($framePath) && is_file($framePath)) {
                            $content = file_get_contents($framePath);
                            $find = [
                                '$iFrameHolder.html(iFrameTag);',
                            ];
                            $replace = [
                                'iFrameTag = \'<a target ="_blank" href="'.$proxyPath.'?type=link&src=\'+ pageSrc + \'">Open website. <img width="16px" src="'.Display::returnIconPath('link-external.png').'"></a>\'; $iFrameHolder.html(iFrameTag); ',
                            ];
                            $content = str_replace($find, $replace, $content);
                            file_put_contents($framePath, $content);
                        }

                        // Fix new window generation
                        $newWindowPath = str_replace('imsmanifest.xml', 'models_html5/newWindow.html', $file);

                        if (file_exists($newWindowPath) && is_file($newWindowPath)) {
                            $content = file_get_contents($newWindowPath);
                            $find = [
                                'var src = x_currentPageXML',
                            ];
                            $replace = [
                                'var src = "'.$proxyPath.'?type=link&src=" + x_currentPageXML',
                            ];
                            $content = str_replace($find, $replace, $content);
                            file_put_contents($newWindowPath, $content);
                        }
                    }
                }
            }
        }

        // TODO: Close the DOM handler.
        return $this->manifest;
    }

    /**
     * Import the scorm object (as a result from the parse_manifest function) into the database structure.
     *
     * @param string $courseCode
     * @param int    $userMaxScore
     * @param int    $sessionId
     * @param int    $userId
     *
     * @return bool Returns -1 on error
     */
    public function import_manifest(
        $courseCode,
        $userMaxScore = 1,
        $sessionId = 0,
        $userId = 0,
        $lpName = null
    ) {
        if ($this->debug > 0) {
            error_log('New LP - Entered import_manifest('.$courseCode.')', 0);
        }
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        $userId = (int) $userId;
        if (empty($userId)) {
            $userId = api_get_user_id();
        }
        $em = Database::getManager();
        // Get table names.
        $new_lp = Database::get_course_table(TABLE_LP_MAIN);
        $new_lp_item = Database::get_course_table(TABLE_LP_ITEM);
        $userMaxScore = (int) $userMaxScore;
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;
        foreach ($this->organizations as $id => $dummy) {
            $oOrganization = &$this->organizations[$id];
            // Prepare and execute insert queries:
            // -for learnpath
            // -for items
            // -for views?
            $get_max = "SELECT MAX(display_order) FROM $new_lp WHERE c_id = $courseId ";
            $res_max = Database::query($get_max);
            $dsp = 1;
            if (Database::num_rows($res_max) > 0) {
                $row = Database::fetch_array($res_max);
                $dsp = $row[0] + 1;
            }
            $myname = api_utf8_decode($oOrganization->get_name());
            $now = api_get_utc_datetime(null, false, true);
            if (!empty($lpName)) {
                $myname = api_utf8_decode($lpName);
            }

            $newScorm = (new CLp())
                ->setCId($courseId)
                ->setLpType(2)
                ->setName($myname)
                ->setRef($oOrganization->get_ref())
                ->setDescription('')
                ->setPath($this->subdir)
                ->setForceCommit(false)
                ->setDefaultViewMod('embedded')
                ->setDefaultEncoding($this->manifest_encoding)
                ->setJsLib('scorm_api.php')
                ->setDisplayOrder($dsp)
                ->setSessionId($sessionId)
                ->setUseMaxScore($userMaxScore)
                ->setContentMaker('')
                ->setContentLicense('')
                ->setDebug(false)
                ->setTheme('')
                ->setPreviewImage('')
                ->setAuthor('')
                ->setPrerequisite(0)
                ->setHideTocFrame(false)
                ->setSeriousgameMode(false)
                ->setAutolaunch(0)
                ->setCategoryId(0)
                ->setMaxAttempts(0)
                ->setSubscribeUsers(0)
                ->setCreatedOn($now)
                ->setModifiedOn($now)
                ->setPublicatedOn($now)
                ->setAccumulateScormTime(1)
            ;

            $em->persist($newScorm);
            $em->flush();

            HookLearningPathCreated::create()
                ->setEventData(['lp' => $newScorm])
                ->notifyCreated()
            ;

            $newScorm->setId($newScorm->getIid());

            $em->flush();

            $this->lp_id = $newScorm->getIid();

            // Insert into item_property.
            api_item_property_update(
                $courseInfo,
                TOOL_LEARNPATH,
                $this->lp_id,
                'LearnpathAdded',
                $userId
            );

            api_item_property_update(
                $courseInfo,
                TOOL_LEARNPATH,
                $this->lp_id,
                'visible',
                $userId
            );

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
                $field_add = '';
                $value_add = '';

                if (!empty($item['masteryscore'])) {
                    $field_add .= 'mastery_score, ';
                    $value_add .= $item['masteryscore'].',';
                }

                if (!empty($item['maxtimeallowed'])) {
                    $field_add .= 'max_time_allowed, ';
                    $value_add .= "'".$item['maxtimeallowed']."',";
                }
                $title = Database::escape_string($item['title']);
                $title = api_utf8_decode($title);
                $max_score = (int) $item['max_score'];

                if ($max_score === 0) {
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

                $identifier = Database::escape_string($item['identifier']);

                if (empty($title)) {
                    $title = get_lang('Untitled');
                }

                $prereq = Database::escape_string($item['prerequisites']);
                $item['datafromlms'] = Database::escape_string($item['datafromlms']);
                $item['parameters'] = Database::escape_string($item['parameters']);

                $sql = "INSERT INTO $new_lp_item (c_id, lp_id,item_type,ref,title, path,min_score,max_score, $field_add parent_item_id,previous_item_id,next_item_id, prerequisite,display_order,launch_data, parameters)
                        VALUES ($courseId, {$newScorm->getIid()}, '$type', '$identifier', '$title', '$path' , 0, $max_score, $value_add $parent, $previous, 0, '$prereq', ".$item['rel_order'].", '".$item['datafromlms']."', '".$item['parameters']."' )";

                Database::query($sql);
                if ($this->debug > 1) {
                    error_log('New LP - In import_manifest(), inserting item : '.$sql);
                }
                $item_id = Database::insert_id();

                if ($item_id) {
                    $sql = "UPDATE $new_lp_item SET id = iid WHERE iid = $item_id";
                    Database::query($sql);

                    // Now update previous item to change next_item_id.
                    $upd = "UPDATE $new_lp_item SET next_item_id = $item_id
                            WHERE iid = $previous";
                    Database::query($upd);
                    // Update previous item id.
                    $previous = $item_id;
                }

                // Code for indexing, now only index specific fields like terms and the title.
                if (!empty($_POST['index_document'])) {
                    require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

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
                    $courseid = api_get_course_id();
                    $ic_slide->addCourseId($courseid);
                    $ic_slide->addToolId(TOOL_LEARNPATH);
                    // TODO: Unify with other lp types.
                    $xapian_data = [
                        SE_COURSE_ID => $courseid,
                        SE_TOOL_ID => TOOL_LEARNPATH,
                        SE_DATA => ['lp_id' => $newScorm->getIid(), 'lp_item' => $previous, 'document_id' => ''],
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
                        $sql = sprintf($sql, $tbl_se_ref, $courseCode, TOOL_LEARNPATH, $newScorm->getIid(), $previous, $did);
                        Database::query($sql);
                    }
                }
            }
        }
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
        if ($this->debug > 0) {
            error_log(
                'In scorm::import_package('.print_r($zipFileInfo, true).',"'.$currentDir.'") method'
            );
        }

        $courseInfo = empty($courseInfo) ? api_get_course_info() : $courseInfo;
        $maxFilledSpace = DocumentManager::get_course_quota($courseInfo['code']);

        $zipFilePath = $zipFileInfo['tmp_name'];
        $zipFileName = $zipFileInfo['name'];

        if ($this->debug > 1) {
            error_log(
                'New LP - import_package() - zip file path = '.$zipFilePath.', zip file name = '.$zipFileName,
                0
            );
        }

        $courseRelDir = api_get_course_path($courseInfo['code']).'/scorm'; // scorm dir web path starting from /courses
        $courseSysDir = api_get_path(SYS_COURSE_PATH).$courseRelDir; // Absolute system path for this course.
        $currentDir = api_replace_dangerous_char(trim($currentDir)); // Current dir we are in, inside scorm/

        if ($this->debug > 1) {
            error_log('New LP - import_package() - current_dir = '.$currentDir, 0);
        }

        // Get name of the zip file without the extension.
        $fileInfo = pathinfo($zipFileName);
        $filename = $fileInfo['basename'];
        $extension = $fileInfo['extension'];
        $fileBaseName = str_replace('.'.$extension, '', $filename); // Filename without its extension.
        $this->zipname = $fileBaseName; // Save for later in case we don't have a title.
        $newDir = api_replace_dangerous_char(trim($fileBaseName));
        $this->subdir = $newDir;
        if ($this->debug > 1) {
            error_log('New LP - Received zip file name: '.$zipFilePath);
            error_log("New LP - subdir is first set to : ".$this->subdir);
            error_log("New LP - base file name is : ".$fileBaseName);
        }

        $zipFile = new PclZip($zipFilePath);
        // Check the zip content (real size and file extension).
        $zipContentArray = $zipFile->listContent();
        $packageType = '';
        $manifestList = [];
        // The following loop should be stopped as soon as we found the right imsmanifest.xml (how to recognize it?).
        $realFileSize = 0;
        foreach ($zipContentArray as $thisContent) {
            if (preg_match('~.(php.*|phtml)$~i', $thisContent['filename'])) {
                $file = $thisContent['filename'];
                $this->set_error_msg("File $file contains a PHP script");
            } elseif (stristr($thisContent['filename'], 'imsmanifest.xml')) {
                if ($thisContent['filename'] == basename($thisContent['filename'])) {
                } else {
                    if ($this->debug > 2) {
                        error_log("New LP - subdir is now ".$this->subdir);
                    }
                }
                $packageType = 'scorm';
                $manifestList[] = $thisContent['filename'];
            }
            $realFileSize += $thisContent['size'];
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

        $this->subdir .= '/'.dirname($shortestPath); // Do not concatenate because already done above.
        $manifest = $shortestPath;
        if ($this->debug) {
            error_log("New LP - Package type is now: '$packageType'");
        }
        if ($packageType == '') {
            Display::addFlash(
                Display::return_message(get_lang('NotScormContent'))
            );

            return false;
        }

        if (!enough_size($realFileSize, $courseSysDir, $maxFilledSpace)) {
            if ($this->debug > 1) {
                error_log('New LP - Not enough space to store package');
            }
            Display::addFlash(
                Display::return_message(get_lang('NoSpace'))
            );

            return false;
        }

        if ($updateDirContents && $lpToCheck) {
            $originalPath = str_replace('/.', '', $lpToCheck->path);
            if ($originalPath != $newDir) {
                Display::addFlash(Display::return_message(get_lang('FileError')));

                return false;
            }
        }

        // It happens on Linux that $newDir sometimes doesn't start with '/'
        if ($newDir[0] != '/') {
            $newDir = '/'.$newDir;
        }

        if ($newDir[strlen($newDir) - 1] == '/') {
            $newDir = substr($newDir, 0, -1);
        }

        /* Uncompressing phase */
        /*
            We need to process each individual file in the zip archive to
            - add it to the database
            - parse & change relative html links
            - make sure the filenames are secure (filter funny characters or php extensions)
        */
        if (is_dir($courseSysDir.$newDir) ||
            @mkdir(
                $courseSysDir.$newDir,
                api_get_permissions_for_new_directories()
            )
        ) {
            // PHP method - slower...
            if ($this->debug >= 1) {
                error_log('New LP - Changing dir to '.$courseSysDir.$newDir);
            }

            chdir($courseSysDir.$newDir);

            $callBack = 'clean_up_files_in_zip';
            if ($allowHtaccess) {
                $callBack = 'cleanZipFilesAllowHtaccess';
            }

            if (api_get_configuration_value('skip_scorm_package_clean_up')) {
                $callBack = 'cleanZipFilesNoRename';
            }

            $zipFile->extract(
                PCLZIP_CB_PRE_EXTRACT,
                $callBack
            );

            if (!empty($newDir)) {
                $newDir = $newDir.'/';
            }
            api_chmod_R($courseSysDir.$newDir, api_get_permissions_for_new_directories());
        } else {
            return false;
        }

        return $courseSysDir.$newDir.$manifest;
    }

    /**
     * Sets the proximity setting in the database.
     *
     * @param string    Proximity setting
     * @param int $courseId
     *
     * @return bool
     */
    public function set_proximity($proxy = '', $courseId = null)
    {
        if ($this->debug > 0) {
            error_log('In scorm::set_proximity('.$proxy.') method');
        }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET content_local = '$proxy'
                    WHERE iid = $lp";
            $res = Database::query($sql);

            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the theme setting in the database.
     *
     * @param string    theme setting
     *
     * @return bool
     */
    public function set_theme($theme = '')
    {
        if ($this->debug > 0) {
            error_log('In scorm::set_theme('.$theme.') method');
        }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET theme = '$theme'
                    WHERE iid = $lp";
            $res = Database::query($sql);

            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the image setting in the database.
     *
     * @param string preview_image setting
     *
     * @return bool
     */
    public function set_preview_image($preview_image = '')
    {
        if ($this->debug > 0) {
            error_log('In scorm::set_theme('.$preview_image.') method', 0);
        }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET preview_image = '$preview_image'
                    WHERE iid = $lp";
            $res = Database::query($sql);

            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the author  setting in the database.
     *
     * @param string $author
     *
     * @return bool
     */
    public function set_author($author = '')
    {
        if ($this->debug > 0) {
            error_log('In scorm::set_author('.$author.') method', 0);
        }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET author = '$author'
                    WHERE iid = ".$lp;
            $res = Database::query($sql);

            return $res;
        } else {
            return false;
        }
    }

    /**
     * Sets the content maker setting in the database.
     *
     * @param string    Proximity setting
     *
     * @return bool
     */
    public function set_maker($maker = '', $courseId = null)
    {
        if ($this->debug > 0) {
            error_log('In scorm::set_maker method('.$maker.')', 0);
        }
        $lp = $this->get_id();
        if ($lp != 0) {
            $tbl_lp = Database::get_course_table(TABLE_LP_MAIN);
            $sql = "UPDATE $tbl_lp SET content_maker = '$maker' WHERE iid = $lp";
            $res = Database::query($sql);

            return $res;
        } else {
            return false;
        }
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
        //error_log('New LP - cleaning dir '.$zipfoldername, 0);
        my_delete($zipfoldername); // Make sure the temp dir is cleared.
        mkdir($zipfoldername, api_get_permissions_for_new_directories());

        // Create zipfile of given directory.
        $zip_folder = new PclZip($zipfilename);
        $zip_folder->create($scormfoldername.'/', PCLZIP_OPT_REMOVE_PATH, $scormfoldername.'/');

        //This file sending implies removing the default mime-type from php.ini
        //DocumentManager::file_send_for_download($zipfilename, true, $LPnamesafe.'.zip');
        DocumentManager::file_send_for_download($zipfilename, true);

        // Delete the temporary zip file and directory in fileManage.lib.php
        my_delete($zipfilename);
        my_delete($zipfoldername);

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
        } elseif (count($this->organizations) == 1) {
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
            error_log('New LP - scorm::reimport_manifest() '.__LINE__.' - Querying lp: '.$sql);
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
            if ($this->type == 2) {
                if ($row['force_commit'] == 1) {
                    $this->force_commit = true;
                }
            }
            $this->mode = $row['default_view_mod'];
            $this->subdir = $row['path'];
        }
        // Parse the manifest (it is already in this lp's details).
        $manifest_file = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/scorm/'.$this->subdir.'/imsmanifest.xml';
        if ($this->subdir == '') {
            $manifest_file = api_get_path(SYS_COURSE_PATH).$courseInfo['directory'].'/scorm/imsmanifest.xml';
        }
        echo $manifest_file;
        if (is_file($manifest_file) && is_readable($manifest_file)) {
            // Re-parse the manifest file.
            if ($this->debug > 1) {
                error_log('New LP - In scorm::reimport_manifest() - Parsing manifest '.$manifest_file);
            }
            $manifest = $this->parse_manifest($manifest_file);
            // Import new LP in DB (ignore the current one).
            if ($this->debug > 1) {
                error_log('New LP - In scorm::reimport_manifest() - Importing manifest '.$manifest_file);
            }
            $this->import_manifest($this->cc);
        } else {
            if ($this->debug > 0) {
                error_log('New LP - In scorm::reimport_manifest() - Could not find manifest file at '.$manifest_file);
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

        if (preg_match(_PCRE_XML_ENCODING, $xml, $matches)) {
            $declared_encoding = api_refine_encoding_id($matches[1]);
        } else {
            $declared_encoding = '';
        }

        if (!empty($declared_encoding) && !api_is_utf8($declared_encoding)) {
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
