<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ActivityExport;
use Chamilo\CourseBundle\Entity\CDocument;
use DocumentManager;

use const PHP_EOL;
use const PATHINFO_EXTENSION;

/**
 * Handles the export of files and metadata for Moodle backup.
 *
 * Important:
 * - This class must preserve the original owner of each file entry.
 * - It must NOT normalize everything to mod_folder.
 * - Real ownership examples:
 *   - Documents tree          => mod_folder / content
 *   - Page embedded files     => mod_page / content
 *   - Resource main file      => mod_resource / content
 *   - Question embedded files => question / questiontext|answer
 *   - Assign attachments      => mod_assign / introattachment
 */
class FileExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * Constructor to initialize course data.
     *
     * @param object $course course object containing resources and path data
     */
    public function __construct(object $course)
    {
        $this->course = $course;
    }

    /**
     * Export files and metadata from files.xml to the specified directory.
     *
     * This method:
     * - keeps logical file entries separated by ownership
     * - deduplicates only exact duplicate logical rows
     * - copies physical payloads only once per contenthash
     */
    public function exportFiles(array $filesData, string $exportDir): void
    {
        @error_log('[FileExport::exportFiles] Start. exportDir='.$exportDir.' inputCount='.(int) count($filesData['files'] ?? []));

        $filesDir = $exportDir.'/files';
        if (!is_dir($filesDir)) {
            mkdir($filesDir, api_get_permissions_for_new_directories(), true);
            @error_log('[FileExport::exportFiles] Created dir '.$filesDir);
        }

        $this->createPlaceholderFile($filesDir);

        $uniqueRows = ['files' => []];
        $seenLogicalKeys = [];
        $logicalDuplicates = 0;

        foreach (($filesData['files'] ?? []) as $idx => $file) {
            $file = $this->normalizeFileEntry($file);

            $contenthash = (string) ($file['contenthash'] ?? '');
            $filename = (string) ($file['filename'] ?? '');

            if ('' === $contenthash || '' === $filename) {
                @error_log('[FileExport::exportFiles] Skip invalid row idx='.$idx.' (missing contenthash or filename)');
                continue;
            }

            // Exact logical dedupe only. Ownership must remain part of the key.
            $logicalKey = implode('|', [
                (string) ($file['contenthash'] ?? ''),
                (string) ($file['contextid'] ?? ''),
                (string) ($file['component'] ?? ''),
                (string) ($file['filearea'] ?? ''),
                (string) ($file['itemid'] ?? ''),
                (string) ($file['filepath'] ?? ''),
                (string) ($file['filename'] ?? ''),
            ]);

            if (isset($seenLogicalKeys[$logicalKey])) {
                $logicalDuplicates++;
                continue;
            }

            $seenLogicalKeys[$logicalKey] = true;
            $uniqueRows['files'][] = $file;

            // Register for inforef resolution by contenthash/file id when needed
            FileIndex::register($file);
        }

        // Copy physical payloads only once per contenthash
        $copiedHashes = [];
        $copied = 0;
        foreach ($uniqueRows['files'] as $file) {
            $contenthash = (string) ($file['contenthash'] ?? '');
            if (isset($copiedHashes[$contenthash])) {
                continue;
            }

            $subdir = FileIndex::resolveSubdirByContenthash($contenthash);
            $this->copyFileToExportDir($file, $filesDir, $subdir);
            $copiedHashes[$contenthash] = true;
            $copied++;
        }

        @error_log('[FileExport::exportFiles] Copied payloads='.$copied);

        $this->createFilesXml($uniqueRows, $exportDir);

        @error_log('[FileExport::exportFiles] Done.');
    }

    /**
     * Get file data from course resources.
     *
     * Returned rows must already contain the correct logical owner.
     *
     * @return array<string,mixed>
     */
    public function getFilesData(): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 0);

        $filesData = ['files' => []];

        $docResources =
            $this->course->resources[\defined('RESOURCE_DOCUMENT') ? RESOURCE_DOCUMENT : 'document']
            ?? $this->course->resources['document']
            ?? [];

        if (!\is_array($docResources)) {
            $docResources = [];
        }

        foreach ($docResources as $document) {
            if (!\is_object($document)) {
                continue;
            }
            $filesData = $this->processDocument($filesData, $document);
        }

        $workResources =
            $this->course->resources[\defined('RESOURCE_WORK') ? RESOURCE_WORK : 'work']
            ?? $this->course->resources['work']
            ?? [];

        if (!\is_array($workResources)) {
            $workResources = [];
        }

        foreach ($workResources as $work) {
            if (!\is_object($work)) {
                continue;
            }

            if (!\function_exists('getAllDocumentToWork')) {
                continue;
            }

            $workId = (int) ($work->params['id'] ?? 0);
            $courseRealId = (int) ($this->course->info['real_id'] ?? 0);

            if ($workId <= 0 || $courseRealId <= 0) {
                continue;
            }

            $workFiles = getAllDocumentToWork($workId, $courseRealId) ?: [];
            if (!\is_array($workFiles) || empty($workFiles)) {
                continue;
            }

            foreach ($workFiles as $file) {
                $docId = (int) ($file['document_id'] ?? 0);
                if ($docId <= 0) {
                    continue;
                }

                $docData = DocumentManager::get_document_data_by_id(
                    $docId,
                    (string) ($this->course->info['code'] ?? '')
                );

                if (!\is_array($docData) || empty($docData['path'])) {
                    continue;
                }

                $absPath = null;
                if (!empty($docData['id']) && class_exists(Container::class)) {
                    try {
                        $repo = Container::getDocumentRepository();
                        $doc = $repo->findOneBy(['iid' => (int) $docData['id']]);
                        if ($doc instanceof CDocument) {
                            $absPath = $repo->getAbsolutePathForDocument($doc);
                        }
                    } catch (\Throwable $e) {
                        @error_log('[FileExport::getFilesData] Assign attachment abs_path error: '.$e->getMessage());
                    }
                }

                $filesData['files'][] = $this->normalizeFileEntry([
                    'id'           => (int) $docId,
                    'contenthash'  => is_file((string) $absPath)
                        ? sha1_file((string) $absPath)
                        : hash('sha1', basename((string) $docData['path'])),
                    'contextid'    => (int) ($this->course->info['real_id'] ?? 0),
                    'component'    => 'mod_assign',
                    'filearea'     => 'introattachment',
                    'itemid'       => $workId,
                    'filepath'     => '/',
                    'documentpath' => 'document'.$docData['path'],
                    'filename'     => basename((string) $docData['path']),
                    'userid'       => $adminId,
                    'filesize'     => (int) ($docData['size'] ?? 0),
                    'mimetype'     => $this->getMimeType((string) $docData['path']),
                    'status'       => 0,
                    'timecreated'  => time() - 3600,
                    'timemodified' => time(),
                    'source'       => (string) ($docData['title'] ?? ''),
                    'author'       => 'Unknown',
                    'license'      => 'allrightsreserved',
                    'abs_path'     => $absPath,
                ]);
            }
        }

        return $filesData;
    }

    /**
     * Create a placeholder index.html file to prevent an empty directory.
     */
    private function createPlaceholderFile(string $filesDir): void
    {
        $placeholderFile = $filesDir.'/index.html';
        file_put_contents($placeholderFile, '<!-- Placeholder file to ensure the directory is not empty -->');
    }

    /**
     * Copy a file payload to the export directory using its contenthash.
     *
     * Payload copy is physical dedupe only; logical files.xml entries are preserved.
     */
    private function copyFileToExportDir(array $file, string $filesDir, ?string $precomputedSubdir = null): void
    {
        $contenthash = (string) ($file['contenthash'] ?? '');
        if ('' === $contenthash) {
            return;
        }

        $subDir = $precomputedSubdir ?: substr($contenthash, 0, 2);
        if ('' === $subDir) {
            return;
        }

        $exportSubDir = rtrim($filesDir, '/').'/'.$subDir;
        if (!is_dir($exportSubDir)) {
            mkdir($exportSubDir, api_get_permissions_for_new_directories(), true);
        }

        $destinationFile = $exportSubDir.'/'.$contenthash;
        if (is_file($destinationFile)) {
            return;
        }

        $filePath = $file['abs_path'] ?? null;
        if (empty($filePath)) {
            $documentPath = (string) ($file['documentpath'] ?? '');
            $filePath = rtrim((string) $this->course->path, '/').'/'.$documentPath;
        }

        if (!is_file((string) $filePath)) {
            @error_log('[FileExport::copyFileToExportDir] Missing source file: '.(string) $filePath);
            return;
        }

        if (@copy((string) $filePath, $destinationFile)) {
            @error_log('[FileExport::copyFileToExportDir] Copied '.$destinationFile);
        }
    }

    /**
     * Create the files.xml with the provided file data.
     *
     * @param array<string,mixed> $filesData
     */
    private function createFilesXml(array $filesData, string $destinationDir): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<files>'.PHP_EOL;

        foreach (($filesData['files'] ?? []) as $file) {
            $xmlContent .= $this->createFileXmlEntry($this->normalizeFileEntry($file));
        }

        $xmlContent .= '</files>'.PHP_EOL;
        file_put_contents($destinationDir.'/files.xml', $xmlContent);
    }

    /**
     * Create an XML entry for a file.
     */
    private function createFileXmlEntry(array $file): string
    {
        $file = $this->normalizeFileEntry($file);

        $itemId = (int) ($file['itemid'] ?? 0);

        if (
            'mod_folder' === (string) ($file['component'] ?? '')
            && 'content' === (string) ($file['filearea'] ?? '')
        ) {
            $itemId = 0;
        }

        return '  <file id="'.(int) $file['id'].'">'.PHP_EOL
            .'    <contenthash>'.htmlspecialchars((string) $file['contenthash']).'</contenthash>'.PHP_EOL
            .'    <contextid>'.(int) $file['contextid'].'</contextid>'.PHP_EOL
            .'    <component>'.htmlspecialchars((string) $file['component']).'</component>'.PHP_EOL
            .'    <filearea>'.htmlspecialchars((string) $file['filearea']).'</filearea>'.PHP_EOL
            .'    <itemid>'.$itemId.'</itemid>'.PHP_EOL
            .'    <filepath>'.htmlspecialchars((string) $file['filepath']).'</filepath>'.PHP_EOL
            .'    <filename>'.htmlspecialchars((string) $file['filename']).'</filename>'.PHP_EOL
            .'    <userid>'.(int) $file['userid'].'</userid>'.PHP_EOL
            .'    <filesize>'.(int) $file['filesize'].'</filesize>'.PHP_EOL
            .'    <mimetype>'.htmlspecialchars((string) $file['mimetype']).'</mimetype>'.PHP_EOL
            .'    <status>'.(int) $file['status'].'</status>'.PHP_EOL
            .'    <timecreated>'.(int) $file['timecreated'].'</timecreated>'.PHP_EOL
            .'    <timemodified>'.(int) $file['timemodified'].'</timemodified>'.PHP_EOL
            .'    <source>'.htmlspecialchars((string) $file['source']).'</source>'.PHP_EOL
            .'    <author>'.htmlspecialchars((string) $file['author']).'</author>'.PHP_EOL
            .'    <license>'.htmlspecialchars((string) $file['license']).'</license>'.PHP_EOL
            .'    <sortorder>0</sortorder>'.PHP_EOL
            .'    <repositorytype>$@NULL@$</repositorytype>'.PHP_EOL
            .'    <repositoryid>$@NULL@$</repositoryid>'.PHP_EOL
            .'    <reference>$@NULL@$</reference>'.PHP_EOL
            .'  </file>'.PHP_EOL;
    }

    /**
     * Process a document and add its data to the files array.
     *
     * Documents tree files belong to the synthetic root folder activity.
     * Top-level HTML files are skipped because they are exported as Moodle pages.
     *
     * @param array<string,mixed> $filesData
     */
    private function processDocument(array $filesData, object $document): array
    {
        if (($document->file_type ?? null) !== 'file') {
            return $filesData;
        }

        $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
        $sourceId = (int) ($document->source_id ?? 0);

        $logicalPath = (string) ($document->path ?? '');
        if ($sourceId > 0 && '' !== $courseCode) {
            $docData = DocumentManager::get_document_data_by_id($sourceId, $courseCode);
            if (\is_array($docData) && !empty($docData['path'])) {
                $logicalPath = (string) $docData['path'];
            }
        }

        $logicalPath = str_replace('\\', '/', trim($logicalPath));
        $logicalPath = ltrim($logicalPath, '/');

        if (!preg_match('#^(?:document/?)+#i', $logicalPath)) {
            $logicalPath = 'document/'.$logicalPath;
        }

        $extension = strtolower((string) pathinfo($logicalPath, PATHINFO_EXTENSION));

        // Root HTML documents are exported as page activities, not folder files.
        if (\in_array($extension, ['html', 'htm'], true) && 1 === substr_count((string) $logicalPath, '/')) {
            return $filesData;
        }

        $fileData = $this->getFileData($document);

        $fileData['documentpath'] = $logicalPath;
        $fileData['filename'] = basename($logicalPath);
        $fileData['contextid'] = ActivityExport::DOCS_MODULE_ID;
        $fileData['component'] = 'mod_folder';
        $fileData['filearea'] = 'content';
        $fileData['itemid'] = 0;
        $fileData['filepath'] = $this->buildMoodleFilepathFromChamiloPath($logicalPath);

        $filesData['files'][] = $this->normalizeFileEntry($fileData);

        return $filesData;
    }

    /**
     * Ensure mandatory file fields exist for Moodle restore.
     */
    private function normalizeFileEntry(array $file): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 1);

        $file['id'] = (int) ($file['id'] ?? 0);
        $file['contextid'] = (int) ($file['contextid'] ?? 0);
        $file['component'] = (string) ($file['component'] ?? '');
        $file['filearea'] = (string) ($file['filearea'] ?? '');
        $file['itemid'] = (int) ($file['itemid'] ?? 0);
        $file['filepath'] = $this->ensureTrailingSlash((string) ($file['filepath'] ?? '/'));
        $file['filename'] = (string) ($file['filename'] ?? '');
        $file['userid'] = (int) ($file['userid'] ?? $adminId);
        $file['filesize'] = (int) ($file['filesize'] ?? 0);
        $file['mimetype'] = (string) ($file['mimetype'] ?? 'application/octet-stream');
        $file['status'] = (int) ($file['status'] ?? 0);
        $file['timecreated'] = (int) ($file['timecreated'] ?? time());
        $file['timemodified'] = (int) ($file['timemodified'] ?? time());
        $file['source'] = (string) ($file['source'] ?? $file['filename']);
        $file['author'] = (string) ($file['author'] ?? 'Unknown');
        $file['license'] = (string) ($file['license'] ?? 'allrightsreserved');

        if ('' === $file['filename'] && !empty($file['documentpath'])) {
            $file['filename'] = basename((string) $file['documentpath']);
        }

        if (
            'mod_folder' === $file['component']
            && 'content' === $file['filearea']
        ) {
            $file['itemid'] = 0;

            if (!empty($file['documentpath'])) {
                $file['filepath'] = $this->buildMoodleFilepathFromChamiloPath((string) $file['documentpath']);
            }
        }

        if (
            \in_array($file['component'], ['mod_page', 'mod_resource'], true)
            && 'content' === $file['filearea']
        ) {
            $file['itemid'] = 0;
        }

        if ((int) $file['filesize'] <= 0 && !empty($file['abs_path']) && is_file((string) $file['abs_path'])) {
            $stat = @stat((string) $file['abs_path']);
            if ($stat) {
                $file['filesize'] = (int) ($stat['size'] ?? 0);
            }
        }

        return $file;
    }

    /**
     * Build a Moodle folder filepath from a Chamilo document path.
     *
     * Examples:
     * - /folder/file.pdf -> /folder/
     * - file.pdf         -> /
     */
    private function buildMoodleFilepathFromChamiloPath(string $documentPath): string
    {
        $normalizedPath = $this->normalizeCourseDocumentPath($documentPath);

        if ('' === $normalizedPath) {
            return '/';
        }

        $relDir = dirname($normalizedPath);

        if ('.' === $relDir || '/' === $relDir) {
            return '/';
        }

        return $this->ensureTrailingSlash('/'.trim($relDir, '/').'/');
    }

    private function normalizeCourseDocumentPath(string $documentPath): string
    {
        $path = str_replace('\\', '/', trim($documentPath));
        $path = ltrim($path, '/');
        $path = (string) preg_replace('#^document/#i', '', $path);

        if ('' === $path) {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), static fn ($part) => '' !== $part));
        if (empty($segments)) {
            return '';
        }

        $courseCode = (string) ($this->course->code ?? $this->course->info['code'] ?? '');
        $courseCodePattern = '' !== $courseCode
            ? '#^'.preg_quote($courseCode, '#').'(?:-\d+)?$#i'
            : null;

        // Typical polluted C2 paths currently seen:
        // - localhost/CURSOTESTBACKUP001/file.ext
        // - localhost/CURSOTESTBACKUP001/repertoire1/file.ext
        // - CURSOTESTBACKUP001/file.ext
        // - CURSOTESTBACKUP001-19078/file.ext
        if (
            null !== $courseCodePattern
            && count($segments) >= 2
            && preg_match($courseCodePattern, $segments[1])
        ) {
            array_shift($segments); // host/access-url slug
            array_shift($segments); // course code or course-code-id
        } elseif (
            null !== $courseCodePattern
            && preg_match($courseCodePattern, $segments[0])
        ) {
            array_shift($segments); // course code or course-code-id
        }

        return implode('/', $segments);
    }

    /**
     * Get file data for a single document.
     *
     * @return array<string,mixed>
     */
    private function getFileData(object $document): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = (int) ($adminData['id'] ?? 0);

        $contenthash = hash('sha1', basename((string) $document->path));
        $mimetype = $this->getMimeType((string) $document->path);

        $absPath = null;
        if (isset($document->source_id) && class_exists(Container::class)) {
            try {
                $repo = Container::getDocumentRepository();
                $doc = $repo->findOneBy(['iid' => (int) $document->source_id]);
                if ($doc instanceof CDocument) {
                    $absPath = $repo->getAbsolutePathForDocument($doc);
                    if (is_file((string) $absPath)) {
                        $contenthash = sha1_file((string) $absPath);
                    }
                }
            } catch (\Throwable $e) {
                @error_log('[FileExport::getFileData] abs_path resolution error: '.$e->getMessage());
            }
        }

        return [
            'id'           => (int) $document->source_id,
            'contenthash'  => $contenthash,
            'contextid'    => (int) $document->source_id,
            'component'    => 'mod_resource',
            'filearea'     => 'content',
            'itemid'       => (int) $document->source_id,
            'filepath'     => '/',
            'documentpath' => (string) $document->path,
            'filename'     => basename((string) $document->path),
            'userid'       => $adminId,
            'filesize'     => (int) ($document->size ?? 0),
            'mimetype'     => $mimetype,
            'status'       => 0,
            'timecreated'  => time() - 3600,
            'timemodified' => time(),
            'source'       => (string) ($document->title ?? basename((string) $document->path)),
            'author'       => 'Unknown',
            'license'      => 'allrightsreserved',
            'abs_path'     => $absPath,
        ];
    }

    /**
     * Ensure the directory path has a trailing slash.
     */
    private function ensureTrailingSlash(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = (string) preg_replace('#/\./#', '/', $path);
        $path = (string) preg_replace('#/+#', '/', $path);

        if ('' === $path || '.' === $path || '/' === $path) {
            return '/';
        }

        return rtrim($path, '/').'/';
    }

    /**
     * Get MIME type based on the file extension.
     */
    public function getMimeType(string $filePath): string
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = $this->getMimeTypes();

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Get an array of file extensions and their corresponding MIME types.
     *
     * @return array<string,string>
     */
    private function getMimeTypes(): array
    {
        return [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'svg' => 'image/svg+xml',
        ];
    }
}
