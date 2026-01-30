<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Activities\ActivityExport;
use Chamilo\CourseBundle\Entity\CDocument;
use DocumentManager;
use Exception;

use const PATHINFO_EXTENSION;
use const PHP_EOL;

/**
 * Class FileExport.
 * Handles the export of files and metadata from Moodle courses.
 */
class FileExport
{
    /**
     * @var object
     */
    private $course;

    /**
     * Module context id for the mod_folder activity inside the backup.
     * Default kept for safety; MoodleExport must override via setModuleContextId().
     */
    private int $moduleContextId = 1000000;

    /**
     * Keep legacy folder-children traversal but disabled by default to avoid duplicates.
     * You can re-enable via setUseFolderTraversal(true) without losing this code path.
     */
    private bool $useFolderTraversal = false;

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
     * Allow caller (MoodleExport) to set the real context id of the created mod_folder activity.
     */
    public function setModuleContextId(int $ctx): void
    {
        // INFO: caller should pass the module's context placeholder id from activities/folder_* (e.g. 1000000)
        $this->moduleContextId = $ctx;
        @error_log('[FileExport] Module context id set to '.$this->moduleContextId);
    }

    /**
     * Keep legacy folder-children traversal available (OFF by default).
     * Turning it on may create duplicates, but dedupe will catch them.
     */
    public function setUseFolderTraversal(bool $on): void
    {
        $this->useFolderTraversal = $on;
        @error_log('[FileExport] Use folder traversal = '.($on ? 'true' : 'false'));
    }

    /**
     * Export files and metadata from files.xml to the specified directory.
     */
    public function exportFiles(array $filesData, string $exportDir): void
    {
        @error_log('[FileExport::exportFiles] Start. exportDir='.$exportDir.' inputCount='.(int)count($filesData['files'] ?? []));

        $filesDir = $exportDir.'/files';
        if (!is_dir($filesDir)) {
            mkdir($filesDir, api_get_permissions_for_new_directories(), true);
            @error_log('[FileExport::exportFiles] Created dir '.$filesDir);
        }
        $this->createPlaceholderFile($filesDir);

        $unique = ['files' => []];
        $seenKeys = [];

        $dedupSkipped = 0;
        $badPaths = 0;

        foreach (($filesData['files'] ?? []) as $idx => $file) {
            // Normalize every row to what Moodle restore expects for mod_folder
            $file = $this->normalizeRow($file);

            $ch   = (string) ($file['contenthash'] ?? '');
            $path = $this->ensureTrailingSlash((string) ($file['filepath'] ?? '/'));
            $name = (string) ($file['filename'] ?? '');

            if ('' === $ch || '' === $name) {
                @error_log('[FileExport::exportFiles] WARNING: Skipping entry idx='.$idx.' (missing contenthash or filename).');
                continue;
            }

            // Dedupe across sources (do NOT include component/filearea/contextid to actually remove duplicates)
            $dedupeKey = implode('|', [$ch, $path, $name]);
            if (isset($seenKeys[$dedupeKey])) {
                $dedupSkipped++;
                continue;
            }
            $seenKeys[$dedupeKey] = true;

            // register for inforef resolution
            FileIndex::register($file);

            if (strpos($path, '/Documents/') === 0) {
                $badPaths++;
                @error_log('[FileExport::exportFiles] WARNING: filepath starts with /Documents/ (Moodle folder expects /). filepath='.$path.' filename='.$name);
            }

            $file['filepath'] = $path;
            $unique['files'][] = $file;
        }

        @error_log('[FileExport::exportFiles] After dedupe: '.count($unique['files']).' file(s). dedupSkipped='.$dedupSkipped.' badPathsDetected='.$badPaths);

        $copied = 0;
        foreach ($unique['files'] as $f) {
            $ch = (string) $f['contenthash'];
            $subdir = FileIndex::resolveSubdirByContenthash($ch);
            $this->copyFileToExportDir($f, $filesDir, $subdir);
            $copied++;
        }
        @error_log('[FileExport::exportFiles] Copied payloads: '.$copied);

        $this->createFilesXml($unique, $exportDir);
        @error_log('[FileExport::exportFiles] Done.');
    }

    /**
     * Get file data from course resources. This is for testing purposes.
     *
     * @return array<string,mixed>
     */
    public function getFilesData(): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $filesData = ['files' => []];

