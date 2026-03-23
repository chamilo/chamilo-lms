<?php

/* For licensing terms, see /license.txt */

namespace moodleexport;

use Exception;

/**
 * Class ActivityExport.
 *
 * Base class for exporting common activities.
 */
abstract class ActivityExport
{
    public const DOCS_MODULE_ID = 0;
    protected $course;

    public function __construct($course)
    {
        $this->course = $course;
    }

    /**
     * Abstract method for exporting the activity.
     * Must be implemented by child classes.
     */
    abstract public function export($activityId, $exportDir, $moduleId, $sectionId);

    /**
     * Get the section ID (learnpath source_id) for a given activity.
     */
    public function getSectionIdForActivity(int $activityId, string $itemType): int
    {
        if (empty($this->course->resources[RESOURCE_LEARNPATH])) {
            return 0;
        }

        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $learnpath) {
            if (empty($learnpath->items)) {
                continue;
            }

            foreach ($learnpath->items as $item) {
                $normalizedType = $item['item_type'] === 'student_publication'
                    ? 'work'
                    : $item['item_type'];

                if ($normalizedType !== $itemType) {
                    continue;
                }

                // Classic case: LP stores the numeric id in "path"
                if (ctype_digit((string) $item['path']) && (int) $item['path'] === $activityId) {
                    return (int) $learnpath->source_id;
                }

                // Fallback for documents when LP stores the path instead of the id
                if ($itemType === RESOURCE_DOCUMENT) {
                    $doc = \DocumentManager::get_document_data_by_id($activityId, $this->course->code);
                    if (!empty($doc['path'])) {
                        $p = (string) $doc['path'];
                        foreach ([$p, 'document/'.$p, '/'.$p] as $candidate) {
                            if ((string) $item['path'] === $candidate) {
                                return (int) $learnpath->source_id;
                            }
                        }
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Prepares the directory for the activity.
     */
    protected function prepareActivityDirectory(string $exportDir, string $activityType, int $moduleId): string
    {
        $activityDir = "{$exportDir}/activities/{$activityType}_{$moduleId}";
        if (!is_dir($activityDir)) {
            mkdir($activityDir, 0777, true);
        }

        return $activityDir;
    }

    /**
     * Creates a generic XML file.
     */
    protected function createXmlFile(string $fileName, string $xmlContent, string $directory): void
    {
        $filePath = $directory.'/'.$fileName.'.xml';
        if (file_put_contents($filePath, $xmlContent) === false) {
            throw new Exception("Error creating {$fileName}.xml");
        }
    }

    /**
     * Creates the module.xml file.
     */
    protected function createModuleXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<module id="'.$data['moduleid'].'" version="2021051700">'.PHP_EOL;
        $xmlContent .= '  <modulename>'.$data['modulename'].'</modulename>'.PHP_EOL;
        $xmlContent .= '  <sectionid>'.$data['sectionid'].'</sectionid>'.PHP_EOL;
        $xmlContent .= '  <sectionnumber>'.$data['sectionnumber'].'</sectionnumber>'.PHP_EOL;
        $xmlContent .= '  <idnumber></idnumber>'.PHP_EOL;
        $xmlContent .= '  <added>'.time().'</added>'.PHP_EOL;
        $xmlContent .= '  <score>0</score>'.PHP_EOL;
        $xmlContent .= '  <indent>0</indent>'.PHP_EOL;
        $xmlContent .= '  <visible>1</visible>'.PHP_EOL;
        $xmlContent .= '  <visibleoncoursepage>1</visibleoncoursepage>'.PHP_EOL;
        $xmlContent .= '  <visibleold>1</visibleold>'.PHP_EOL;
        $xmlContent .= '  <groupmode>0</groupmode>'.PHP_EOL;
        $xmlContent .= '  <groupingid>0</groupingid>'.PHP_EOL;
        $xmlContent .= '  <completion>1</completion>'.PHP_EOL;
        $xmlContent .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>'.PHP_EOL;
        $xmlContent .= '  <completionview>0</completionview>'.PHP_EOL;
        $xmlContent .= '  <completionexpected>0</completionexpected>'.PHP_EOL;
        $xmlContent .= '  <availability>$@NULL@$</availability>'.PHP_EOL;
        $xmlContent .= '  <showdescription>0</showdescription>'.PHP_EOL;
        $xmlContent .= '  <tags></tags>'.PHP_EOL;
        $xmlContent .= '</module>'.PHP_EOL;

        $this->createXmlFile('module', $xmlContent, $directory);
    }

    /**
     * Creates the grades.xml file.
     */
    protected function createGradesXml(array $data, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<activity_gradebook>'.PHP_EOL;
        $xmlContent .= '  <grade_items></grade_items>'.PHP_EOL;
        $xmlContent .= '</activity_gradebook>'.PHP_EOL;

        $this->createXmlFile('grades', $xmlContent, $directory);
    }

    /**
     * Creates the inforef.xml file, referencing users and files associated with the activity.
     */
    protected function createInforefXml(array $references, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<inforef>'.PHP_EOL;

        if (isset($references['users']) && is_array($references['users'])) {
            $xmlContent .= '  <userref>'.PHP_EOL;
            foreach ($references['users'] as $userId) {
                $xmlContent .= '    <user>'.PHP_EOL;
                $xmlContent .= '      <id>'.htmlspecialchars((string) $userId).'</id>'.PHP_EOL;
                $xmlContent .= '    </user>'.PHP_EOL;
            }
            $xmlContent .= '  </userref>'.PHP_EOL;
        }

        if (isset($references['files']) && is_array($references['files'])) {
            $xmlContent .= '  <fileref>'.PHP_EOL;
            foreach ($references['files'] as $file) {
                $fileId = is_array($file) ? (int) ($file['id'] ?? 0) : (int) $file;
                if ($fileId <= 0) {
                    continue;
                }

                $xmlContent .= '    <file>'.PHP_EOL;
                $xmlContent .= '      <id>'.$fileId.'</id>'.PHP_EOL;
                $xmlContent .= '    </file>'.PHP_EOL;
            }
            $xmlContent .= '  </fileref>'.PHP_EOL;
        }

        $xmlContent .= '</inforef>'.PHP_EOL;

        $this->createXmlFile('inforef', $xmlContent, $directory);
    }

    /**
     * Creates the roles.xml file.
     */
    protected function createRolesXml(array $activityData, string $directory): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<roles></roles>'.PHP_EOL;

        $this->createXmlFile('roles', $xmlContent, $directory);
    }

    /**
     * Creates the filters.xml file for the activity.
     */
    protected function createFiltersXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<filters>'.PHP_EOL;
        $xmlContent .= '  <filter_actives>'.PHP_EOL;
        $xmlContent .= '  </filter_actives>'.PHP_EOL;
        $xmlContent .= '  <filter_configs>'.PHP_EOL;
        $xmlContent .= '  </filter_configs>'.PHP_EOL;
        $xmlContent .= '</filters>'.PHP_EOL;

        $this->createXmlFile('filters', $xmlContent, $destinationDir);
    }

    /**
     * Creates the grade_history.xml file for the activity.
     */
    protected function createGradeHistoryXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<grade_history>'.PHP_EOL;
        $xmlContent .= '  <grade_grades>'.PHP_EOL;
        $xmlContent .= '  </grade_grades>'.PHP_EOL;
        $xmlContent .= '</grade_history>'.PHP_EOL;

        $this->createXmlFile('grade_history', $xmlContent, $destinationDir);
    }

