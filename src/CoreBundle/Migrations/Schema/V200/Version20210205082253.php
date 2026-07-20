<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUserGroup;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\Usergroup;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UsergroupRepository;
use Doctrine\DBAL\Schema\Schema;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20210205082253 extends AbstractMigrationChamilo
{
    private const FILE_BATCH_SIZE = 50;
    private const USERGROUP_BATCH_SIZE = 100;

    public function getDescription(): string
    {
        return 'Migrate User/Usergroup images with filtered candidates and batched flushes';
    }

    public function up(Schema $schema): void
    {
        $illustrationRepo = $this->container->get(IllustrationRepository::class);
        $splitDirectories = 'true' === (string) $this->connection->fetchOne(
            "SELECT selected_value FROM settings WHERE variable = 'split_users_upload_directory' AND access_url = 1 LIMIT 1"
        );

        $this->migrateUserImages($illustrationRepo, $splitDirectories);
        $this->migrateUsergroupResources();
        $this->migrateUsergroupImages($illustrationRepo, $splitDirectories);
    }

    private function migrateUserImages(IllustrationRepository $illustrationRepo, bool $splitDirectories): void
    {
        $query = $this->entityManager->createQuery(
            "SELECT u FROM Chamilo\\CoreBundle\\Entity\\User u
             WHERE u.pictureUri IS NOT NULL AND u.pictureUri <> :empty"
        )->setParameter('empty', '');

        $seen = 0;
        $migrated = 0;
        $missing = 0;
        $pendingFlush = 0;

        /** @var User $user */
        foreach ($query->toIterable() as $user) {
            ++$seen;

            // Preserve the original migration rule: only users without an
            // illustration resource node are candidates.
            if ($user->hasResourceNode()) {
                continue;
            }

            $id = (int) $user->getId();
            $picture = trim((string) $user->getPictureUri());
            if ('' === $picture) {
                continue;
            }

            $path = $splitDirectories
                ? 'users/'.substr((string) $id, 0, 1).'/'.$id.'/'
                : 'users/'.$id.'/';
            $picturePath = $this->getUpdateRootPath().'/app/upload/'.$path.$picture;

            if (!$this->fileExists($picturePath)) {
                ++$missing;
                $this->warnIf(true, "User image {$id} not found: {$picturePath}");
                continue;
            }

            $mimeType = mime_content_type($picturePath) ?: 'application/octet-stream';
            $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
            $illustrationRepo->addIllustration($user, $user, $file);
            ++$migrated;
            ++$pendingFlush;

            if ($pendingFlush >= self::FILE_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $pendingFlush = 0;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('User image migration completed.', [
            'seen' => $seen,
            'migrated' => $migrated,
            'missing' => $missing,
        ]);
    }

    private function migrateUsergroupResources(): void
    {
        /** @var UsergroupRepository $userGroupRepo */
        $userGroupRepo = $this->container->get(UsergroupRepository::class);
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = $this->container->get(AccessUrlRepository::class);

        /** @var AccessUrl|null $url */
        $url = $urlRepo->findOneBy([], ['id' => 'ASC']);
        if (!$url instanceof AccessUrl) {
            throw new RuntimeException('No access URL was found for usergroup migration.');
        }

        $admin = $this->getAdmin();
        $query = $this->entityManager->createQuery('SELECT u FROM Chamilo\\CoreBundle\\Entity\\Usergroup u');
        $pendingFlush = 0;
        $migrated = 0;

        /** @var Usergroup $userGroup */
        foreach ($query->toIterable() as $userGroup) {
            if ($userGroup->hasResourceNode()) {
                continue;
            }

            $userGroup->setCreator($admin);
            if (0 === $userGroup->getUrls()->count()) {
                $relation = (new AccessUrlRelUserGroup())
                    ->setUserGroup($userGroup)
                    ->setUrl($url);
                $userGroup->getUrls()->add($relation);
            }

            $userGroupRepo->addResourceNode($userGroup, $admin, $url);
            $this->entityManager->persist($userGroup);
            ++$migrated;
            ++$pendingFlush;

            if ($pendingFlush >= self::USERGROUP_BATCH_SIZE) {
                $this->entityManager->flush();
                $pendingFlush = 0;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Usergroup resource migration completed.', ['migrated' => $migrated]);
    }

    private function migrateUsergroupImages(IllustrationRepository $illustrationRepo, bool $splitDirectories): void
    {
        $query = $this->entityManager->createQuery(
            "SELECT u FROM Chamilo\\CoreBundle\\Entity\\Usergroup u
             WHERE u.picture IS NOT NULL AND u.picture <> :empty"
        )->setParameter('empty', '');

        $admin = $this->getAdmin();
        $pendingFlush = 0;
        $seen = 0;
        $migrated = 0;
        $missing = 0;

        /** @var Usergroup $userGroup */
        foreach ($query->toIterable() as $userGroup) {
            ++$seen;
            if (!$userGroup->hasResourceNode()) {
                continue;
            }

            $picture = trim((string) $userGroup->getPicture());
            if ('' === $picture) {
                continue;
            }

            $id = (int) $userGroup->getId();
            $path = $splitDirectories
                ? 'groups/'.substr((string) $id, 0, 1).'/'.$id.'/'
                : 'groups/'.$id.'/';
            $picturePath = $this->getUpdateRootPath().'/app/upload/'.$path.$picture;

            if (!$this->fileExists($picturePath)) {
                ++$missing;
                $this->warnIf(true, "Usergroup image {$id} not found: {$picturePath}");
                continue;
            }

            $mimeType = mime_content_type($picturePath) ?: 'application/octet-stream';
            $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
            $illustrationRepo->addIllustration($userGroup, $admin, $file);
            ++$migrated;
            ++$pendingFlush;

            if ($pendingFlush >= self::FILE_BATCH_SIZE) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $admin = $this->getAdmin();
                $pendingFlush = 0;
            }
        }

        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->getLogger()->info('Usergroup image migration completed.', [
            'seen' => $seen,
            'migrated' => $migrated,
            'missing' => $missing,
        ]);
    }
}
