<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20241003120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update HTML content blocks and files to replace old user paths by fallbackUser paths for deleted users.';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        $userRepo = $this->container->get(UserRepository::class);
        $personalRepo = $this->container->get(PersonalFileRepository::class);
        $fallbackUser = $userRepo->findOneBy(['status' => User::ROLE_FALLBACK], ['id' => 'ASC']);

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

        // Process the tables and update the paths in the content
        foreach ($updateConfigurations as $config) {
            $this->updateContent($config, $fallbackUser, $personalRepo);
        }

        // Process the HTML files and update paths
        $this->updateHtmlFiles($fallbackUser, $personalRepo);
    }

    private function updateContent(array $config, $fallbackUser, $personalRepo): void
    {
        $fields = isset($config['field']) ? [$config['field']] : $config['fields'] ?? [];

        foreach ($fields as $field) {
            $sql = "SELECT iid, {$field} FROM {$config['table']}";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();

            foreach ($items as $item) {
                $content = $item[$field];
                if (is_string($content) && trim($content) !== '') {
                    // Process URLs in the content
                    $updatedContent = $this->processContentUrls($content, $fallbackUser, $personalRepo);
                    if ($content !== $updatedContent) {
                        $updateSql = "UPDATE {$config['table']} SET {$field} = :newContent WHERE iid = :id";
                        $this->connection->executeQuery($updateSql, ['newContent' => $updatedContent, 'id' => $item['iid']]);
                    }
                }
            }
        }
    }

    private function updateHtmlFiles($fallbackUser, $personalRepo): void
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
                    // Process URLs in the HTML content
                    $updatedContent = $this->processContentUrls($content, $fallbackUser, $personalRepo);
                    if ($content !== $updatedContent) {
                        $documentRepo->updateResourceFileContent($document, $updatedContent);
                        $documentRepo->update($document);
                    }
                }
            } catch (Exception $e) {
                error_log("Error processing file for document ID {$item['iid']}: " . $e->getMessage());
            }
        }
    }

    private function processContentUrls(string $content, $fallbackUser, $personalRepo): string
    {
        // Define the regular expression pattern to match URLs containing "/app/upload/users/"
        $pattern = '/(href|src)="[^"]*\/app\/upload\/users\/(\d+)\/(\d+)\/my_files\/([^\/"]+)"/i';

        // Use a callback function to process each matched URL
        return preg_replace_callback($pattern, function ($matches) use ($fallbackUser, $personalRepo) {
            $attribute = $matches[1];      // Capture whether it's a `href` or `src`
            $folderId = (int)$matches[2];  // Capture the first digit of the userId (folderId)
            $userId = (int)$matches[3];    // Capture the full userId
            $filename = urldecode($matches[4]);  // Decode the filename

            error_log("Processing file: $filename for userId: $userId (Folder ID: $folderId)");

            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                // If the user doesn't exist, use the fallback user
                $user = $fallbackUser;
                error_log("User with ID $userId not found, using fallbackUser");
            }

            // Search for the personal file by name and creator (user)
            $personalFile = $personalRepo->getResourceByCreatorFromTitle($filename, $user, $user->getResourceNode());
            if ($personalFile !== null) {
                $newUrl = $personalRepo->getResourceFileUrl($personalFile);
                if (!empty($newUrl)) {
                    error_log("Replaced URL for $filename: $newUrl");
                    return "{$attribute}=\"{$newUrl}\"";
                }
            }

            // Return the original URL if no file was found
            return $matches[0];
        }, $content);
    }
}
