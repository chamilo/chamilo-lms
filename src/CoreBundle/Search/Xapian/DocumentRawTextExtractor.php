<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Search\Xapian;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PATHINFO_EXTENSION;

final class DocumentRawTextExtractor
{
    private const MAX_ARCHIVE_BYTES = 30_000_000; // 30MB safety limit for zip-based docs

    private const GENERIC_EXTENSIONS = ['bin', 'tmp', 'dat'];

    private const SUPPORTED_EXTENSIONS = [
        'html', 'htm', 'txt', 'md', 'csv', 'log',
        'pdf', 'ps', 'doc', 'ppt', 'rtf', 'xls',
        'docx', 'docm', 'dotx', 'dotm',
        'pptx', 'pptm', 'ppsx', 'ppsm', 'potx', 'potm',
        'xlsx', 'xlsm', 'xltx', 'xltm',
        'odt', 'ods', 'odp', 'ott', 'ots', 'otp',
    ];

    public function __construct(
        private readonly ResourceNodeRepository $resourceNodeRepository,
    ) {}

    public function extract(CDocument $document): string
    {
        $resourceNode = $document->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            error_log('[Xapian] DocumentRawTextExtractor: missing ResourceNode');

            return '';
        }

        // Prefer node content if exists (for “inline” HTML/text resources)
        $nodeContent = (string) ($resourceNode->getContent() ?? '');
        if ('' !== trim($nodeContent)) {
            return $this->toPlainText($nodeContent);
        }

        // Fallback to file content from ResourceFile
        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            error_log('[Xapian] DocumentRawTextExtractor: no ResourceFile found, nodeId='.$resourceNode->getId());

