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

final class Version20240924120200 extends AbstractMigrationChamilo
{
    private const int CONTENT_BATCH_SIZE = 500;
    private const int DOCUMENT_BATCH_SIZE = 250;

    public function getDescription(): string
    {
        return 'Update HTML content blocks to replace old CKEditor image paths with new ones and convert .gif references to .png, including HTML files in the document repository';
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

        foreach ($updateConfigurations as $config) {
            $this->updateContent($config);
        }

        $this->updateHtmlFiles();
    }

    private function updateContent(array $config): void
    {
        $fields = isset($config['field']) ? [$config['field']] : $config['fields'] ?? [];

        foreach ($fields as $field) {
            $lastIid = 0;

            while (true) {
                $sql = \sprintf(
                    'SELECT iid, %s
                       FROM %s
                      WHERE iid > :lastIid
                      ORDER BY iid
                      LIMIT %d',
                    $field,
                    $config['table'],
                    self::CONTENT_BATCH_SIZE
                );

                $items = $this->connection->fetchAllAssociative(
                    $sql,
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

                    $updatedText = $this->replaceGifWithPng($originalText);

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
            }
        }
    }

    private function updateHtmlFiles(): void
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
                    $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);

                    if (!\is_string($content) || '' === trim($content)) {
                        continue;
                    }

                    $updatedContent = $this->replaceGifWithPng($content);

                    if ($content === $updatedContent) {
                        continue;
                    }

                    $documentRepo->updateResourceFileContent($document, $updatedContent);
                    $documentRepo->update($document);
                } catch (Exception $e) {
                    // Keep the migration tolerant of unreadable legacy files.
                }
            }

            unset($items);

            // Document processing uses ORM repositories and resource graphs.
            // Release managed entities after every bounded batch.
            $this->entityManager->clear();
            gc_collect_cycles();
        }
    }

    private function replaceGifWithPng(string $content): string
    {
        $pattern = '/(src=["\'])(https?:\/\/[^\/]+\/)?(\/?web\/assets\/ckeditor\/plugins\/smiley\/images\/([a-zA-Z0-9_\-]+))\.(gif|png)(["\'])/i';

        return preg_replace_callback($pattern, function ($matches) {
            $prefix = $matches[1];
            $filename = $matches[4];
            $extension = 'png';

            return "{$prefix}/img/legacy/{$filename}.{$extension}{$matches[6]}";
        }, $content);
    }
}