        // Defensive read: documents may be missing
        $docResources = $this->course->resources[RESOURCE_DOCUMENT] ?? [];
        if (!\is_array($docResources)) {
            $docResources = [];
        }

        foreach ($docResources as $document) {
            $filesData = $this->processDocument($filesData, $document);
        }

        // Defensive read: works may be missing (avoids "Undefined array key 'work'")
        $workResources = $this->course->resources[RESOURCE_WORK] ?? [];
        if (!\is_array($workResources)) {
            $workResources = [];
        }

        foreach ($workResources as $work) {
            // getAllDocumentToWork might not exist in some installs; guard it
            $workFiles = \function_exists('getAllDocumentToWork')
                ? (getAllDocumentToWork($work->params['id'] ?? 0, $this->course->info['real_id'] ?? 0) ?: [])
                : [];

            if (!\is_array($workFiles) || empty($workFiles)) {
                continue;
            }

            foreach ($workFiles as $file) {
                // Safely fetch doc data
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

                $row = [
                    'id'           => $docId,
                    'contenthash'  => hash('sha1', basename($docData['path'])),
                    'contextid'    => (int) ($this->course->info['real_id'] ?? 0), // will be normalized to moduleContextId
                    'component'    => 'mod_assign', // will be normalized to mod_folder
                    'filearea'     => 'introattachment', // will be normalized to content
                    'itemid'       => (int) ($work->params['id'] ?? 0), // will be normalized to 0
                    'filepath'     => '/Documents/', // will be normalized
                    'documentpath' => 'document/'.$docData['path'],
                    'filename'     => basename($docData['path']),
                    'userid'       => $adminId,
                    'filesize'     => (int) ($docData['size'] ?? 0),
                    'mimetype'     => $this->getMimeType($docData['path']),
                    'status'       => 0,
                    'timecreated'  => time() - 3600,
                    'timemodified' => time(),
                    'source'       => (string) ($docData['title'] ?? ''),
                    'author'       => 'Unknown',
                    'license'      => 'allrightsreserved',
                ];

                // Normalize to folder activity before pushing
                $filesData['files'][] = $this->normalizeRow($row);
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
     * Copy a file to the export directory using its contenthash.
     *
     * @param array<string,mixed> $file
     */
    private function copyFileToExportDir(array $file, string $filesDir, ?string $precomputedSubdir = null): void
    {
        $id = (int)($file['id'] ?? 0);
        $fp = (string)($file['filepath'] ?? '.');
        if ($fp === '.') {
            @error_log('[FileExport::copyFileToExportDir] Skipping entry with filepath dot. id='.$id);
            return;
        }

        $contenthash = (string)($file['contenthash'] ?? '');
        if ($contenthash === '') {
            @error_log('[FileExport::copyFileToExportDir] WARN missing contenthash, skipping. id='.$id);
            return;
        }

        // Moodle-style files storage: first two chars as bucket dir.
        $subDir = $precomputedSubdir ?: substr($contenthash, 0, 2);
        if ($subDir === '' || $subDir === false) {
            @error_log('[FileExport::copyFileToExportDir] WARN invalid subdir derived from contenthash, skipping. id='.$id.' contenthash='.$contenthash);
            return;
        }

        $exportSubDir = rtrim($filesDir, '/').'/'.$subDir;

        if (!is_dir($exportSubDir)) {
            mkdir($exportSubDir, api_get_permissions_for_new_directories(), true);
            @error_log('[FileExport::copyFileToExportDir] Created subdir '.$exportSubDir);
        }

        $destinationFile = $exportSubDir.'/'.$contenthash;

        // Resolve source path (prefer precomputed absolute path).
        $filePath = $file['abs_path'] ?? null;
        if (empty($filePath)) {
            $documentPath = (string)($file['documentpath'] ?? '');
            $filePath = rtrim((string)$this->course->path, '/').$documentPath;
        }

        // do not abort the entire export if source is missing or is a directory.
        // This prevents full exports from failing due to stale/bad entries.
        if (!is_file($filePath)) {
            return;
        }

        if (!is_file($destinationFile)) {
            if (@copy($filePath, $destinationFile)) {
                @error_log('[FileExport::copyFileToExportDir] OK copy id='.$id.' -> '.$destinationFile);
            }
        }
    }

    /**
     * Create the files.xml with the provided file data.
     *
     * @param array<string,mixed> $filesData
     */
    private function createFilesXml(array $filesData, string $destinationDir): void
    {
        @error_log('[FileExport::createFilesXml] Start. destinationDir='.$destinationDir);

        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xmlContent .= '<files>'.PHP_EOL;

        $total = 0;
        $modFolder = 0;
        $badFilepath = 0;
        $wrongItemId = 0;

        foreach (($filesData['files'] ?? []) as $file) {
            // Safety: ensure already-normalized rows
            $file = $this->normalizeRow($file);

            $xmlContent .= $this->createFileXmlEntry($file);
            $total++;

            $comp = (string)($file['component'] ?? '');
            $area = (string)($file['filearea'] ?? '');
            $fp   = (string)($file['filepath'] ?? '');
            $it   = (int)($file['itemid'] ?? 0);

            if ($comp === 'mod_folder' && $area === 'content') {
                $modFolder++;
                if ($fp === '' || $fp[0] !== '/' || substr($fp, -1) !== '/') {
                    $badFilepath++;
                    @error_log('[FileExport::createFilesXml] WARNING bad filepath (must start/end with /): id='.(int)($file['id'] ?? 0).' filepath='.$fp);
                }
                if ($it !== 0) {
                    $wrongItemId++;
                    @error_log('[FileExport::createFilesXml] WARNING itemid must be 0 for mod_folder/content id='.(int)($file['id'] ?? 0).' itemid='.$it);
                }
            }
        }

        $xmlContent .= '</files>'.PHP_EOL;
        file_put_contents($destinationDir.'/files.xml', $xmlContent);

        @error_log('[FileExport::createFilesXml] Done. total='.$total.' mod_folder='.$modFolder.' badFilepath='.$badFilepath.' wrongItemId='.$wrongItemId);
    }

    /**
     * Create an XML entry for a file.
     *
     * @param array<string,mixed> $file
     */
    private function createFileXmlEntry(array $file): string
    {
        // itemid is forced to 0 in V1 files.xml for consistency with restore
        return '  <file id="'.(int) $file['id'].'">'.PHP_EOL.
            '    <contenthash>'.htmlspecialchars((string) $file['contenthash']).'</contenthash>'.PHP_EOL.
            '    <contextid>'.(int) $file['contextid'].'</contextid>'.PHP_EOL.
            '    <component>'.htmlspecialchars((string) $file['component']).'</component>'.PHP_EOL.
            '    <filearea>'.htmlspecialchars((string) $file['filearea']).'</filearea>'.PHP_EOL.
            '    <itemid>0</itemid>'.PHP_EOL.
            '    <filepath>'.htmlspecialchars((string) $file['filepath']).'</filepath>'.PHP_EOL.
            '    <filename>'.htmlspecialchars((string) $file['filename']).'</filename>'.PHP_EOL.
            '    <userid>'.(int) $file['userid'].'</userid>'.PHP_EOL.
            '    <filesize>'.(int) $file['filesize'].'</filesize>'.PHP_EOL.
            '    <mimetype>'.htmlspecialchars((string) $file['mimetype']).'</mimetype>'.PHP_EOL.
            '    <status>'.(int) $file['status'].'</status>'.PHP_EOL.
            '    <timecreated>'.(int) $file['timecreated'].'</timecreated>'.PHP_EOL.
            '    <timemodified>'.(int) $file['timemodified'].'</timemodified>'.PHP_EOL.
            '    <source>'.htmlspecialchars((string) $file['source']).'</source>'.PHP_EOL.
            '    <author>'.htmlspecialchars((string) $file['author']).'</author>'.PHP_EOL.
            '    <license>'.htmlspecialchars((string) $file['license']).'</license>'.PHP_EOL.
            '    <sortorder>0</sortorder>'.PHP_EOL.
            '    <repositorytype>$@NULL@$</repositorytype>'.PHP_EOL.
            '    <repositoryid>$@NULL@$</repositoryid>'.PHP_EOL.
            '    <reference>$@NULL@$</reference>'.PHP_EOL.
            '  </file>'.PHP_EOL;
    }

    /**
     * Process a document or folder and add its data to the files array.
     *
     * @param array<string,mixed> $filesData
     */
    private function processDocument(array $filesData, object $document): array
    {
        // Skip files already embedded/handled by PageExport
        if (
            ($document->file_type ?? null) === 'file'
            && isset($this->course->used_page_doc_ids)
            && \in_array($document->source_id, (array) $this->course->used_page_doc_ids, true)
        ) {
            @error_log('[FileExport::processDocument] Skipping file id='.$document->source_id.' (used by PageExport)');
            return $filesData;
        }

        // Skip top-level HTML documents that are exported as Page
        if (
            ($document->file_type ?? null) === 'file'
            && 'html' === strtolower((string)pathinfo($document->path, PATHINFO_EXTENSION))
            && 1 === substr_count($document->path, '/')
        ) {
            @error_log('[FileExport::processDocument] Skipping top-level HTML (will be Page) id='.$document->source_id);
            return $filesData;
        }

        // Simple FILE -> becomes part of Folder activity
        if (($document->file_type ?? null) === 'file') {
            $extension = strtolower((string) pathinfo($document->path, PATHINFO_EXTENSION));
            if (!\in_array($extension, ['html', 'htm'], true)) {
                $fileData = $this->getFileData($document);

                // Derive hierarchical folder path inside moodle folder content
                [$filepath, /*$fn*/, $rest] = $this->deriveRelativeDirAndName((string)$document->path);
                $fileData['filepath']  = $filepath;          // "/folder001/" or "/folder001/subfolder 001/" or "/"

                // Normalize to mod_folder + moduleContextId
                $fileData = $this->normalizeRow($fileData);

                @error_log('[FileExport::processDocument] FILE id='.$fileData['id'].' rest='.$rest.' -> fp='.$fileData['filepath'].' name='.$fileData['filename']);

                $filesData['files'][] = $fileData;
            }
        } elseif (($document->file_type ?? null) === 'folder') {
            if (!$this->useFolderTraversal) {
                @error_log('[FileExport::processDocument] INFO: folder traversal disabled, skipping folder iid='.(int)$document->source_id.' (kept code for compatibility)');
                return $filesData;
            }

            $docRepo = Container::getDocumentRepository();
            $folderFiles = $docRepo->listFilesByParentIid((int) $document->source_id);

            @error_log('[FileExport::processDocument] FOLDER iid='.(int)$document->source_id.' contains '.count($folderFiles).' file(s)');

            foreach ($folderFiles as $file) {
                [$filepath, $fn, $rest] = $this->deriveRelativeDirAndName((string)($file['path'] ?? ''));

                $row = $this->getFolderFileData(
                    $file,
                    (int)$document->source_id,
                    $filepath
                );

                // Normalize to mod_folder + moduleContextId
                $row = $this->normalizeRow($row);

                @error_log('[FileExport::processDocument] CHILD id='.$row['id'].' rest='.$rest.' -> fp='.$row['filepath'].' name='.$row['filename']);

                $filesData['files'][] = $row;
            }
        }

        return $filesData;
    }

    /**
     * Normalize one row to Moodle mod_folder/content requirements.
     * - component=mod_folder
     * - filearea=content
     * - itemid=0
     * - contextid=$this->moduleContextId
     * - filepath normalized, never "/Documents/"
     */
    private function normalizeRow(array $row): array
    {
        $row['component'] = 'mod_folder';
        $row['filearea']  = 'content';
        $row['itemid']    = 0;
        $row['contextid'] = $this->moduleContextId;

        $fp = (string)($row['filepath'] ?? '/');
        if ($fp === '' || $fp === '.' || $fp === '/') {
            $fp = '/';
        } else {
            // convert legacy /Documents/... to /
            if (strpos($fp, '/Documents/') === 0) {
                $fp = '/';
            }
        }
        $row['filepath'] = $this->ensureTrailingSlash($fp);

        // Safety: filename must never be empty
        $row['filename'] = (string)($row['filename'] ?? '');
        if ($row['filename'] === '') {
            $row['filename'] = 'unnamed';
        }

        return $row;
    }

    private function deriveRelativeDirAndName(string $absolutePath): array
    {
        $code = (string)($this->course->code ?? '');
        $raw  = str_replace('\\', '/', $absolutePath);
        $raw  = ltrim($raw, '/');

        // Look for the course code segment and take the remainder
        if (preg_match('#(?:^|/)'.preg_quote($code, '#').'/+(.*)$#', $raw, $m)) {
            $rest = $m[1]; // e.g. "folder001/subfolder 001/settings-changed.odt"
        } else {
            // Fallback: trim common prefixes document/document/localhost/...
            $rest = preg_replace('#^document/(?:document/)?(?:[^/]+/)?#', '', $raw);
        }

        $rest     = trim((string)$rest, '/');
        $filename = basename($rest);
        $dir      = trim((string)dirname($rest), '.');

        $filepath = ($dir === '' || $dir === '/') ? '/' : '/'.$dir.'/';

        @error_log('[FileExport::deriveRelativeDirAndName] code='.$code.' raw='.$absolutePath.' rest='.$rest.' dir='.$dir.' -> filepath='.$filepath.' filename='.$filename);

        return [$filepath, $filename, $rest];
    }

    private function normalizeMoodleFilepath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '' || $path === '.' || $path === '/') {
            return '/';
        }
        $path = ltrim($path, '/');
        $path = (string)preg_replace('#/+#', '/', $path);
        $path = trim($path, '/');
        return '/'.$path.'/';
    }

