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
            $sql = "SELECT iid, {$field} FROM {$config['table']}";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            foreach ($items as $item) {
                $originalText = $item[$field];
                if (is_string($originalText) && trim($originalText) !== '') {
                    $updatedText = $this->replaceGifWithPng($originalText);
                    if ($originalText !== $updatedText) {
                        $updateSql = "UPDATE {$config['table']} SET {$field} = :newText WHERE iid = :id";
                        $this->connection->executeQuery($updateSql, ['newText' => $updatedText, 'id' => $item['iid']]);
                    }
                }
            }
        }
    }

    private function updateHtmlFiles(): void
    {
        $sql = "SELECT iid, resource_node_id FROM c_document WHERE filetype = 'file'";
        $result = $this->connection->executeQuery($sql);
        $items = $result->fetchAllAssociative();

        $documentRepo = $this->container->get(CDocumentRepository::class);
        $resourceNodeRepo = $this->container->get(ResourceNodeRepository::class);

        foreach ($items as $item) {
            /** @var CDocument $document */
            $document = $documentRepo->find($item['iid']);
            if (!$document) {
                continue;
            }

            $resourceNode = $document->getResourceNode();
            if (!$resourceNode || !$resourceNode->hasResourceFile()) {
                continue;
            }

            $resourceFile = $resourceNode->getResourceFiles()->first();
            if (!$resourceFile || $resourceFile->getMimeType() !== 'text/html') {
                continue;
            }

            try {
                $content = $resourceNodeRepo->getResourceNodeFileContent($resourceNode);
                if (is_string($content) && trim($content) !== '') {
                    $updatedContent = $this->replaceGifWithPng($content);
                    if ($content !== $updatedContent) {
                        $documentRepo->updateResourceFileContent($document, $updatedContent);
                        $documentRepo->update($document);
                    }
                }
            } catch (Exception $e) {
               // error_log("Error processing file for document ID {$item['iid']}: " . $e->getMessage());
            }
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
