<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SearchEngineRef;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PATHINFO_EXTENSION;

/**
 * Handles Xapian indexing for CDocument entities.
 */
final class DocumentXapianIndexer
{
    private bool $isEnabled;
    private array $preFilterPrefix = [];

    public function __construct(
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        SettingsManager $settingsManager,
        private readonly DocumentRawTextExtractor $rawTextExtractor,
        private readonly RequestStack $requestStack,
    ) {
        $this->isEnabled = 'true' === $settingsManager->getSetting('search.search_enabled', true);

        $raw = (string) $settingsManager->getSetting('search.search_prefilter_prefix', true);

        if (!empty($raw) && 'false' !== $raw) {
            $this->preFilterPrefix = json_decode($raw, true);
        }
    }

    /**
     * Index a CDocument into Xapian.
     *
     * @return int|null Xapian document id or null when indexing is skipped
     */
    public function indexDocument(CDocument $document): ?int
    {
        $resourceNode = $document->getResourceNode();

        if (!$this->isEnabled) {
            return null;
        }

        if (!$resourceNode instanceof ResourceNode) {
            return null;
        }

        if ('folder' === $document->getFiletype()) {
            return null;
        }

        [$courseId, $sessionId, $courseRootNodeId] = $this->resolveCourseSessionAndRootNode($resourceNode);

        $content = $this->rawTextExtractor->extract($document);

        $fields = [
            'title' => (string) $document->getTitle(),
            'description' => (string) ($document->getComment() ?? ''),
            'content' => $content,
            'filetype' => (string) $document->getFiletype(),
            'resource_node_id' => (string) $resourceNode->getId(),
            'course_id' => null !== $courseId ? (string) $courseId : '',
            'session_id' => null !== $sessionId ? (string) $sessionId : '',
            'course_root_node_id' => null !== $courseRootNodeId ? (string) $courseRootNodeId : '',
            'full_path' => $document->getFullPath(),
        ];

        $terms = ['Tdocument'];

        if (null !== $courseId) {
            $terms[] = 'C'.$courseId;
        }
        if (null !== $sessionId) {
            $terms[] = 'S'.$sessionId;
        }

        $this->applyPrefilterConfigToTerms($terms, $courseId, $sessionId, $document);

        $resourceNodeId = (int) $resourceNode->getId();
        $resourceNodeRef = $this->em->getReference(ResourceNode::class, $resourceNodeId);

        /** @var SearchEngineRef|null $existingRef */
        $existingRef = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        $existingDocId = $existingRef?->getSearchDid();

        if (null !== $existingDocId) {
            try {
                $this->xapianIndexService->deleteDocument($existingDocId);
            } catch (Throwable $e) {
                error_log('[Xapian] indexDocument: failed to delete previous docId='.$existingDocId.' error='.$e->getMessage());
            }
        }

        // Get raw input from request (might be keyed by code OR by field_id)
        $rawInput = $this->extractSearchFieldValuesFromRequest();

        // Normalize into code => value (t/d/k/whatever)
        $inputByCode = $this->normalizeSearchFieldValuesToCode($rawInput);

        // Merge with stored values (stored wins only when request has nothing for that field)
        $storedByCode = $this->fetchStoredSearchFieldValuesByCode($resourceNodeId);

        // Request should override stored
        $searchFieldValuesByCode = array_replace($storedByCode, $inputByCode);

        // resolve language ISO for stemming (resource_file > resource_node)
        $languageIso = $this->resolveLanguageIsoForResourceNode($resourceNode);

        try {
            // Pass language ISO to the index service (it will map ISO -> Xapian language)
            $docId = $this->xapianIndexService->indexDocument(
                $fields,
                $terms,
                $languageIso,
                $searchFieldValuesByCode
            );
        } catch (Throwable $e) {
            error_log('[Xapian] indexDocument: Xapian indexing failed: '.$e->getMessage());

            return null;
        }

        if ($existingRef instanceof SearchEngineRef) {
            $existingRef->setSearchDid($docId);
        } else {
            $existingRef = new SearchEngineRef();
            $existingRef->setResourceNode($resourceNodeRef);
            $existingRef->setSearchDid($docId);
            $this->em->persist($existingRef);
        }

        // Persist dynamic search field values (create/update)
        $this->syncSearchEngineFieldValues($resourceNodeId, $document, $content);

        $this->em->flush();

        return $docId;
    }

