<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Admin;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

abstract class AbstractMigrationChamilo extends AbstractMigration
{
    public const BATCH_SIZE = 20;

    protected ?EntityManagerInterface $entityManager = null;
    protected ?ContainerInterface $container = null;

    private LoggerInterface $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        parent::__construct($connection, $logger);
        $this->logger = $logger;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    public function adminExist(): bool
    {
        $sql = 'SELECT user_id FROM admin WHERE user_id IN (SELECT id FROM user) ORDER BY id LIMIT 1';
        $result = $this->connection->executeQuery($sql);
        $adminRow = $result->fetchAssociative();

        if (empty($adminRow)) {
            return false;
        }

        return true;
    }

    public function getAdmin(): User
    {
        $admin = $this->entityManager
            ->getRepository(Admin::class)
            ->findOneBy([], ['id' => 'ASC'])
        ;

        return $admin->getUser();
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
        $accessUrl = $this->entityManager->find(AccessUrl::class, $accessUrl);

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
            ->setAccessUrlChangeable((int) $accessUrlChangeable)
            ->setAccessUrlLocked((int) $accessUrlLocked)
        ;

        $this->entityManager->persist($setting);

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

                $this->entityManager->persist($settingOption);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * @param string     $variable
     * @param null|mixed $configuration
     */
    public function getConfigurationValue($variable, $configuration = null)
    {
        global $_configuration;

        if (isset($configuration)) {
            $_configuration = $configuration;
        }

        if (isset($_configuration[$variable])) {
            return $_configuration[$variable];
        }

        return false;
    }

    public function getMailConfigurationValue(string $variable, array $configuration = []): mixed
    {
        global $platform_email;

        if ($configuration) {
            $platform_email = $configuration;
        }

        if (isset($platform_email[$variable])) {
            return $platform_email[$variable];
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
        // to be implemented
    }

    public function addLegacyFileToResource(
        string $filePath,
        ResourceRepository $repo,
        AbstractResource $resource,
        $id,
        $fileName = '',
        $description = ''
    ): bool {
        $class = $resource::class;
        $documentPath = basename($filePath);

        if (is_dir($filePath) || (!is_dir($filePath) && !file_exists($filePath))) {
            $this->warnIf(true, "Cannot migrate {$class} #'.{$id}.' file not found: {$documentPath}");

            return false;
        }

        $mimeType = mime_content_type($filePath);
        if (empty($fileName)) {
            $fileName = basename($documentPath);
        }
        $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);
        $repo->addFile($resource, $file);

        return true;
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
        $courseId = $course->getId();
        $id = $resource->getResourceIdentifier();

        if (empty($items)) {
            $sql = "SELECT * FROM c_item_property
                    WHERE tool = '{$tool}' AND c_id = {$courseId} AND ref = {$id}";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
        }

        // For some reason the resource doesn't have a c_item_property value.
        if (empty($items)) {
            return false;
        }

        $sessionRepo = $this->container->get(SessionRepository::class);
        $groupRepo = $this->container->get(CGroupRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

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
            if (empty($item['lastedit_date'])) {
                $lastUpdatedAt = new DateTime('now', new DateTimeZone('UTC'));
            } else {
                $lastUpdatedAt = new DateTime($item['lastedit_date'], new DateTimeZone('UTC'));
            }
            $newVisibility = ResourceLink::VISIBILITY_DRAFT;

            // Old 1.11.x visibility (item property) is based in this switch:
            switch ($visibility) {
                case 0:
                    $newVisibility = ResourceLink::VISIBILITY_DRAFT;

                    break;

                case 1:
                    $newVisibility = ResourceLink::VISIBILITY_PUBLISHED;

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
                $this->entityManager->persist($resourceNode);
            }
            $resource->addCourseLink($course, $session, $group, $newVisibility);

            if (2 === $visibility) {
                $link = $resource->getResourceNode()->getResourceLinkByContext($course, $session, $group);
                $link->setDeletedAt($lastUpdatedAt);
            }

            $this->entityManager->persist($resource);
        }

        return true;
    }

    public function fileExists($filePath): bool
    {
        return file_exists($filePath) && !is_dir($filePath) && is_readable($filePath);
    }

    public function findCourse(int $id): ?Course
    {
        if (0 === $id) {
            return null;
        }

        return $this->entityManager->find(Course::class, $id);
    }

    public function findSession(int $id): ?Session
    {
        if (0 === $id) {
            return null;
        }

        return $this->entityManager->find(Session::class, $id);
    }

    public function getMailConfigurationValueFromFile(string $variable): ?string
    {
        global $platform_email;

        $rootPath = $this->container->get('kernel')->getProjectDir();
        $oldConfigPath = $this->getUpdateRootPath().'/app/config/mail.conf.php';

        $configFileLoaded = \in_array($oldConfigPath, get_included_files(), true);

        if (!$configFileLoaded) {
            include_once $oldConfigPath;
        }

        $settingValue = $this->getConfigurationValue($variable, $platform_email);

        if (\is_bool($settingValue)) {
            $selectedValue = var_export($settingValue, true);
        } else {
            $selectedValue = (string) $settingValue;
        }

        return $selectedValue;
    }

    private function generateFilePath(string $filename): string
    {
        $cacheDir = $this->container->get('kernel')->getCacheDir();

        return $cacheDir.'/migration_'.$filename;
    }

    protected function writeFile(string $filename, string $content): void
    {
        $fullFilename = $this->generateFilePath($filename);

        $fs = new Filesystem();
        $fs->dumpFile($fullFilename, $content);
    }

    protected function readFile(string $filename): string
    {
        $fullFilename = $this->generateFilePath($filename);

        return file_get_contents($fullFilename);
    }

    protected function removeFile(string $filename): void
    {
        $fullFilename = $this->generateFilePath($filename);

        $fs = new Filesystem();
        $fs->remove($fullFilename);
    }

    protected function getUpdateRootPath(): string
    {
        $updateRootPath = getenv('UPDATE_PATH');

        if (!empty($updateRootPath)) {
            error_log('getUpdateRootPath ::: '.$updateRootPath);

            return rtrim($updateRootPath, '/');
        }

        return $this->container->getParameter('kernel.project_dir');
    }
}
