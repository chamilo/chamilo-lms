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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class Version20210205082253 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate User/Usergroups images';
    }

    public function up(Schema $schema): void
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $kernel = $container->get('kernel');
        $rootPath = $kernel->getProjectDir();

        $illustrationRepo = $container->get(IllustrationRepository::class);

        // Adding users to the resource node tree.
        $batchSize = self::BATCH_SIZE;
        $counter = 1;
        $q = $em->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\User u');

        $sql = "SELECT * FROM settings_current WHERE variable = 'split_users_upload_directory' AND access_url = 1";
        $result = $connection->executeQuery($sql);
        $setting = $result->fetchAssociative();

        /** @var User $userEntity */
        foreach ($q->toIterable() as $userEntity) {
            if ($userEntity->hasResourceNode()) {
                continue;
            }
            $id = $userEntity->getId();
            $picture = $userEntity->getPictureUri();
            if (empty($picture)) {
                continue;
            }
            $path = "users/{$id}/";
            if (!empty($setting) && 'true' === $setting['selected_value']) {
                $path = 'users/'.substr((string) $id, 0, 1).'/'.$id.'/';
            }
            $picturePath = $rootPath.'/app/upload/'.$path.'/'.$picture;
            error_log('MIGRATIONS :: $filePath -- '.$picturePath.' ...');
            if ($this->fileExists($picturePath)) {
                $mimeType = mime_content_type($picturePath);
                $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
                $illustrationRepo->addIllustration($userEntity, $userEntity, $file);
            }

            if (($counter % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }

        $em->flush();
        $em->clear();

        // Migrate Usergroup.
        $counter = 1;
        $q = $em->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\Usergroup u');
        $admin = $this->getAdmin();

        $userGroupRepo = $container->get(UsergroupRepository::class);
        $urlRepo = $container->get(AccessUrlRepository::class);
        $urlList = $urlRepo->findAll();
        /** @var AccessUrl $url */
        $url = $urlList[0];

        /** @var Usergroup $userGroup */
        foreach ($q->toIterable() as $userGroup) {
            if ($userGroup->hasResourceNode()) {
                continue;
            }

            $userGroup->setCreator($admin);

            if (0 === $userGroup->getUrls()->count()) {
                $accessUrlRelUserGroup = (new AccessUrlRelUserGroup())
                    ->setUserGroup($userGroup)
                    ->setUrl($url)
                ;
                $userGroup->getUrls()->add($accessUrlRelUserGroup);
            }
            $userGroupRepo->addResourceNode($userGroup, $admin, $url);
            $em->persist($userGroup);
            $em->flush();
        }
        $em->clear();

        // Migrate Usergroup images.
        $q = $em->createQuery('SELECT u FROM Chamilo\CoreBundle\Entity\Usergroup u');
        /** @var Usergroup $userGroup */
        foreach ($q->toIterable() as $userGroup) {
            if (!$userGroup->hasResourceNode()) {
                continue;
            }

            $picture = $userGroup->getPicture();
            if (empty($picture)) {
                continue;
            }
            $id = $userGroup->getId();
            $path = "groups/{$id}/";
            if (!empty($setting) && 'true' === $setting['selected_value']) {
                $path = 'groups/'.substr((string) $id, 0, 1).'/'.$id.'/';
            }
            $picturePath = $rootPath.'/app/upload/'.$path.'/'.$picture;
            error_log('MIGRATIONS :: $filePath -- '.$picturePath.' ...');
            if ($this->fileExists($picturePath)) {
                $mimeType = mime_content_type($picturePath);
                $file = new UploadedFile($picturePath, $picture, $mimeType, null, true);
                $illustrationRepo->addIllustration($userGroup, $admin, $file);
            }

            if (($counter % $batchSize) === 0) {
                $em->flush();
                $em->clear(); // Detaches all objects from Doctrine!
            }
            $counter++;
        }

        $em->flush();
        $em->clear();
    }
}
