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
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
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

    /**
     * Cached repositories (avoid container->get per call).
     */
    private ?SessionRepository $sessionRepoCache = null;
    private ?CGroupRepository $groupRepoCache = null;
    private ?UserRepository $userRepoCache = null;

    /**
     * Internal finfo handler for faster MIME detection (optional).
     */
    private ?\finfo $mimeFinfo = null;

    /**
     * Existence caches to avoid repeated lookups during migration.
     * We cache only when the check succeeds (true/false). If a DB error happens,
     * we return null and fallback to the original repository->find() behavior.
     *
     * @var array<int,bool>
     */
    private array $userExistsCache = [];

    /**
     * @var array<int,bool>
     */
    private array $sessionExistsCache = [];

    /**
     * @var array<int,bool>
     */
    private array $groupExistsCache = [];

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

    public function removeSettingCurrent($variable): void
    {
        // to be implemented
    }

    /**
     * Optimized: skip directories, require readable regular files, robust MIME type detection.
     * Logic stays the same: if file missing => warn & return false.
     */
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

        if (!is_file($filePath) || !is_readable($filePath)) {
            $this->warnIf(true, "Cannot migrate {$class} #{$id} file not found: {$documentPath}");
            return false;
        }

        if ('' === (string) $fileName) {
            $fileName = $documentPath;
        }

        $mimeType = $this->detectMimeTypeInternal($filePath);

        $file = new UploadedFile($filePath, $fileName, $mimeType, null, true);

        // Explicit false for clarity (no behavior change).
        $repo->addFile($resource, $file, $description, false);

        return true;
    }

    /**
     * Prefetch c_item_property rows for many refs in one shot (chunked IN()).
     * This is intentionally implemented here to:
     * - avoid "IN (?)" parameter binding issues
     * - avoid N+1 queries in fixItemProperty()
     *
     * @param string     $tool
     * @param int        $courseId
     * @param array<int> $refs
     *
     * @return array<int, array<int, array<string,mixed>>> Map: ref => rows[]
     */
    protected function fetchItemPropertiesMap(string $tool, int $courseId, array $refs): array
    {
        $tool = (string) $tool;
        $courseId = (int) $courseId;

        if ($courseId <= 0 || empty($refs)) {
            return [];
        }

        $refs = array_values(array_unique(array_map('intval', $refs)));

        $map = [];
        $chunkSize = 500;

        for ($i = 0; $i < \count($refs); $i += $chunkSize) {
            $chunk = \array_slice($refs, $i, $chunkSize);

            $sql = 'SELECT ref, visibility, insert_user_id, session_id, to_group_id, lastedit_date
                    FROM c_item_property
                    WHERE tool = :tool AND c_id = :cid AND ref IN (:refs)';

            try {
                $rows = $this->connection->executeQuery(
                    $sql,
                    [
                        'tool' => $tool,
                        'cid' => $courseId,
                        'refs' => $chunk,
                    ],
                    [
                        'refs' => ArrayParameterType::INTEGER,
                    ]
                )->fetchAllAssociative();
            } catch (Throwable) {
                return [];
            }

            foreach ($rows as $r) {
                $ref = (int) ($r['ref'] ?? 0);
                if ($ref > 0) {
                    $map[$ref][] = $r;
                }
            }
        }

        return $map;
    }

    /**
     * Fast existence check with caching.
     * Returns:
     * - true/false if the query ran successfully
     * - null if a DB error happened (caller should fallback to repository->find() to preserve behavior)
     */
    private function idExistsFast(string $table, int $id, array &$cache): ?bool
    {
        if ($id <= 0) {
            return false;
        }

        if (\array_key_exists($id, $cache)) {
            return $cache[$id];
        }

        try {
            $exists = (bool) $this->connection->fetchOne(
                "SELECT 1 FROM {$table} WHERE id = :id LIMIT 1",
                ['id' => $id]
            );
        } catch (Throwable) {
            // Do not cache failures. Fallback to the original ORM find() logic.
            return null;
        }

        $cache[$id] = $exists;

        return $exists;
    }

    private function userIdExistsFast(int $userId): ?bool
    {
        return $this->idExistsFast('user', $userId, $this->userExistsCache);
    }

    private function sessionIdExistsFast(int $sessionId): ?bool
    {
        return $this->idExistsFast('session', $sessionId, $this->sessionExistsCache);
    }

    private function groupIdExistsFast(int $groupId): ?bool
    {
        // Most Chamilo installs use "c_group". If your schema differs, adjust here.
        return $this->idExistsFast('c_group', $groupId, $this->groupExistsCache);
    }

    /**
     * Optimized:
     * - If $items already passed => no query.
     * - Fallback query is parametrized + selects only needed columns.
     * - Uses getReference() when the related ID exists, avoiding heavy hydration via find().
     * - If the fast existence check fails (DB error), falls back to repository->find() to preserve behavior.
     * - persist($resource) is done once (not inside loop).
     * Logic is the same.
     */
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
        $courseId = (int) $course->getId();
        $id = (int) $resource->getResourceIdentifier();
        $tool = (string) $tool;

        if (empty($items)) {
            $sql = 'SELECT visibility, insert_user_id, session_id, to_group_id, lastedit_date
                    FROM c_item_property
                    WHERE tool = :tool AND c_id = :cid AND ref = :ref';

            $items = $this->connection->fetchAllAssociative($sql, [
                'tool' => $tool,
                'cid' => $courseId,
                'ref' => $id,
            ]);
        }

        if (empty($items)) {
            $path = $this->guessResourcePathForLog($resource);
            $this->logItemPropertyInconsistency($tool, $id, $path);

            $this->warnIf(true, "Missing c_item_property for tool '{$tool}', ref '{$id}'. Resource skipped.");

            return false;
        }

        // Keep original repository caching (fallback path).
        if (null === $this->sessionRepoCache) {
            $this->sessionRepoCache = $this->container->get(SessionRepository::class);
        }
        if (null === $this->groupRepoCache) {
            $this->groupRepoCache = $this->container->get(CGroupRepository::class);
        }
        if (null === $this->userRepoCache) {
            $this->userRepoCache = $this->container->get(UserRepository::class);
        }

        $sessionRepo = $this->sessionRepoCache;
        $groupRepo = $this->groupRepoCache;
        $userRepo = $this->userRepoCache;

        $resourceType = $resourceType ?: $repo->getResourceType();

        $resource->setParent($parentResource);

        $resourceNode = null;

        // Per-call caches (safe even with entityManager->clear in migration loops).
        // Values can be real entities or proxies (getReference()).
        $userList = [];
        $groupList = [];
        $sessionList = [];

        $utc = new DateTimeZone('UTC');

        foreach ($items as $item) {
            $visibility = (int) ($item['visibility'] ?? 0);
            $userId = (int) ($item['insert_user_id'] ?? 0);
            $sessionId = (int) ($item['session_id'] ?? 0);
            $groupId = (int) ($item['to_group_id'] ?? 0);

            $lastEdit = (string) ($item['lastedit_date'] ?? '');
            $lastUpdatedAt = '' === $lastEdit
                ? new \DateTime('now', $utc)
                : new \DateTime($lastEdit, $utc);

            $newVisibility = ResourceLink::VISIBILITY_DRAFT;

            // Old 1.11.x visibility (item property) is based on this switch:
            switch ($visibility) {
                case 0:
                    $newVisibility = ResourceLink::VISIBILITY_DRAFT;
                    break;
                case 1:
                    $newVisibility = ResourceLink::VISIBILITY_PUBLISHED;
                    break;
                default:
                    // Keep legacy behavior: anything else becomes DRAFT unless explicitly handled.
                    $newVisibility = ResourceLink::VISIBILITY_DRAFT;
                    break;
            }

            // If c_item_property.insert_user_id doesn't exist we use the first admin id.
            $user = $admin;

            if ($userId > 0) {
                if (isset($userList[$userId])) {
                    $user = $userList[$userId];
                } else {
                    $exists = $this->userIdExistsFast($userId);

                    if (true === $exists) {
                        // Fast path: create a managed proxy without loading the full entity.
                        $userRef = $this->entityManager->getReference(User::class, $userId);
                        $userList[$userId] = $userRef;
                        $user = $userRef;
                    } elseif (null === $exists) {
                        // DB error: fallback to original behavior (find()).
                        $userFound = $userRepo->find($userId);
                        if ($userFound) {
                            $userList[$userId] = $userFound;
                            $user = $userFound;
                        }
                    }
                    // If $exists is false => keep $admin (same as before when find() returned null).
                }
            }

            $session = null;
            if ($sessionId > 0) {
                if (isset($sessionList[$sessionId])) {
                    $session = $sessionList[$sessionId];
                } else {
                    $exists = $this->sessionIdExistsFast($sessionId);

                    if (true === $exists) {
                        $sessionRef = $this->entityManager->getReference(Session::class, $sessionId);
                        $sessionList[$sessionId] = $sessionRef;
                        $session = $sessionRef;
                    } elseif (null === $exists) {
                        // DB error: fallback to original behavior (find()).
                        $sessionFound = $sessionRepo->find($sessionId);
                        $sessionList[$sessionId] = $sessionFound;
                        $session = $sessionFound;
                    } else {
                        // Not found => null (same as before).
                        $sessionList[$sessionId] = null;
                        $session = null;
                    }
                }
            }

            $group = null;
            if ($groupId > 0) {
                if (isset($groupList[$groupId])) {
                    $group = $groupList[$groupId];
                } else {
                    $exists = $this->groupIdExistsFast($groupId);

                    if (true === $exists) {
                        $groupRef = $this->entityManager->getReference(CGroup::class, $groupId);
                        $groupList[$groupId] = $groupRef;
                        $group = $groupRef;
                    } elseif (null === $exists) {
                        // DB error: fallback to original behavior (find()).
                        $groupFound = $groupRepo->find($groupId);
                        $groupList[$groupId] = $groupFound;
                        $group = $groupFound;
                    } else {
                        // Not found => null (same as before).
                        $groupList[$groupId] = null;
                        $group = null;
                    }
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
        }

        // Persist once (same result, less overhead).
        $this->entityManager->persist($resource);

        return true;
    }

    /**
     * Keep behavior but slightly stricter: only regular readable files.
     */
    public function fileExists($filePath): bool
    {
        return is_file($filePath) && is_readable($filePath);
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
            return (string) file_get_contents($fullFilename);
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

    protected function isHtmlFile(string $filePath): bool
    {
        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));

        return \in_array($ext, ['html', 'htm'], true);
    }

    protected function rewriteLegacyCoursesDocumentLinksFallbackToCurrentCourse(
        string $html,
        string $currentCourseDirectory
    ): string {
        if ('' === $html || '' === $currentCourseDirectory) {
            return $html;
        }

        if (!str_contains($html, '/courses/') || !str_contains($html, '/document/')) {
            return $html;
        }

        $updateRootPath = rtrim($this->getUpdateRootPath(), '/');
        $currentCourseDirectory = trim($currentCourseDirectory);

        $rewriteUrl = function (string $url) use ($updateRootPath, $currentCourseDirectory): string {
            $prefix = '';
            $rest = $url;

            if (preg_match('~^(https?:\/\/[^\/]+)(\/.*)$~i', $url, $m)) {
                $prefix = (string) $m[1];
                $rest = (string) $m[2];
            }

            if (!preg_match('~^\/courses\/([^\/]+)\/document\/(.+)$~i', $rest, $m)) {
                return $url;
            }

            $token = (string) $m[1];
            $relWithSuffix = (string) $m[2];

            if ($this->legacyCourseExistsByCodeOrDirectory($token)) {
                return $url;
            }

            $rel = $relWithSuffix;
            $suffix = '';
            if (preg_match('~^([^?#]+)([?#].*)$~', $relWithSuffix, $mm)) {
                $rel = (string) $mm[1];
                $suffix = (string) $mm[2];
            }

            $relDecoded = rawurldecode($rel);

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

        $html = (string) preg_replace_callback(
            '~\b(?:src|href)\s*=\s*([\'"])([^\'"]+)\1~i',
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

        // Cheap pre-check (avoid regex passes when not needed).
        if (!str_contains($html, '/courses/') || !str_contains($html, '/document/')) {
            return $filePath;
        }

        $newHtml = $this->rewriteLegacyCoursesDocumentLinksFallbackToCurrentCourse($html, $currentCourseDirectory);
        if ($newHtml === $html) {
            return $filePath;
        }

        $ext = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        $tmpName = 'rewrite_html_'.sha1($currentCourseDirectory.'|'.$filePath).'.'.$ext;

        $tmpPath = $this->container->get('kernel')->getCacheDir().'/migration_'.$tmpName;

        @file_put_contents($tmpPath, $newHtml);

        @error_log('[Migration][Documents] Created rewritten HTML temp file: '.$tmpPath);

        return $tmpPath;
    }

    /**
     * Internal MIME detector with cached finfo instance.
     */
    private function detectMimeTypeInternal(string $filePath): string
    {
        if (null === $this->mimeFinfo && \class_exists(\finfo::class)) {
            try {
                $this->mimeFinfo = new \finfo(\FILEINFO_MIME_TYPE);
            } catch (Throwable) {
                $this->mimeFinfo = null;
            }
        }

        if ($this->mimeFinfo instanceof \finfo) {
            try {
                $mime = $this->mimeFinfo->file($filePath);
                if (\is_string($mime) && '' !== $mime) {
                    return $mime;
                }
            } catch (Throwable) {
                // Ignore and fallback.
            }
        }

        $mime = @mime_content_type($filePath);
        if (\is_string($mime) && '' !== $mime) {
            return $mime;
        }

        return 'application/octet-stream';
    }
}
