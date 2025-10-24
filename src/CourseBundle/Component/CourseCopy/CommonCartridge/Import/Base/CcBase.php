<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Import\Base;

use DOMDocument;
use DOMXPath;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use const DIRECTORY_SEPARATOR;
use const LIBXML_NONET;

class CcBase
{
    /**
     * Common Cartridge v1.3 resource types (strings as they appear in manifest).
     */
    public const CC_TYPE_FORUM = 'imsdt_xmlv1p3';
    public const CC_TYPE_QUIZ = 'imsqti_xmlv1p3/imscc_xmlv1p3/assessment';
    public const CC_TYPE_QUESTION_BANK = 'imsqti_xmlv1p3/imscc_xmlv1p3/question-bank';
    public const CC_TYPE_WEBLINK = 'imswl_xmlv1p3';
    public const CC_TYPE_WEBCONTENT = 'webcontent';
    public const CC_TYPE_ASSOCIATED_CONTENT = 'associatedcontent/imscc_xmlv1p3/learning-application-resource';
    public const CC_TYPE_EMPTY = '';

    /**
     * Internal tool types (used as keys within $instances). Keep them stable.
     */
    public const TOOL_TYPE_FORUM = 'forum';
    public const TOOL_TYPE_QUIZ = 'quiz';
    public const TOOL_TYPE_WEBLINK = 'weblink';
    public const TOOL_TYPE_DOCUMENT = 'document';
    public const TOOL_TYPE_UNKNOWN = 'unknown';

    /**
     * Depth constant for top-level items inside <organization>.
     */
    public const ROOT_DEEP = 1;

    /**
     * Legacy/rest helpers (kept for compatibility).
     */
    public static $restypes = ['associatedcontent/imscc_xmlv1p0/learning-application-resource', 'webcontent'];
    public static $forumns = ['dt' => 'http://www.imsglobal.org/xsd/imsdt_v1p0'];
    public static $quizns = ['xmlns' => 'http://www.imsglobal.org/xsd/ims_qtiasiv1p2'];
    public static $resourcens = ['wl' => 'http://www.imsglobal.org/xsd/imswl_v1p0'];

    /**
     * Instances index and manifest handling.
     */
    public static $instances = [];
    public static $manifest;
    public static $pathToManifestFolder;

    /**
     * Default namespaces (older baseline). NOTE: Cc1p3Convert overrides these with v1p3 values.
     * Late static binding ensures subclass mappings are used when called from the subclass.
     */
    public static $namespaces = [
        'imscc' => 'http://www.imsglobal.org/xsd/imscc/imscp_v1p1',
        'lomimscc' => 'http://ltsc.ieee.org/xsd/imscc/LOM',
        'lom' => 'http://ltsc.ieee.org/xsd/LOM',
        'voc' => 'http://ltsc.ieee.org/xsd/LOM/vocab',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
        'cc' => 'http://www.imsglobal.org/xsd/imsccauth_v1p0',
    ];

    public function __construct($path_to_manifest)
    {
        static::$manifest = new DOMDocument();
        static::$manifest->validateOnParse = false;

        static::$pathToManifestFolder = \dirname($path_to_manifest);

        static::logAction('Process start');
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

    /**
     * Find the imsmanifest.xml file inside the given folder and return its path.
     *
     * @param string $folder Full path name of the folder in which we expect to find imsmanifest.xml
     *
     * @return false|string
     */
    public static function getManifest(string $folder)
    {
        if (!is_dir($folder)) {
            return false;
        }

        // Quick top-level check
        if (file_exists($folder.'/imsmanifest.xml')) {
            return $folder.'/imsmanifest.xml';
        }

        $result = false;

        try {
            $dirIter = new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::KEY_AS_PATHNAME);
            $recIter = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($recIter as $info) {
                if ($info->isFile() && ('imsmanifest.xml' === $info->getFilename())) {
                    $result = $info->getPathname();

                    break;
                }
            }
        } catch (Exception $e) {
            // Non-fatal: just skip and return false
            static::logAction('Warning: Exception while scanning for imsmanifest.xml: '.$e->getMessage());
        }

        return $result;
    }