    /**
     * Creates the completion.xml file.
     */
    protected function createCompletionXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<completion>'.PHP_EOL;
        $xmlContent .= '  <completiondata>'.PHP_EOL;
        $xmlContent .= '    <completion>'.PHP_EOL;
        $xmlContent .= '      <timecompleted>0</timecompleted>'.PHP_EOL;
        $xmlContent .= '      <completionstate>1</completionstate>'.PHP_EOL;
        $xmlContent .= '    </completion>'.PHP_EOL;
        $xmlContent .= '  </completiondata>'.PHP_EOL;
        $xmlContent .= '</completion>'.PHP_EOL;

        $this->createXmlFile('completion', $xmlContent, $destinationDir);
    }

    /**
     * Creates the comments.xml file.
     */
    protected function createCommentsXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<comments>'.PHP_EOL;
        $xmlContent .= '  <comment>'.PHP_EOL;
        $xmlContent .= '    <content>This is a sample comment</content>'.PHP_EOL;
        $xmlContent .= '    <author>Professor</author>'.PHP_EOL;
        $xmlContent .= '  </comment>'.PHP_EOL;
        $xmlContent .= '</comments>'.PHP_EOL;

        $this->createXmlFile('comments', $xmlContent, $destinationDir);
    }

    /**
     * Creates the competencies.xml file.
     */
    protected function createCompetenciesXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<competencies>'.PHP_EOL;
        $xmlContent .= '  <competency>'.PHP_EOL;
        $xmlContent .= '    <name>Sample Competency</name>'.PHP_EOL;
        $xmlContent .= '  </competency>'.PHP_EOL;
        $xmlContent .= '</competencies>'.PHP_EOL;

        $this->createXmlFile('competencies', $xmlContent, $destinationDir);
    }

    /**
     * Creates the calendar.xml file.
     */
    protected function createCalendarXml(array $activityData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<calendar>'.PHP_EOL;
        $xmlContent .= '  <event>'.PHP_EOL;
        $xmlContent .= '    <name>Due Date</name>'.PHP_EOL;
        $xmlContent .= '    <timestart>'.time().'</timestart>'.PHP_EOL;
        $xmlContent .= '  </event>'.PHP_EOL;
        $xmlContent .= '</calendar>'.PHP_EOL;

        $this->createXmlFile('calendar', $xmlContent, $destinationDir);
    }

    /**
     * Creates a Moodle-safe activity name.
     * - Strip HTML
     * - Decode entities
     * - Normalize whitespace
     * - Truncate to a maximum length.
     */
    protected function sanitizeMoodleActivityName(string $raw, int $maxLen = 255): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '';
        }

        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = strip_tags($s);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);

        if ($s === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($s, 'UTF-8') > $maxLen) {
                $s = mb_substr($s, 0, $maxLen, 'UTF-8');
            }
        } else {
            if (strlen($s) > $maxLen) {
                $s = substr($s, 0, $maxLen);
            }
        }

        return $s;
    }

    /**
     * Returns the title of the item in the LP if it exists, otherwise the fallback.
     */
    protected function lpItemTitle(int $sectionId, string $itemType, int $resourceId, ?string $fallback): string
    {
        if (!isset($this->course->resources[RESOURCE_LEARNPATH])) {
            return $fallback ?? '';
        }
        foreach ($this->course->resources[RESOURCE_LEARNPATH] as $lp) {
            if ((int) $lp->source_id !== $sectionId || empty($lp->items)) {
                continue;
            }
            foreach ($lp->items as $it) {
                $type = $it['item_type'] === 'student_publication' ? 'work' : $it['item_type'];
                if ($type === $itemType && (int) $it['path'] === $resourceId) {
                    return $it['title'] ?? ($fallback ?? '');
                }
            }
        }

        return $fallback ?? '';
    }

    /**
     * Extract embedded files from HTML content and normalize URLs to @@PLUGINFILE@@.
     *
     * Supported sources:
     * - Course documents: /document/...
     * - Platform assets: /main/default_course_document/... and /main/img/...
     *
     * @param callable $fileIdBuilder Receives the 1-based sequence and must return a unique file id.
     *
     * @return array{content:string, files:array<int,array<string,mixed>>}
     */
    protected function extractEmbeddedFilesAndNormalizeContent(
        string $html,
        int $contextId,
        string $component,
        string $fileArea,
        int $itemId,
        callable $fileIdBuilder
    ): array {
        if ($html === '') {
            return [
                'content' => '',
                'files' => [],
            ];
        }

        $courseInfo = api_get_course_info($this->course->code);
        $adminId = (int) (MoodleExport::getAdminUserData()['id'] ?? 1);
        $fileExport = new FileExport($this->course);

        $files = [];
        $seenSources = [];
        $sequence = 0;

        $normalizedHtml = preg_replace_callback(
            '#<img[^>]+src=["\'](?<url>[^"\']+)["\']#i',
            function ($match) use (
                $courseInfo,
                $contextId,
                $component,
                $fileArea,
                $itemId,
                $fileIdBuilder,
                $adminId,
                $fileExport,
                &$files,
                &$seenSources,
                &$sequence
            ) {
                $src = (string) ($match['url'] ?? '');
                $resolved = $this->resolveEmbeddedFileSource($src, $courseInfo);

                if ($resolved === null) {
                    return $match[0];
                }

                $sourceKey = (string) $resolved['sourcekey'];

                if (!isset($seenSources[$sourceKey])) {
                    $sequence++;
                    $fileId = (int) call_user_func($fileIdBuilder, $sequence);

                    $absolutePath = (string) $resolved['absolutepath'];
                    $filename = (string) $resolved['filename'];

                    $files[] = [
                        'id' => $fileId,
                        'contenthash' => is_file($absolutePath)
                            ? sha1_file($absolutePath)
                            : hash('sha1', $filename),
                        'contextid' => $contextId,
                        'component' => $component,
                        'filearea' => $fileArea,
                        'itemid' => $itemId,
                        'filepath' => (string) $resolved['filepath'],
                        'documentpath' => (string) $resolved['documentpath'],
                        'absolutepath' => $absolutePath,
                        'filename' => $filename,
                        'userid' => $adminId,
                        'filesize' => is_file($absolutePath) ? (int) filesize($absolutePath) : 0,
                        'mimetype' => $fileExport->getMimeType($filename),
                        'status' => 0,
                        'timecreated' => time() - 3600,
                        'timemodified' => time(),
                        'source' => $filename,
                        'author' => 'Unknown',
                        'license' => 'allrightsreserved',
                    ];

                    $seenSources[$sourceKey] = true;
                }

                return str_replace($src, '@@PLUGINFILE@@'.(string) $resolved['pluginfilepath'], $match[0]);
            },
            $html
        );

        return [
            'content' => (string) $normalizedHtml,
            'files' => $files,
        ];
    }

    /**
     * Resolve an embeddable source URL into export metadata.
     *
     * @return array<string,string>|null
     */
    protected function resolveEmbeddedFileSource(string $src, array $courseInfo): ?array
    {
        $urlPath = $this->extractUrlPath($src);

        if (preg_match('#/document(?P<path>/[^"\']+)#', $urlPath, $m)) {
            $documentRelativePath = (string) $m['path'];
            $docId = \DocumentManager::get_document_id($courseInfo, $documentRelativePath);
            if (empty($docId)) {
                return null;
            }

            $document = \DocumentManager::get_document_data_by_id((int) $docId, $this->course->code);
            if (empty($document)) {
                return null;
            }

            $documentPath = (string) ($document['path'] ?? '');
            if ($documentPath === '') {
                return null;
            }

            return [
                'sourcekey' => 'document:'.$docId,
                'filepath' => $this->buildPluginFileDirectoryFromChamiloDocumentPath($documentPath),
                'pluginfilepath' => $this->buildPluginFilePathFromChamiloDocumentPath($documentRelativePath),
                'documentpath' => 'document'.$documentPath,
                'absolutepath' => $this->buildChamiloDocumentAbsolutePath($documentPath),
                'filename' => basename($documentPath),
            ];
        }

        if (preg_match('#(?P<path>/main/(?:default_course_document|img)/[^"\']+)#', $urlPath, $m)) {
            $assetPath = (string) $m['path'];

            return [
                'sourcekey' => 'static:'.$assetPath,
                'filepath' => $this->buildPluginFileDirectoryFromStaticAssetPath($assetPath),
                'pluginfilepath' => $this->buildPluginFilePathFromStaticAssetPath($assetPath),
                'documentpath' => '',
                'absolutepath' => $this->buildStaticAssetAbsolutePath($assetPath),
                'filename' => basename($assetPath),
            ];
        }

        return null;
    }

    /**
     * Extract the path part from a raw URL.
     */
    protected function extractUrlPath(string $src): string
    {
        $decoded = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $path = (string) parse_url($decoded, PHP_URL_PATH);

        return $path !== '' ? $path : $decoded;
    }

    /**
     * Build the absolute path for one Chamilo course document.
     */
    protected function buildChamiloDocumentAbsolutePath(string $documentPath): string
    {
        $normalizedPath = (string) preg_replace('#^/?document/#', '', str_replace('\\', '/', trim($documentPath)));
        $normalizedPath = '/'.ltrim($normalizedPath, '/');

        return rtrim((string) $this->course->path, '/').'/document'.$normalizedPath;
    }

    /**
     * Build the absolute path for one static platform asset.
     */
    protected function buildStaticAssetAbsolutePath(string $assetPath): string
    {
        return rtrim(api_get_path(SYS_PATH), '/').'/'.ltrim($assetPath, '/');
    }

    /**
     * Build the pluginfile directory path from a Chamilo document path.
     */
    protected function buildPluginFileDirectoryFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        $dir = dirname($relative);
        if ($dir === '.' || $dir === '/') {
            return '/';
        }

        return '/'.trim($dir, '/').'/';
    }

    /**
     * Build the pluginfile full path used in HTML content.
     */
    protected function buildPluginFilePathFromChamiloDocumentPath(string $documentPath): string
    {
        $relative = $this->stripChamiloDocumentPrefix($documentPath);
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        return '/'.$relative;
    }

    /**
     * Build the pluginfile directory path for one static platform asset.
     */
    protected function buildPluginFileDirectoryFromStaticAssetPath(string $assetPath): string
    {
        $relative = ltrim(str_replace('\\', '/', trim($assetPath)), '/');
        $dir = dirname($relative);

        if ($dir === '.' || $dir === '/') {
            return '/Static/';
        }

        return '/Static/'.trim($dir, '/').'/';
    }

    /**
     * Build the pluginfile full path used in HTML content for one static platform asset.
     */
    protected function buildPluginFilePathFromStaticAssetPath(string $assetPath): string
    {
        $relative = ltrim(str_replace('\\', '/', trim($assetPath)), '/');

        return '/Static/'.$relative;
    }

    /**
     * Remove the internal Chamilo "document/" prefix if present.
     */
    protected function stripChamiloDocumentPrefix(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = preg_replace('#^/?document/#', '', $path);

        return (string) $path;
    }
}
