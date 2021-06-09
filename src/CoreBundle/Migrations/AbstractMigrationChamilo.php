<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractMigrationChamilo extends AbstractMigration implements ContainerAwareInterface
{
    public const BATCH_SIZE = 20;

    private ?EntityManager $manager = null;
    private ?ContainerInterface $container = null;

    public function setEntityManager(EntityManager $manager): void
    {
        $this->manager = $manager;
    }

    public function setContainer(ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function adminExist(): bool
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();

        $sql = 'SELECT user_id FROM admin WHERE user_id IN (SELECT id FROM user) ORDER BY id LIMIT 1';
        $result = $connection->executeQuery($sql);
        $adminRow = $result->fetchAssociative();

        if (empty($adminRow)) {
            return false;
        }

        return true;
    }

    public function getAdmin(): User
    {
        $container = $this->getContainer();
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $userRepo = $container->get(UserRepository::class);

        $sql = 'SELECT user_id FROM admin ORDER BY id LIMIT 1';
        $result = $connection->executeQuery($sql);
        $adminRow = $result->fetchAssociative();
        $adminId = $adminRow['user_id'];

        return $userRepo->find($adminId);
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        if (empty($this->manager)) {
            //$params = $this->connection->getParams();
            /*
            $dbParams = [
                'driver' => 'pdo_mysql',
                'host' => $this->connection->getHost(),
                'user' => $this->connection->getUsername(),
                'password' => $this->connection->getPassword(),
                'dbname' => $this->connection->getDatabase(),
                'port' => $this->connection->getPort(),
            ];*/
            /*$database = new \Database();
            $database->connect(
                $params,
                __DIR__.'/../../',
                __DIR__.'/../../'
            );
            $this->manager = $database->getManager();*/
        }

        return $this->manager;
    }

    /**
     * Speeds up SettingsCurrent creation.
     *
     * @param string $variable            The variable itself
     * @param string $subKey              The subkey
     * @param string $type                The type of setting (text, radio, select, etc)
     * @param string $category            The category (Platform, User, etc)
     * @param string $selectedValue       The default value
     * @param string $title               The setting title string name
     * @param string $comment             The setting comment string name
     * @param string $scope               The scope
     * @param string $subKeyText          Text if there is a subKey
     * @param int    $accessUrl           What URL it is for
     * @param bool   $accessUrlChangeable Whether it can be changed on each url
     * @param bool   $accessUrlLocked     Whether the setting for the current URL is
     *                                    locked to the current value
     * @param array  $options             Optional array in case of a radio-type field,
     *                                    to insert options
     */
    public function addSettingCurrent(
        $variable,
        $subKey,
        $type,
        $category,
        $selectedValue,
        $title,
        $comment,
        $scope = '',
        $subKeyText = '',
        $accessUrl = 1,
        $accessUrlChangeable = false,
        $accessUrlLocked = true,
        $options = []
    ): void {
        $em = $this->getEntityManager();
        $setting = new SettingsCurrent();
        $setting
            ->setVariable($variable)
            ->setSubkey($subKey)
            ->setType($type)
            ->setCategory($category)
            ->setSelectedValue($selectedValue)
            ->setTitle($title)
            ->setComment($comment)
            ->setScope($scope)
            ->setSubkeytext($subKeyText)
            ->setUrl($accessUrl)
            ->setAccessUrlChangeable($accessUrlChangeable)
            ->setAccessUrlLocked($accessUrlLocked)
        ;

        $em->persist($setting);

        if (\count($options) > 0) {
            foreach ($options as $option) {
                if (empty($option['text'])) {
                    if ('true' === $option['value']) {
                        $option['text'] = 'Yes';
                    } else {
                        $option['text'] = 'No';
                    }
                }

                $settingOption = new SettingsOptions();
                $settingOption
                    ->setVariable($variable)
                    ->setValue($option['value'])
                    ->setDisplayText($option['text'])
                ;

                $em->persist($settingOption);
            }
        }
        $em->flush();
    }

    /**
     * @param string $variable
     */
    public function getConfigurationValue($variable)
    {
        global $_configuration;
        if (isset($_configuration[$variable])) {
            return $_configuration[$variable];
        }

        return false;
    }

    /**
     * Remove a setting completely.
     *
     * @param string $variable The setting variable name
     */
    public function removeSettingCurrent($variable): void
    {
        //to be implemented
    }

    public function addLegacyFileToResource(
        string $filePath,
        ResourceRepository $repo,
        AbstractResource $resource,
        $id,
        $fileName = '',
        $description = ''
    ): void {
        if (!is_dir($filePath)) {
            $class = \get_class($resource);
            $documentPath = basename($filePath);
            if (file_exists($filePath)) {
                $mimeType = mime_content_type($filePath);
                if (empty($fileName)) {
                    $fileName = basename($documentPath);
                }
                $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
                if ($file) {
                    $repo->addFile($resource, $file);
                } else {
                    $this->warnIf(true, "Cannot migrate {$class} #{$id} path: {$documentPath} ");
                }
            } else {
                $this->warnIf(true, "Cannot migrate {$class} #'.{$id}.' file not found: {$documentPath}");
            }
        }
    }

    public function fixItemProperty(
        $tool,
        ResourceRepository $repo,
        $course,
        $admin,
        ResourceInterface $resource,
        $parentResource,
        array $items = []
    ) {
        $container = $this->getContainer();
        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();
        /** @var Connection $connection */
        $connection = $em->getConnection();

        $courseId = $course->getId();
        $id = $resource->getResourceIdentifier();

        if (empty($items)) {
            $sql = "SELECT * FROM c_item_property
                    WHERE tool = '{$tool}' AND c_id = {$courseId} AND ref = {$id}";
            $result = $connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
        }

        // For some reason the resource doesnt have a c_item_property value.
        if (empty($items)) {
            return false;
        }

        $sessionRepo = $container->get(SessionRepository::class);
        $groupRepo = $container->get(CGroupRepository::class);
        $userRepo = $container->get(UserRepository::class);

        $resource->setParent($parentResource);
        $resourceNode = null;
        $userList = [];
        $groupList = [];
        $sessionList = [];
        foreach ($items as $item) {
            $visibility = (int) $item['visibility'];
            $userId = (int) $item['insert_user_id'];
            $sessionId = $item['session_id'] ?? 0;
            $groupId = $item['to_group_id'] ?? 0;

            $newVisibility = ResourceLink::VISIBILITY_PENDING;
            // Old visibility (item property) is based in this switch:
            switch ($visibility) {
                case 0:
                    $newVisibility = ResourceLink::VISIBILITY_PENDING;

                    break;
                case 1:
                    $newVisibility = ResourceLink::VISIBILITY_PUBLISHED;

                    break;
                case 2:
                    $newVisibility = ResourceLink::VISIBILITY_DELETED;

                    break;
            }

            // If c_item_property.insert_user_id doesn't exist we use the first admin id.
            $user = null;
            if (isset($userList[$userId])) {
                $user = $userList[$userId];
            } else {
                if (!empty($userId)) {
                    $userFound = $userRepo->find($userId);
                    if ($userFound) {
                        $user = $userList[$userId] = $userRepo->find($userId);
                    }
                }
            }

            if (null === $user) {
                $user = $admin;
            }

            $session = null;
            if (!empty($sessionId)) {
                if (isset($sessionList[$sessionId])) {
                    $session = $sessionList[$sessionId];
                } else {
                    $session = $sessionList[$sessionId] = $sessionRepo->find($sessionId);
                }
            }

            $group = null;
            if (!empty($groupId)) {
                if (isset($groupList[$groupId])) {
                    $group = $groupList[$groupId];
                } else {
                    $group = $groupList[$groupId] = $groupRepo->find($groupId);
                }
            }

            if (null === $resourceNode) {
                $resourceNode = $repo->addResourceNode($resource, $user, $parentResource);
                $em->persist($resourceNode);
            }
            $resource->addCourseLink($course, $session, $group, $newVisibility);
            $em->persist($resource);
        }

        return true;
    }
}
