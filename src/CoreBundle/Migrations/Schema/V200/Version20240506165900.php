<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Schema\Schema;

use const PREG_SET_ORDER;

final class Version20240506165900 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Update HTML content blocks to replace old file paths with resource links to personal documents (images, videos, audio) in course tools';
    }

    public function up(Schema $schema): void
    {
        $this->entityManager->clear();

        $updateConfigurations = [
            ['table' => 'c_tool_intro', 'field' => 'intro_text'],
            ['table' => 'c_course_description', 'field' => 'content'],
            ['table' => 'c_quiz', 'fields' => ['description', 'text_when_finished']],
            ['table' => 'c_quiz_question', 'fields' => ['description', 'question']],
            ['table' => 'c_quiz_answer', 'fields' => ['answer', 'comment']],
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

        foreach ($updateConfigurations as $config) {
            $this->updateContent($config);
        }
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
                if (!empty($originalText)) {
                    $updatedText = $this->replaceOldURLsWithNew($originalText);
                    if ($originalText !== $updatedText) {
                        $updateSql = "UPDATE {$config['table']} SET {$field} = :newText WHERE iid = :id";
                        $this->connection->executeQuery($updateSql, ['newText' => $updatedText, 'id' => $item['iid']]);
                    }
                }
            }
        }
    }

    private function replaceOldURLsWithNew(string $content): string
    {
        $personalRepo = $this->container->get(PersonalFileRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $pattern = '/(src|href)=["\']?(https?:\/\/[^\/]+)?(\/app\/upload\/users\/(\d+)\/(\d+)\/my_files\/([^\/"\']+))["\']?/i';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $attribute = $match[1];
            $baseUrlWithApp = $match[2] ? $match[2].$match[3] : $match[3];
            $folderId = (int) $match[4];
            $userId = (int) $match[5];
            $filename = $match[6];

            // Decode the filename to handle special characters
            $decodedFilename = urldecode($filename);
            $user = $userRepo->find($userId);
            if (null !== $user) {
                $personalFile = $personalRepo->getResourceByCreatorFromTitle($decodedFilename, $user, $user->getResourceNode());
                if ($personalFile) {
                    $newUrl = $personalRepo->getResourceFileUrl($personalFile);
                    if ($newUrl) {
                        $content = str_replace($baseUrlWithApp, $newUrl, $content);
                        error_log("Replaced old URL: {$baseUrlWithApp} with new URL: {$newUrl}");
                    }
                }
            }
        }

        return $content;
    }
}
