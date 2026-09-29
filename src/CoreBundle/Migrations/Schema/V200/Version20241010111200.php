<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20241010111200 extends AbstractMigrationChamilo
{
    private const int CONTENT_BATCH_SIZE = 500;
    private const int DOCUMENT_BATCH_SIZE = 250;

    public function getDescription(): string
    {
        return 'Update document and link URLs in HTML content blocks and replace old legacy paths with new resource paths';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        // Define the content fields to update
        $updateConfigurations = [
            ['table' => 'c_tool_intro', 'field' => 'intro_text'],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_quiz', 'fields' => ['description', 'text_when_finished']],
            ['table' => 'c_quiz_question', 'fields' => ['description', 'question']],
            ['table' => 'c_quiz_answer', 'fields' => ['answer', 'comment']],
            ['table' => 'c_student_publication', 'field' => 'description'],
            ['table' => 'c_student_publication_comment', 'field' => 'comment'],
            ['table' => 'c_forum_post', 'field' => 'post_text'],
            ['table' => 'c_glossary', 'field' => 'description'],
            ['table' => 'c_survey', 'fields' => ['title', 'subtitle']],
            ['table' => 'c_survey_question', 'fields' => ['survey_question', 'survey_question_comment']],
            ['table' => 'c_survey_question_option', 'field' => 'option_text'],
        ];

        $documentRepo = $this->container->get(CDocumentRepository::class);

        foreach ($updateConfigurations as $config) {
            $this->updateContent($config, $documentRepo);
        }

        $this->updateDocumentLinks();
    }

    /**
     * Updates content fields with new URLs.
     *
     * @param mixed $documentRepo
     */
    private function updateContent(array $config, $documentRepo): void
    {
        $fields = isset($config['field']) ? [$config['field']] : $config['fields'] ?? [];

        foreach ($fields as $field) {
            $lastIid = 0;

            while (true) {
                $items = $this->connection->fetchAllAssociative(
                    \sprintf(
                        'SELECT iid, %s
                           FROM %s
                          WHERE iid > :lastIid
                          ORDER BY iid
                          LIMIT %d',
                        $field,
                        $config['table'],
                        self::CONTENT_BATCH_SIZE
                    ),
                    ['lastIid' => $lastIid]
                );

                if ([] === $items) {
                    break;
                }

                foreach ($items as $item) {
                    $iid = (int) $item['iid'];
                    $lastIid = $iid;

                    $originalText = $item[$field];

                    if (!\is_string($originalText) || '' === trim($originalText)) {
                        continue;
                    }

                    $updatedText = $this->replaceOldURLs(
                        $originalText,
                        $documentRepo
                    );

                    if ($originalText === $updatedText) {
                        continue;
                    }

                    $updateSql = \sprintf(
                        'UPDATE %s SET %s = :newText WHERE iid = :id',
                        $config['table'],
                        $field
                    );

                    $this->connection->executeStatement(
                        $updateSql,
                        [
                            'newText' => $updatedText,
                            'id' => $iid,
                        ]
                    );
                }

                unset($items);

                $this->entityManager->clear();
                gc_collect_cycles();
            }
        }
    }

    /**
     * Updates HTML content in document files by replacing old URLs with new resource paths.
     */
    private function updateDocumentLinks(): void
    {
        $documentRepo = $this->container->get(CDocumentRepository::class);
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        $lastIid = 0;

        while (true) {
            $items = $this->connection->fetchAllAssociative(
                \sprintf(
                    "SELECT iid
                       FROM c_document
                      WHERE filetype = 'file'
                        AND iid > :lastIid
                      ORDER BY iid
                      LIMIT %d",
                    self::DOCUMENT_BATCH_SIZE
                ),
                ['lastIid' => $lastIid]
            );

            if ([] === $items) {
                break;
            }

            foreach ($items as $item) {
                $iid = (int) $item['iid'];
                $lastIid = $iid;

                /** @var CDocument|null $document */
                $document = $documentRepo->find($iid);
                if (!$document) {
                    continue;
                }

                $resourceNode = $document->getResourceNode();
                if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                    continue;
                }

                $resourceFile = $resourceNode->getResourceFiles()->first();
                if (!$resourceFile || 'text/html' !== $resourceFile->getMimeType()) {
                    continue;
                }

                try {
                    $content = $resourceNodeRepo->getResourceNodeFileContent(
                        $resourceNode
                    );

                    if (!\is_string($content) || '' === trim($content)) {
                        continue;
                    }

                    $updatedContent = $this->replaceOldURLs(
                        $content,
                        $documentRepo
                    );

                    if ($content === $updatedContent) {
                        continue;
                    }

                    $documentRepo->updateResourceFileContent(
                        $document,
                        $updatedContent
                    );
                    $documentRepo->update($document);
                } catch (Exception $e) {
                    // Keep the migration tolerant of unreadable legacy files.
                }
            }

            unset($items);

            $this->entityManager->clear();
            gc_collect_cycles();
        }
    }

    /**
     * Replace old URLs with new Vue.js resource paths.
     *
     * @param mixed $documentRepo
     */
    private function replaceOldURLs(string $content, $documentRepo): string
    {
        // Replace document URLs
        $content = $this->replaceOldDocumentURLs($content, $documentRepo);

        // Replace link URLs
        return $this->replaceOldLinkURLs($content);
    }

    /**
     * Replace old document URLs with the new relative paths.
     *
     * @param mixed $documentRepo
     */
    private function replaceOldDocumentURLs(string $content, $documentRepo): string
    {
        $pattern = '/(href|src)=["\'](https?:\/\/[^\/]+)?\/main\/document\/(document\.php|show_content\.php|showinframes\.php)\?([^"\']*)["\']/i';

        return preg_replace_callback($pattern, function ($matches) use ($documentRepo) {
            $attribute = $matches[1];
            $params = str_replace('&amp;', '&', $matches[4]);

            parse_str($params, $parsedParams);
            $documentId = $parsedParams['id'] ?? null;
            $courseId = $parsedParams['cid'] ?? null;
            $sessionId = $parsedParams['id_session'] ?? $parsedParams['sid'] ?? '0';
            $groupId = $parsedParams['gidReq'] ?? $parsedParams['gid'] ?? '0';

            if (!$courseId && isset($parsedParams['cidReq'])) {
                $courseCode = $parsedParams['cidReq'];
                $sql = 'SELECT id FROM course WHERE code = :code ORDER BY id DESC LIMIT 1';
                $stmt = $this->connection->executeQuery($sql, ['code' => $courseCode]);
                $course = $stmt->fetchAssociative();

                if ($course) {
                    $courseId = $course['id'];
                }
            }

            if ($documentId && $courseId) {
                $documentData = $this->connection->fetchAssociative(
                    'SELECT iid, filetype, resource_node_id
                       FROM c_document
                      WHERE iid = :documentId',
                    ['documentId' => $documentId]
                );

                if ($documentData) {
                    if ('folder' === $documentData['filetype']) {
                        $newUrl = $this->generateFolderUrl((int) $documentData['resource_node_id'], (int) $courseId, $sessionId, $groupId);
                    } else {
                        $document = $documentRepo->find($documentId);
                        $newUrl = $documentRepo->getResourceFileUrl($document);
                    }

                    return \sprintf('%s="%s"', $attribute, $newUrl);
                }
            } elseif ($courseId) {
                $sql = 'SELECT resource_node_id FROM course WHERE id = :courseId';
                $result = $this->connection->executeQuery($sql, ['courseId' => $courseId]);
                $course = $result->fetchAssociative();

                if ($course && isset($course['resource_node_id'])) {
                    $newUrl = $this->generateFolderUrl((int) $course['resource_node_id'], (int) $courseId, $sessionId, $groupId);

                    return \sprintf('%s="%s"', $attribute, $newUrl);
                }
            }

            return $matches[0];
        }, $content);
    }

    /**
     * Replace old link URLs.
     */
    private function replaceOldLinkURLs(string $content): string
    {
        $pattern = '/(href|src)=["\'](https?:\/\/[^\/]+)?\/main\/link\/link\.php\?([^"\']*)["\']/i';

        return preg_replace_callback($pattern, function ($matches) {
            $attribute = $matches[1];
            $params = str_replace('&amp;', '&', $matches[3]);
            parse_str($params, $parsedParams);

            $courseId = isset($parsedParams['cid']) ? (int) $parsedParams['cid'] : null;
            $sessionId = $parsedParams['id_session'] ?? $parsedParams['sid'] ?? '0';
            $groupId = $parsedParams['gidReq'] ?? $parsedParams['gid'] ?? '0';

            if (!$courseId && isset($parsedParams['cidReq'])) {
                $courseCode = $parsedParams['cidReq'];
                $courseId = $this->getCourseIdFromCode($courseCode);
            }

            if ($courseId) {
                $sql = 'SELECT resource_node_id FROM course WHERE id = :courseId';
                $result = $this->connection->executeQuery($sql, ['courseId' => $courseId]);
                $course = $result->fetchAssociative();

                if ($course && isset($course['resource_node_id'])) {
                    $newUrl = $this->generateLinkUrl((int) $course['resource_node_id'], (int) $courseId, $sessionId, $groupId);

                    return \sprintf('%s="%s"', $attribute, $newUrl);
                }
            }

            return $matches[0];
        }, $content);
    }

    /**
     * Generate the relative URL for link-type documents.
     */
    private function generateLinkUrl(int $resourceNodeId, int $courseId, string $sessionId, string $groupId): string
    {
        return \sprintf('/resources/links/%d/?cid=%d&sid=%s&gid=%s', $resourceNodeId, $courseId, $sessionId, $groupId);
    }

    /**
     * Retrieves the course ID from the course code.
     * Returns null if the course is not found.
     */
    private function getCourseIdFromCode(string $courseCode): ?int
    {
        $sql = 'SELECT id FROM course WHERE code = :code ORDER BY id DESC LIMIT 1';
        $stmt = $this->connection->executeQuery($sql, ['code' => $courseCode]);
        $course = $stmt->fetchAssociative();

        return $course ? (int) $course['id'] : null;
    }

    /**
     * Generate the relative URL for folder-type documents using resource_node_id.
     */
    private function generateFolderUrl(int $resourceNodeId, int $courseId, string $sessionId, string $groupId): string
    {
        return \sprintf('/resources/document/%d/?cid=%d&sid=%s&gid=%s', $resourceNodeId, $courseId, $sessionId, $groupId);
    }
}
