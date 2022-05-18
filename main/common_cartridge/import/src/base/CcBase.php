<?php
/* For licensing terms, see /license.txt */

class CcBase
{
    public const CC_TYPE_FORUM = 'imsdt_xmlv1p3';
    public const CC_TYPE_QUIZ = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const CC_TYPE_QUESTION_BANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const CC_TYPE_WEBLINK = 'imswl_xmlv1p3';
    public const CC_TYPE_WEBCONTENT = 'webcontent';
    public const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const CC_TYPE_EMPTY = '';

    public static $restypes = ['associatedcontent/imscc_xmlv1p0/learning-application-resource', 'webcontent'];
    public static $forumns = ['dt' => 'http://www.imsglobal.org/xsd/imsdt_v1p0'];
    public static $quizns = ['xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2'];
    public static $resourcens = ['wl' => 'http://www.imsglobal.org/xsd/imswl_v1p0'];

    public static $instances = [];
    public static $manifest;
    public static $pathToManifestFolder;

    public static $namespaces = ['imscc' => 'http://www.imsglobal.org/xsd/imscc/imscp_v1p1',
                                      'lomimscc' => 'http://ltsc.ieee.org/xsd/imscc/LOM',
                                      'lom' => 'http://ltsc.ieee.org/xsd/LOM',
                                      'voc' => 'http://ltsc.ieee.org/xsd/LOM/vocab',
                                      'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
                                      'cc' => 'http://www.imsglobal.org/xsd/imsccauth_v1p0', ];

    public function __construct($path_to_manifest)
    {
        static::$manifest = new DOMDocument();
        static::$manifest->validateOnParse = false;

        static::$pathToManifestFolder = dirname($path_to_manifest);

        static::logAction('Proccess start');
        static::logAction('Load the manifest file: '.$path_to_manifest);

        if (!static::$manifest->load($path_to_manifest, LIBXML_NONET)) {
            static::logAction('Cannot load the manifest file: '.$path_to_manifest, true);
        }
    }

    /**
     * @return array
     */
    public static function getquizns()
    {
        return static::$quizns;
    }

    /**
     * @return array
     */
    public static function getforumns()
    {
        return static::$forumns;
    }

    /**
     * @return array
     */
    public static function getresourcens()
    {
        return static::$resourcens;
    }

    public static function getManifest($folder)
    {
        if (!is_dir($folder)) {
            return false;
        }

        // Before iterate over directories, try to find one manifest at top level
        if (file_exists($folder.'/imsmanifest.xml')) {
            return $folder.'/imsmanifest.xml';
        }

        $result = false;
        try {
            $dirIter = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
            $recIter = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($recIter as $info) {
                if ($info->isFile() && ($info->getFilename() == 'imsmanifest.xml')) {
                    $result = $info->getPathname();
                    break;
                }
            }
        } catch (Exception $e) {
        }

        return $result;
    }

