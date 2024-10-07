<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20230720143000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate my_files of users to personal_files ';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $fallbackUser = $this->getFallbackUser();
        $directory = $this->getUpdateRootPath().'/app/upload/users/';

        $variable = 'split_users_upload_directory';
        $query = $this->entityManager->createQuery('SELECT s.selectedValue FROM Chamilo\CoreBundle\Entity\SettingsCurrent s WHERE s.variable = :variable')
            ->setParameter('variable', $variable);
        $result = $query->getOneOrNullResult();
        $splitUsersUploadDirectory = $result['selectedValue'] ?? 'false';

        if ('true' === $splitUsersUploadDirectory) {
            $parentDirectories = glob($directory.'*', GLOB_ONLYDIR);

            foreach ($parentDirectories as $parentDir) {
                $firstDigit = basename($parentDir);
                $subDirectories = glob($parentDir.'/*', GLOB_ONLYDIR);
                foreach ($subDirectories as $userDir) {
                    $userId = basename($userDir);
                    $this->processUserDirectory($userId, $userDir, $fallbackUser, true);
                }
            }
        } else {
            $userDirectories = glob($directory.'*', GLOB_ONLYDIR);
            foreach ($userDirectories as $userDir) {
                $userId = basename($userDir);
                $this->processUserDirectory($userId, $userDir, $fallbackUser, false);
            }
        }
    }

    private function processUserDirectory(string $userId, string $userDir, User $fallbackUser, bool $splitUsersUploadDirectory): void
    {
        $userEntity = $this->entityManager->getRepository(User::class)->find($userId);
        $userToAssign = $userEntity ?? $fallbackUser;

        if ($userEntity === null) {
            error_log("User with ID {$userId} not found. Using fallback_user.");
        } else {
            error_log("Processing files for user with ID {$userId}.");
        }

        if ($splitUsersUploadDirectory) {
            $baseDir = $userDir.'/';
        } else {
            $baseDir = $this->getUpdateRootPath().'/app/upload/users/'.$userId.'/';
        }

        error_log("Final path to check: {$baseDir}");

        if (!is_dir($baseDir)) {
            error_log("Directory not found for user with ID {$userId}. Skipping.");
            return;
        }

        $myFilesDir = $baseDir.'my_files/';
        $files = glob($myFilesDir.'*');

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $title = basename($file);
            error_log("Processing file: {$file} for user with ID {$userToAssign->getId()}.");

            $queryBuilder = $this->entityManager->createQueryBuilder();
            $queryBuilder
                ->select('p')
                ->from(ResourceNode::class, 'n')
                ->innerJoin(PersonalFile::class, 'p', Join::WITH, 'p.resourceNode = n.id')
                ->where('n.title = :title')
                ->andWhere('n.creator = :creator')
                ->setParameter('title', $title)
                ->setParameter('creator', $userToAssign->getId());

            $result = $queryBuilder->getQuery()->getOneOrNullResult();

            if ($result) {
                error_log('MIGRATIONS :: '.$file.' (Skipped: Already exists) ...');
                continue;
            }

            error_log("MIGRATIONS :: Associating file {$file} to user with ID {$userToAssign->getId()}.");
            $personalFile = new PersonalFile();
            $personalFile->setTitle($title);
            $personalFile->setCreator($userToAssign);
            $personalFile->setParentResourceNode($userToAssign->getResourceNode()->getId());
            $personalFile->setResourceName($title);
            $mimeType = mime_content_type($file);
            $uploadedFile = new UploadedFile($file, $title, $mimeType, null, true);
            $personalFile->setUploadFile($uploadedFile);
            $personalFile->addUserLink($userToAssign);

            // Save the object to the database
            $this->entityManager->persist($personalFile);
            $this->entityManager->flush();
        }
    }

    private function getFallbackUser(): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['status' => User::ROLE_FALLBACK], ['id' => 'ASC']);
    }
}