    public function deleteForResourceNodeId(int $resourceNodeId): void
    {
        if (!$this->isEnabled) {
            error_log('[Xapian] deleteForResourceNodeId: search is disabled, skipping');

            return;
        }

        try {
            $this->em->getConnection()->executeStatement(
                'DELETE FROM search_engine_field_value WHERE resource_node_id = ?',
                [$resourceNodeId]
            );
        } catch (Throwable $e) {
            error_log('[Xapian] deleteForResourceNodeId: failed to delete field values: '.$e->getMessage());
        }

        $resourceNodeRef = $this->em->getReference(ResourceNode::class, $resourceNodeId);

        /** @var SearchEngineRef|null $ref */
        $ref = $this->em
            ->getRepository(SearchEngineRef::class)
            ->findOneBy(['resourceNode' => $resourceNodeRef])
        ;

        if (!$ref instanceof SearchEngineRef) {
            error_log('[Xapian] deleteForResourceNodeId: no SearchEngineRef found, nothing to delete');

            return;
        }

        $docId = $ref->getSearchDid();
        if (null !== $docId) {
            try {
                $this->xapianIndexService->deleteDocument($docId);
            } catch (Throwable $e) {
                error_log('[Xapian] deleteForResourceNodeId: deleteDocument failed for did='.$docId.' error='.$e->getMessage());
            }
        }

        $this->em->remove($ref);
        $this->em->flush();
    }

    /**
     * Persist search_engine_field_value dynamically based on values sent by UI/API.
     *
     * Accepts:
     * - multipart: searchFieldValues[t]=..., searchFieldValues[d]=...
     * - multipart: searchFieldValues as JSON string {"t":"..."}
     * - legacy/alt: searchFieldValues as array keyed by field id (1,2,3)
     */
    private function syncSearchEngineFieldValues(int $resourceNodeId, CDocument $document, string $content): void
    {
        $conn = $this->em->getConnection();

        $maps = $this->fetchSearchEngineFields($conn);
        $byCode = $maps['byCode'];
        $byId = $maps['byId'];

        if (empty($byCode)) {
            error_log('[Xapian] syncSearchEngineFieldValues: no search_engine_field rows found, skipping');

            return;
        }

        // Raw values from request (could be keyed by code OR id)
        $rawValues = $this->extractSearchFieldValuesFromRequest();
        $hasExplicitInput = \is_array($rawValues) && \count($rawValues) > 0;

        // If we didn't receive anything, do NOT overwrite existing values on update.
        // This prevents accidental resets when the request does not carry searchFieldValues.
        try {
            $existingCount = (int) $conn->fetchOne(
                'SELECT COUNT(*) FROM search_engine_field_value WHERE resource_node_id = ?',
                [$resourceNodeId]
            );
        } catch (Throwable $e) {
            $existingCount = 0;
        }

        if (!$hasExplicitInput && $existingCount > 0) {
            error_log(
                '[Xapian] syncSearchEngineFieldValues: no input received, keeping existing values for resource_node_id='.$resourceNodeId
            );

            return;
        }

        // Normalize into field_id => value
        $valuesByFieldId = [];

        foreach ($rawValues as $key => $val) {
            // NOTE: keep explicit empty strings to allow "clear",
            // but skip when building inserts
            $value = (string) $val;

            $fieldId = null;

            if (is_numeric((string) $key)) {
                $id = (int) $key;
                if (isset($byId[$id])) {
                    $fieldId = $id;
                }
            } else {
                $code = strtolower(trim((string) $key));
                if (isset($byCode[$code])) {
                    $fieldId = (int) $byCode[$code]['id'];
                }
            }

            if (null === $fieldId) {
                continue;
            }

            $valuesByFieldId[$fieldId] = trim($value);
        }

        // Conservative fallback: only fill missing ones for known semantics (t/d/c)
        foreach ($byCode as $code => $meta) {
            $fid = (int) $meta['id'];
            if (isset($valuesByFieldId[$fid])) {
                continue;
            }

            $fallback = $this->guessFallbackValue(
                (string) $code,
                (string) ($meta['title'] ?? ''),
                $document,
                $content
            );

            if (null !== $fallback) {
                $fallback = trim($fallback);
                if ('' !== $fallback) {
                    $valuesByFieldId[$fid] = $fallback;
                }
            }
        }

        try {
            $conn->executeStatement(
                'DELETE FROM search_engine_field_value WHERE resource_node_id = ?',
                [$resourceNodeId]
            );

            foreach ($valuesByFieldId as $fid => $value) {
                $conn->insert('search_engine_field_value', [
                    'resource_node_id' => $resourceNodeId,
                    'field_id' => (int) $fid,
                    'value' => (string) $value,
                ]);
            }
        } catch (Throwable $e) {
            error_log('[Xapian] syncSearchEngineFieldValues: failed: '.$e->getMessage());
        }
    }

