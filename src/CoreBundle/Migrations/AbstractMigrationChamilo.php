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
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\SettingsOptions;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

use const FILE_APPEND;
use const LOCK_EX;
use const PATHINFO_EXTENSION;

abstract class AbstractMigrationChamilo extends AbstractMigration
{
    public const BATCH_SIZE = 20;

    protected ?EntityManagerInterface $entityManager = null;
    protected ?ContainerInterface $container = null;
    private array $itemPropertyInconsistencySeen = [];

    private LoggerInterface $logger;

    /**
     * Cache to avoid repeated DB lookups for the same legacy token.
     *
     * @var array<string,bool>
     */
    private array $legacyCourseExistsCache = [];

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
        $accessUrlLocked = false,
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
        $repo->addFile($resource, $file, $description);

        return true;
    }

    public function fixItemProperty(
        $tool,
        ResourceRepository $repo,
        $course,
        $admin,
        ResourceInterface $resource,
        $parentResource,
        array $items = [],
        ?ResourceType $resourceType = null,
    ) {
        $courseId = $course->getId();
        $id = $resource->getResourceIdentifier();

        if (empty($items)) {
            $sql = "SELECT * FROM c_item_property
                    WHERE tool = '{$tool}' AND c_id = {$courseId} AND ref = {$id}";
            $result = $this->connection->executeQuery($sql);
            $items = $result->fetchAllAssociative();
        }

        // The resource has no c_item_property row in the legacy database: skip it and log the inconsistency.
        if (empty($items)) {
            $path = $this->guessResourcePathForLog($resource);
            $this->logItemPropertyInconsistency((string) $tool, (int) $id, $path);

            $this->warnIf(true, "Missing c_item_property for tool '{$tool}', ref '{$id}'. Resource skipped.");

            return false;
        }

        $sessionRepo = $this->container->get(SessionRepository::class);
        $groupRepo = $this->container->get(CGroupRepository::class);
        $userRepo = $this->container->get(UserRepository::class);

        $resourceType = $resourceType ?: $repo->getResourceType();

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
            $user = $admin;

            if ($userId) {
                if (isset($userList[$userId])) {
                    $user = $userList[$userId];
                } elseif ($userFound = $userRepo->find($userId)) {
                    $user = $userList[$userId] = $userFound;
                }
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
                $resourceNode = $repo->addResourceNode(
                    $resource,
                    $user,
                    $parentResource,
                    $resourceType
                );
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

        if ($this->fileExists($fullFilename)) {
            return file_get_contents($fullFilename);
        }

        return '';
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

    protected static function pluginNameReplacements(): array
    {
        return [
            'bbb' => 'Bbb',
            'before_login' => 'BeforeLogin',
            'buycourses' => 'BuyCourses',
            'card_game' => 'CardGame',
            'check_extra_field_author_company' => 'CheckExtraFieldAuthorCompany',
            'cleandeletedfiles' => 'CleanDeletedFiles',
            'courseblock' => 'CourseBlock',
            'coursehomenotify' => 'CourseHomeNotify',
            'courselegal' => 'CourseLegal',
            'customcertificate' => 'CustomCertificate',
            'customfooter' => 'CustomFooter',
            'dashboard' => 'Dashboard',
            'dictionary' => 'Dictionary',
            'embedregistry' => 'EmbedRegistry',
            'exercise_signature' => 'ExerciseSignature',
            'ext_auth_chamilo_logout_button_behaviour' => 'ExtAuthChamiloLogoutButtonBehaviour',
            'externalnotificationconnect' => 'ExternalNotificationConnect',
            'extramenufromwebservice' => 'ExtraMenuFromWebservice',
            'google_maps' => 'GoogleMaps',
            'grading_electronic' => 'GradingElectronic',
            'h5pimport' => 'H5pImport',
            'hello_world' => 'HelloWorld',
            'ims_lti' => 'ImsLti',
            'justification' => 'Justification',
            'learning_calendar' => 'LearningCalendar',
            'lti_provider' => 'LtiProvider',
            'maintenancemode' => 'MaintenanceMode',
            'migrationmoodle' => 'MigrationMoodle',
            'nosearchindex' => 'NoSearchIndex',
            'notebookteacher' => 'NotebookTeacher',
            'onlyoffice' => 'Onlyoffice',
            'pausetraining' => 'PauseTraining',
            'pens' => 'Pens',
            'positioning' => 'Positioning',
            'questionoptionsevaluation' => 'QuestionOptionsEvaluation',
            'redirection' => 'Redirection',
            'resubscription' => 'Resubscription',
            'rss' => 'Rss',
            'search_course' => 'SearchCourse',
            'show_regions' => 'ShowRegions',
            'show_user_info' => 'ShowUserInfo',
            'static' => 'Static',
            'studentfollowup' => 'StudentFollowUp',
            'surveyexportcsv' => 'SurveyExportCsv',
            'surveyexporttxt' => 'SurveyExportTxt',
            'test2pdf' => 'Test2Pdf',
            'toplinks' => 'TopLinks',
            'tour' => 'Tour',
            'userremoteservice' => 'UserRemoteService',
            'xapi' => 'XApi',
            'zoom' => 'Zoom',
        ];
    }

    protected function logItemPropertyInconsistency(string $tool, int $iid, string $path): void
    {
        $tool = trim($tool);
        $path = trim($path);

        $key = $tool.'|'.$iid.'|'.$path;
        if (isset($this->itemPropertyInconsistencySeen[$key])) {
            return;
        }
        $this->itemPropertyInconsistencySeen[$key] = true;

        $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $line = $date."\t".$tool."\t".$iid."\t".$path."\n";

        $baseDir = null;
        if (null !== $this->container && $this->container->has('kernel')) {
            $kernel = $this->container->get('kernel');
            if (method_exists($kernel, 'getLogDir')) {
                $baseDir = $kernel->getLogDir();
            }
            if (empty($baseDir) && method_exists($kernel, 'getCacheDir')) {
                $baseDir = $kernel->getCacheDir();
            }
        }

        if (empty($baseDir)) {
            $baseDir = sys_get_temp_dir();
        }

        $file = rtrim($baseDir, '/').'/itempropertyinconsistency.log';
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    protected function guessResourcePathForLog(ResourceInterface $resource): string
    {
        foreach (['getPath', 'getPathname', 'getFilePath', 'getRelativePath', 'getFilename', 'getTitle', '__toString'] as $method) {
            if (method_exists($resource, $method)) {
                try {
                    $value = (string) $resource->{$method}();
                    if ('' !== trim($value)) {
                        return $value;
                    }
                } catch (Throwable) {
                    // Ignore and try next method.
                }
            }
        }

        return '';
    }

    /**
     * Checks whether a legacy token matches an existing course by code or directory.
     */
    protected function legacyCourseExistsByCodeOrDirectory(string $token): bool
    {
        $token = trim($token);
        if ('' === $token) {
            return false;
        }

        if (\array_key_exists($token, $this->legacyCourseExistsCache)) {
            return $this->legacyCourseExistsCache[$token];
        }

        $sql = 'SELECT 1 FROM course WHERE code = :t OR directory = :t LIMIT 1';
        $exists = (bool) $this->connection->fetchOne($sql, ['t' => $token]);

        $this->legacyCourseExistsCache[$token] = $exists;

        return $exists;
    }

    /**
     * Quick HTML file check based on extension.
     */
    protected function isHtmlFile(string $filePath): bool
    {
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        return \in_array($ext, ['html', 'htm'], true);
    }

    /**
     * Rewrites legacy "/courses/{TOKEN}/document/{REL_PATH}" links when TOKEN is not an existing course,
     * falling back to the current course directory if the referenced file exists there.
     */
    protected function rewriteLegacyCoursesDocumentLinksFallbackToCurrentCourse(
        string $html,
        string $currentCourseDirectory
    ): string {
        if ('' === $html || '' === $currentCourseDirectory) {
            return $html;
        }

        // Fast pre-check.
        if (!str_contains($html, '/courses/') || !str_contains($html, '/document/')) {
            return $html;
        }

        $updateRootPath = rtrim($this->getUpdateRootPath(), '/');
        $currentCourseDirectory = trim($currentCourseDirectory);

        $rewriteUrl = function (string $url) use ($updateRootPath, $currentCourseDirectory): string {
            // Extract optional prefix (scheme+host), keep it if present.
            $prefix = '';
            $rest = $url;

            if (preg_match('~^(https?:\/\/[^\/]+)(\/.*)$~i', $url, $m)) {
                $prefix = (string) $m[1];
                $rest = (string) $m[2];
            }

            // Only handle /courses/{TOKEN}/document/{REL...}
            if (!preg_match('~^\/courses\/([^\/]+)\/document\/(.+)$~i', $rest, $m)) {
                return $url;
            }

            $token = (string) $m[1];
            $relWithSuffix = (string) $m[2];

            // If course exists, do not touch it.
            if ($this->legacyCourseExistsByCodeOrDirectory($token)) {
                return $url;
            }

            // Split REL from query/fragment to check filesystem path.
            $rel = $relWithSuffix;
            $suffix = '';
            if (preg_match('~^([^?#]+)([?#].*)$~', $relWithSuffix, $mm)) {
                $rel = (string) $mm[1];
                $suffix = (string) $mm[2];
            }

            $relDecoded = rawurldecode($rel);

            // Safety: avoid traversal.
            if (str_contains($relDecoded, '..')) {
                @error_log('[Migration][Documents] Skipping suspicious legacy link containing "..": '.$url);

                return $url;
            }

            $fsPath = $updateRootPath.'/app/courses/'.$currentCourseDirectory.'/document/'.$relDecoded;
            if (!is_file($fsPath)) {
                @error_log('[Migration][Documents] Legacy token "'.$token.'" does not exist and file not found in current course "'.$currentCourseDirectory.'": '.$url);

                return $url;
            }

            $newUrl = $prefix.'/courses/'.$currentCourseDirectory.'/document/'.$rel.$suffix;
            @error_log('[Migration][Documents] Rewrote legacy link: '.$url.' -> '.$newUrl);

            return $newUrl;
        };

        // Pass 1: src/href="..."
        $html = (string) preg_replace_callback(
            '~\b(?:src|href)\s*=\s*([\'"])([^\'"]+)\1~i',
            function (array $m) use ($rewriteUrl) {
                $quote = (string) $m[1];
                $url = (string) $m[2];

                if (!str_contains($url, '/courses/') || !str_contains($url, '/document/')) {
                    return $m[0];
                }

                $newUrl = $rewriteUrl($url);
                if ($newUrl === $url) {
                    return $m[0];
                }

                return str_replace($url, $newUrl, $m[0]);
            },
            $html
        );

        // Pass 2: url(...)
        return (string) preg_replace_callback(
            '~\burl\(\s*([\'"]?)([^\'")]+)\1\s*\)~i',
            function (array $m) use ($rewriteUrl) {
                $url = (string) $m[2];

                if (!str_contains($url, '/courses/') || !str_contains($url, '/document/')) {
                    return $m[0];
                }

                $newUrl = $rewriteUrl($url);
                if ($newUrl === $url) {
                    return $m[0];
                }

                return str_replace($url, $newUrl, $m[0]);
            },
            $html
        );
    }

    /**
     * If HTML file contains legacy broken /courses/{TOKEN}/document links, create a rewritten temp copy and return its path.
     * Otherwise returns original file path.
     *
     * Important: Use $originalBasename to keep the original name when uploading.
     */
    protected function rewriteHtmlFileLegacyLinksIfNeeded(string $filePath, string $currentCourseDirectory): string
    {
        if (!$this->fileExists($filePath) || !$this->isHtmlFile($filePath)) {
            return $filePath;
        }

        $html = (string) @file_get_contents($filePath);
        if ('' === $html) {
            return $filePath;
        }

        $newHtml = $this->rewriteLegacyCoursesDocumentLinksFallbackToCurrentCourse($html, $currentCourseDirectory);
        if ($newHtml === $html) {
            return $filePath;
        }

        // Write a temp copy under cache dir (same as other migration temp files).
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        $tmpName = 'rewrite_html_'.sha1($filePath).'.'.$ext;

        $tmpPath = $this->container->get('kernel')->getCacheDir().'/migration_'.$tmpName;
        $fs = new Filesystem();
        $fs->dumpFile($tmpPath, $newHtml);

        @error_log('[Migration][Documents] Created rewritten HTML temp file: '.$tmpPath);

        return $tmpPath;
    }
}
