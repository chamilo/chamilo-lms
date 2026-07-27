<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20231022124700 extends AbstractMigrationChamilo
{
    private const int CONTENT_BATCH_SIZE = 1000;
    private const int DOCUMENT_BATCH_SIZE = 100;

    /**
     * @var array<string, int>
     */
    private array $courseIdsByCode = [];

    public function getDescription(): string
    {
        return 'Replace old cidReq URLs with keyset batches and a cached course map';
    }

    public function isTransactional(): bool
    {
        // Every replacement is idempotent. Committing statement by statement makes
        // this migration safely resumable on large Ricky tables.
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();
        $this->courseIdsByCode = $this->loadCourseIdsByCode();

        // Extend MySQL session timeouts to survive long processing on shared hosting.
        try {
            $this->connection->executeStatement('SET SESSION wait_timeout = 28800');
            $this->connection->executeStatement('SET SESSION interactive_timeout = 28800');
            $this->connection->executeStatement('SET SESSION net_read_timeout = 3600');
            $this->connection->executeStatement('SET SESSION net_write_timeout = 3600');
        } catch (Exception) {
            // Non-fatal: the server may not allow changing these session variables.
        }

        $updateConfigurations = [
            ['table' => 'c_tool_intro', 'fields' => ['intro_text']],
            ['table' => 'c_course_description', 'fields' => ['content']],
            ['table' => 'c_quiz', 'fields' => ['description', 'text_when_finished']],
            ['table' => 'c_quiz_question', 'fields' => ['description', 'question']],
            ['table' => 'c_quiz_answer', 'fields' => ['answer', 'comment']],
            ['table' => 'c_student_publication', 'fields' => ['description']],
            ['table' => 'c_student_publication_comment', 'fields' => ['comment']],
            ['table' => 'c_forum_post', 'fields' => ['post_text']],
            ['table' => 'c_glossary', 'fields' => ['description']],
            ['table' => 'c_survey', 'fields' => ['title', 'subtitle']],
            ['table' => 'c_survey_question', 'fields' => ['survey_question', 'survey_question_comment']],
            ['table' => 'c_survey_question_option', 'fields' => ['option_text']],
        ];

        foreach ($updateConfigurations as $configuration) {
            foreach ($configuration['fields'] as $field) {
                $this->updateContentField($configuration['table'], $field);
            }
        }

        $this->updateHtmlFiles();
    }

    private function updateContentField(string $table, string $field): void
    {
        $lastIid = 0;
        $seen = 0;
        $updated = 0;
        $startedAt = microtime(true);

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    'SELECT iid, %1$s AS content
                     FROM %2$s
                     WHERE iid > :lastIid
                       AND %1$s IS NOT NULL
                       AND %1$s <> \'\'
                       AND %1$s LIKE :needle
                     ORDER BY iid
                     LIMIT %3$d',
                    $field,
                    $table,
                    self::CONTENT_BATCH_SIZE
                ),
                [
                    'lastIid' => $lastIid,
                    'needle' => '%cidReq=%',
                ]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];

            foreach ($rows as $row) {
                ++$seen;
                $original = (string) ($row['content'] ?? '');
                $replacement = $this->replaceURLParametersInContent($original);

                if ($replacement === $original) {
                    continue;
                }

                $this->executeWithReconnect(function () use ($table, $field, $replacement, $row): void {
                    $this->connection->executeStatement(
                        "UPDATE {$table} SET {$field} = :content WHERE iid = :iid",
                        [
                            'content' => $replacement,
                            'iid' => (int) $row['iid'],
                        ]
                    );
                });
                ++$updated;
            }

            $this->getLogger()->info('cidReq database-content migration progress.', [
                'table' => $table,
                'field' => $field,
                'seen_candidates' => $seen,
                'updated' => $updated,
                'last_iid' => $lastIid,
                'elapsed_seconds' => (int) (microtime(true) - $startedAt),
            ]);
            $this->keepAlive();
        }
    }

    /**
     * Execute a callable, reconnecting once if the MySQL connection has gone away.
     */
    private function executeWithReconnect(callable $fn): void
    {
        try {
            $fn();
        } catch (Exception $exception) {
            if (str_contains($exception->getMessage(), 'server has gone away') || str_contains($exception->getMessage(), '2006')) {
                $this->connection->close();
                $this->connection->connect();
                $fn();

                return;
            }

            throw $exception;
        }
    }

    /**
     * Send a lightweight query to keep the MySQL connection alive.
     */
    private function keepAlive(): void
    {
        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (Exception) {
            $this->connection->close();
            $this->connection->connect();
        }
    }

    private function updateHtmlFiles(): void
    {
        /** @var CDocumentRepository $documentRepo */
        $documentRepo = $this->container->get(CDocumentRepository::class);

        /** @var ResourceNodeRepository $resourceNodeRepo */
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        $lastIid = 0;
        $seen = 0;
        $htmlCandidates = 0;
        $updated = 0;

        while (true) {
            $rows = $this->connection->fetchAllAssociative(
                \sprintf(
                    <<<'SQL'
SELECT iid
FROM c_document
WHERE filetype = 'file'
  AND resource_node_id IS NOT NULL
  AND iid > :lastIid
ORDER BY iid
LIMIT %d
SQL,
                    self::DOCUMENT_BATCH_SIZE
                ),
                ['lastIid' => $lastIid]
            );

            if ([] === $rows) {
                break;
            }

            $lastIid = (int) $rows[array_key_last($rows)]['iid'];

            foreach ($rows as $row) {
                ++$seen;
                $document = $documentRepo->find((int) $row['iid']);
                if (null === $document) {
                    continue;
                }

                $resourceNode = $document->getResourceNode();
                if (null === $resourceNode || !$resourceNode->hasResourceFile()) {
                    continue;
                }

                $resourceFile = $resourceNode->getResourceFiles()->first();
                if (false === $resourceFile || 'text/html' !== $resourceFile->getMimeType()) {
                    continue;
                }

                ++$htmlCandidates;

                try {
                    $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                    if (!\is_string($content) || !str_contains($content, 'cidReq=')) {
                        continue;
                    }

                    $replacement = $this->replaceURLParametersInContent($content);
                    if ($replacement === $content) {
                        continue;
                    }

                    $documentRepo->updateResourceFileContent($document, $replacement);
                    $documentRepo->update($document);
                    ++$updated;
                } catch (Exception $exception) {
                    $this->getLogger()->warning('Could not update cidReq URL in HTML document.', [
                        'document_iid' => (int) $row['iid'],
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $this->entityManager->clear();
            $this->getLogger()->info('cidReq HTML-file migration progress.', [
                'documents_seen' => $seen,
                'html_candidates' => $htmlCandidates,
                'updated' => $updated,
                'last_iid' => $lastIid,
            ]);
            $this->keepAlive();
        }
    }

    /**
     * @return array<string, int>
     */
    private function loadCourseIdsByCode(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, code FROM course WHERE code IS NOT NULL AND code <> \'\' ORDER BY id DESC'
        );

        $map = [];
        foreach ($rows as $row) {
            $code = (string) $row['code'];
            if (!isset($map[$code])) {
                $map[$code] = (int) $row['id'];
            }
        }

        return $map;
    }

    private function replaceURLParametersInContent(string $content): string
    {
        $pattern = '/((https?:\/\/[^\/\s]*|)\/[^?\s]+?)\?(.*?)(cidReq=([a-zA-Z0-9_]+))((?:&|&amp;)id_session=([0-9]+))?((?:&|&amp;)gidReq=([0-9]+))?(.*)/i';

        try {
            $newContent = @preg_replace_callback(
                $pattern,
                function (array $matches): string {
                    $code = $matches[5] ?? null;
                    if (null === $code || '' === $code) {
                        return $matches[0];
                    }

                    $courseId = $this->courseIdsByCode[$code] ?? null;
                    if (null === $courseId) {
                        return $matches[0];
                    }

                    $sessionId = $matches[7] ?? '0';
                    $groupId = $matches[9] ?? '0';
                    $remainingParams = $matches[10] ?? '';
                    $beforeCidReqParams = $matches[3] ?? '';
                    $newParams = "cid={$courseId}&sid={$sessionId}&gid={$groupId}";

                    if ('' !== $remainingParams) {
                        $newParams .= '&'.ltrim($remainingParams, '&amp;');
                    }

                    return str_replace(
                        '&amp;',
                        '&',
                        $matches[1].'?'.$beforeCidReqParams.$newParams
                    );
                },
                $content
            );

            if (false === $newContent || null === $newContent) {
                return $content;
            }

            return $newContent;
        } catch (Exception $exception) {
            $this->getLogger()->warning('cidReq URL replacement failed.', [
                'error' => $exception->getMessage(),
            ]);

            return $content;
        }
    }
}
