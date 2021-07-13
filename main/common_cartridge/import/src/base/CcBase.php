<?php
/* For licensing terms, see /license.txt */

class CcBase 
{
    
    const CC_TYPE_FORUM              = 'imsdt_xmlv1p3';
    const CC_TYPE_QUIZ               = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    const CC_TYPE_QUESTION_BANK      = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    const CC_TYPE_WEBLINK            = 'imswl_xmlv1p3';
    const CC_TYPE_WEBCONTENT         = 'webcontent';
    const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    const CC_TYPE_EMPTY              = '';

    public static $restypes = array('associatedcontent/imscc_xmlv1p0/learning-application-resource', 'webcontent');
    public static $forumns  = array('dt' => 'http://www.imsglobal.org/xsd/imsdt_v1p0');
    public static $quizns   = array('xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2');
    public static $resourcens = array('wl' => 'http://www.imsglobal.org/xsd/imswl_v1p0');
    /**
     *
     * @return array
     */
    public static function getquizns() {
        return static::$quizns;
    }

    /**
     *
     * @return array
     */
    public static function getforumns() {
        return static::$forumns;
    }

    /**
     *
     * @return array
     */
    public static function getresourcens() {
        return static::$resourcens;
    }

    public static function get_manifest($folder) {
        if (!is_dir($folder)) {
            return false;
        }

        // Before iterate over directories, try to find one manifest at top level
        if (file_exists($folder . '/imsmanifest.xml')) {
            return $folder . '/imsmanifest.xml';
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
        } catch (Exception $e) {}

        return $result;
    }

    public static $instances = array();
    public static $manifest;
    public static $path_to_manifest_folder;

    public static $namespaces = array('imscc'    => 'http://www.imsglobal.org/xsd/imscc/imscp_v1p1',
                                      'lomimscc' => 'http://ltsc.ieee.org/xsd/imscc/LOM',
                                      'lom'      => 'http://ltsc.ieee.org/xsd/LOM',
                                      'voc'      => 'http://ltsc.ieee.org/xsd/LOM/vocab',
                                      'xsi'      => 'http://www.w3.org/2001/XMLSchema-instance',
                                      'cc'       => 'http://www.imsglobal.org/xsd/imsccauth_v1p0');    
 
    function __construct ($path_to_manifest) {

        static::$manifest = new DOMDocument();
        static::$manifest->validateOnParse = false;

        static::$path_to_manifest_folder = dirname($path_to_manifest);

        static::log_action('Proccess start');
        static::log_action('Load the manifest file: ' . $path_to_manifest);

        if (!static::$manifest->load($path_to_manifest, LIBXML_NONET)) {
            static::log_action('Cannot load the manifest file: ' . $path_to_manifest, true);
        }
    }

    public function is_auth () {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $count_auth = $xpath->evaluate('count(/imscc:manifest/cc:authorizations)');

        if ($count_auth > 0) {
            $response = true;
        } else {
            $response = false;
        }

        return $response;
    }

    protected function get_metadata ($section, $key) {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $metadata = $xpath->query('/imscc:manifest/imscc:metadata/lomimscc:lom/lomimscc:' . $section . '/lomimscc:' . $key . '/lomimscc:string');
        $value = !empty($metadata->item(0)->nodeValue) ? $metadata->item(0)->nodeValue : '';

        return $value;
    }
    
    

