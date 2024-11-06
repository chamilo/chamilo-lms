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
    public function getDescription(): string
    {
        return 'Replace old cidReq URL path with the new version and handle updates for HTML files.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        // Configuration for content updates in the database
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
                    $updatedText = $this->replaceURLParametersInContent($originalText);
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
                    $updatedContent = $this->replaceURLParametersInContent($content);
                    if ($content !== $updatedContent) {
                        $documentRepo->updateResourceFileContent($document, $updatedContent);
                        $documentRepo->update($document);
                    }
                }
            } catch (Exception $e) {
                // Error handling for specific documents
                error_log("Error processing file for document ID {$item['iid']}: " . $e->getMessage());
            }
        }
    }

    private function replaceURLParametersInContent(string $content): string
    {
        // Pattern to find and replace cidReq, id_session, and gidReq
        $pattern = '/((https?:\/\/[^\/\s]*|)\/[^?\s]+?)\?(.*?)(cidReq=([a-zA-Z0-9_]+))((?:&|&amp;)id_session=([0-9]+))?((?:&|&amp;)gidReq=([0-9]+))?(.*)/i';

        $newContent = @preg_replace_callback(
            $pattern,
            function ($matches) {
                $code = $matches[5];

                $courseId = null;
                $sql = 'SELECT id FROM course WHERE code = :code ORDER BY id DESC LIMIT 1';
                $stmt = $this->connection->executeQuery($sql, ['code' => $code]);
                $course = $stmt->fetch();

                if ($course) {
                    $courseId = $course['id'];
                }

                if (null === $courseId) {
                    return $matches[0]; // If the courseId is not found, return the original URL.
                }

                // Ensure sid and gid are always populated
                $sessionId = isset($matches[7]) && !empty($matches[7]) ? $matches[7] : '0';
                $groupId = isset($matches[9]) && !empty($matches[9]) ? $matches[9] : '0';
                $remainingParams = isset($matches[10]) ? $matches[10] : '';

                // Prepare new URL with updated parameters
                $newParams = "cid=$courseId&sid=$sessionId&gid=$groupId";
                $beforeCidReqParams = isset($matches[3]) ? $matches[3] : '';

                // Ensure other parameters are maintained
                if (!empty($remainingParams)) {
                    $newParams .= '&' . ltrim($remainingParams, '&amp;');
                }

                $finalUrl = $matches[1] . '?' . $beforeCidReqParams . $newParams;

                return str_replace('&amp;', '&', $finalUrl); // Replace any remaining &amp; with &
            },
            $content
        );

        if (PREG_NO_ERROR !== preg_last_error()) {
            error_log('Error encountered in preg_replace_callback: ' . preg_last_error());
        }

        return $newContent;
    }
}
