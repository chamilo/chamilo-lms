<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

use const PATHINFO_FILENAME;
use const PHP_URL_PATH;

final class Version20230913162700 extends AbstractMigrationChamilo
{
    /**
     * When enabled, missing embedded files referenced in HTML may be created as new documents.
     * This is the "original behavior", but now guarded to avoid duplicates.
     */
    private const ENABLE_DOCUMENT_CREATION = true;

    /**
     * Deterministic filesystem search only:
     * - Search in referenced course dir and/or current course dir (no global recursion).
     */
    private const ENABLE_DETERMINISTIC_FS_SEARCH = true;

    public function getDescription(): string
    {
        return 'Replace old document path by resource file path';
    }

    public function up(Schema $schema): void
    {
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        $q = $this->entityManager->createQuery('SELECT c FROM Chamilo\CoreBundle\Entity\Course c');
        $updateConfigurations = [
            ['table' => 'c_tool_intro', 'field' => 'intro_text'],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_quiz', 'fields' => ['description', 'text_when_finished']],
            ['table' => 'c_quiz_question', 'fields' => ['description', 'question']],
            ['table' => 'c_quiz_answer', 'fields' => ['answer', 'comment']],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_student_publication', 'field' => 'description'],
            ['table' => 'c_student_publication_comment', 'field' => 'comment'],
            ['table' => 'c_forum_category', 'field' => 'cat_comment'],
            ['table' => 'c_forum_forum', 'field' => 'forum_comment'],
            ['table' => 'c_forum_post', 'field' => 'post_text'],
            ['table' => 'c_glossary', 'field' => 'description'],
            ['table' => 'c_survey', 'fields' => ['title', 'subtitle']],
            ['table' => 'c_survey_question', 'fields' => ['survey_question', 'survey_question_comment']],
            ['table' => 'c_survey_question_option', 'field' => 'option_text'],
        ];

        /** @var Course $course */
        foreach ($q->toIterable() as $course) {
            $courseDirectory = (string) $course->getDirectory();

            if ('' === $courseDirectory) {
                continue;
            }

            foreach ($updateConfigurations as $config) {
                $this->updateContent($config, $course, $documentRepo);
            }

            $this->updateHtmlContent($course, $documentRepo, $resourceNodeRepo);
        }
    }

    private function updateContent(array $config, Course $course, CDocumentRepository $documentRepo): void
    {
        $courseId = (int) $course->getId();
        $courseDirectory = (string) $course->getDirectory();

        if (isset($config['field'])) {
            $fields = [$config['field']];
        } elseif (isset($config['fields'])) {
            $fields = (array) $config['fields'];
        } else {
            throw new Exception('No field or fields specified for updating.');
        }

        foreach ($fields as $field) {
            $sql = "SELECT iid, {$field} FROM {$config['table']} WHERE c_id = {$courseId}";
            $items = $this->connection->executeQuery($sql)->fetchAllAssociative();

            foreach ($items as $item) {
                $originalText = (string) ($item[$field] ?? '');
                if ('' === trim($originalText)) {
                    continue;
                }

                $updatedText = $this->replaceOldURLsWithNew($originalText, $courseDirectory, $course, $documentRepo);

                if ($originalText !== $updatedText) {
                    $sql = "UPDATE {$config['table']} SET {$field} = :newText WHERE iid = :id";
                    $this->connection->executeQuery($sql, [
                        'newText' => $updatedText,
                        'id' => (int) $item['iid'],
                    ]);
                }
            }
        }
    }

    private function updateHtmlContent(
        Course $course,
        CDocumentRepository $documentRepo,
        ResourceNodeRepository $resourceNodeRepo
    ): void {
        $courseId = (int) $course->getId();
        $courseDirectory = (string) $course->getDirectory();
        $sql = "SELECT iid, c_id FROM c_document WHERE filetype = 'file'";
        $items = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($items as $item) {
            if ((int) ($item['c_id'] ?? 0) !== $courseId) {
                continue;
            }

            /** @var CDocument|null $document */
            $document = $documentRepo->find((int) $item['iid']);
            if (!$document) {
                continue;
            }

            $resourceNode = $document->getResourceNode();
            if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            if (!$resourceFile) {
                continue;
            }

            if ('text/html' !== $resourceFile->getMimeType()) {
                continue;
            }

            try {
                $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                $updatedContent = $this->replaceOldURLsWithNew($content, $courseDirectory, $course, $documentRepo);

                if ($content !== $updatedContent) {
                    $documentRepo->updateResourceFileContent($document, $updatedContent);
                    $documentRepo->update($document);
                }
            } catch (Exception $e) {
                error_log('[MIGRATION] HTML processing failed for document IID='.$item['iid'].' in course_id='.$courseId.': '.$e->getMessage());
            }
        }
    }

