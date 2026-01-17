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
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\DBAL\Schema\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use const PHP_URL_PATH;
use const PREG_SET_ORDER;

final class Version20260116085001 extends AbstractMigrationChamilo
{
    private ?CDocumentRepository $documentRepo;
    private ?CourseRepository $courseRepo;
    private ?PersonalFileRepository $personalRepo;
    private ?UserRepository $userRepo;

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
        $fields = isset($config['field']) ? [$config['field']] : $config['fields'] ?? [];

        foreach ($fields as $field) {
            $items = $this->connection
                ->executeQuery("SELECT id, {$field} FROM {$config['table']}")
                ->fetchAllAssociative()
            ;

            foreach ($items as $item) {
                $originalText = $item[$field];

                if (!empty($originalText)) {
                    $updatedText = $this->replaceUserFilePaths($originalText);

                    if ($originalText !== $updatedText) {
                        $updateSql = "UPDATE {$config['table']} SET {$field} = :newText WHERE id = :id";
                        $this->connection->executeQuery($updateSql, ['newText' => $updatedText, 'id' => $item['id']]);
                    }
                }
            }
        }
    }

    private function replaceUserFilePaths(string $content): string
    {
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
            $user = $this->userRepo->find($userId);
            if (null !== $user) {
                /** @var AbstractResource $personalFile */
                $personalFile = $this->personalRepo->getResourceByCreatorFromTitle(
                    $decodedFilename,
                    $user,
                    $user->getResourceNode()
                );

                if (!$personalFile) {
                    continue;
                }

                $newUrl = $this->personalRepo->getResourceFileUrl($personalFile);

                if ($newUrl) {
                    $content = str_replace($baseUrlWithApp, $newUrl, $content);

                    $this->write("Replaced old URL: {$baseUrlWithApp} with new URL: {$newUrl}");
                }
            }
        }

        return $content;
    }

    private function updateContentForCourseFile($tableName, $fieldName): void
    {
        $items = $this->connection
            ->executeQuery("SELECT id, $fieldName FROM $tableName")
            ->fetchAllAssociative()
        ;

        foreach ($items as $item) {
            $originalText = $item[$fieldName];

            if (empty($originalText)) {
                continue;
            }

            $updatedText = $this->replaceCourseFilePaths($originalText);

            if ($originalText !== $updatedText) {
                $this->connection->executeQuery(
                    "UPDATE $tableName SET $fieldName = :newText WHERE iid = :id",
                    ['newText' => $updatedText, 'id' => $item['iid']]
                );
            }
        }
    }

    private function replaceCourseFilePaths($itemDataText): string
    {
        $contentText = $itemDataText;
        $specificCoursePattern = '/(src|href)=["\']((https?:\/\/[^\/]+)?(\/courses\/([^\/]+)\/document\/[^"\']+\.\w+))["\']/i';

        preg_match_all($specificCoursePattern, $contentText, $matches);

        foreach ($matches[2] as $index => $fullUrl) {
            $videoPath = parse_url($fullUrl, PHP_URL_PATH) ?: $fullUrl;
            $fileName = basename($videoPath);

            $actualCourseDirectory = $matches[5][$index];

            /** @var Course $course */
            $course = $this->courseRepo->findOneBy(['directory' => $actualCourseDirectory]);

            if (!$course) {
                continue;
            }

            $resourcesQb = $this->documentRepo->getResources();

            $documents = $this->documentRepo
                ->addCourseQueryBuilder($course, $resourcesQb)
                ->andWhere('node.title = :title')
                ->setParameter('title', $fileName)
                ->getQuery()
                ->getResult()
            ;

            if (!empty($documents)) {
                $this->replaceDocumentLinks($documents, $matches, $index, $contentText);

                continue;
            }

            if ($document = $this->createNewDocument($videoPath, $course)) {
                if ($newUrl = $this->documentRepo->getResourceFileUrl($document)) {
                    $replacement = $matches[1][$index].'="'.$newUrl.'"';
                    $contentText = str_replace($matches[0][$index], $replacement, $contentText);
                }
            }
        }

        return $contentText;
    }

    /**
     * @param array<int, CDocument> $documents
     * @param array                 $matches
     * @param int                   $index
     * @param string                $contentText
     */
    private function replaceDocumentLinks(array $documents, $matches, $index, $contentText): void
    {
        foreach ($documents as $document) {
            $newUrl = $this->documentRepo->getResourceFileUrl($document);

            if (empty($newUrl)) {
                continue;
            }

            $this->write("Replacing old URL with new URL: $newUrl");

            $patternForReplacement = '/'.preg_quote($matches[0][$index], '/').'/';
            $replacement = $matches[1][$index].'="'.$newUrl.'"';

            preg_replace($patternForReplacement, $replacement, $contentText, 1);
        }
    }

    private function createNewDocument($videoPath, Course $course)
    {
        $rootPath = $this->getUpdateRootPath();
        $appCourseOldPath = $rootPath.'/app'.$videoPath;
        $title = basename($appCourseOldPath);

        if (file_exists($appCourseOldPath) && !is_dir($appCourseOldPath)) {
            $document = new CDocument();
            $document->setFiletype('file')
                ->setTitle($title)
                ->setComment(null)
                ->setReadonly(false)
                ->setCreator($this->getAdmin())
                ->setParent($course)
                ->addCourseLink($course)
            ;

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $this->documentRepo->addFileFromPath($document, $title, $appCourseOldPath);

            $this->write("Document '$title' successfully created for course ".$course->getId());

            return $document;
        }

        if ($foundPath = $this->recursiveFileSearch($rootPath.'/app/courses/', $title)) {
            $document = new CDocument();
            $document->setFiletype('file')
                ->setTitle($title)
                ->setComment(null)
                ->setReadonly(false)
                ->setCreator($this->getAdmin())
                ->setParent($course)
                ->addCourseLink($course)
            ;

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $this->documentRepo->addFileFromPath($document, $title, $foundPath);

            $this->write("File found in new location: $foundPath");

            return $document;
        }

        $this->write("File '$title' not found for course ".$course->getId());

        return null;
    }

    private function recursiveFileSearch($directory, $title)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $title) {
                return $file->getRealPath();
            }
        }

        return null;
    }
}