    /**
     * @return array{
     *   byCode: array<string, array{id:int,title:string}>,
     *   byId: array<int, array{code:string,title:string}>
     * }
     */
    private function fetchSearchEngineFields(Connection $conn): array
    {
        try {
            $rows = $conn->fetchAllAssociative('SELECT id, code, title FROM search_engine_field');
        } catch (Throwable $e) {
            error_log('[Xapian] fetchSearchEngineFields: query failed: '.$e->getMessage());

            return ['byCode' => [], 'byId' => []];
        }

        $byCode = [];
        $byId = [];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            $title = (string) ($row['title'] ?? '');

            if ($id <= 0 || '' === $code) {
                continue;
            }

            $byCode[$code] = ['id' => $id, 'title' => $title];
            $byId[$id] = ['code' => $code, 'title' => $title];
        }

        return ['byCode' => $byCode, 'byId' => $byId];
    }

    /**
     * Normalize any request-provided values to "code => value".
     *
     * Input can be:
     *  - ['t' => '...', 'k' => '...']
     *  - [1 => '...', 3 => '...'] (field IDs)
     *
     * Output is always:
     *  - ['t' => '...', 'k' => '...']
     *
     * @param array<string|int, mixed> $rawValues
     *
     * @return array<string, string>
     */
    private function normalizeSearchFieldValuesToCode(array $rawValues): array
    {
        if (empty($rawValues)) {
            return [];
        }

        $conn = $this->em->getConnection();
        $maps = $this->fetchSearchEngineFields($conn);

        $byCode = $maps['byCode']; // code => ['id'=>..]
        $byId = $maps['byId'];     // id => ['code'=>..]

        if (empty($byCode) || empty($byId)) {
            // Safe fallback: if DB read fails, keep only string codes as-is
            $out = [];
            foreach ($rawValues as $k => $v) {
                if (!\is_string($k)) {
                    continue;
                }
                $code = strtolower(trim($k));
                if ('' === $code) {
                    continue;
                }
                $out[$code] = trim((string) $v);
            }

            return $out;
        }

        $out = [];

        foreach ($rawValues as $key => $val) {
            $value = trim((string) $val);

            $code = null;

            // Key is numeric => treat as field_id
            if (is_numeric((string) $key)) {
                $id = (int) $key;
                if (isset($byId[$id])) {
                    $code = strtolower(trim((string) $byId[$id]['code']));
                }
            } else {
                // Key is string => treat as code
                $candidate = strtolower(trim((string) $key));
                if ('' !== $candidate && isset($byCode[$candidate])) {
                    $code = $candidate;
                }
            }

            if (null === $code || '' === $code) {
                continue;
            }

            // Keep empty string (allows "clear"), indexer will skip empties anyway
            $out[$code] = $value;
        }

        return $out;
    }

    /**
     * Extract values from the current HTTP request.
     *
     * Supports:
     * - multipart: searchFieldValues[t]=... (Symfony returns array)
     * - multipart: searchFieldValues as JSON string {"t":"..."}
     * - JSON body: { "searchFieldValues": {...} }
     *
     * @return array<string|int, string>
     */
    private function extractSearchFieldValuesFromRequest(): array
    {
        $req = $this->requestStack->getCurrentRequest();
        if (!$req instanceof Request) {
            return [];
        }

        // Standard multipart parsed array: searchFieldValues[t]=...
        $fromForm = $req->get('searchFieldValues');
        if (\is_array($fromForm)) {
            $out = [];
            foreach ($fromForm as $k => $v) {
                $out[$k] = (string) $v;
            }

            return $out;
        }

        // If it's a string, it might be JSON (or broken "[object Object]")
        if (\is_string($fromForm) && '' !== trim($fromForm)) {
            $raw = trim($fromForm);

            if ('[object Object]' === $raw) {
                error_log(
                    '[Xapian] extractSearchFieldValuesFromRequest: searchFieldValues arrived as "[object Object]". '.
                    'Frontend must JSON.stringify() or send searchFieldValues[code]=...'
                );

                return [];
            }

            $decoded = json_decode($raw, true);
            if (\is_array($decoded)) {
                $out = [];
                foreach ($decoded as $k => $v) {
                    $out[$k] = (string) $v;
                }

                return $out;
            }
        }

        // JSON body
        $contentType = (string) $req->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $body = $req->getContent();
            if (\is_string($body) && '' !== trim($body)) {
                $decoded = json_decode($body, true);
                if (\is_array($decoded)) {
                    $blob = $decoded['searchFieldValues'] ?? null;
                    if (\is_array($blob)) {
                        $out = [];
                        foreach ($blob as $k => $v) {
                            $out[$k] = (string) $v;
                        }

                        return $out;
                    }
                }
            }
        }

        return [];
    }

    /**
     * Only used when request didn't provide values.
     * Keeps it conservative: title/description/content.
     */
    private function guessFallbackValue(string $code, string $title, CDocument $document, string $content): ?string
    {
        $code = strtolower(trim($code));
        $titleNorm = strtolower(trim($title));

        // By code (common convention)
        if ('t' === $code) {
            return (string) $document->getTitle();
        }
        if ('d' === $code) {
            return (string) ($document->getComment() ?? '');
        }
        if ('c' === $code) {
            return $content;
        }

        // By title label (common in UI)
        if ('title' === $titleNorm) {
            return (string) $document->getTitle();
        }
        if ('description' === $titleNorm) {
            return (string) ($document->getComment() ?? '');
        }
        if ('content' === $titleNorm) {
            return $content;
        }

        return null;
    }

    /**
     * Resolve course id, session id and course root node id from resource links.
     *
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function resolveCourseSessionAndRootNode(ResourceNode $resourceNode): array
    {
        $courseId = null;
        $sessionId = null;
        $courseRootNodeId = null;

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            if (null === $courseId && $link->getCourse()) {
                $course = $link->getCourse();
                $courseId = $course->getId();

                $courseRootNode = $course->getResourceNode();
                if ($courseRootNode instanceof ResourceNode) {
                    $courseRootNodeId = $courseRootNode->getId();
                }
            }

            if (null === $sessionId && $link->getSession()) {
                $sessionId = $link->getSession()->getId();
            }

            if (null !== $courseId && null !== $sessionId && null !== $courseRootNodeId) {
                break;
            }
        }

        return [$courseId, $sessionId, $courseRootNodeId];
    }

    /**
     * Apply configured prefilter prefixes to Xapian terms.
     */
    private function applyPrefilterConfigToTerms(
        array &$terms,
        ?int $courseId,
        ?int $sessionId,
        CDocument $document
    ): void {
        foreach ($this->preFilterPrefix as $key => $item) {
            if (!\is_array($item)) {
                continue;
            }

            $prefix = (string) ($item['prefix'] ?? '');
            if ('' === $prefix) {
                $prefix = strtoupper((string) $key);
            }

            switch ($key) {
                case 'course':
                    if (null !== $courseId) {
                        $terms[] = $prefix.(string) $courseId;
                    }

                    break;

                case 'session':
                    if (null !== $sessionId) {
                        $terms[] = $prefix.(string) $sessionId;
                    }

                    break;

                case 'filetype':
                    $terms[] = $prefix.$document->getFiletype();

                    break;

                default:
                    // Unknown key: ignore for now
                    break;
            }
        }
    }

    private function extractRawTextContent(CDocument $document): string
    {
        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return '';
        }

        // Prefer content stored directly on the node (if any)
        $nodeContent = (string) ($resourceNode->getContent() ?? '');
        if ('' !== trim($nodeContent)) {
            return $this->toPlainText($nodeContent);
        }

        // Fallback to file content from ResourceFile (most documents are stored as files)
        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            return '';
        }

        $path = $this->resolveResourceFilePath($resourceFile);
        if (null === $path || !is_file($path) || !is_readable($path)) {
            error_log('[Xapian] extractRawTextContent: file path not resolved or not readable');

            return '';
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        // HTML
        if (\in_array($ext, ['html', 'htm'], true)) {
            $html = $this->safeReadFile($path);

            return '' !== $html ? $this->toPlainText($html) : '';
        }

        // Plain text-like
        if (\in_array($ext, ['txt', 'md', 'csv', 'log'], true)) {
            return $this->safeReadFile($path);
        }

        // Zip-based office formats (no external tools needed)
        if ('docx' === $ext) {
            return $this->extractTextFromZipXml($path, [
                'word/document.xml',
                'word/footnotes.xml',
                'word/endnotes.xml',
            ]);
        }

        if ('odt' === $ext) {
            return $this->extractTextFromZipXml($path, [
                'content.xml',
            ]);
        }

        if ('pptx' === $ext) {
            return $this->extractTextFromPptx($path);
        }

        // PDF: optional hook (NO legacy, but depends on OS tool)
        // If you want pure-PHP only, just return '' here.
        if ('pdf' === $ext) {
            $text = $this->extractPdfWithPdftotext($path);
            if ('' !== $text) {
                return $text;
            }

            return '';
        }

        return '';
    }

    private function toPlainText(string $input): string
    {
        $text = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function resolveResourceFilePath(ResourceFile $resourceFile): ?string
    {
        // Most common in Symfony/Vich setups: getFile() returns a Symfony File instance
        if (method_exists($resourceFile, 'getFile')) {
            $file = $resourceFile->getFile();

            if ($file instanceof File) {
                return $file->getPathname();
            }

            if ($file instanceof SplFileInfo) {
                return $file->getPathname();
            }
        }

        // Some entities store a direct path/string field (depending on implementation)
        foreach (['getPathname', 'getPath', 'getFilePath', 'getAbsolutePath'] as $method) {
            if (method_exists($resourceFile, $method)) {
                $value = $resourceFile->{$method}();
                if (\is_string($value) && '' !== trim($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function safeReadFile(string $path, int $maxBytes = 2_000_000): string
    {
        try {
            $size = filesize($path);
            if (\is_int($size) && $size > $maxBytes) {
                error_log('[Xapian] safeReadFile: file too large, truncating. size='.$size.' max='.$maxBytes);
            }

            $handle = fopen($path, 'rb');
            if (false === $handle) {
                return '';
            }

            $data = fread($handle, $maxBytes);
            fclose($handle);

            return \is_string($data) ? $data : '';
        } catch (Throwable $e) {
            error_log('[Xapian] safeReadFile: read failed: '.$e->getMessage());

            return '';
        }
    }

    private function extractTextFromZipXml(string $zipPath, array $xmlCandidates): string
    {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);

        if (true !== $opened) {
            error_log('[Xapian] extractTextFromZipXml: failed to open zip');

            return '';
        }

        $chunks = [];

        foreach ($xmlCandidates as $xmlName) {
            $xml = $zip->getFromName($xmlName);
            if (\is_string($xml) && '' !== trim($xml)) {
                $chunks[] = $this->toPlainText($xml);
            }
        }

        $zip->close();

        $text = trim(implode(' ', $chunks));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim((string) $text);
    }

    private function extractTextFromPptx(string $zipPath): string
    {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);

        if (true !== $opened) {
            error_log('[Xapian] extractTextFromPptx: failed to open pptx zip');

            return '';
        }

        $chunks = [];
        $slideFiles = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            if (str_starts_with($name, 'ppt/slides/slide') && str_ends_with($name, '.xml')) {
                $slideFiles[] = $name;
            }
        }

        sort($slideFiles);

        foreach ($slideFiles as $slideName) {
            $xml = $zip->getFromName($slideName);
            if (\is_string($xml) && '' !== trim($xml)) {
                $chunks[] = $this->toPlainText($xml);
            }
        }

        $zip->close();

        $text = trim(implode(' ', $chunks));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim((string) $text);
    }

    private function extractPdfWithPdftotext(string $path): string
    {
        // Check if command exists
        $process = new Process(['which', 'pdftotext']);
        $process->run();

        if (!$process->isSuccessful()) {
            return '';
        }

        $tmp = sys_get_temp_dir().'/xapian_'.uniqid('', true).'.txt';

        try {
            $process = new Process(['pdftotext', '-enc', 'UTF-8', $path, $tmp]);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful() || !is_file($tmp)) {
                return '';
            }

            $text = $this->safeReadFile($tmp, 2_000_000);

            return trim($text);
        } catch (Throwable $e) {
            error_log('[Xapian] extractPdfWithPdftotext: failed: '.$e->getMessage());

            return '';
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private function fetchStoredSearchFieldValuesByCode(int $resourceNodeId): array
    {
        $conn = $this->em->getConnection();

        try {
            $rows = $conn->fetchAllAssociative(
                'SELECT f.code, v.value
             FROM search_engine_field_value v
             INNER JOIN search_engine_field f ON f.id = v.field_id
             WHERE v.resource_node_id = ?',
                [$resourceNodeId]
            );
        } catch (Throwable $e) {
            error_log('[Xapian] fetchStoredSearchFieldValuesByCode: query failed: '.$e->getMessage());

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $code = strtolower(trim((string) ($row['code'] ?? '')));
            $val = trim((string) ($row['value'] ?? ''));
            if ('' === $code) {
                continue;
            }

            // Keep empty string too (allows clear), but indexer will skip empties
            $out[$code] = $val;
        }

        return $out;
    }

    private function resolveLanguageIsoForResourceNode(ResourceNode $resourceNode): ?string
    {
        // Prefer ResourceFile language when possible
        $file = $resourceNode->getFirstResourceFile();
        if ($file instanceof ResourceFile) {
            $lang = $file->getLanguage();
            if (null !== $lang) {
                $iso = trim((string) $lang->getIsocode());
                if ('' !== $iso) {
                    return $iso;
                }
            }
        }

        // Fallback to ResourceNode language
        $nodeLang = $resourceNode->getLanguage();
        if (null !== $nodeLang) {
            $iso = trim((string) $nodeLang->getIsocode());
            if ('' !== $iso) {
                return $iso;
            }
        }

        // Unknown language
        return null;
    }
}
