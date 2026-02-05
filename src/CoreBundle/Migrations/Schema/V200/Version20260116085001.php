<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\PersonalFileRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;

use const PATHINFO_FILENAME;
use const PHP_URL_PATH;
use const PREG_SET_ORDER;

final class Version20260116085001 extends AbstractMigrationChamilo
{
    private ?CDocumentRepository $documentRepo = null;
    private ?CourseRepository $courseRepo = null;
    private ?PersonalFileRepository $personalRepo = null;
    private ?UserRepository $userRepo = null;

    /**
     * keep false to avoid creating new documents during link rewrite migrations,
     * which may generate duplicates at the root folder.
     */
    private const ENABLE_DOCUMENT_CREATION = false;

    public function getDescription(): string
    {
        return 'Migrate embed files in portfolio items and comments';
    }

    public function up(Schema $schema): void
    {
        $this->documentRepo = $this->container->get(CDocumentRepository::class);
        $this->courseRepo = $this->container->get(CourseRepository::class);
        $this->personalRepo = $this->container->get(PersonalFileRepository::class);
        $this->userRepo = $this->container->get(UserRepository::class);

        $this->entityManager->clear();

        $updateConfigurations = [
            ['table' => 'portfolio', 'field' => 'content'],
            ['table' => 'portfolio_comment', 'field' => 'content'],
        ];

        foreach ($updateConfigurations as $config) {
            $this->updateContentForUserFiles($config);
            $this->updateContentForCourseFile($config['table'], $config['field']);
        }
    }

    private function updateContentForUserFiles(array $config): void
    {
        $fields = isset($config['field']) ? [$config['field']] : ($config['fields'] ?? []);

        foreach ($fields as $field) {
            $items = $this->connection
                ->executeQuery("SELECT id, {$field} FROM {$config['table']}")
                ->fetchAllAssociative()
            ;

            foreach ($items as $item) {
                $originalText = (string) ($item[$field] ?? '');

                if ('' === trim($originalText)) {
                    continue;
                }

                $updatedText = $this->replaceUserFilePaths($originalText);

                if ($originalText !== $updatedText) {
                    $updateSql = "UPDATE {$config['table']} SET {$field} = :newText WHERE id = :id";
                    $this->connection->executeQuery($updateSql, [
                        'newText' => $updatedText,
                        'id' => (int) $item['id'],
                    ]);
                }
            }
        }
    }

    private function replaceUserFilePaths(string $content): string
    {
        $pattern = '/(src|href)=["\']?(https?:\/\/[^\/]+)?(\/app\/upload\/users\/(\d+)\/(\d+)\/my_files\/([^\/"\']+))["\']?/i';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $baseUrlWithApp = $match[2] ? $match[2].$match[3] : $match[3];
            $userId = (int) $match[5];
            $filename = (string) $match[6];

            $decodedFilename = urldecode($filename);
            $user = $this->userRepo?->find($userId);
            if (null === $user) {
                continue;
            }

            /** @var AbstractResource|null $personalFile */
            $personalFile = $this->personalRepo?->getResourceByCreatorFromTitle(
                $decodedFilename,
                $user,
                $user->getResourceNode()
            );

            if (!$personalFile) {
                continue;
            }

            $newUrl = $this->personalRepo?->getResourceFileUrl($personalFile);
            if (!empty($newUrl)) {
                $content = str_replace($baseUrlWithApp, $newUrl, $content);
                $this->write("Replaced old user file URL with new URL: {$newUrl}");
            }
        }

        return $content;
    }

    private function updateContentForCourseFile(string $tableName, string $fieldName): void
    {
        // NOTE: portfolio tables use "id" (not "iid").
        $items = $this->connection
            ->executeQuery("SELECT id, {$fieldName} FROM {$tableName}")
            ->fetchAllAssociative()
        ;

        foreach ($items as $item) {
            $originalText = (string) ($item[$fieldName] ?? '');

            if ('' === trim($originalText)) {
                continue;
            }

            $updatedText = $this->replaceCourseFilePaths($originalText);

            if ($originalText !== $updatedText) {
                $this->connection->executeQuery(
                    "UPDATE {$tableName} SET {$fieldName} = :newText WHERE id = :id",
                    [
                        'newText' => $updatedText,
                        'id' => (int) $item['id'],
                    ]
                );
            }
        }
    }

    private function replaceCourseFilePaths(string $itemDataText): string
    {
        $contentText = $itemDataText;

        $pattern = '/(src|href)=["\']((https?:\/\/[^\/]+)?(\/courses\/([^\/]+)\/document\/[^"\']+\.\w+))["\']/i';
        preg_match_all($pattern, $contentText, $matches);

        foreach (($matches[2] ?? []) as $index => $fullUrl) {
            $path = parse_url((string) $fullUrl, PHP_URL_PATH) ?: (string) $fullUrl;
            $fileName = urldecode(basename($path));
            $courseDirectory = (string) ($matches[5][$index] ?? '');

            if ('' === $courseDirectory || '' === $fileName) {
                continue;
            }

            /** @var Course|null $course */
            $course = $this->courseRepo?->findOneBy(['directory' => $courseDirectory]);
            if (!$course) {
                continue;
            }

            // Best match: ResourceFile.originalName
            $doc = $this->documentRepo?->findResourceByOriginalNameInCourse($fileName, $course);

            // Fallback: node.title (ignore visibility)
            if (!$doc) {
                $doc = $this->documentRepo?->findResourceByTitleInCourseIgnoreVisibility($fileName, $course);

                if (!$doc) {
                    $withoutExt = pathinfo($fileName, PATHINFO_FILENAME);
                    if ('' !== $withoutExt && $withoutExt !== $fileName) {
                        $doc = $this->documentRepo?->findResourceByTitleInCourseIgnoreVisibility($withoutExt, $course);
                    }
                }
            }

            if (!$doc) {
                if (self::ENABLE_DOCUMENT_CREATION) {
                    // Intentionally left disabled by default.
                    $this->write("Document creation is disabled. Skipping '{$fileName}'.");
                } else {
                    $this->write("Skipped embedded course file '{$fileName}' (document not found).");
                }

                continue;
            }

            $newUrl = $this->documentRepo?->getResourceFileUrl($doc);
            if (empty($newUrl)) {
                continue;
            }

            $replacement = (string) $matches[1][$index].'="'.$newUrl.'"';
            $contentText = preg_replace(
                '/'.preg_quote((string) $matches[0][$index], '/').'/',
                $replacement,
                $contentText,
                1
            ) ?? $contentText;

            $this->write("Replaced old course file URL with new URL: {$newUrl}");
        }

        return $contentText;
    }
}