    /**
     * Get file data for a single document.
     *
     * @return array<string,mixed>
     */
    private function getFileData(object $document): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $contenthash = hash('sha1', basename($document->path));
        $mimetype = $this->getMimeType($document->path);

        // Try to resolve absolute path for single file documents
        $absPath = null;
        if (isset($document->source_id)) {
            $repo = Container::getDocumentRepository();
            $doc = $repo->findOneBy(['iid' => (int) $document->source_id]);
            if ($doc instanceof CDocument) {
                $absPath = $repo->getAbsolutePathForDocument($doc);
            }
        }

        return [
            'id' => (int) $document->source_id,
            'contenthash' => $contenthash,
            'contextid' => (int) $document->source_id, // will be normalized to moduleContextId
            'component' => 'mod_resource',             // will be normalized to mod_folder
            'filearea' => 'content',                   // will be normalized (kept for compatibility)
            'itemid' => (int) $document->source_id,    // will be normalized to 0
            'filepath' => '/',                         // will be replaced by deriveRelativeDirAndName()
            'documentpath' => (string) $document->path,
            'filename' => basename($document->path),
            'userid' => $adminId,
            'filesize' => (int) $document->size,
            'mimetype' => $mimetype,
            'status' => 0,
            'timecreated' => time() - 3600,
            'timemodified' => time(),
            'source' => (string) $document->title,
            'author' => 'Unknown',
            'license' => 'allrightsreserved',
            // New: absolute path for reliable copy
            'abs_path' => $absPath,
        ];
    }

    /**
     * Get file data for files inside a folder (legacy flow preserved).
     *
     * @param array<string,mixed> $file
     *
     * @return array<string,mixed>
     */
    private function getFolderFileData(array $file, int $sourceId, string $parentPath = '/'): array
    {
        $adminData = MoodleExport::getAdminUserData();
        $adminId = $adminData['id'] ?? 0;

        $contenthash = hash('sha1', basename((string) $file['path']));
        $mimetype    = $this->getMimeType((string) $file['path']);
        $filename    = basename((string) $file['path']);

        $filepath = $this->normalizeMoodleFilepath($parentPath);

        return [
            'id'          => (int) ($file['id'] ?? 0),
            'contenthash' => $contenthash,
            'contextid'   => $sourceId, // will be normalized to moduleContextId
            'component'   => 'mod_folder',
            'filearea'    => 'content',
            'itemid'      => (int) ($file['id'] ?? 0), // will be normalized to 0
            'filepath'    => $filepath,
            'documentpath'=> 'document/'.$file['path'],
            'filename'    => $filename,
            'userid'      => $adminId,
            'filesize'    => (int) ($file['size'] ?? 0),
            'mimetype'    => $mimetype,
            'status'      => 0,
            'timecreated' => time() - 3600,
            'timemodified'=> time(),
            'source'      => (string) ($file['title'] ?? ''),
            'author'      => 'Unknown',
            'license'     => 'allrightsreserved',
            'abs_path'    => $file['abs_path'] ?? null,
        ];
    }

    /**
     * Ensure the directory path has a trailing slash.
     */
    private function ensureTrailingSlash(string $path): string
    {
        // Normalize slashes and remove '/./'
        $path = (string) str_replace('\\', '/', $path);
        $path = (string) preg_replace('#/\./#', '/', $path);
        $path = (string) preg_replace('#/+#', '/', $path);

        if ($path === '' || $path === '.' || $path === '/') {
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
        ];
    }
}