    /**
     * Replace old /courses/<dir>/document/... links with C2 resource file URLs.
     * If enabled, missing documents may be created from filesystem (guarded to avoid duplicates).
     */
    private function replaceOldURLsWithNew(
        string $itemDataText,
        string $courseDirectory,
        Course $course,
        CDocumentRepository $documentRepo
    ): string {
        $contentText = $itemDataText;

        // Capture:
        // 1) attribute (src|href)
        // 2) full URL or path
        // 4) courseDirectory in URL
        // 5) relative path after /document/
        $pattern = '/(src|href)=["\']((https?:\/\/[^\/]+)?\/courses\/([^\/]+)\/document\/([^"\']+))["\']/i';
        preg_match_all($pattern, $contentText, $matches);

        foreach (($matches[2] ?? []) as $index => $fullUrl) {
            $path = parse_url((string) $fullUrl, PHP_URL_PATH) ?: (string) $fullUrl;

            $urlCourseDir = (string) ($matches[4][$index] ?? '');
            $relativeDocPath = (string) ($matches[5][$index] ?? '');
            $fileName = urldecode(basename($path));

            if ('' === $fileName) {
                continue;
            }

            // 1) Strong DB lookup by original_name inside the CURRENT course.
            // This avoids creating duplicates when the same file already exists (even in a folder).
            $doc = $this->findDocInCourseByOriginalName($course, $fileName, $documentRepo);

            // 2) Fallbacks to existing repository helpers (keep behavior close to current code).
            if (!$doc) {
                $doc = $documentRepo->findResourceByOriginalNameInCourse($fileName, $course);
            }

            if (!$doc) {
                $doc = $documentRepo->findResourceByTitleInCourseIgnoreVisibility($fileName, $course);

                if (!$doc) {
                    $withoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                    if ('' !== $withoutExt && $withoutExt !== $fileName) {
                        $doc = $documentRepo->findResourceByTitleInCourseIgnoreVisibility($withoutExt, $course);
                    }
                }
            }

            // 3) Create missing document (optional, guarded).
            if (!$doc) {
                if (!self::ENABLE_DOCUMENT_CREATION) {
                    error_log("[MIGRATION] Skipped embedded file '{$fileName}' (document not found; creation disabled).");

                    continue;
                }

                $foundPath = $this->findEmbeddedFileDeterministically(
                    $courseDirectory,
                    $urlCourseDir,
                    $relativeDocPath,
                    $fileName
                );

                if (!$foundPath) {
                    error_log("[MIGRATION] Missing embedded file '{$fileName}' (not found on disk).");

                    continue;
                }

                // Extra guard: avoid duplicates by matching original_name + size in DB.
                $size = (int) @filesize($foundPath);
                if ($size > 0) {
                    $existing = $this->findDocInCourseByOriginalNameAndSize($course, $fileName, $size, $documentRepo);
                    if ($existing) {
                        $doc = $existing;
                    }
                }

                if (!$doc) {
                    $doc = $this->createNewDocumentFromPath($course, $foundPath, $fileName, $documentRepo);
                    if (!$doc) {
                        error_log("[MIGRATION] Failed to create document for '{$fileName}'.");

                        continue;
                    }
                }
            }

            // 4) Rewrite URL
            $newUrl = $documentRepo->getResourceFileUrl($doc);
            if (empty($newUrl)) {
                continue;
            }

            $patternForReplacement = '/'.preg_quote((string) $matches[0][$index], '/').'/';
            $replacement = (string) $matches[1][$index].'="'.$newUrl.'"';

            $contentText = preg_replace($patternForReplacement, $replacement, $contentText, 1) ?? $contentText;
        }

        return $contentText;
    }

