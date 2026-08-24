<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use Exception;

final class Version20241003120000 extends AbstractMigrationChamilo
{
    private const int CONTENT_BATCH_SIZE = 500;
    private const int DOCUMENT_BATCH_SIZE = 250;

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
        $fallbackUserId = $fallbackUser?->getId();

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
            $this->updateContent($config, $fallbackUserId, $personalRepo);
        }

        // Process the HTML files and update paths
        $this->updateHtmlFiles($fallbackUserId, $personalRepo);
    }

    private function updateContent(
        array $config,
        ?int $fallbackUserId,
        PersonalFileRepository $personalRepo
    ): void {
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

                    $content = $item[$field];

                    if (!\is_string($content) || '' === trim($content)) {
                        continue;
                    }

                    $updatedContent = $this->processContentUrls(
                        $content,
                        $fallbackUserId,
                        $personalRepo
                    );

                    if ($content === $updatedContent) {
                        continue;
                    }

                    $updateSql = \sprintf(
                        'UPDATE %s SET %s = :newContent WHERE iid = :id',
                        $config['table'],
                        $field
                    );

                    $this->connection->executeStatement(
                        $updateSql,
                        [
                            'newContent' => $updatedContent,
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

    private function updateHtmlFiles(
        ?int $fallbackUserId,
        PersonalFileRepository $personalRepo
    ): void {
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

                    $updatedContent = $this->processContentUrls(
                        $content,
                        $fallbackUserId,
                        $personalRepo
                    );

                    if ($content === $updatedContent) {
                        continue;
                    }

                    $documentRepo->updateResourceFileContent($document, $updatedContent);
                    $documentRepo->update($document);
                } catch (Exception $e) {
                    error_log(
                        "Error processing file for document ID {$iid}: ".$e->getMessage()
                    );
                }
            }

            unset($items);

            $this->entityManager->clear();
            gc_collect_cycles();
        }
    }

    private function processContentUrls(
        string $content,
        ?int $fallbackUserId,
        PersonalFileRepository $personalRepo
    ): string {
        $pattern = '/(href|src)="[^"]*\/app\/upload\/users\/(\d+)\/(\d+)\/my_files\/([^\/"]+)"/i';

        $result = preg_replace_callback(
            $pattern,
            function ($matches) use ($fallbackUserId, $personalRepo) {
                $attribute = $matches[1];
                $userId = (int) $matches[3];
                $filename = urldecode($matches[4]);

                error_log("Processing file: $filename for userId: $userId");

                /** @var User|null $user */
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->find($userId)
                ;

                if (!$user && null !== $fallbackUserId) {
                    /** @var User|null $user */
                    $user = $this->entityManager
                        ->getRepository(User::class)
                        ->find($fallbackUserId)
                    ;

                    if ($user) {
                        error_log(
                            "User with ID $userId not found, using fallbackUser"
                        );
                    }
                }

                if (!$user) {
                    return $matches[0];
                }

                $personalFile = $personalRepo->getResourceByCreatorFromTitle(
                    $filename,
                    $user,
                    $user->getResourceNode()
                );

                if (null !== $personalFile) {
                    $newUrl = $personalRepo->getResourceFileUrl($personalFile);

                    if (!empty($newUrl)) {
                        error_log("Replaced URL for $filename: $newUrl");

                        return "{$attribute}=\"{$newUrl}\"";
                    }
                }

                return $matches[0];
            },
            $content
        );

        return $result ?? $content;
    }
}