    public function isAuth()
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);

        $count_auth = $xpath->evaluate('count(/imscc:manifest/cc:authorizations)');

        if ($count_auth > 0) {
            $response = true;
        } else {
            $response = false;
        }

        return $response;
    }

    public function getNodesByCriteria($key, $value)
    {
        $response = [];

        if (array_key_exists('index', static::$instances)) {
            foreach (static::$instances['index'] as $item) {
                if ($item[$key] == $value) {
                    $response[] = $item;
                }
            }
        }

        return $response;
    }

    public function countInstances($type)
    {
        $quantity = 0;

        if (array_key_exists('index', static::$instances)) {
            if (static::$instances['index'] && $type) {
                foreach (static::$instances['index'] as $instance) {
                    if (!empty($instance['tool_type'])) {
                        $types[] = $instance['tool_type'];
                    }
                }

                $quantityInstances = array_count_values($types);
                $quantity = array_key_exists($type, $quantityInstances) ? $quantityInstances[$type] : 0;
            }
        }

        return $quantity;
    }

    public function getItemCcType($identifier)
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);

        $nodes = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]/@type');

        if ($nodes && !empty($nodes->item(0)->nodeValue)) {
            return $nodes->item(0)->nodeValue;
        } else {
            return '';
        }
    }

    public static function newxPath(DOMDocument $manifest, $namespaces = '')
    {
        $xpath = new DOMXPath($manifest);

        if (!empty($namespaces)) {
            foreach ($namespaces as $prefix => $ns) {
                if (!$xpath->registerNamespace($prefix, $ns)) {
                    static::logAction('Cannot register the namespace: '.$prefix.':'.$ns, true);
                }
            }
        }

        return $xpath;
    }

    public static function logFile()
    {
        return static::$pathToManifestFolder.DIRECTORY_SEPARATOR.'cc_import.log';
    }

    public static function logAction($text, $criticalError = false)
    {
        $full_message = strtoupper(date("j/n/Y g:i:s a"))." - ".$text."\r";

        file_put_contents(static::logFile(), $full_message, FILE_APPEND);

        if ($criticalError) {
            static::criticalError($text);
        }
    }

    public function convertToToolType($ccType)
    {
        $type = TYPE_UNKNOWN;

        if ($ccType == static::CC_TYPE_FORUM) {
            $type = TOOL_TYPE_FORUM;
        }

        if ($ccType == static::CC_TYPE_QUIZ) {
            $type = TOOL_TYPE_QUIZ;
        }

        if ($ccType == static::CC_TYPE_WEBLINK) {
            $type = TOOL_TYPE_WEBLINK;
        }

        if ($ccType == static::CC_TYPE_WEBCONTENT) {
            $type = TOOL_TYPE_DOCUMENT;
        }

        return $type;
    }

    protected function getMetadata($section, $key)
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);

        $metadata = $xpath->query('/imscc:manifest/imscc:metadata/lomimscc:lom/lomimscc:'.$section.'/lomimscc:'.$key.'/lomimscc:string');
        $value = !empty($metadata->item(0)->nodeValue) ? $metadata->item(0)->nodeValue : '';

        return $value;
    }

    /**
     * Is activity visible or not.
     *
     * @param string $identifier
     *
     * @return number
     */
    protected function getModuleVisible($identifier)
    {
        //Should item be hidden or not
        $mod_visible = 1;
        if (!empty($identifier)) {
            $xpath = static::newxPath(static::$manifest, static::$namespaces);
            $query = '/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]';
            $query .= '//lom:intendedEndUserRole/voc:vocabulary/lom:value';
            $intendeduserrole = $xpath->query($query);
            if (!empty($intendeduserrole) && ($intendeduserrole->length > 0)) {
                $role = trim($intendeduserrole->item(0)->nodeValue);
                if (strcasecmp('Instructor', $role) == 0) {
                    $mod_visible = 0;
                }
            }
        }

        return $mod_visible;
    }

    protected function createInstances($items, $level = 0, &$array_index = 0, $index_root = 0)
    {
        $level++;
        $i = 1;

        if ($items) {
            $xpath = self::newxPath(static::$manifest, static::$namespaces);

            foreach ($items as $item) {
                $array_index++;
                if ($item->nodeName == "item") {
                    $identifierref = '';
                    if ($item->hasAttribute('identifierref')) {
                        $identifierref = $item->getAttribute('identifierref');
                    }

                    $title = '';
                    $titles = $xpath->query('imscc:title', $item);
                    if ($titles->length > 0) {
                        $title = $titles->item(0)->nodeValue;
                    }

                    $ccType = $this->getItemCcType($identifierref);
                    $tool_type = $this->convertToToolType($ccType);
                    //Fix the label issue - MDL-33523
                    if (empty($identifierref) && empty($title)) {
                        $tool_type = TYPE_UNKNOWN;
                    }
                } elseif ($item->nodeName == "resource") {
                    $identifierref = $xpath->query('@identifier', $item);
                    $identifierref = !empty($identifierref->item(0)->nodeValue) ? $identifierref->item(0)->nodeValue : '';

                    $ccType = $this->getItemCcType($identifierref);
                    $tool_type = $this->convertToToolType($ccType);

                    $title = 'Quiz Bank '.($this->countInstances($tool_type) + 1);
                }

                if ($level == ROOT_DEEP) {
                    $index_root = $array_index;
                }

                static::$instances['index'][$array_index]['common_cartriedge_type'] = $ccType;
                static::$instances['index'][$array_index]['tool_type'] = $tool_type;
                static::$instances['index'][$array_index]['title'] = $title ? $title : '';
                static::$instances['index'][$array_index]['root_parent'] = $index_root;
                static::$instances['index'][$array_index]['index'] = $array_index;
                static::$instances['index'][$array_index]['deep'] = $level;
                static::$instances['index'][$array_index]['instance'] = $this->countInstances($tool_type);
                static::$instances['index'][$array_index]['resource_indentifier'] = $identifierref;

                static::$instances['instances'][$tool_type][] = ['title' => $title,
                                                                        'instance' => static::$instances['index'][$array_index]['instance'],
                                                                        'common_cartriedge_type' => $ccType,
                                                                        'resource_indentifier' => $identifierref,
                                                                        'deep' => $level, ];

                $more_items = $xpath->query('imscc:item', $item);

                if ($more_items->length > 0) {
                    $this->createInstances($more_items, $level, $array_index, $index_root);
                }

                $i++;
            }
        }
    }

    protected static function criticalError($text)
    {
        $path_to_log = static::logFile();

        echo '

        <p>
        <hr />A critical error has been found!

        <p>'.$text.'</p>


        <p>
        The process has been stopped. Please see the <a href="'.$path_to_log.'">log file</a> for more information.</p>

        <p>Log: '.$path_to_log.'</p>

        <hr />

        </p>
        ';

        exit();
    }

    protected function createCourseCode($title)
    {
        //Making sure that text of the short name does not go over the DB limit.
        //and leaving the space to add additional characters by the platform
        $code = substr(strtoupper(str_replace(' ', '', trim($title))), 0, 94);

        return $code;
    }
}