            return '';
        }

        $fileKey = $this->resourceNodeRepository->getFilename($resourceFile);
        if (!\is_string($fileKey) || '' === trim($fileKey)) {
            error_log('[Xapian] DocumentRawTextExtractor: resolveUri returned empty key, nodeId='.$resourceNode->getId());

            return '';
        }

        $ext = $this->detectExtension($fileKey, $resourceFile);
        if ('' === $ext) {
            error_log('[Xapian] DocumentRawTextExtractor: could not detect extension, nodeId='.$resourceNode->getId());

            return '';
        }

        // Quick path for text-like formats (read directly from flysystem)
        if (\in_array($ext, ['html', 'htm', 'txt', 'md', 'csv', 'log'], true)) {
            try {
                $raw = $this->resourceNodeRepository->getResourceNodeFileContent($resourceNode, $resourceFile);
            } catch (Throwable $e) {
                error_log('[Xapian] DocumentRawTextExtractor: read failed for key='.$fileKey.' error='.$e->getMessage());

                return '';
            }

            if ('' === trim($raw)) {
                error_log('[Xapian] DocumentRawTextExtractor: file content empty, ext='.$ext.', nodeId='.$resourceNode->getId());

                return '';
            }

            if (\in_array($ext, ['html', 'htm'], true)) {
                return $this->toPlainText($raw);
            }

            return $this->normalizePlainText($raw);
        }

        // For binary formats (including zip-based): we need a local temp file
        $tmpIn = $this->dumpResourceFileToTemp($resourceNode, $resourceFile, $ext);
        if (null === $tmpIn) {
            error_log('[Xapian] DocumentRawTextExtractor: could not create temp file, nodeId='.$resourceNode->getId());

            return '';
        }

        try {
            $text = $this->extractFromBinaryByExtension($ext, $tmpIn);
            $text = $this->normalizePlainText($text);

            if ('' === $text) {
                error_log('[Xapian] DocumentRawTextExtractor: extracted text is empty, ext='.$ext.', nodeId='.$resourceNode->getId());
            }

            return $text;
        } finally {
            @unlink($tmpIn);
        }
    }

    private function extractFromBinaryByExtension(string $ext, string $tmpIn): string
    {
        return match ($ext) {
            // External tools
            'pdf' => $this->extractPdf($tmpIn),
            'ps' => $this->extractPs($tmpIn),

            'doc' => $this->runToStdout(['catdoc', $tmpIn], 10),
            'ppt' => $this->runToStdout(['catppt', $tmpIn], 10),

            'rtf' => $this->extractRtf($tmpIn),

            'xls' => $this->runToStdout(['xls2csv', $tmpIn], 12),

            // ZIP/XML (no extra dependencies)
            'docx', 'docm', 'dotx', 'dotm' => $this->extractDocx($tmpIn),
            'pptx', 'pptm', 'ppsx', 'ppsm', 'potx', 'potm' => $this->extractPptx($tmpIn),
            'xlsx', 'xlsm', 'xltx', 'xltm' => $this->extractXlsx($tmpIn),

            // ODF (zip + content.xml)
            'odt', 'ods', 'odp', 'ott', 'ots', 'otp' => $this->extractOdf($tmpIn),

            default => $this->unsupported($ext),
        };
    }

    private function extractDocx(string $zipPath): string
    {
        if (!$this->canOpenZip($zipPath)) {
            return '';
        }

        return $this->withZip($zipPath, function (ZipArchive $zip): string {
            $names = $this->collectZipEntries($zip, [
                '~^word/document\.xml$~',
                '~^word/(header|footer)\d*\.xml$~',
                '~^word/(footnotes|endnotes|comments)\.xml$~',
            ]);

            $chunks = $this->extractOfficeMetadata($zip);

            foreach ($names as $name) {
                $xml = $zip->getFromName($name);
                if (!\is_string($xml) || '' === trim($xml)) {
                    continue;
                }
                $chunks[] = $this->extractTextByRegex($xml, '~<(?:[\w.-]+:)?t[^>]*>(.*?)</(?:[\w.-]+:)?t>~s');
            }

            return trim(implode(' ', array_filter($chunks)));
        });
    }

    private function extractPptx(string $zipPath): string
    {
        if (!$this->canOpenZip($zipPath)) {
            return '';
        }

        return $this->withZip($zipPath, function (ZipArchive $zip): string {
            $slideNames = $this->collectZipEntries($zip, [
                '~^ppt/slides/slide\d+\.xml$~',
                '~^ppt/notesSlides/notesSlide\d+\.xml$~',
            ]);

            // Sort by numeric part when possible (slide1, slide2...)
            usort($slideNames, function (string $a, string $b): int {
                return $this->extractFirstInt($a) <=> $this->extractFirstInt($b);
            });

            $chunks = $this->extractOfficeMetadata($zip);

            foreach ($slideNames as $name) {
                $xml = $zip->getFromName($name);
                if (!\is_string($xml) || '' === trim($xml)) {
                    continue;
                }
                $chunks[] = $this->extractTextByRegex($xml, '~<(?:[\w.-]+:)?t[^>]*>(.*?)</(?:[\w.-]+:)?t>~s');
            }

            return trim(implode(' ', array_filter($chunks)));
        });
    }

    private function extractXlsx(string $zipPath): string
    {
        if (!$this->canOpenZip($zipPath)) {
            return '';
        }

        return $this->withZip($zipPath, function (ZipArchive $zip): string {
            $chunks = $this->extractOfficeMetadata($zip);

            // Shared strings are common in XLSX, but some generators store text
            // directly in worksheet cells as <v> with t="str" or as inline strings.
            $shared = $zip->getFromName('xl/sharedStrings.xml');
            if (\is_string($shared) && '' !== trim($shared)) {
                $chunks[] = $this->extractTextByRegex($shared, '~<(?:[\w.-]+:)?t[^>]*>(.*?)</(?:[\w.-]+:)?t>~s');
            }

            $sheetNames = $this->collectZipEntries($zip, [
                '~^xl/worksheets/sheet\d+\.xml$~',
            ]);

            usort($sheetNames, function (string $a, string $b): int {
                return $this->extractFirstInt($a) <=> $this->extractFirstInt($b);
            });

            foreach ($sheetNames as $name) {
                $xml = $zip->getFromName($name);
                if (!\is_string($xml) || '' === trim($xml)) {
                    continue;
                }

                $chunks[] = $this->extractWorksheetText($xml);
            }

            return trim(implode(' ', array_filter($chunks)));
        });
    }

    private function extractWorksheetText(string $xml): string
    {
        $chunks = [];

        // Inline strings usually appear as <is>...<t>Text</t>...</is>.
        $chunks[] = $this->extractTextByRegex($xml, '~<(?:[\w.-]+:)?t[^>]*>(.*?)</(?:[\w.-]+:)?t>~s');

        // Some generators, including minimal spreadsheet writers, store strings as:
        // <c t="str"><v>Text</v></c>. Keep numeric values too; they can be searchable.
        $chunks[] = $this->extractTextByRegex($xml, '~<(?:[\w.-]+:)?v[^>]*>(.*?)</(?:[\w.-]+:)?v>~s');

        return trim(implode(' ', array_filter($chunks)));
    }

    private function extractOdf(string $zipPath): string
    {
        if (!$this->canOpenZip($zipPath)) {
            return '';
        }

        return $this->withZip($zipPath, function (ZipArchive $zip): string {
            $xml = $zip->getFromName('content.xml');
            if (!\is_string($xml) || '' === trim($xml)) {
                error_log('[Xapian] DocumentRawTextExtractor: ODF content.xml missing');

                return '';
            }

            // ODF uses XML; stripping tags gives usable plain text for indexing
            return $this->toPlainText($xml);
        });
    }

    /**
     * Extract basic document metadata from OOXML packages.
     *
     * @return array<int, string>
     */
    private function extractOfficeMetadata(ZipArchive $zip): array
    {
        $chunks = [];

        foreach (['docProps/core.xml', 'docProps/app.xml', 'docProps/custom.xml'] as $name) {
            $xml = $zip->getFromName($name);
            if (!\is_string($xml) || '' === trim($xml)) {
                continue;
            }

            $text = $this->toPlainText($xml);
            if ('' !== $text) {
                $chunks[] = $text;
            }
        }

        return $chunks;
    }

    private function canOpenZip(string $zipPath): bool
    {
        if (!class_exists(ZipArchive::class)) {
            error_log('[Xapian] DocumentRawTextExtractor: ZipArchive not available (php-zip missing?)');

            return false;
        }

        $size = @filesize($zipPath);
        if (\is_int($size) && $size > self::MAX_ARCHIVE_BYTES) {
            error_log('[Xapian] DocumentRawTextExtractor: zip file too large, skipping. size='.$size);

            return false;
        }

        return true;
    }

    /**
     * @param callable(ZipArchive): string $callback
     */
    private function withZip(string $zipPath, callable $callback): string
    {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath);

        if (true !== $opened) {
            error_log('[Xapian] DocumentRawTextExtractor: failed to open zip archive');

            return '';
        }

        try {
            return (string) $callback($zip);
        } finally {
            $zip->close();
        }
    }

    /**
     * @param array<int, string> $patterns Regex patterns
     *
     * @return array<int, string>
     */
    private function collectZipEntries(ZipArchive $zip, array $patterns): array
    {
        $names = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $name)) {
                    $names[] = $name;

                    break;
                }
            }
        }

        return $names;
    }

    private function extractTextByRegex(string $xml, string $regex): string
    {
        $matches = [];
        preg_match_all($regex, $xml, $matches);

        if (empty($matches[1])) {
            return '';
        }

        $parts = [];
        foreach ($matches[1] as $piece) {
            $piece = (string) $piece;

            // Decode XML/HTML entities and normalize whitespace
            $piece = html_entity_decode($piece, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $piece = preg_replace('/\s+/u', ' ', $piece) ?? $piece;
            $piece = trim($piece);

            if ('' !== $piece) {
                $parts[] = $piece;
            }
        }

        return trim(implode(' ', $parts));
    }

    private function extractFirstInt(string $s): int
    {
        if (preg_match('~(\d+)~', $s, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private function extractPdf(string $tmpIn): string
    {
        if (!$this->commandExists('pdftotext')) {
            error_log('[Xapian] DocumentRawTextExtractor: pdftotext not installed');

            return '';
        }

        $tmpOut = sys_get_temp_dir().'/xapian_pdftotext_'.uniqid('', true).'.txt';

        try {
            $process = new Process(['pdftotext', '-enc', 'UTF-8', $tmpIn, $tmpOut]);
            $process->setTimeout(12);
            $process->run();

            if (!$process->isSuccessful()) {
                error_log('[Xapian] DocumentRawTextExtractor: pdftotext failed: '.$process->getErrorOutput());

                return '';
            }

            if (!is_file($tmpOut)) {
                error_log('[Xapian] DocumentRawTextExtractor: pdftotext output file missing');

                return '';
            }

            return (string) @file_get_contents($tmpOut);
        } finally {
            if (is_file($tmpOut)) {
                @unlink($tmpOut);
            }
        }
    }

    private function extractPs(string $tmpIn): string
    {
        if (!$this->commandExists('ps2pdf')) {
            error_log('[Xapian] DocumentRawTextExtractor: ps2pdf not installed');

            return '';
        }

        $tmpPdf = sys_get_temp_dir().'/xapian_ps2pdf_'.uniqid('', true).'.pdf';

        try {
            $process = new Process(['ps2pdf', $tmpIn, $tmpPdf]);
            $process->setTimeout(15);
            $process->run();

            if (!$process->isSuccessful()) {
                error_log('[Xapian] DocumentRawTextExtractor: ps2pdf failed: '.$process->getErrorOutput());

                return '';
            }

            if (!is_file($tmpPdf)) {
                error_log('[Xapian] DocumentRawTextExtractor: ps2pdf output pdf missing');

                return '';
            }

            return $this->extractPdf($tmpPdf);
        } finally {
            if (is_file($tmpPdf)) {
                @unlink($tmpPdf);
            }
        }
    }

    private function extractRtf(string $tmpIn): string
    {
        if (!$this->commandExists('unrtf')) {
            error_log('[Xapian] DocumentRawTextExtractor: unrtf not installed');

            return '';
        }

        return $this->runToStdout(['unrtf', '--text', $tmpIn], 10);
    }

    private function runToStdout(array $cmd, int $timeoutSeconds): string
    {
        $process = new Process($cmd);
        $process->setTimeout($timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            error_log('[Xapian] DocumentRawTextExtractor: command failed: '.implode(' ', $cmd).' err='.$process->getErrorOutput());

            return '';
        }

        return (string) $process->getOutput();
    }

    private function commandExists(string $cmd): bool
    {
        $p = new Process(['which', $cmd]);
        $p->setTimeout(3);
        $p->run();

        return $p->isSuccessful() && '' !== trim((string) $p->getOutput());
    }

    private function unsupported(string $ext): string
    {
        error_log('[Xapian] DocumentRawTextExtractor: unsupported extension='.$ext.', skipping content');

        return '';
    }

    private function dumpResourceFileToTemp(ResourceNode $node, ResourceFile $file, string $ext): ?string
    {
        $tmp = sys_get_temp_dir().'/xapian_in_'.uniqid('', true).'.'.$ext;

        try {
            $stream = $this->resourceNodeRepository->getResourceNodeFileStream($node, $file);
            if (!\is_resource($stream)) {
                error_log('[Xapian] DocumentRawTextExtractor: readStream returned no resource');

                return null;
            }

            $out = @fopen($tmp, 'wb');
            if (false === $out) {
                error_log('[Xapian] DocumentRawTextExtractor: cannot open temp file for writing');

                return null;
            }

            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);

            if (!is_file($tmp) || 0 === filesize($tmp)) {
                error_log('[Xapian] DocumentRawTextExtractor: temp file created but empty');

                @unlink($tmp);

                return null;
            }

            return $tmp;
        } catch (Throwable $e) {
            error_log('[Xapian] DocumentRawTextExtractor: dumpResourceFileToTemp failed: '.$e->getMessage());
            @unlink($tmp);

            return null;
        }
    }

    private function detectExtension(string $fileKey, ResourceFile $resourceFile): string
    {
        $candidateExtensions = [];

        $originalName = (string) $resourceFile->getOriginalName();
        if ('' !== trim($originalName)) {
            $candidateExtensions[] = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        }

        $title = (string) $resourceFile->getTitle();
        if ('' !== trim($title)) {
            $candidateExtensions[] = strtolower((string) pathinfo($title, PATHINFO_EXTENSION));
        }

        $candidateExtensions[] = strtolower((string) pathinfo($fileKey, PATHINFO_EXTENSION));

        foreach ($candidateExtensions as $ext) {
            if ('' === $ext || \in_array($ext, self::GENERIC_EXTENSIONS, true)) {
                continue;
            }

            if (\in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
                return $ext;
            }
        }

        $mimeExt = $this->detectExtensionFromMimeType((string) $resourceFile->getMimeType());
        if ('' !== $mimeExt) {
            return $mimeExt;
        }

        foreach ($candidateExtensions as $ext) {
            if ('' !== $ext) {
                return $ext;
            }
        }

        return '';
    }

    private function detectExtensionFromMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        return match ($mimeType) {
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-word.document.macroenabled.12' => 'docm',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'dotx',
            'application/vnd.ms-word.template.macroenabled.12' => 'dotm',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel.sheet.macroenabled.12' => 'xlsm',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
            'application/vnd.ms-excel.template.macroenabled.12' => 'xltm',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.ms-powerpoint.presentation.macroenabled.12' => 'pptm',
            'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'ppsx',
            'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => 'ppsm',
            'application/vnd.openxmlformats-officedocument.presentationml.template' => 'potx',
            'application/vnd.ms-powerpoint.template.macroenabled.12' => 'potm',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'application/vnd.oasis.opendocument.presentation' => 'odp',
            'application/vnd.oasis.opendocument.text-template' => 'ott',
            'application/vnd.oasis.opendocument.spreadsheet-template' => 'ots',
            'application/vnd.oasis.opendocument.presentation-template' => 'otp',
            default => '',
        };
    }

    private function toPlainText(string $input): string
    {
        // Remove script/style blocks (basic HTML hygiene)
        $clean = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $input) ?? $input;

        $text = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);

        return $this->normalizePlainText($text);
    }

    private function normalizePlainText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
