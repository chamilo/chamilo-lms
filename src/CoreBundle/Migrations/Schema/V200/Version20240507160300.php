<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20240507160300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Verify and migrate user profile images to illustrations.';
    }

    public function up(Schema $schema): void
    {
        $kernel = $this->container->get('kernel');
        $rootPath = $kernel->getProjectDir();
        error_log('MIGRATIONS :: Migration for missing illustrations started.');

        $illustrationRepo = $this->container->get(IllustrationRepository::class);

        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $userEntity) {
            if ($userEntity->getResourceNode() && !$illustrationRepo->hasIllustration($userEntity)) {
                $picture = $userEntity->getPictureUri();
                if (empty($picture)) {
                    continue;
                }

                $path = $this->determinePath($userEntity->getId(), $picture);
                $picturePath = $this->getUpdateRootPath().'/app/upload/'.$path.$picture;

                if (file_exists($picturePath)) {
                    $mimeType = mime_content_type($picturePath);
                    $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
                    if ($userEntity->getResourceNode()) {
                        $illustrationRepo->addIllustration($userEntity, $userEntity, $file);
                        error_log('Illustration added for User ID: '.$userEntity->getId());
                    } else {
                        error_log('No resource node found for User ID: '.$userEntity->getId());
                    }
                }
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function determinePath(int $userId, string $picture): string
    {
        $path = "users/{$userId}/";
        $splitSetting = $this->fetchSplitSetting();
        if (!empty($splitSetting) && 'true' === $splitSetting['selected_value']) {
            $path = 'users/'.substr((string) $userId, 0, 1).'/'.$userId.'/';
        }

        return $path;
    }

    private function fetchSplitSetting(): array
    {
        $sql = "SELECT * FROM settings WHERE variable = 'split_users_upload_directory' AND access_url = 1";
        $result = $this->connection->executeQuery($sql);

        return $result->fetchAssociative() ?? [];
    }
}