    public function isAuth()
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);
        $count_auth = $xpath->evaluate('count(/imscc:manifest/cc:authorizations)');

        return $count_auth > 0;
    }

    public function getNodesByCriteria($key, $value)
    {
        $response = [];

        if (\array_key_exists('index', static::$instances)) {
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

        if (\array_key_exists('index', static::$instances)) {
            if (!empty(static::$instances['index']) && $type) {
                $types = []; // Initialize accumulator to avoid notices
                foreach (static::$instances['index'] as $instance) {
                    if (!empty($instance['tool_type'])) {
                        $types[] = $instance['tool_type'];
                    }
                }

                $quantityInstances = array_count_values($types);
                $quantity = \array_key_exists($type, $quantityInstances) ? $quantityInstances[$type] : 0;
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
        }

        return '';
    }

    public function getItemHref($identifier)
    {
        $xpath = static::newxPath(static::$manifest, static::$namespaces);
        $nodes = $xpath->query('/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]/imscc:file/@href');

        if ($nodes && !empty($nodes->item(0)->nodeValue)) {
            return $nodes->item(0)->nodeValue;
        }

        return '';
    }

    public static function newxPath(DOMDocument $manifest, $namespaces = '')
    {
        $xpath = new DOMXPath($manifest);

        if (!empty($namespaces)) {
            foreach ($namespaces as $prefix => $ns) {
                if (!$xpath->registerNamespace($prefix, $ns)) {
                    // Critical because namespace mismatches will break all queries
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

    /**
     * Simple, file-based logging. Critical errors abort the process.
     *
     * @param null|mixed $context
     */
    public static function logAction(string $message, $context = null, bool $ok = true): void
    {
        // Minimal, centralized logger for CC 1.3 importer steps
        error_log('(imscc13) '.$message.' , level: '.($ok ? 'info' : 'warn').' , extra: '.json_encode($context));
    }

    /**
     * Map CC resource type (as read from manifest) to an internal tool type string.
     * Always returns a defined TOOL_TYPE_* constant.
     *
     * @param mixed $ccType
     */
    public function convertToToolType($ccType)
    {
        $type = self::TOOL_TYPE_UNKNOWN;

        if ($ccType === static::CC_TYPE_FORUM) {
            $type = self::TOOL_TYPE_FORUM;
        } elseif ($ccType === static::CC_TYPE_QUIZ) {
            $type = self::TOOL_TYPE_QUIZ;
        } elseif ($ccType === static::CC_TYPE_WEBLINK) {
            $type = self::TOOL_TYPE_WEBLINK;
        } elseif ($ccType === static::CC_TYPE_WEBCONTENT) {
            $type = self::TOOL_TYPE_DOCUMENT;
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
     * Is activity visible or not (based on LOM roles metadata).
     *
     * @param string $identifier
     *
     * @return int 1 visible, 0 hidden
     */
    protected function getModuleVisible($identifier)
    {
        $mod_visible = 1;
        if (!empty($identifier)) {
            $xpath = static::newxPath(static::$manifest, static::$namespaces);
            $query = '/imscc:manifest/imscc:resources/imscc:resource[@identifier="'.$identifier.'"]';
            $query .= '//lom:intendedEndUserRole/voc:vocabulary/lom:value';
            $intendeduserrole = $xpath->query($query);
            if (!empty($intendeduserrole) && ($intendeduserrole->length > 0)) {
                $role = trim($intendeduserrole->item(0)->nodeValue);
                if (0 === strcasecmp('Instructor', $role)) {
                    $mod_visible = 0;
                }
            }
        }

        return $mod_visible;
    }

    /**
     * Build the internal flat index of resources/items with hierarchy metadata.
     * Adds robust logging to help troubleshooting unknown/missing types/titles.
     *
     * @param mixed $items
     * @param mixed $level
     * @param mixed $array_index
     * @param mixed $index_root
     */
    protected function createInstances($items, $level = 0, &$array_index = 0, $index_root = 0): void
    {
        $level++;
        $i = 1;

        if ($items) {
            $xpath = self::newxPath(static::$manifest, static::$namespaces);

            foreach ($items as $item) {
                $array_index++;
                $title = $path = $tool_type = $identifierref = '';
                $ccType = '';

                if ('item' === $item->nodeName) {
                    if ($item->hasAttribute('identifierref')) {
                        $identifierref = $item->getAttribute('identifierref');
                    }

                    $titles = $xpath->query('imscc:title', $item);
                    if ($titles->length > 0) {
                        $title = $titles->item(0)->nodeValue ?? '';
                    }

                    $ccType = $this->getItemCcType($identifierref);
                    $tool_type = $this->convertToToolType($ccType);

                    // If completely empty (label-only folder), keep as unknown
                    if (empty($identifierref) && empty($title)) {
                        $tool_type = self::TOOL_TYPE_UNKNOWN;
                    }
                } elseif ('resource' === $item->nodeName) {
                    $identifierref = $xpath->query('@identifier', $item);
                    $identifierref = !empty($identifierref->item(0)->nodeValue) ? $identifierref->item(0)->nodeValue : '';

                    $ccType = $this->getItemCcType($identifierref);
                    $tool_type = $this->convertToToolType($ccType);

                    if (self::CC_TYPE_WEBCONTENT === $ccType) {
                        $path = $this->getItemHref($identifierref);
                        $title = basename((string) $path);
                    } else {
                        // For non-file resources (e.g., question bank), give a stable label
                        $title = 'Quiz Bank '.($this->countInstances($tool_type) + 1);
                    }
                }

                if (self::ROOT_DEEP === $level) {
                    $index_root = $array_index;
                }

                // Log each discovered entry
                static::logAction(\sprintf(
                    'Indexing node: nodeName=%s, identifier=%s, ccType=%s, toolType=%s, title=%s, level=%d',
                    $item->nodeName,
                    $identifierref ?: '(none)',
                    $ccType ?: '(none)',
                    $tool_type ?: '(none)',
                    $title ?: '(empty)',
                    $level
                ));

                static::$instances['index'][$array_index] = [
                    'common_cartridge_type' => $ccType,
                    'tool_type' => $tool_type,
                    'title' => $title ?: '',
                    'root_parent' => $index_root,
                    'index' => $array_index,
                    'deep' => $level,
                    'instance' => $this->countInstances($tool_type),
                    'resource_identifier' => $identifierref,
                ];

                static::$instances['instances'][$tool_type][] = [
                    'title' => $title,
                    'instance' => static::$instances['index'][$array_index]['instance'],
                    'common_cartridge_type' => $ccType,
                    'resource_identifier' => $identifierref,
                    'deep' => $level,
                    'src' => $path,
                ];

                $more_items = $xpath->query('imscc:item', $item);
                if ($more_items->length > 0) {
                    $this->createInstances($more_items, $level, $array_index, $index_root);
                }

                $i++;
            }
        }
    }

    protected static function criticalError($text): void
    {
        $path_to_log = static::logFile();

        echo '
        <p>
        <hr />A critical error has been found!
        <p>'.htmlentities($text).'</p>
        <p>
        The process has been stopped. Please see the <a href="'.htmlentities($path_to_log).'">log file</a> for more information.</p>
        <p>Log: '.htmlentities($path_to_log).'</p>
        <hr />
        </p>
        ';

        exit;
    }

    protected function createCourseCode($title)
    {
        // Ensure shortname does not exceed DB limit and leave room for platform suffixes
        return substr(strtoupper(str_replace(' ', '', trim((string) $title))), 0, 94);
    }
}
