<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\AiProvider;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

use const ENT_HTML5;
use const ENT_QUOTES;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

final class AiTaskGraderService
{
    private const LOG_PREFIX = '[Assignments][AI][task_grader]';
    private bool $logEnabled = false;

    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly ResourceNodeRepository $resourceNodeRepository,
    ) {}

    /**
     * Grades an assignment submission using AI.
     * This method does NOT persist anything.
     *
     * @param array<string,mixed> $options
     *
     * @return array{
     *   success: bool,
     *   feedback?: string,
     *   suggestedScore?: float|null,
     *   mode?: string,
     *   message?: string,
     *   httpStatus?: int
     * }
     */
    public function gradeSubmission(CStudentPublication $submission, User $teacher, array $options = []): array
    {
        $this->dbg('Grade request received', [
            'submissionIid' => $submission->getIid(),
            'teacherId' => method_exists($teacher, 'getId') ? $teacher->getId() : null,
        ]);

        $assignment = $submission->getPublicationParent();
        if (!$assignment instanceof CStudentPublication) {
            $this->dbg('Missing assignment parent', ['submissionIid' => $submission->getIid()]);

            return $this->fail('This submission has no assignment parent.', 400, 'text');
        }

        $maxScore = (float) $assignment->getQualification();
        $hasMaxScore = $maxScore > 0;

        if (!$hasMaxScore) {
            $maxScore = 0.0;
        }

        $providerName = trim((string) ($options['ai_provider'] ?? ''));
        if ('' === $providerName) {
            $this->dbg('Missing ai_provider', ['submissionIid' => $submission->getIid()]);

            return $this->fail('Missing ai_provider.', 400, 'text');
        }

        $language = trim((string) ($options['language'] ?? 'en'));
        $requestedMode = trim((string) ($options['mode'] ?? 'auto')); // auto|text|document
        $userPrompt = trim((string) ($options['prompt'] ?? ''));

        $providerOptions = $options['provider_options'] ?? null;
        $providerOptions = \is_array($providerOptions) ? $providerOptions : [];

        $teacherNotes = trim((string) ($options['teacher_notes'] ?? ''));
        $rubric = trim((string) ($options['rubric'] ?? ''));

        // Submission text typed by student (HTML -> plain text)
        $studentText = $this->toPlainText((string) ($submission->getDescription() ?? ''));

        // File meta + bytes (if any)
        $fileMeta = $this->getSubmissionFileMeta($submission);
        $hasFile = (bool) ($fileMeta['hasFile'] ?? false);

        $this->dbg('Context resolved', [
            'assignmentIid' => $assignment->getIid(),
            'maxScore' => $maxScore,
            'hasMaxScore' => $hasMaxScore,
            'providerName' => $providerName,
            'language' => $language,
            'requestedMode' => $requestedMode,
            'hasFile' => $hasFile,
            'fileMeta' => $hasFile ? $fileMeta : null,
            'studentTextLen' => mb_strlen($studentText),
        ]);

        $effectiveMode = $requestedMode;
        if ('auto' === $effectiveMode) {
            if ($hasFile) {
                $filename = (string) $fileMeta['filename'];
                $mimeType = (string) $fileMeta['mimeType'];

                if ($this->isPdfForDocumentProcess($filename, $mimeType)) {
                    $effectiveMode = 'document';
                } elseif ($this->isDocxFile($filename, $mimeType)) {
                    $effectiveMode = 'text';
                } elseif ($this->isPlainTextFile($filename, $mimeType)) {
                    $effectiveMode = 'text';
                } else {
                    $effectiveMode = 'text';
                }
            } else {
                $effectiveMode = 'text';
            }
        }

        $this->dbg('Effective mode chosen', [
            'effectiveMode' => $effectiveMode,
            'requestedMode' => $requestedMode,
        ]);

        // If user forced document but there is no file, fail early.
        if ('document' === $effectiveMode && !$hasFile) {
            return $this->fail('No file attached to this submission. Please attach a PDF or switch to text mode.', 400, 'document');
        }

        // If file is image, fail early (document_process does not support images in this feature).
        if ($hasFile && $this->isImageFile((string) $fileMeta['filename'], (string) $fileMeta['mimeType'])) {
            return $this->fail(
                'Image submissions are not supported by the AI grader. Please upload a PDF or paste the text in the submission.',
                400,
                'text'
            );
        }

        // Read file bytes if needed
        $fileText = '';
        $fileForDocument = null; // [filename, mimeType, bytes]

        if ($hasFile) {
            [$fn, $mt, $bytes, $err] = $this->readSubmissionFileBytes($submission);

            if (null !== $err) {
                return $this->fail($err, 400, $effectiveMode);
            }

            $this->dbg('File bytes loaded', [
                'filename' => $fn,
                'mimeType' => $mt,
                'bytesLen' => \strlen($bytes),
                'sha1' => sha1($bytes),
                'headAscii' => substr($bytes, 0, 8),
                'headHex' => bin2hex(substr($bytes, 0, 8)),
            ]);

            if ('document' === $effectiveMode) {
                if (!$this->isPdfForDocumentProcess($fn, $mt)) {
                    return $this->fail('Unsupported file type for document processing. Please upload a PDF.', 400, 'document');
                }

                // Basic PDF signature check (helps detect wrong bytes)
                if (!$this->looksLikePdfBytes($bytes)) {
                    $this->dbg('PDF signature check failed', [
                        'filename' => $fn,
                        'mimeType' => $mt,
                        'headAscii' => substr($bytes, 0, 16),
                        'headHex' => bin2hex(substr($bytes, 0, 16)),
                    ]);
                    // Do not hard-fail: some systems prepend BOM/whitespace, but log it clearly.
                }

                if (\strlen($bytes) > 12 * 1024 * 1024) {
                    return $this->fail('Document is too large for AI processing (max 12MB).', 400, 'document');
                }

                // Optional local sanity check: does pdftotext extract anything?
                if ($this->isDebugEnabled()) {
                    $pdfCheck = $this->debugPdfSanityCheck($bytes);
                    $this->dbg('PDF sanity check', $pdfCheck);
                }

                $fileForDocument = [$fn, $mt, $bytes];
            } else {
                // text mode: try to inline docx/txt content
                if ($this->isDocxFile($fn, $mt)) {
                    $fileText = $this->extractDocxText($bytes);
                    $this->dbg('DOCX extracted', [
                        'extractedLen' => mb_strlen($fileText),
                    ]);

                    if ('' === trim($fileText) && '' === trim($studentText)) {
                        return $this->fail(
                            'DOCX detected but no text could be extracted. Please upload a PDF or paste the text in the submission.',
                            400,
                            'text'
                        );
                    }
                } elseif ($this->isPlainTextFile($fn, $mt)) {
                    $fileText = $this->safeDecodeTextBytes($bytes);
                    $this->dbg('Text file decoded', [
                        'decodedLen' => mb_strlen($fileText),
                    ]);

                    if ('' === trim($fileText) && '' === trim($studentText)) {
                        return $this->fail(
                            'Text file detected but it seems empty. Please upload a non-empty file or paste the text in the submission.',
                            400,
                            'text'
                        );
                    }
                } else {
                    // Unsupported file type in text mode: still continue if student typed text
                    if ('' === trim($studentText)) {
                        return $this->fail(
                            'Unsupported file type. Please upload a PDF (recommended) or a text-based file (txt/md/html).',
                            400,
                            'text'
                        );
                    }
                }
            }
        }

        // Build the prompt
        $basePrompt = ('' !== $userPrompt)
            ? $userPrompt
            : $this->buildDefaultTaskGraderPrompt($language, $maxScore);

        // Hard guard: avoid "SCORE:" being translated
        $guard = "IMPORTANT OUTPUT RULES:\n"
            ."- Return plain text only.\n"
            ."- The LAST line must start with exactly: SCORE: <number>.\n"
            ."- Do NOT translate the word 'SCORE'.\n";

        // Context: assignment + submission + explicit instruction for document mode
        $context = $this->buildTaskGraderContextBlock(
            submission: $submission,
            studentText: $studentText,
            fileText: $fileText,
            hasFile: $hasFile,
            mode: $effectiveMode,
            teacherNotes: $teacherNotes,
            rubric: $rubric
        );

        $finalPrompt = $basePrompt."\n\n".$guard."\n---\n".$context;

        // Call provider
        try {
            $provider = $this->aiProviderFactory->create($providerName);
        } catch (Throwable $e) {
            $this->dbg('Failed to create provider', ['error' => $e->getMessage()]);

            return $this->fail('Failed to initialize AI provider.', 500, $effectiveMode);
        }

        $this->dbg('Provider created', [
            'providerClass' => \is_object($provider) ? $provider::class : \gettype($provider),
            'effectiveMode' => $effectiveMode,
        ]);

        try {
            if ('document' === $effectiveMode) {
                if (!$provider instanceof AiDocumentProcessProviderInterface) {
                    return $this->fail('Selected provider does not support document processing.', 400, 'document');
                }

                if (!\is_array($fileForDocument) || 3 !== \count($fileForDocument)) {
                    return $this->fail('Internal error: document file payload is missing.', 500, 'document');
                }

                [$filename, $mimeType, $bytes] = $fileForDocument;

                $this->dbg('Calling processDocument()', [
                    'toolName' => 'task_grader',
                    'filename' => $filename,
                    'mimeType' => $mimeType,
                    'bytesLen' => \strlen($bytes),
                    'sha1' => sha1($bytes),
                    'providerOptionsKeys' => array_keys($providerOptions),
                ]);

                $raw = (string) $provider->processDocument(
                    prompt: $finalPrompt,
                    toolName: 'task_grader',
                    filename: $filename,
                    mimeType: $mimeType,
                    binaryContent: $bytes,
                    options: $providerOptions
                );
            } else {
                if (!method_exists($provider, 'generateText')) {
                    return $this->fail('Selected provider does not support text generation.', 400, 'text');
                }

                /** @var callable $call */
                $call = [$provider, 'generateText'];
                $raw = (string) $call($finalPrompt, $providerOptions);
            }
        } catch (Throwable $e) {
            $this->dbg('Provider exception', ['error' => $e->getMessage()]);

            return $this->fail('AI provider failed: '.$e->getMessage(), 502, $effectiveMode);
        }

        $raw = trim((string) $raw);
        $this->dbg('Provider response received', [
            'rawLen' => mb_strlen($raw),
            'mode' => $effectiveMode,
        ]);

        if ('' === $raw) {
            return $this->fail('AI returned an empty response.', 500, $effectiveMode);
        }

        if (str_starts_with($raw, 'Error:')) {
            return $this->fail($raw, 400, $effectiveMode);
        }

        $suggested = $this->extractSuggestedScore($raw, $maxScore);

        return [
            'success' => true,
            'feedback' => $raw,
            'suggestedScore' => $suggested,
            'mode' => $effectiveMode,
        ];
    }

    private function fail(string $message, int $httpStatus, string $mode): array
    {
        $this->dbg('Fail', [
            'message' => $message,
            'httpStatus' => $httpStatus,
            'mode' => $mode,
        ]);

        return [
            'success' => false,
            'message' => $message,
            'httpStatus' => $httpStatus,
            'mode' => $mode,
        ];
    }

    private function buildDefaultTaskGraderPrompt(string $language, float $maxScore): string
    {
        $language = '' !== trim($language) ? $language : 'en';

        if ($maxScore > 0) {
            return \sprintf(
                "You are an assignment grader.\nLanguage: %s.\nProvide constructive feedback and actionable improvements.\nAt the end, add a final line exactly like: SCORE: <number> (0 to %.1f).\nReturn plain text only.",
                $language,
                $maxScore
            );
        }

        return \sprintf(
            "You are an assignment grader.\nLanguage: %s.\nProvide constructive feedback and actionable improvements.\nAt the end, add a final line exactly like: SCORE: N/A.\nReturn plain text only.",
            $language
        );
    }

    private function buildTaskGraderContextBlock(
        CStudentPublication $submission,
        string $studentText,
        string $fileText,
        bool $hasFile,
        string $mode,
        string $teacherNotes,
        string $rubric
    ): string {
        $assignment = $submission->getPublicationParent();
        $assignmentTitle = $assignment?->getTitle() ?? 'Assignment';
        $assignmentInstructions = $this->toPlainText((string) ($assignment?->getDescription() ?? ''));

        $lines = [];
        $lines[] = 'ASSIGNMENT TITLE: '.$assignmentTitle;
        $lines[] = 'ASSIGNMENT INSTRUCTIONS:'."\n".('' !== $assignmentInstructions ? $assignmentInstructions : '(none)');

        if ('' !== $rubric) {
            $lines[] = 'RUBRIC (teacher):'."\n".$rubric;
        }
        if ('' !== $teacherNotes) {
            $lines[] = 'TEACHER NOTES:'."\n".$teacherNotes;
        }

        if ('' !== trim($studentText)) {
            $lines[] = 'STUDENT SUBMISSION (TEXT):'."\n".$this->safeTruncateText($studentText, 12000);
        } else {
            $lines[] = 'STUDENT SUBMISSION (TEXT): (empty)';
        }

        if ('' !== trim($fileText)) {
            $lines[] = 'STUDENT SUBMISSION (FILE TEXT):'."\n".$this->safeTruncateText($fileText, 12000);
        } elseif ($hasFile) {
            if ('document' === $mode) {
                $lines[] = 'STUDENT SUBMISSION (ATTACHED FILE): A file is attached as input_file. You MUST read it and grade its content.';
            } else {
                $lines[] = 'STUDENT SUBMISSION (ATTACHED FILE): A file is attached but its content was not inlined as text.';
            }
        }

        if ('document' === $mode) {
            $lines[] = "DOCUMENT MODE NOTE:\n- The attached PDF is available as input_file.\n- If the PDF is unreadable or blank, say so explicitly.\n- Do NOT claim you cannot access the file if you received it as input_file.";
        }

        return implode("\n\n", $lines);
    }

    private function getSubmissionFileMeta(CStudentPublication $submission): array
    {
        $node = $submission->getResourceNode();
        if (null === $node) {
            return ['hasFile' => false];
        }

        $rf = $node->getFirstResourceFile();
        if (null === $rf) {
            return ['hasFile' => false];
        }

        $filename = (string) ($rf->getOriginalName() ?? 'submission.bin');
        $mimeType = (string) ($rf->getMimeType() ?? 'application/octet-stream');
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        $size = 0;
        if (method_exists($rf, 'getSize')) {
            $size = (int) ($rf->getSize() ?? 0);
        }

        $this->dbg('Submission file meta', [
            'nodeId' => method_exists($node, 'getId') ? $node->getId() : null,
            'resourceFileId' => method_exists($rf, 'getId') ? $rf->getId() : null,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'ext' => $ext,
            'size' => $size,
        ]);

        return [
            'hasFile' => true,
            'filename' => $filename,
            'mimeType' => $mimeType,
            'extension' => $ext,
            'fileSize' => $size,
        ];
    }

    /**
     * @return array{0:string,1:string,2:string,3:?string} [filename, mimeType, bytes, errorMessage]
     */
    private function readSubmissionFileBytes(CStudentPublication $submission): array
    {
        $node = $submission->getResourceNode();
        if (null === $node) {
            return ['', 'application/octet-stream', '', 'Submission resource node is missing.'];
        }

        $rf = $node->getFirstResourceFile();
        if (null === $rf) {
            return ['', 'application/octet-stream', '', 'No file found on this submission.'];
        }

        $filename = (string) ($rf->getOriginalName() ?? 'submission.bin');
        $mimeType = (string) ($rf->getMimeType() ?? 'application/octet-stream');

        try {
            $pathKey = $this->resourceNodeRepository->getFilename($rf);

            if (!\is_string($pathKey) || '' === trim($pathKey)) {
                $this->dbg('resolveUri returned empty key', [
                    'filename' => $filename,
                    'mimeType' => $mimeType,
                    'resourceFileId' => method_exists($rf, 'getId') ? $rf->getId() : null,
                ]);

                return [$filename, $mimeType, '', 'Failed to resolve file storage key.'];
            }

            $this->dbg('Reading file from storage', [
                'key' => $pathKey,
                'filename' => $filename,
                'mimeType' => $mimeType,
            ]);

            // read() is OK for our size limits; for safety, we log lengths/hashes only.
            $bytes = (string) $this->resourceNodeRepository->getFileSystem()->read($pathKey);

            if ('' === $bytes) {
                $this->dbg('Read returned empty bytes', [
                    'key' => $pathKey,
                    'filename' => $filename,
                ]);

                return [$filename, $mimeType, '', 'File is empty or could not be read.'];
            }

            return [$filename, $mimeType, $bytes, null];
        } catch (Throwable $e) {
            $this->dbg('Failed reading submission file', [
                'filename' => $filename,
                'mimeType' => $mimeType,
                'error' => $e->getMessage(),
            ]);

            return [$filename, $mimeType, '', 'Failed to read submission file.'];
        }
    }

    private function isPdfForDocumentProcess(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ('pdf' === $ext) {
            return true;
        }

        return 'application/pdf' === strtolower(trim($mimeType));
    }

    private function looksLikePdfBytes(string $bytes): bool
    {
        $head = ltrim(substr($bytes, 0, 64));

        return str_starts_with($head, '%PDF-');
    }

    private function isDocxFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ('docx' === $ext) {
            return true;
        }

        return str_contains(strtolower($mimeType), 'officedocument.wordprocessingml.document');
    }

    private function isPlainTextFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $mime = strtolower(trim($mimeType));

        $allowedExt = ['txt', 'md', 'markdown', 'html', 'htm', 'json', 'xml', 'yaml', 'yml', 'csv', 'log', 'ini', 'env'];
        if (\in_array($ext, $allowedExt, true)) {
            return true;
        }

        return str_starts_with($mime, 'text/');
    }

    private function isImageFile(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if (\in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'], true)) {
            return true;
        }

        return str_starts_with(strtolower(trim($mimeType)), 'image/');
    }

    private function extractDocxText(string $bytes): string
    {
        if ('' === $bytes) {
            return '';
        }

        $tmp = tempnam(sys_get_temp_dir(), 'docx_');
        if (!$tmp) {
            return '';
        }

        file_put_contents($tmp, $bytes);

        $zip = new ZipArchive();
        $ok = $zip->open($tmp);
        if (true !== $ok) {
            @unlink($tmp);

            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        if (!\is_string($xml) || '' === $xml) {
            return '';
        }

        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $xml = preg_replace('/<\/w:tr>/', "\n", $xml);

        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        $text = preg_replace("/[ \t]+\n/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim((string) $text);
    }

    private function safeDecodeTextBytes(string $bytes): string
    {
        $s = $bytes;

        if (!mb_check_encoding($s, 'UTF-8')) {
            $converted = @mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-16, UTF-16LE, UTF-16BE');
            if (\is_string($converted) && '' !== $converted) {
                $s = $converted;
            }
        }

        return trim((string) $s);
    }

    private function safeTruncateText(string $s, int $maxChars = 12000): string
    {
        $s = trim($s);
        if (mb_strlen($s) <= $maxChars) {
            return $s;
        }

        return mb_substr($s, 0, $maxChars)."\n\n[...truncated...]";
    }

    private function toPlainText(string $html): string
    {
        $s = trim($html);
        if ('' === $s) {
            return '';
        }

        $s = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $s);
        $s = preg_replace('/<\/(p|div|li|h[1-6])\s*>/i', "\n", $s);

        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);

        $s = preg_replace("/[ \t]+\n/", "\n", $s);
        $s = preg_replace("/\n{3,}/", "\n\n", $s);

        return trim($s);
    }

    private function extractSuggestedScore(string $text, float $max): ?float
    {
        if (preg_match('/\bSCORE\s*:\s*([0-9]+(?:[.,][0-9]+)?)\b/i', $text, $m)) {
            $v = (float) str_replace(',', '.', $m[1]);
            if (!is_finite($v)) {
                return null;
            }

            $v = max(0.0, min($max, $v));

            return round($v, 1);
        }

        if (preg_match('/\bSCORE\s*:\s*([0-9]+(?:[.,][0-9]+)?)\s*\/\s*([0-9]+(?:[.,][0-9]+)?)\b/i', $text, $m)) {
            $v = (float) str_replace(',', '.', $m[1]);
            $den = (float) str_replace(',', '.', $m[2]);

            if (!is_finite($v) || !is_finite($den) || $den <= 0) {
                return null;
            }

            $scaled = ($v / $den) * $max;
            $scaled = max(0.0, min($max, $scaled));

            return round($scaled, 1);
        }

        return null;
    }

    private function dbg(string $message, array $context = []): void
    {
        if (!$this->logEnabled) {
            return;
        }

        $ctx = '';
        if (!empty($context)) {
            $encoded = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (\is_string($encoded)) {
                $ctx = ' '.$this->truncateForLog($encoded, 1500);
            }
        }

        error_log(self::LOG_PREFIX.' '.$message.$ctx);
    }

    private function truncateForLog(string $s, int $maxLen): string
    {
        if (mb_strlen($s) <= $maxLen) {
            return $s;
        }

        return mb_substr($s, 0, $maxLen).'...[truncated]';
    }

    private function isDebugEnabled(): bool
    {
        $env = (string) ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '');
        $debug = (string) ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? '');
        if ('1' === $debug || 'true' === strtolower($debug)) {
            return true;
        }

        return '' !== $env && 'prod' !== strtolower($env);
    }

    /**
     * Local sanity checks to confirm the bytes represent a readable PDF with extractable text.
     * This helps determine whether the issue is inside the provider pipeline or in the document itself.
     *
     * @return array<string,mixed>
     */
    private function debugPdfSanityCheck(string $pdfBytes): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'chamilo_ai_pdf_');
        if (false === $tmp || '' === $tmp) {
            return [
                'sanityCheck' => 'failed',
                'reason' => 'tempnam failed',
            ];
        }

        $tmpPdf = $tmp.'.pdf';
        @rename($tmp, $tmpPdf);

        try {
            $written = @file_put_contents($tmpPdf, $pdfBytes);
            if (!\is_int($written) || $written <= 0) {
                return [
                    'sanityCheck' => 'failed',
                    'reason' => 'file_put_contents failed',
                ];
            }

            $result = [
                'sanityCheck' => 'ok',
                'tmpPdfBytes' => $written,
                'hasPdfSignature' => $this->looksLikePdfBytes($pdfBytes),
                'pdftotextAvailable' => $this->commandExists('pdftotext'),
                'pdfinfoAvailable' => $this->commandExists('pdfinfo'),
            ];

            if ($result['pdfinfoAvailable']) {
                $info = $this->runToStdout(['pdfinfo', $tmpPdf], 8);
                $pages = null;
                if (preg_match('/^Pages:\s+(\d+)/mi', $info, $m)) {
                    $pages = (int) $m[1];
                }
                $result['pdfPages'] = $pages;
            }

            if ($result['pdftotextAvailable']) {
                // Output to stdout using "-" output file
                $text = $this->runToStdout(['pdftotext', '-enc', 'UTF-8', $tmpPdf, '-'], 12);
                $result['pdftotextLen'] = mb_strlen(trim((string) $text));
            }

            return $result;
        } catch (Throwable $e) {
            return [
                'sanityCheck' => 'failed',
                'error' => $e->getMessage(),
            ];
        } finally {
            @unlink($tmpPdf);
        }
    }

    private function commandExists(string $cmd): bool
    {
        $p = new Process(['which', $cmd]);
        $p->setTimeout(3);
        $p->run();

        return $p->isSuccessful() && '' !== trim((string) $p->getOutput());
    }

    private function runToStdout(array $cmd, int $timeoutSeconds): string
    {
        $process = new Process($cmd);
        $process->setTimeout($timeoutSeconds);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->dbg('Command failed', [
                'cmd' => implode(' ', $cmd),
                'error' => $process->getErrorOutput(),
            ]);

            return '';
        }

        return (string) $process->getOutput();
    }
}