    /**
    *
    * Is activity visible or not
    * @param string $identifier
    * @return number
    */
    protected function get_module_visible($identifier) {
        //Should item be hidden or not
        $mod_visible = 1;
        if (!empty($identifier)) {
            $xpath = static::newx_path(static::$manifest, static::$namespaces);
            $query  = '/imscc:manifest/imscc:resources/imscc:resource[@identifier="' . $identifier . '"]';
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


    public function get_nodes_by_criteria ($key, $value) {

        $response = array();

        if (array_key_exists('index', static::$instances)) {
            foreach (static::$instances['index'] as $item) {
                if ($item[$key] == $value) {
                    $response[] = $item;
                }
            }
        }

        return $response;
    }


    protected function create_instances ($items, $level = 0, &$array_index = 0, $index_root = 0) {

        $level++;
        $i = 1;

        if ($items) {

            $xpath = self::newx_path(static::$manifest, static::$namespaces);

            foreach ($items as $item) {

                $array_index++;                                
                if ($item->nodeName == "item")  {
                    $identifierref = '';
                    if ($item->hasAttribute('identifierref')) {
                      $identifierref = $item->getAttribute('identifierref');
                    }

                    $title = '';
                    $titles = $xpath->query('imscc:title', $item);
                    if ($titles->length > 0) {
                        $title = $titles->item(0)->nodeValue;
                    }
                    
                    $cc_type = $this->get_item_cc_type($identifierref);                    
                    $tool_type = $this->convert_to_tool_type($cc_type);
                    //Fix the label issue - MDL-33523
                    if (empty($identifierref) && empty($title)) {
                      $tool_type = TYPE_UNKNOWN;
                    }
                }
                elseif ($item->nodeName == "resource")  {

                    $identifierref = $xpath->query('@identifier', $item);
                    $identifierref = !empty($identifierref->item(0)->nodeValue) ? $identifierref->item(0)->nodeValue : '';

                    $cc_type = $this->get_item_cc_type($identifierref);
                    $tool_type = $this->convert_to_tool_type($cc_type);

                    $title = 'Quiz Bank ' . ($this->count_instances($tool_type) + 1);

                }

                if ($level == ROOT_DEEP) {
                    $index_root = $array_index;
                }

                static::$instances['index'][$array_index]['common_cartriedge_type'] = $cc_type;
                static::$instances['index'][$array_index]['tool_type'] = $tool_type;
                static::$instances['index'][$array_index]['title'] = $title ? $title : '';
                static::$instances['index'][$array_index]['root_parent'] = $index_root;
                static::$instances['index'][$array_index]['index'] = $array_index;
                static::$instances['index'][$array_index]['deep'] = $level;
                static::$instances['index'][$array_index]['instance'] = $this->count_instances($tool_type);
                static::$instances['index'][$array_index]['resource_indentifier'] = $identifierref;

                static::$instances['instances'][$tool_type][] = array('title' => $title,
                                                                        'instance' => static::$instances['index'][$array_index]['instance'],
                                                                        'common_cartriedge_type' => $cc_type,
                                                                        'resource_indentifier' => $identifierref,
                                                                        'deep' => $level);

                $more_items = $xpath->query('imscc:item', $item);

                if ($more_items->length > 0) {
                    $this->create_instances($more_items, $level, $array_index, $index_root);
                }

                $i++;

            }
        }
    }

    public function count_instances ($type) {

        $quantity = 0;

        if (array_key_exists('index', static::$instances)) {
            if (static::$instances['index'] && $type) {

                foreach (static::$instances['index'] as $instance) {
                    if (!empty($instance['tool_type'])) {
                        $types[] = $instance['tool_type'];
                    }
                }

                $quantity_instances = array_count_values($types);
                $quantity = array_key_exists($type, $quantity_instances) ? $quantity_instances[$type] : 0;
            }
        }

        return $quantity;
    }

    public function get_item_cc_type ($identifier) {

        $xpath = static::newx_path(static::$manifest, static::$namespaces);

        $nodes = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="' . $identifier . '"]/@type');

        if ($nodes && !empty($nodes->item(0)->nodeValue)) {
            return $nodes->item(0)->nodeValue;
        } else {
            return '';
        }
    }

    public static function newx_path (DOMDocument $manifest, $namespaces = '') {

        $xpath = new DOMXPath($manifest);

        if (!empty($namespaces)) {
            foreach ($namespaces as $prefix => $ns) {
                if (!$xpath->registerNamespace($prefix, $ns)) {
                    static::log_action('Cannot register the namespace: ' . $prefix . ':' . $ns, true);
                }
            }
        }

        return $xpath;
    }


    public static function log_file() {
        return static::$path_to_manifest_folder . DIRECTORY_SEPARATOR . 'cc_import.log';
    }

    public static function log_action ($text, $critical_error = false) {

        $full_message = strtoupper(date("j/n/Y g:i:s a")) . " - " . $text . "\r";

        file_put_contents(static::log_file(), $full_message, FILE_APPEND);

        if ($critical_error) {
            static::critical_error($text);
        }
    }

    protected static function critical_error ($text) {

        $path_to_log = static::log_file();

        echo '

        <p>
        <hr />A critical error has been found!

        <p>' . $text . '</p>


        <p>
        The process has been stopped. Please see the <a href="' . $path_to_log . '">log file</a> for more information.</p>

        <p>Log: ' . $path_to_log . '</p>

        <hr />

        </p>
        ';

        die();
    }

    protected function create_course_code ($title) {
        //Making sure that text of the short name does not go over the DB limit.
        //and leaving the space to add additional characters by the platform
        $code = substr(strtoupper(str_replace(' ', '', trim($title))),0,94);
        return $code;
    }
    
        public function convert_to_tool_type ($cc_type) {
        $type = TYPE_UNKNOWN;

        if ($cc_type == static::CC_TYPE_FORUM) {
            $type = TOOL_TYPE_FORUM;
        }

        if ($cc_type == static::CC_TYPE_QUIZ) {
            $type = TOOL_TYPE_QUIZ;
        }

        if ($cc_type == static::CC_TYPE_WEBLINK) {
            $type = TOOL_TYPE_WEBLINK;
        }

        if ($cc_type == static::CC_TYPE_WEBCONTENT) {
            $type = TOOL_TYPE_DOCUMENT;
        }

        return $type;
    }
    
}
