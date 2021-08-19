<?php
/* For licensing terms, see /license.txt */

class Cc13Resource extends Cc13Entities
{
    public function generateData($resource_type)
    {
        $data = [];
        if (!empty(Cc1p3Convert::$instances['instances'][$resource_type])) {
            foreach (Cc1p3Convert::$instances['instances'][$resource_type] as $instance) {
                $data[] = $this->getResourceData($instance);
            }
        }

        return $data;
    }

    public function storeLinks($links)
    {
        foreach ($links as $link) {
            $_POST['title'] = $link[1];
            $_POST['url'] = $link[4];
            $_POST['description'] = '';
            $_POST['category_id'] = 0;
            $_POST['target'] = '_blank';
            Link::addlinkcategory('link');
        }

        return true;
    }

    public function storeDocuments($documents, $path)
    {
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
            '/cc1p3',
            'Common Cartridge folder',
            0
        );

        foreach ($documents as $document) {
            if ($document[2] == 'file') {
                $filepath = $path.DIRECTORY_SEPARATOR.$document[4];
                $files = [];
                $files['file']['name'] = $document[1];
                $files['file']['tmp_name'] = $filepath;
                $files['file']['type'] = mime_content_type($filepath);
                $files['file']['error'] = 0;
                $files['file']['size'] = filesize($filepath);
                $files['file']['from_file'] = true;
                $files['file']['move_file'] = true;
                $_POST['language'] = $courseInfo['language'];
                $_POST['cc_import'] = true;

                DocumentManager::upload_document(
                    $files,
                    '/cc1p3',
                    $document[1],
                    '',
                    null,
                    null,
                    true,
                    true
                );
            }
        }

        return true;
    }

    public function getResourceData($instance)
    {
        //var_dump($instance);

        $xpath = Cc1p3Convert::newxPath(Cc1p3Convert::$manifest, Cc1p3Convert::$namespaces);
        $link = '';

        if ($instance['common_cartriedge_type'] == Cc1p3Convert::CC_TYPE_WEBCONTENT || $instance['common_cartriedge_type'] == Cc1p3Convert::CC_TYPE_ASSOCIATED_CONTENT) {
            $resource = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$instance['resource_indentifier'].'"]/@href');
            if ($resource->length > 0) {
                $resource = !empty($resource->item(0)->nodeValue) ? $resource->item(0)->nodeValue : '';
            } else {
                $resource = '';
            }

            if (empty($resource)) {
                unset($resource);
                $resource = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$instance['resource_indentifier'].'"]/imscc:file/@href');
                if ($resource->length > 0) {
                    $resource = !empty($resource->item(0)->nodeValue) ? $resource->item(0)->nodeValue : '';
                } else {
                    $resource = '';
                }
            }
            if (!empty($resource)) {
                $link = $resource;
            }
        }

        if ($instance['common_cartriedge_type'] == Cc1p3Convert::CC_TYPE_WEBLINK) {
            $external_resource = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$instance['resource_indentifier'].'"]/imscc:file/@href')->item(0)->nodeValue;

            if ($external_resource) {
                $resource = $this->loadXmlResource(Cc1p3Convert::$pathToManifestFolder.DIRECTORY_SEPARATOR.$external_resource);

                if (!empty($resource)) {
                    $xpath = Cc1p3Convert::newxPath($resource, Cc1p3Convert::$resourcens);
                    $resource = $xpath->query('/wl:webLink/wl:url/@href');
                    if ($resource->length > 0) {
                        $rawlink = $resource->item(0)->nodeValue;
                        if (!validateUrlSyntax($rawlink, 's+')) {
                            $changed = rawurldecode($rawlink);
                            if (validateUrlSyntax($changed, 's+')) {
                                $link = $changed;
                            } else {
                                $link = 'http://invalidurldetected/';
                            }
                        } else {
                            $link = htmlspecialchars(trim($rawlink), ENT_COMPAT, 'UTF-8', false);
                        }
                    }
                }
            }
        }

        $mod_type = 'file';
        $mod_options = 'objectframe';
        $mod_reference = $link;
        //detected if we are dealing with html file
        if (!empty($link) && ($instance['common_cartriedge_type'] == Cc1p3Convert::CC_TYPE_WEBCONTENT)) {
            $ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
            if (in_array($ext, ['html', 'htm', 'xhtml'])) {
                $mod_type = 'html';
                //extract the content of the file
                $rootpath = realpath(Cc1p3Convert::$pathToManifestFolder);
                $htmlpath = realpath($rootpath.DIRECTORY_SEPARATOR.$link);
                $dirpath = dirname($htmlpath);
                if (file_exists($htmlpath)) {
                    $fcontent = file_get_contents($htmlpath);
                    $mod_alltext = $this->prepareContent($fcontent);
                    $mod_reference = '';
                    $mod_options = '';
                    /**
                     * try to handle embedded resources
                     * images, linked static resources, applets, videos.
                     */
                    $doc = new DOMDocument();
                    $cdir = getcwd();
                    chdir($dirpath);
                    try {
                        $doc->loadHTML($mod_alltext);
                        $xpath = new DOMXPath($doc);
                        $attributes = ['href', 'src', 'background', 'archive', 'code'];
                        $qtemplate = "//*[@##][not(contains(@##,'://'))]/@##";
                        $query = '';
                        foreach ($attributes as $attrname) {
                            if (!empty($query)) {
                                $query .= " | ";
                            }
                            $query .= str_replace('##', $attrname, $qtemplate);
                        }
                        $list = $xpath->query($query);
                        $searches = [];
                        $replaces = [];
                        foreach ($list as $resrc) {
                            $rpath = $resrc->nodeValue;
                            $rtp = realpath($rpath);
                            if (($rtp !== false) && is_file($rtp)) {
                                //file is there - we are in business
                                $strip = str_replace("\\", "/", str_ireplace($rootpath, '', $rtp));
                                $encoded_file = '$@FILEPHP@$'.str_replace('/', '$@SLASH@$', $strip);
                                $searches[] = $resrc->nodeValue;
                                $replaces[] = $encoded_file;
                            }
                        }
                        $mod_alltext = str_replace($searches, $replaces, $mod_alltext);
                    } catch (Exception $e) {
                        //silence the complaints
                    }
                    chdir($cdir);
                    $mod_alltext = self::safexml($mod_alltext);
                }
            }
        }

        $values = [$instance['instance'],
                                self::safexml($instance['title']),
                                $mod_type,
                                $mod_alltext,
                                $mod_reference,
                                $mod_options,
                ];

        return $values;
    }
}
