<?php

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Symfony\Component\Filesystem\Filesystem;

class ScormExport
{
    public static function export(learnpath $lp)
    {
        // @todo fix export
        api_set_more_memory_and_time_limits();

        $_course = api_get_course_info();
        $course_id = $_course['real_id'];
        // Create the zip handler (this will remain available throughout the method).
        $archivePath = api_get_path(SYS_ARCHIVE_PATH);
        $sys_course_path = api_get_path(SYS_COURSE_PATH);
        $temp_dir_short = uniqid('scorm_export', true);
        $temp_zip_dir = $archivePath.'/'.$temp_dir_short;
        $temp_zip_file = $temp_zip_dir.'/'.md5(time()).'.zip';
        $zip_folder = new PclZip($temp_zip_file);
        $current_course_path = api_get_path(SYS_COURSE_PATH).api_get_course_path();
        $root_path = $main_path = api_get_path(SYS_PATH);
        $files_cleanup = [];

        // Place to temporarily stash the zip file.
        // create the temp dir if it doesn't exist
        // or do a cleanup before creating the zip file.
        if (!is_dir($temp_zip_dir)) {
            mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
        } else {
            // Cleanup: Check the temp dir for old files and delete them.
            $handle = opendir($temp_zip_dir);
            while (false !== ($file = readdir($handle))) {
                if ('.' != $file && '..' != $file) {
                    unlink("$temp_zip_dir/$file");
                }
            }
            closedir($handle);
        }
        $zip_files = $zip_files_abs = $zip_files_dist = [];
        if (is_dir($current_course_path.'/scorm/'.$lp->path) &&
            is_file($current_course_path.'/scorm/'.$lp->path.'/imsmanifest.xml')
        ) {
            // Remove the possible . at the end of the path.
            $dest_path_to_lp = '.' == substr($lp->path, -1) ? substr($lp->path, 0, -1) : $lp->path;
            $dest_path_to_scorm_folder = str_replace('//', '/', $temp_zip_dir.'/scorm/'.$dest_path_to_lp);
            mkdir(
                $dest_path_to_scorm_folder,
                api_get_permissions_for_new_directories(),
                true
            );
            copyr(
                $current_course_path.'/scorm/'.$lp->path,
                $dest_path_to_scorm_folder,
                ['imsmanifest'],
                $zip_files
            );
        }

        // Build a dummy imsmanifest structure.
        // Do not add to the zip yet (we still need it).
        // This structure is developed following regulations for SCORM 1.2 packaging in the SCORM 1.2 Content
        // Aggregation Model official document, section "2.3 Content Packaging".
        // We are going to build a UTF-8 encoded manifest.
        // Later we will recode it to the desired (and supported) encoding.
        $xmldoc = new DOMDocument('1.0');
        $root = $xmldoc->createElement('manifest');
        $root->setAttribute('identifier', 'SingleCourseManifest');
        $root->setAttribute('version', '1.1');
        $root->setAttribute('xmlns', 'http://www.imsproject.org/xsd/imscp_rootv1p1p2');
        $root->setAttribute('xmlns:adlcp', 'http://www.adlnet.org/xsd/adlcp_rootv1p2');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute(
            'xsi:schemaLocation',
            'http://www.imsproject.org/xsd/imscp_rootv1p1p2 imscp_rootv1p1p2.xsd http://www.imsglobal.org/xsd/imsmd_rootv1p2p1 imsmd_rootv1p2p1.xsd http://www.adlnet.org/xsd/adlcp_rootv1p2 adlcp_rootv1p2.xsd'
        );
        // Build mandatory sub-root container elements.
        $metadata = $xmldoc->createElement('metadata');
        $md_schema = $xmldoc->createElement('schema', 'ADL SCORM');
        $metadata->appendChild($md_schema);
        $md_schemaversion = $xmldoc->createElement('schemaversion', '1.2');
        $metadata->appendChild($md_schemaversion);
        $root->appendChild($metadata);

        $organizations = $xmldoc->createElement('organizations');
        $resources = $xmldoc->createElement('resources');

        // Build the only organization we will use in building our learnpaths.
        $organizations->setAttribute('default', 'chamilo_scorm_export');
        $organization = $xmldoc->createElement('organization');
        $organization->setAttribute('identifier', 'chamilo_scorm_export');
        // To set the title of the SCORM entity (=organization), we take the name given
        // in Chamilo and convert it to HTML entities using the Chamilo charset (not the
        // learning path charset) as it is the encoding that defines how it is stored
        // in the database. Then we convert it to HTML entities again as the "&" character
        // alone is not authorized in XML (must be &amp;).
        // The title is then decoded twice when extracting (see scorm::parse_manifest).
        $org_title = $xmldoc->createElement('title', api_utf8_encode($lp->get_name()));
        $organization->appendChild($org_title);
        $folder_name = 'document';

        // Removes the learning_path/scorm_folder path when exporting see #4841
        $path_to_remove = '';
        $path_to_replace = '';
        $result = $lp->generate_lp_folder($_course);
        if (isset($result['dir']) && strpos($result['dir'], 'learning_path')) {
            $path_to_remove = 'document'.$result['dir'];
            $path_to_replace = $folder_name.'/';
        }

        // Fixes chamilo scorm exports
        if ('chamilo_scorm_export' === $lp->ref) {
            $path_to_remove = 'scorm/'.$lp->path.'/document/';
        }

        // For each element, add it to the imsmanifest structure, then add it to the zip.
        $link_updates = [];
        $links_to_create = [];
        foreach ($lp->ordered_items as $index => $itemId) {
            /** @var learnpathItem $item */
            $item = $lp->items[$itemId];
            if (!in_array($item->type, [TOOL_QUIZ, TOOL_FORUM, TOOL_THREAD, TOOL_LINK, TOOL_STUDENTPUBLICATION])) {
                // Get included documents from this item.
                if ('sco' === $item->type) {
                    $inc_docs = $item->get_resources_from_source(
                        null,
                        $current_course_path.'/scorm/'.$lp->path.'/'.$item->get_path()
                    );
                } else {
                    $inc_docs = $item->get_resources_from_source();
                }

                // Give a child element <item> to the <organization> element.
                $my_item_id = $item->get_id();
                $my_item = $xmldoc->createElement('item');
                $my_item->setAttribute('identifier', 'ITEM_'.$my_item_id);
                $my_item->setAttribute('identifierref', 'RESOURCE_'.$my_item_id);
                $my_item->setAttribute('isvisible', 'true');
                // Give a child element <title> to the <item> element.
                $my_title = $xmldoc->createElement(
                    'title',
                    htmlspecialchars(
                        api_utf8_encode($item->get_title()),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                );
                $my_item->appendChild($my_title);
                // Give a child element <adlcp:prerequisites> to the <item> element.
                $my_prereqs = $xmldoc->createElement(
                    'adlcp:prerequisites',
                    $lp->get_scorm_prereq_string($my_item_id)
                );
                $my_prereqs->setAttribute('type', 'aicc_script');
                $my_item->appendChild($my_prereqs);
                // Give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:maxtimeallowed','');
                // Give a child element <adlcp:timelimitaction> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:timelimitaction','');
                // Give a child element <adlcp:datafromlms> to the <item> element - not yet supported.
                //$xmldoc->createElement('adlcp:datafromlms','');
                // Give a child element <adlcp:masteryscore> to the <item> element.
                $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                $my_item->appendChild($my_masteryscore);

                // Attach this item to the organization element or hits parent if there is one.
                if (!empty($item->parent) && 0 != $item->parent) {
                    $children = $organization->childNodes;
                    $possible_parent = $lp->get_scorm_xml_node($children, 'ITEM_'.$item->parent);
                    if (is_object($possible_parent)) {
                        $possible_parent->appendChild($my_item);
                    } else {
                        if ($lp->debug > 0) {
                            error_log('Parent ITEM_'.$item->parent.' of item ITEM_'.$my_item_id.' not found');
                        }
                    }
                } else {
                    if ($lp->debug > 0) {
                        error_log('No parent');
                    }
                    $organization->appendChild($my_item);
                }

                // Get the path of the file(s) from the course directory root.
                $my_file_path = $item->get_file_path('scorm/'.$lp->path.'/');
                $my_xml_file_path = $my_file_path;
                if (!empty($path_to_remove)) {
                    // From docs
                    $my_xml_file_path = str_replace($path_to_remove, $path_to_replace, $my_file_path);

                    // From quiz
                    if ('chamilo_scorm_export' === $lp->ref) {
                        $path_to_remove = 'scorm/'.$lp->path.'/';
                        $my_xml_file_path = str_replace($path_to_remove, '', $my_file_path);
                    }
                }

                $my_sub_dir = dirname($my_file_path);
                $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                $my_xml_sub_dir = $my_sub_dir;
                // Give a <resource> child to the <resources> element
                $my_resource = $xmldoc->createElement('resource');
                $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                $my_resource->setAttribute('type', 'webcontent');
                $my_resource->setAttribute('href', $my_xml_file_path);
                // adlcp:scormtype can be either 'sco' or 'asset'.
                if ('sco' === $item->type) {
                    $my_resource->setAttribute('adlcp:scormtype', 'sco');
                } else {
                    $my_resource->setAttribute('adlcp:scormtype', 'asset');
                }
                // xml:base is the base directory to find the files declared in this resource.
                $my_resource->setAttribute('xml:base', '');
                // Give a <file> child to the <resource> element.
                $my_file = $xmldoc->createElement('file');
                $my_file->setAttribute('href', $my_xml_file_path);
                $my_resource->appendChild($my_file);

                // Dependency to other files - not yet supported.
                $i = 1;
                if ($inc_docs) {
                    foreach ($inc_docs as $doc_info) {
                        if (count($doc_info) < 1 || empty($doc_info[0])) {
                            continue;
                        }
                        $my_dep = $xmldoc->createElement('resource');
                        $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                        $my_dep->setAttribute('identifier', $res_id);
                        $my_dep->setAttribute('type', 'webcontent');
                        $my_dep->setAttribute('adlcp:scormtype', 'asset');
                        $my_dep_file = $xmldoc->createElement('file');
                        // Check type of URL.
                        if ('remote' == $doc_info[1]) {
                            // Remote file. Save url as is.
                            $my_dep_file->setAttribute('href', $doc_info[0]);
                            $my_dep->setAttribute('xml:base', '');
                        } elseif ('local' === $doc_info[1]) {
                            switch ($doc_info[2]) {
                                case 'url':
                                    // Local URL - save path as url for now, don't zip file.
                                    $abs_path = api_get_path(SYS_PATH).
                                        str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
                                    $current_dir = dirname($abs_path);
                                    $current_dir = str_replace('\\', '/', $current_dir);
                                    $file_path = realpath($abs_path);
                                    $file_path = str_replace('\\', '/', $file_path);
                                    $my_dep_file->setAttribute('href', $file_path);
                                    $my_dep->setAttribute('xml:base', '');
                                    if (false !== strstr($file_path, $main_path)) {
                                        // The calculated real path is really inside Chamilo's root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $replace = $file_path;
                                        $file_path = substr($file_path, strlen($root_path) - 1);
                                        $destinationFile = $file_path;

                                        if (false !== strstr($file_path, 'upload/users')) {
                                            $pos = strpos($file_path, 'my_files/');
                                            if (false !== $pos) {
                                                $onlyDirectory = str_replace(
                                                    'upload/users/',
                                                    '',
                                                    substr($file_path, $pos, strlen($file_path))
                                                );
                                            }
                                            $replace = $onlyDirectory;
                                            $destinationFile = $replace;
                                        }
                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = [
                                            'orig' => $doc_info[0],
                                            'dest' => $destinationFile,
                                            'replace' => $replace,
                                        ];
                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (empty($file_path)) {
                                        $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                        $file_path = str_replace('//', '/', $file_path);
                                        if (file_exists($file_path)) {
                                            // We get the relative path.
                                            $file_path = substr($file_path, strlen($current_dir));
                                            $zip_files[] = $my_sub_dir.'/'.$file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => $file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', $file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        }
                                    }
                                    break;
                                case 'abs':
                                    // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                    $my_dep_file->setAttribute('href', $doc_info[0]);
                                    $my_dep->setAttribute('xml:base', '');

                                    // The next lines fix a bug when using the "subdir" mode of Chamilo, whereas
                                    // an image path would be constructed as /var/www/subdir/subdir/img/foo.bar
                                    $abs_img_path_without_subdir = $doc_info[0];
                                    $relp = api_get_path(REL_PATH); // The url-append config param.
                                    $pos = strpos($abs_img_path_without_subdir, $relp);
                                    if (0 === $pos) {
                                        $abs_img_path_without_subdir = trim('/'.substr($abs_img_path_without_subdir, strlen($relp)));
                                    }

                                    $file_path = realpath(api_get_path(SYS_APP_PATH).$abs_img_path_without_subdir);
                                    $file_path = str_replace(['\\', '//'], '/', $file_path);

                                    // Prepare the current directory path (until just under 'document') with a trailing slash.
                                    $cur_path = '/' == substr($current_course_path, -1) ? $current_course_path : $current_course_path.'/';
                                    // Check if the current document is in that path.
                                    if (false !== strstr($file_path, $cur_path)) {
                                        $destinationFile = substr($file_path, strlen($cur_path));
                                        $filePathNoCoursePart = substr($file_path, strlen($cur_path));

                                        $fileToTest = $cur_path.$my_file_path;
                                        if (!empty($path_to_remove)) {
                                            $fileToTest = str_replace(
                                                $path_to_remove.'/',
                                                $path_to_replace,
                                                $cur_path.$my_file_path
                                            );
                                        }

                                        $relative_path = api_get_relative_path($fileToTest, $file_path);

                                        // Put the current document in the zip (this array is the array
                                        // that will manage documents already in the course folder - relative).
                                        $zip_files[] = $filePathNoCoursePart;
                                        // Update the links to the current document in the
                                        // containing document (make them relative).
                                        $link_updates[$my_file_path][] = [
                                            'orig' => $doc_info[0],
                                            'dest' => $destinationFile,
                                            'replace' => $relative_path,
                                        ];

                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (false !== strstr($file_path, $main_path)) {
                                        // The calculated real path is really inside Chamilo's root path.
                                        // Reduce file path to what's under the DocumentRoot.
                                        $file_path = substr($file_path, strlen($root_path));
                                        $zip_files_abs[] = $file_path;
                                        $link_updates[$my_file_path][] = ['orig' => $doc_info[0], 'dest' => $file_path];
                                        $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                    } elseif (empty($file_path)) {
                                        // Probably this is an image inside "/main" directory
                                        $file_path = api_get_path(SYS_PATH).$abs_img_path_without_subdir;
                                        $abs_path = api_get_path(SYS_PATH).str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);

                                        if (file_exists($file_path)) {
                                            if (false !== strstr($file_path, 'main/default_course_document')) {
                                                // We get the relative path.
                                                $pos = strpos($file_path, 'main/default_course_document/');
                                                if (false !== $pos) {
                                                    $onlyDirectory = str_replace(
                                                        'main/default_course_document/',
                                                        '',
                                                        substr($file_path, $pos, strlen($file_path))
                                                    );
                                                }

                                                $destinationFile = 'default_course_document/'.$onlyDirectory;
                                                $fileAbs = substr($file_path, strlen(api_get_path(SYS_PATH)));
                                                $zip_files_abs[] = $fileAbs;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $destinationFile,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                    }
                                    break;
                                case 'rel':
                                    // Path relative to the current document.
                                    // Save xml:base as current document's directory and save file in zip as subdir.file_path
                                    if ('..' === substr($doc_info[0], 0, 2)) {
                                        // Relative path going up.
                                        $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($current_dir.$doc_info[0]);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        if (false !== strstr($file_path, $main_path)) {
                                            // The calculated real path is really inside Chamilo's root path.
                                            // Reduce file path to what's under the DocumentRoot.
                                            $file_path = substr($file_path, strlen($root_path));
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = ['orig' => $doc_info[0], 'dest' => $file_path];
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        }
                                    } else {
                                        $zip_files[] = $my_sub_dir.'/'.$doc_info[0];
                                        $my_dep_file->setAttribute('href', $doc_info[0]);
                                        $my_dep->setAttribute('xml:base', $my_xml_sub_dir);
                                    }
                                    break;
                                default:
                                    $my_dep_file->setAttribute('href', $doc_info[0]);
                                    $my_dep->setAttribute('xml:base', '');
                                    break;
                            }
                        }
                        $my_dep->appendChild($my_dep_file);
                        $resources->appendChild($my_dep);
                        $dependency = $xmldoc->createElement('dependency');
                        $dependency->setAttribute('identifierref', $res_id);
                        $my_resource->appendChild($dependency);
                        $i++;
                    }
                }
                $resources->appendChild($my_resource);
                $zip_files[] = $my_file_path;
            } else {
                // If the item is a quiz or a link or whatever non-exportable, we include a step indicating it.
                switch ($item->type) {
                    case TOOL_LINK:
                        $my_item = $xmldoc->createElement('item');
                        $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                        $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                        $my_item->setAttribute('isvisible', 'true');
                        // Give a child element <title> to the <item> element.
                        $my_title = $xmldoc->createElement(
                            'title',
                            htmlspecialchars(
                                api_utf8_encode($item->get_title()),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
                        $my_item->appendChild($my_title);
                        // Give a child element <adlcp:prerequisites> to the <item> element.
                        $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $item->get_prereq_string());
                        $my_prereqs->setAttribute('type', 'aicc_script');
                        $my_item->appendChild($my_prereqs);
                        // Give a child element <adlcp:maxtimeallowed> to the <item> element - not yet supported.
                        //$xmldoc->createElement('adlcp:maxtimeallowed', '');
                        // Give a child element <adlcp:timelimitaction> to the <item> element - not yet supported.
                        //$xmldoc->createElement('adlcp:timelimitaction', '');
                        // Give a child element <adlcp:datafromlms> to the <item> element - not yet supported.
                        //$xmldoc->createElement('adlcp:datafromlms', '');
                        // Give a child element <adlcp:masteryscore> to the <item> element.
                        $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                        $my_item->appendChild($my_masteryscore);

                        // Attach this item to the organization element or its parent if there is one.
                        if (!empty($item->parent) && 0 != $item->parent) {
                            $children = $organization->childNodes;
                            for ($i = 0; $i < $children->length; $i++) {
                                $item_temp = $children->item($i);
                                if ('item' == $item_temp->nodeName) {
                                    if ($item_temp->getAttribute('identifier') == 'ITEM_'.$item->parent) {
                                        $item_temp->appendChild($my_item);
                                    }
                                }
                            }
                        } else {
                            $organization->appendChild($my_item);
                        }

                        $my_file_path = 'link_'.$item->get_id().'.html';
                        $sql = 'SELECT url, title FROM '.Database::get_course_table(TABLE_LINK).'
                                WHERE c_id = '.$course_id.' AND id = '.$item->path;
                        $rs = Database::query($sql);
                        if ($link = Database::fetch_array($rs)) {
                            $url = $link['url'];
                            $title = stripslashes($link['title']);
                            $links_to_create[$my_file_path] = ['title' => $title, 'url' => $url];
                            $my_xml_file_path = $my_file_path;
                            $my_sub_dir = dirname($my_file_path);
                            $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                            $my_xml_sub_dir = $my_sub_dir;
                            // Give a <resource> child to the <resources> element.
                            $my_resource = $xmldoc->createElement('resource');
                            $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                            $my_resource->setAttribute('type', 'webcontent');
                            $my_resource->setAttribute('href', $my_xml_file_path);
                            // adlcp:scormtype can be either 'sco' or 'asset'.
                            $my_resource->setAttribute('adlcp:scormtype', 'asset');
                            // xml:base is the base directory to find the files declared in this resource.
                            $my_resource->setAttribute('xml:base', '');
                            // give a <file> child to the <resource> element.
                            $my_file = $xmldoc->createElement('file');
                            $my_file->setAttribute('href', $my_xml_file_path);
                            $my_resource->appendChild($my_file);
                            $resources->appendChild($my_resource);
                        }
                        break;
                    case TOOL_QUIZ:
                        $exe_id = $item->path;
                        // Should be using ref when everything will be cleaned up in this regard.
                        $exe = new Exercise();
                        $exe->read($exe_id);
                        $my_item = $xmldoc->createElement('item');
                        $my_item->setAttribute('identifier', 'ITEM_'.$item->get_id());
                        $my_item->setAttribute('identifierref', 'RESOURCE_'.$item->get_id());
                        $my_item->setAttribute('isvisible', 'true');
                        // Give a child element <title> to the <item> element.
                        $my_title = $xmldoc->createElement(
                            'title',
                            htmlspecialchars(
                                api_utf8_encode($item->get_title()),
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        );
                        $my_item->appendChild($my_title);
                        $my_max_score = $xmldoc->createElement('max_score', $item->get_max());
                        $my_item->appendChild($my_max_score);
                        // Give a child element <adlcp:prerequisites> to the <item> element.
                        $my_prereqs = $xmldoc->createElement('adlcp:prerequisites', $item->get_prereq_string());
                        $my_prereqs->setAttribute('type', 'aicc_script');
                        $my_item->appendChild($my_prereqs);
                        // Give a child element <adlcp:masteryscore> to the <item> element.
                        $my_masteryscore = $xmldoc->createElement('adlcp:masteryscore', $item->get_mastery_score());
                        $my_item->appendChild($my_masteryscore);

                        // Attach this item to the organization element or hits parent if there is one.
                        if (!empty($item->parent) && 0 != $item->parent) {
                            $children = $organization->childNodes;
                            $possible_parent = $lp->get_scorm_xml_node($children, 'ITEM_'.$item->parent);
                            if ($possible_parent) {
                                if ($possible_parent->getAttribute('identifier') === 'ITEM_'.$item->parent) {
                                    $possible_parent->appendChild($my_item);
                                }
                            }
                        } else {
                            $organization->appendChild($my_item);
                        }

                        // Get the path of the file(s) from the course directory root
                        //$my_file_path = $item->get_file_path('scorm/'.$lp->path.'/');
                        $my_file_path = 'quiz_'.$item->get_id().'.html';
                        // Write the contents of the exported exercise into a (big) html file
                        // to later pack it into the exported SCORM. The file will be removed afterwards.
                        $scormExercise = new ScormExercise($exe, true);
                        $contents = $scormExercise->export();

                        $tmp_file_path = $archivePath.$temp_dir_short.'/'.$my_file_path;
                        $res = file_put_contents($tmp_file_path, $contents);
                        if (false === $res) {
                            error_log('Could not write into file '.$tmp_file_path.' '.__FILE__.' '.__LINE__, 0);
                        }
                        $files_cleanup[] = $tmp_file_path;
                        $my_xml_file_path = $my_file_path;
                        $my_sub_dir = dirname($my_file_path);
                        $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                        $my_xml_sub_dir = $my_sub_dir;
                        // Give a <resource> child to the <resources> element.
                        $my_resource = $xmldoc->createElement('resource');
                        $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                        $my_resource->setAttribute('type', 'webcontent');
                        $my_resource->setAttribute('href', $my_xml_file_path);
                        // adlcp:scormtype can be either 'sco' or 'asset'.
                        $my_resource->setAttribute('adlcp:scormtype', 'sco');
                        // xml:base is the base directory to find the files declared in this resource.
                        $my_resource->setAttribute('xml:base', '');
                        // Give a <file> child to the <resource> element.
                        $my_file = $xmldoc->createElement('file');
                        $my_file->setAttribute('href', $my_xml_file_path);
                        $my_resource->appendChild($my_file);

                        // Get included docs.
                        $inc_docs = $item->get_resources_from_source(null, $tmp_file_path);

                        // Dependency to other files - not yet supported.
                        $i = 1;
                        foreach ($inc_docs as $doc_info) {
                            if (count($doc_info) < 1 || empty($doc_info[0])) {
                                continue;
                            }
                            $my_dep = $xmldoc->createElement('resource');
                            $res_id = 'RESOURCE_'.$item->get_id().'_'.$i;
                            $my_dep->setAttribute('identifier', $res_id);
                            $my_dep->setAttribute('type', 'webcontent');
                            $my_dep->setAttribute('adlcp:scormtype', 'asset');
                            $my_dep_file = $xmldoc->createElement('file');
                            // Check type of URL.
                            if ('remote' == $doc_info[1]) {
                                // Remote file. Save url as is.
                                $my_dep_file->setAttribute('href', $doc_info[0]);
                                $my_dep->setAttribute('xml:base', '');
                            } elseif ('local' == $doc_info[1]) {
                                switch ($doc_info[2]) {
                                    case 'url': // Local URL - save path as url for now, don't zip file.
                                        // Save file but as local file (retrieve from URL).
                                        $abs_path = api_get_path(SYS_PATH).
                                            str_replace(api_get_path(WEB_PATH), '', $doc_info[0]);
                                        $current_dir = dirname($abs_path);
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($abs_path);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                        $my_dep->setAttribute('xml:base', '');
                                        if (false !== strstr($file_path, $main_path)) {
                                            // The calculated real path is really inside the chamilo root path.
                                            // Reduce file path to what's under the DocumentRoot.
                                            $file_path = substr($file_path, strlen($root_path));
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => 'document/'.$file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        } elseif (empty($file_path)) {
                                            $file_path = $_SERVER['DOCUMENT_ROOT'].$abs_path;
                                            $file_path = str_replace('//', '/', $file_path);
                                            if (file_exists($file_path)) {
                                                $file_path = substr($file_path, strlen($current_dir));
                                                // We get the relative path.
                                                $zip_files[] = $my_sub_dir.'/'.$file_path;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => 'document/'.$file_path,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                        break;
                                    case 'abs':
                                        // Absolute path from DocumentRoot. Save file and leave path as is in the zip.
                                        $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                        $current_dir = str_replace('\\', '/', $current_dir);
                                        $file_path = realpath($doc_info[0]);
                                        $file_path = str_replace('\\', '/', $file_path);
                                        $my_dep_file->setAttribute('href', $file_path);
                                        $my_dep->setAttribute('xml:base', '');

                                        if (false !== strstr($file_path, $main_path)) {
                                            // The calculated real path is really inside the chamilo root path.
                                            // Reduce file path to what's under the DocumentRoot.
                                            $file_path = substr($file_path, strlen($root_path));
                                            $zip_files_abs[] = $file_path;
                                            $link_updates[$my_file_path][] = [
                                                'orig' => $doc_info[0],
                                                'dest' => $file_path,
                                            ];
                                            $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                            $my_dep->setAttribute('xml:base', '');
                                        } elseif (empty($file_path)) {
                                            $docSysPartPath = str_replace(
                                                api_get_path(REL_COURSE_PATH),
                                                '',
                                                $doc_info[0]
                                            );

                                            $docSysPartPathNoCourseCode = str_replace(
                                                $_course['directory'].'/',
                                                '',
                                                $docSysPartPath
                                            );

                                            $docSysPath = api_get_path(SYS_COURSE_PATH).$docSysPartPath;
                                            if (file_exists($docSysPath)) {
                                                $file_path = $docSysPartPathNoCourseCode;
                                                $zip_files[] = $my_sub_dir.'/'.$file_path;
                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $file_path,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        }
                                        break;
                                    case 'rel':
                                        // Path relative to the current document. Save xml:base as current document's
                                        // directory and save file in zip as subdir.file_path
                                        if ('..' === substr($doc_info[0], 0, 2)) {
                                            // Relative path going up.
                                            $current_dir = dirname($current_course_path.'/'.$item->get_file_path()).'/';
                                            $current_dir = str_replace('\\', '/', $current_dir);
                                            $file_path = realpath($current_dir.$doc_info[0]);
                                            $file_path = str_replace('\\', '/', $file_path);
                                            if (false !== strstr($file_path, $main_path)) {
                                                // The calculated real path is really inside Chamilo's root path.
                                                // Reduce file path to what's under the DocumentRoot.

                                                $file_path = substr($file_path, strlen($root_path));
                                                $file_path_dest = $file_path;

                                                // File path is courses/CHAMILO/document/....
                                                $info_file_path = explode('/', $file_path);
                                                if ('courses' == $info_file_path[0]) {
                                                    // Add character "/" in file path.
                                                    $file_path_dest = 'document/'.$file_path;
                                                }
                                                $zip_files_abs[] = $file_path;

                                                $link_updates[$my_file_path][] = [
                                                    'orig' => $doc_info[0],
                                                    'dest' => $file_path_dest,
                                                ];
                                                $my_dep_file->setAttribute('href', 'document/'.$file_path);
                                                $my_dep->setAttribute('xml:base', '');
                                            }
                                        } else {
                                            $zip_files[] = $my_sub_dir.'/'.$doc_info[0];
                                            $my_dep_file->setAttribute('href', $doc_info[0]);
                                            $my_dep->setAttribute('xml:base', $my_xml_sub_dir);
                                        }
                                        break;
                                    default:
                                        $my_dep_file->setAttribute('href', $doc_info[0]); // ../../courses/
                                        $my_dep->setAttribute('xml:base', '');
                                        break;
                                }
                            }
                            $my_dep->appendChild($my_dep_file);
                            $resources->appendChild($my_dep);
                            $dependency = $xmldoc->createElement('dependency');
                            $dependency->setAttribute('identifierref', $res_id);
                            $my_resource->appendChild($dependency);
                            $i++;
                        }
                        $resources->appendChild($my_resource);
                        $zip_files[] = $my_file_path;
                        break;
                    default:
                        // Get the path of the file(s) from the course directory root
                        $my_file_path = 'non_exportable.html';
                        //$my_xml_file_path = api_htmlentities(api_utf8_encode($my_file_path), ENT_COMPAT, 'UTF-8');
                        $my_xml_file_path = $my_file_path;
                        $my_sub_dir = dirname($my_file_path);
                        $my_sub_dir = str_replace('\\', '/', $my_sub_dir);
                        //$my_xml_sub_dir = api_htmlentities(api_utf8_encode($my_sub_dir), ENT_COMPAT, 'UTF-8');
                        $my_xml_sub_dir = $my_sub_dir;
                        // Give a <resource> child to the <resources> element.
                        $my_resource = $xmldoc->createElement('resource');
                        $my_resource->setAttribute('identifier', 'RESOURCE_'.$item->get_id());
                        $my_resource->setAttribute('type', 'webcontent');
                        $my_resource->setAttribute('href', $folder_name.'/'.$my_xml_file_path);
                        // adlcp:scormtype can be either 'sco' or 'asset'.
                        $my_resource->setAttribute('adlcp:scormtype', 'asset');
                        // xml:base is the base directory to find the files declared in this resource.
                        $my_resource->setAttribute('xml:base', '');
                        // Give a <file> child to the <resource> element.
                        $my_file = $xmldoc->createElement('file');
                        $my_file->setAttribute('href', 'document/'.$my_xml_file_path);
                        $my_resource->appendChild($my_file);
                        $resources->appendChild($my_resource);
                        break;
                }
            }
        }
        $organizations->appendChild($organization);
        $root->appendChild($organizations);
        $root->appendChild($resources);
        $xmldoc->appendChild($root);

        $copyAll = api_get_configuration_value('add_all_files_in_lp_export');

        // then add the file to the zip, then destroy the file (this is done automatically).
        // http://www.reload.ac.uk/scormplayer.html - once done, don't forget to close FS#138
        foreach ($zip_files as $file_path) {
            if (empty($file_path)) {
                continue;
            }

            $filePath = $sys_course_path.$_course['path'].'/'.$file_path;
            $dest_file = $archivePath.$temp_dir_short.'/'.$file_path;

            if (!empty($path_to_remove) && !empty($path_to_replace)) {
                $dest_file = str_replace($path_to_remove, $path_to_replace, $dest_file);
            }

            $lp->create_path($dest_file);
            @copy($filePath, $dest_file);

            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if ('flv' === substr($old_new['dest'], -3) &&
                        'main/' === substr($old_new['dest'], 0, 5)
                    ) {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    } elseif ('flv' === substr($old_new['dest'], -3) &&
                        'video/' === substr($old_new['dest'], 0, 6)
                    ) {
                        $old_new['dest'] = str_replace('video/', '../../../../video/', $old_new['dest']);
                    }

                    // Fix to avoid problems with default_course_document
                    if (false === strpos('main/default_course_document', $old_new['dest'])) {
                        $newDestination = $old_new['dest'];
                        if (isset($old_new['replace']) && !empty($old_new['replace'])) {
                            $newDestination = $old_new['replace'];
                        }
                    } else {
                        $newDestination = str_replace('document/', '', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $newDestination, $string);

                    // Add files inside the HTMLs
                    $new_path = str_replace(api_get_path(REL_COURSE_PATH), '', $old_new['orig']);
                    $destinationFile = $archivePath.$temp_dir_short.'/'.$old_new['dest'];
                    if (file_exists($sys_course_path.$new_path) && is_file($sys_course_path.$new_path)) {
                        copy(
                            $sys_course_path.$new_path,
                            $destinationFile
                        );
                    }
                }
                file_put_contents($dest_file, $string);
            }

            if (file_exists($filePath) && $copyAll) {
                $extension = $lp->get_extension($filePath);
                if (in_array($extension, ['html', 'html'])) {
                    $containerOrigin = dirname($filePath);
                    $containerDestination = dirname($dest_file);

                    $finder = new Finder();
                    $finder->files()->in($containerOrigin)
                        ->notName('*_DELETED_*')
                        ->exclude('share_folder')
                        ->exclude('chat_files')
                        ->exclude('certificates')
                    ;

                    if (is_dir($containerOrigin) &&
                        is_dir($containerDestination)
                    ) {
                        $fs = new Filesystem();
                        $fs->mirror(
                            $containerOrigin,
                            $containerDestination,
                            $finder
                        );
                    }
                }
            }
        }

        foreach ($zip_files_abs as $file_path) {
            if (empty($file_path)) {
                continue;
            }

            if (!is_file($main_path.$file_path) || !is_readable($main_path.$file_path)) {
                continue;
            }

            $dest_file = $archivePath.$temp_dir_short.'/document/'.$file_path;
            if (false !== strstr($file_path, 'upload/users')) {
                $pos = strpos($file_path, 'my_files/');
                if (false !== $pos) {
                    $onlyDirectory = str_replace(
                        'upload/users/',
                        '',
                        substr($file_path, $pos, strlen($file_path))
                    );
                    $dest_file = $archivePath.$temp_dir_short.'/document/'.$onlyDirectory;
                }
            }

            if (false !== strstr($file_path, 'default_course_document/')) {
                $replace = str_replace('/main', '', $file_path);
                $dest_file = $archivePath.$temp_dir_short.'/document/'.$replace;
            }

            if (empty($dest_file)) {
                continue;
            }

            $lp->create_path($dest_file);
            copy($main_path.$file_path, $dest_file);
            // Check if the file needs a link update.
            if (in_array($file_path, array_keys($link_updates))) {
                $string = file_get_contents($dest_file);
                unlink($dest_file);
                foreach ($link_updates[$file_path] as $old_new) {
                    // This is an ugly hack that allows .flv files to be found by the flv player that
                    // will be added in document/main/inc/lib/flv_player/flv_player.swf and that needs
                    // to find the flv to play in document/main/, so we replace main/ in the flv path by
                    // ../../.. to return from inc/lib/flv_player to the document/main path.
                    if ('flv' == substr($old_new['dest'], -3) &&
                        'main/' == substr($old_new['dest'], 0, 5)
                    ) {
                        $old_new['dest'] = str_replace('main/', '../../../', $old_new['dest']);
                    }
                    $string = str_replace($old_new['orig'], $old_new['dest'], $string);
                }
                file_put_contents($dest_file, $string);
            }
        }

        if (is_array($links_to_create)) {
            foreach ($links_to_create as $file => $link) {
                $content = '<!DOCTYPE html><head>
                            <meta charset="'.api_get_language_isocode().'" />
                            <title>'.$link['title'].'</title>
                            </head>
                            <body dir="'.api_get_text_direction().'">
                            <div style="text-align:center">
                            <a href="'.$link['url'].'">'.$link['title'].'</a></div>
                            </body>
                            </html>';
                file_put_contents($archivePath.$temp_dir_short.'/'.$file, $content);
            }
        }

        // Add non exportable message explanation.
        $lang_not_exportable = get_lang('This learning object or activity is not SCORM compliant. That\'s why it is not exportable.');
        $file_content = '<!DOCTYPE html><head>
                        <meta charset="'.api_get_language_isocode().'" />
                        <title>'.$lang_not_exportable.'</title>
                        <meta http-equiv="Content-Type" content="text/html; charset='.api_get_system_encoding().'" />
                        </head>
                        <body dir="'.api_get_text_direction().'">';
        $file_content .=
            <<<EOD
                    <style>
            .error-message {
                font-family: arial, verdana, helvetica, sans-serif;
                border-width: 1px;
                border-style: solid;
                left: 50%;
                margin: 10px auto;
                min-height: 30px;
                padding: 5px;
                right: 50%;
                width: 500px;
                background-color: #FFD1D1;
                border-color: #FF0000;
                color: #000;
            }
        </style>
    <body>
        <div class="error-message">
            $lang_not_exportable
        </div>
    </body>
</html>
EOD;
        if (!is_dir($archivePath.$temp_dir_short.'/document')) {
            @mkdir($archivePath.$temp_dir_short.'/document', api_get_permissions_for_new_directories());
        }
        file_put_contents($archivePath.$temp_dir_short.'/document/non_exportable.html', $file_content);

        // Add the extra files that go along with a SCORM package.
        $main_code_path = api_get_path(SYS_CODE_PATH).'lp/packaging/';

        $fs = new Filesystem();
        $fs->mirror($main_code_path, $archivePath.$temp_dir_short);

        // Finalize the imsmanifest structure, add to the zip, then return the zip.
        $manifest = @$xmldoc->saveXML();
        $manifest = api_utf8_decode_xml($manifest); // The manifest gets the system encoding now.
        file_put_contents($archivePath.'/'.$temp_dir_short.'/imsmanifest.xml', $manifest);
        $zip_folder->add(
            $archivePath.'/'.$temp_dir_short,
            PCLZIP_OPT_REMOVE_PATH,
            $archivePath.'/'.$temp_dir_short.'/'
        );

        // Clean possible temporary files.
        foreach ($files_cleanup as $file) {
            $res = unlink($file);
            if (false === $res) {
                error_log(
                    'Could not delete temp file '.$file.' '.__FILE__.' '.__LINE__,
                    0
                );
            }
        }
        $name = api_replace_dangerous_char($lp->get_name()).'.zip';
        DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    }

    public static function exportToPdf($lp_id, $courseInfo)
    {
        // @todo fix exportToPdf
        $lp_id = (int) $lp_id;
        /** @var CLp $lp */
        $lp = Container::getLpRepository()->find($lp_id);

        $lpItemRepo = Container::getLpItemRepository();

        $files_to_export = [];

        $sessionId = api_get_session_id();
        $courseCode = $courseInfo['code'];
        $scorm_path = null;

        if (!empty($courseInfo)) {
            //$scorm_path = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/scorm/'.$this->path;
            $list = learnpath::get_flat_ordered_items_list($lp);
            if (!empty($list)) {
                foreach ($list as $item_id) {
                    /** @var CLpItem $item */
                    $item = $lpItemRepo->find($item_id);
                    $type = $item->getItemType();
                    switch ($type) {
                        case 'document':
                            // Getting documents from a LP with chamilo documents
                            $file_data = DocumentManager::get_document_data_by_id($item->getPath(), $courseCode);
                            // Try loading document from the base course.
                            if (empty($file_data) && !empty($sessionId)) {
                                $file_data = DocumentManager::get_document_data_by_id(
                                    $item->getPath(),
                                    $courseCode,
                                    false,
                                    0
                                );
                            }
                            $file_path = api_get_path(SYS_COURSE_PATH).$courseCode['path'].'/document'.$file_data['path'];
                            if (file_exists($file_path)) {
                                $files_to_export[] = [
                                    'title' => $item->get_title(),
                                    'path' => $file_path,
                                ];
                            }
                            break;
                        case 'asset': //commes from a scorm package generated by chamilo
                        case 'sco':
                            $file_path = $scorm_path.'/'.$item->getPath();
                            if (file_exists($file_path)) {
                                $files_to_export[] = [
                                    'title' => $item->get_title(),
                                    'path' => $file_path,
                                ];
                            }
                            break;
                        case 'dir':
                            $files_to_export[] = [
                                'title' => $item->get_title(),
                                'path' => null,
                            ];
                            break;
                    }
                }
            }

            $pdf = new PDF();
            $result = $pdf->html_to_pdf(
                $files_to_export,
                $lp->getName(),
                $courseCode,
                true,
                true,
                true,
                $lp->getName()
            );

            return $result;
        }

        return false;
    }
}