    /**
     * Deterministic search: do NOT scan all courses recursively.
     * Try to locate the file exactly in:
     * - referenced course dir + relativeDocPath
     * - referenced course dir + basename
     * - current course dir + relativeDocPath (optional).
     */
    private function findEmbeddedFileDeterministically(
        string $currentCourseDir,
        string $urlCourseDir,
        string $relativeDocPath,
        string $fileName
    ): ?string {
        if (!self::ENABLE_DETERMINISTIC_FS_SEARCH) {
            return null;
        }

        $rootPath = $this->getUpdateRootPath();

        // 1) Prefer the course referenced by URL (cross-course references)
        if ('' !== $urlCourseDir && '' !== $relativeDocPath) {
            $candidate = $rootPath.'/app/courses/'.$urlCourseDir.'/document/'.$relativeDocPath;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // 2) Fallback: referenced course dir by basename
        if ('' !== $urlCourseDir) {
            $candidate = $rootPath.'/app/courses/'.$urlCourseDir.'/document/'.$fileName;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // 3) Optional: current course dir by relative path
        if ('' !== $currentCourseDir && '' !== $relativeDocPath) {
            $candidate = $rootPath.'/app/courses/'.$currentCourseDir.'/document/'.$relativeDocPath;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Strong lookup by original_name within the given course using SQL.
     * This avoids relying solely on "title", which may differ.
     */
    private function findDocInCourseByOriginalName(
        Course $course,
        string $fileName,
        CDocumentRepository $documentRepo
    ): ?CDocument {
        $sql = '
            SELECT d.iid
            FROM c_document d
            INNER JOIN resource_file rf ON rf.resource_node_id = d.resource_node_id
            INNER JOIN resource_link rl ON rl.resource_node_id = d.resource_node_id
            WHERE rl.c_id = :cid
              AND rl.deleted_at IS NULL
              AND rf.original_name = :name
            LIMIT 1
        ';

        $iid = $this->connection->fetchOne($sql, [
            'cid' => (int) $course->getId(),
            'name' => $fileName,
        ]);

        return $iid ? $documentRepo->find((int) $iid) : null;
    }

    /**
     * Extra guard: same original_name AND size (helps prevent duplicates when creation is enabled).
     */
    private function findDocInCourseByOriginalNameAndSize(
        Course $course,
        string $fileName,
        int $size,
        CDocumentRepository $documentRepo
    ): ?CDocument {
        $sql = '
            SELECT d.iid
            FROM c_document d
            INNER JOIN resource_file rf ON rf.resource_node_id = d.resource_node_id
            INNER JOIN resource_link rl ON rl.resource_node_id = d.resource_node_id
            WHERE rl.c_id = :cid
              AND rl.deleted_at IS NULL
              AND rf.original_name = :name
              AND rf.size = :size
            LIMIT 1
        ';

        $iid = $this->connection->fetchOne($sql, [
            'cid' => (int) $course->getId(),
            'name' => $fileName,
            'size' => $size,
        ]);

        return $iid ? $documentRepo->find((int) $iid) : null;
    }

    /**
     * Create a new document in the CURRENT course from an existing filesystem path.
     * This is idempotent-ish when combined with the guards above.
     */
    private function createNewDocumentFromPath(
        Course $course,
        string $filePath,
        string $fileName,
        CDocumentRepository $documentRepo
    ): ?CDocument {
        try {
            if (!is_file($filePath)) {
                error_log("[MIGRATION] File path is not a file: {$filePath}");

                return null;
            }

            $admin = $this->getAdmin();

            $document = new CDocument();
            $document->setFiletype('file')
                ->setTitle($fileName)
                ->setComment(null)
                ->setReadonly(false)
                ->setCreator($admin)
                ->setParent($course)
                ->addCourseLink($course)
            ;

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $documentRepo->addFileFromPath($document, $fileName, $filePath);
            $documentRepo->update($document);

            error_log("[MIGRATION] Created missing document '{$fileName}' from '{$filePath}' in course_id=".$course->getId().'.');

            return $document;
        } catch (Exception $e) {
            error_log('[MIGRATION] Document creation failed: '.$e->getMessage());

            return null;
        }
    }
}
