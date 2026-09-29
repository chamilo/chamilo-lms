<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container as LegacyContainer;
use Chamilo\CoreBundle\Helpers\CourseHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Tool\ToolChain;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Component\CourseCopy\Moodle\Builder\MoodleImport;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use ChamiloSession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Throwable;

final class DemoCoursesFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const array DEMO_COURSES = [
        [
            'title' => 'AI Act',
            'code' => 'AIACT',
            'archive' => 'ai-act.mbz',
            'illustration' => 'ai-act.png',
            'visible_tool' => 'learnpath',
        ],
        [
            'title' => 'Using Chamilo',
            'code' => 'USINGCHAMILO',
            'archive' => 'usingchamilo.mbz',
            'illustration' => 'usingchamilo.png',
            'visible_tool' => 'learnpath',
        ],
    ];

    /**
     * @psalm-suppress ContainerDependency
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseRepository $courseRepository,
        private readonly CourseHelper $courseHelper,
        private readonly ToolChain $toolChain,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'service_container')]
        private readonly ContainerInterface $container,
    ) {}

    public function getDependencies(): array
    {
        return [
            AccessUserFixtures::class,
            AccessUserUrlFixtures::class,
            LanguageFixtures::class,
        ];
    }

    public static function getGroups(): array
    {
        return ['install'];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $admin */
        $admin = $this->getReference(AccessUserFixtures::ADMIN_USER_REFERENCE, User::class);

        $this->installDemoCourses($admin, Course::OPEN_PLATFORM);
    }

    public function installDemoCourses(User $admin, int $visibility): void
    {
        $this->initializeLegacyBridge();

        $adminId = (int) $admin->getId();
        $previousToken = $this->tokenStorage->getToken();

        try {
            foreach (self::DEMO_COURSES as $definition) {
                // loadDemoCourse() clears Doctrine while restoring a course. Reload the
                // administrator and reset the legacy course context before each import so
                // consecutive demo courses never reuse detached entities or the previous CID.
                $managedAdmin = $this->entityManager->find(User::class, $adminId);
                if (!$managedAdmin instanceof User) {
                    throw new RuntimeException('Could not reload the administrator for demo course import.');
                }

                $this->clearLegacyCourseContext();
                $this->primeLegacyUserContext($managedAdmin);
                $this->tokenStorage->setToken(new UsernamePasswordToken(
                    $managedAdmin,
                    'public',
                    $managedAdmin->getRoles()
                ));

                $this->loadDemoCourse($definition, $managedAdmin, $visibility);
            }
        } finally {
            $this->clearLegacyCourseContext();
            $this->tokenStorage->setToken($previousToken);
        }
    }

    /**
     * CourseRestorer and part of the legacy course API still resolve Symfony services
     * through Chamilo\CoreBundle\Framework\Container. HTTP requests initialize that
     * bridge in LegacyListener, but Doctrine fixtures run from Console and do not.
     */
    private function initializeLegacyBridge(): void
    {
        LegacyContainer::setContainer($this->container);
        LegacyContainer::setLegacyServices($this->container);
        LegacyContainer::setSession(new Session(new MockArraySessionStorage()));
    }

    /**
     * Legacy document creation still resolves the current user from the legacy session
     * instead of Symfony's security token. Keep both contexts aligned during fixtures.
     */
    private function clearLegacyCourseContext(): void
    {
        $session = LegacyContainer::getSession();
        if ($session instanceof Session) {
            foreach (['cid', '_real_cid', '_cid', '_course', 'sid', 'gid'] as $key) {
                $session->remove($key);
            }
        }

        foreach (['cid', '_real_cid', '_cid', '_course', 'sid', 'gid'] as $key) {
            ChamiloSession::erase($key);
        }
    }

    private function primeLegacyUserContext(User $admin): void
    {
        $session = LegacyContainer::getSession();
        if (!$session instanceof Session) {
            throw new RuntimeException('Could not initialize the legacy user session for demo course import.');
        }

        $userInfo = [
            'id' => (int) $admin->getId(),
            'user_id' => (int) $admin->getId(),
            'username' => $admin->getUsername(),
            'status' => $admin->getStatus(),
            'firstname' => $admin->getFirstname(),
            'lastname' => $admin->getLastname(),
            'email' => $admin->getEmail(),
        ];

        // Modern services read Symfony's session, while legacy api_get_user_id()
        // still reads ChamiloSession ($_SESSION). Keep both stores in sync.
        $session->set('_user', $userInfo);
        ChamiloSession::write('_user', $userInfo);
    }

    /**
     * Mirror the course context initialized by CidReqListener for normal web requests.
     * Several legacy restore helpers still read these values through api_get_* helpers.
     */
    private function primeLegacyCourseContext(Course $course): void
    {
        $session = LegacyContainer::getSession();
        if (!$session instanceof Session) {
            throw new RuntimeException('Could not initialize the legacy course session for demo course import.');
        }

        $courseInfo = api_get_course_info($course->getCode());
        if (!\is_array($courseInfo) || empty($courseInfo['real_id'])) {
            throw new RuntimeException(\sprintf('Could not resolve course context for demo course "%s".', $course->getCode()));
        }

        $courseId = (int) $course->getId();

        $session->set('cid', $courseId);
        $session->set('_real_cid', $courseId);
        $session->set('_cid', $course->getCode());
        $session->set('_course', $courseInfo);
        $session->remove('sid');
        $session->remove('gid');

        // Mirror CidReqListener: legacy helpers such as DocumentManager use
        // ChamiloSession directly instead of Symfony's session service.
        ChamiloSession::write('cid', $courseId);
        ChamiloSession::write('_real_cid', $courseId);
        ChamiloSession::write('_course', $courseInfo);
        ChamiloSession::erase('sid');
        ChamiloSession::erase('gid');
    }

    /**
     * @param array{title: string, code: string, archive: string, illustration: string, visible_tool: string} $definition
     */
    private function loadDemoCourse(array $definition, User $admin, int $visibility): void
    {
        if ($this->courseRepository->findOneByCode($definition['code']) instanceof Course) {
            $this->logger->info('Demo course already exists, skipping fixture import.', [
                'code' => $definition['code'],
            ]);

            return;
        }

        $resourceDir = \dirname(__DIR__).'/Resources/fixtures/courses';
        $archivePath = $resourceDir.'/'.$definition['archive'];

        // The fixture is intentionally ready before the final reviewed course is committed.
        if (!is_file($archivePath)) {
            $this->logger->info('Demo course archive is not available, skipping fixture import.', [
                'code' => $definition['code'],
                'archive' => $archivePath,
            ]);

            return;
        }

        $legacyCourse = null;

        try {
            $importer = new MoodleImport();
            $legacyCourse = $importer->buildLegacyCourseFromMoodleArchive($archivePath);
            $archiveLanguage = trim((string) ($legacyCourse->meta['moodle']['language'] ?? ''));

            $courseParams = [
                'title' => $definition['title'],
                'wanted_code' => $definition['code'],
                'user_id' => (int) $admin->getId(),
                'exemplary_content' => false,
                'visibility' => $visibility,
            ];

            if ('' !== $archiveLanguage) {
                $courseParams['course_language'] = $archiveLanguage;
            }

            $illustrationPath = $resourceDir.'/'.$definition['illustration'];
            if (is_file($illustrationPath)) {
                $courseParams['illustration_path'] = $illustrationPath;
            }

            $course = $this->courseHelper->createCourse($courseParams);
            if (!$course instanceof Course) {
                throw new RuntimeException(\sprintf('Could not create demo course "%s".', $definition['code']));
            }

            $courseCode = $course->getCode();
            $this->primeLegacyCourseContext($course);

            // CourseHelper creates the complete Course/CTool graph while Doctrine fixtures
            // are still running in one transaction. Flush and clear that graph before the
            // legacy restore so repositories used by CourseRestorer reload managed entities
            // from the current fixture transaction instead of mixing them with stale objects.
            $this->entityManager->flush();
            $this->entityManager->clear();

            $legacyCourse->code = $courseCode;
            $resourcesAll = (array) ($legacyCourse->resources ?? []);

            // Keep parity with CourseMaintenanceController::importRestore(): LP items may
            // reference documents, quizzes, surveys, links or works that must be present in
            // the working resource bags before CourseRestorer starts.
            $this->hydrateLpDependenciesFromSnapshot($legacyCourse, $resourcesAll);
            $this->normalizeBucketsForRestorer($legacyCourse);

            $restorer = new CourseRestorer($legacyCourse);
            $this->ensureFileOverwriteConstant();
            $restorer->set_file_option(FILE_OVERWRITE);
            $restorer->setResourcesAllSnapshot($resourcesAll);
            $restoreCourseSettings = !empty($legacyCourse->resources['course_settings']);
            $restorer->restore($course->getCode(), 0, $restoreCourseSettings);

            // CourseRestorer may clear Doctrine while recovering from an individual
            // tool restore. Never persist the pre-restore Course/CTool graph again:
            // reload the destination course so all associations are managed by the
            // current EntityManager before applying demo-course settings.
            $this->entityManager->clear();

            $managedCourse = $this->courseRepository->findOneByCode($definition['code']);
            if (!$managedCourse instanceof Course) {
                throw new RuntimeException(\sprintf('Could not reload demo course "%s" after restore.', $definition['code']));
            }

            $managedCourse->setVisibility($visibility);
            $this->enableUserLanguageContent($managedCourse);
            $this->keepOnlyToolVisible($managedCourse, $definition['visible_tool']);
            $this->entityManager->flush();
        } catch (Throwable $e) {
            throw new RuntimeException(\sprintf('Could not import demo course "%s": %s', $definition['code'], $e->getMessage()), 0, $e);
        } finally {
            $workDir = \is_object($legacyCourse) ? (string) ($legacyCourse->backup_path ?? '') : '';
            if ('' !== $workDir && is_dir($workDir)) {
                (new Filesystem())->remove($workDir);
            }
        }
    }

    /**
     * Copy dependencies referenced by selected learning paths from the full resource snapshot.
     *
     * This mirrors AbstractCourseMaintenanceController::hydrateLpDependenciesFromSnapshot()
     * used by the regular Moodle import flow.
     *
     * @param array<string, mixed> $snapshot
     */
    private function hydrateLpDependenciesFromSnapshot(object $course, array $snapshot): void
    {
        if (empty($course->resources['learnpath']) || !\is_array($course->resources['learnpath'])) {
            return;
        }

        $need = [];
        $addNeed = static function (string $type, mixed $id) use (&$need): void {
            $resourceId = is_numeric($id) ? (int) $id : (string) $id;
            if ('' === (string) $resourceId || 0 === (int) $resourceId) {
                return;
            }

            $need[$type] ??= [];
            $need[$type][$resourceId] = true;
        };

        foreach ($course->resources['learnpath'] as $lpWrap) {
            $lp = \is_object($lpWrap) && isset($lpWrap->obj) ? $lpWrap->obj : $lpWrap;

            if (\is_object($lpWrap) && !empty($lpWrap->linked_resources) && \is_array($lpWrap->linked_resources)) {
                foreach ($lpWrap->linked_resources as $type => $ids) {
                    if (!\is_array($ids)) {
                        continue;
                    }

                    foreach ($ids as $resourceId) {
                        $addNeed((string) $type, $resourceId);
                    }
                }
            }

            $items = [];
            if (\is_object($lp) && !empty($lp->items) && \is_array($lp->items)) {
                $items = $lp->items;
            } elseif (\is_object($lpWrap) && !empty($lpWrap->items) && \is_array($lpWrap->items)) {
                $items = $lpWrap->items;
            }

            foreach ($items as $item) {
                $itemObject = \is_object($item) ? $item : (object) $item;

                if (!empty($itemObject->linked_resources) && \is_array($itemObject->linked_resources)) {
                    foreach ($itemObject->linked_resources as $type => $ids) {
                        if (!\is_array($ids)) {
                            continue;
                        }

                        foreach ($ids as $resourceId) {
                            $addNeed((string) $type, $resourceId);
                        }
                    }
                }

                foreach ([
                    'document_id' => 'document',
                    'doc_id' => 'document',
                    'link_id' => 'link',
                    'quiz_id' => 'quiz',
                    'work_id' => 'work',
                ] as $field => $type) {
                    if (isset($itemObject->{$field}) && '' !== (string) $itemObject->{$field}) {
                        $addNeed($type, $itemObject->{$field});
                    }
                }

                if (!empty($itemObject->type) && isset($itemObject->ref)) {
                    $addNeed((string) $itemObject->type, $itemObject->ref);
                }
            }
        }

        foreach ($need as $type => $ids) {
            if (empty($snapshot[$type]) || !\is_array($snapshot[$type])) {
                continue;
            }

            $course->resources[$type] ??= [];

            foreach (array_keys($ids) as $resourceId) {
                $source = $snapshot[$type][$resourceId]
                    ?? $snapshot[$type][(string) $resourceId]
                    ?? null;

                if (null === $source) {
                    continue;
                }

                if (!isset($course->resources[$type][$resourceId]) && !isset($course->resources[$type][(string) $resourceId])) {
                    $course->resources[$type][$resourceId] = $source;
                }
            }
        }
    }

    /**
     * Normalize Moodle resource aliases to the bucket names expected by CourseRestorer.
     *
     * This mirrors the normalization used by the regular course-maintenance import flow,
     * so fixture imports and UI imports restore the same resource types.
     */
    private function normalizeBucketsForRestorer(object $course): void
    {
        if (!isset($course->resources) || !\is_array($course->resources)) {
            return;
        }

        $all = $course->resources;
        $meta = [];

        foreach ($all as $key => $value) {
            if (\is_string($key) && str_starts_with($key, '__')) {
                $meta[$key] = $value;
                unset($all[$key]);
            }
        }

        $out = $all;
        $merge = static function (array $destination, array $source): array {
            foreach ($source as $id => $resource) {
                if (!\array_key_exists($id, $destination)) {
                    $destination[$id] = $resource;
                }
            }

            return $destination;
        };

        $aliases = [
            'documents' => 'document',
            'document ' => 'document',
            'Document' => 'document',
            'tool introduction' => 'tool_intro',
            'tool_introduction' => 'tool_intro',
            'tool/introduction' => 'tool_intro',
            'tool intro' => 'tool_intro',
            'Tool introduction' => 'tool_intro',
            'forums' => 'forum',
            'Forum' => 'forum',
            'forum_category' => 'forum_category',
            'Forum_Category' => 'forum_category',
            'forumcategory' => 'forum_category',
            'thread' => 'forum_topic',
            'Thread' => 'forum_topic',
            'forumtopic' => 'forum_topic',
            'forum_topic' => 'forum_topic',
            'post' => 'forum_post',
            'Post' => 'forum_post',
            'forumpost' => 'forum_post',
            'forum_post' => 'forum_post',
            'links' => 'link',
            'link category' => 'link_category',
            'link_category' => 'link_category',
            'Link_Category' => 'link_category',
            'quizzes' => 'quiz',
            'quiz_questions' => 'Exercise_Question',
            'quiz question' => 'Exercise_Question',
            'quiz/questions' => 'Exercise_Question',
            'exercise_questions' => 'Exercise_Question',
            'exercisequestion' => 'Exercise_Question',
            'quiz_question' => 'Exercise_Question',
            'exercise_question' => 'Exercise_Question',
            'surveys' => 'survey',
            'survey_questions' => 'survey_question',
            'survey question' => 'survey_question',
            'survey/questions' => 'survey_question',
            'surveyquestion' => 'survey_question',
            'announcements' => 'announcement',
            'announcement' => 'announcement',
            'news' => 'announcement',
            'Announcements' => 'announcement',
            'events' => 'event',
            'event' => 'event',
            'calendar_event' => 'event',
            'calendar events' => 'event',
            'course_descriptions' => 'course_description',
            'course descriptions' => 'course_description',
            'glossaries' => 'glossary',
            'works' => 'work',
            'learnpaths' => 'learnpath',
            'learnpath categories' => 'learnpath_category',
            'learnpath_categories' => 'learnpath_category',
            'assets' => 'asset',
            'attendance' => 'attendance',
            'gradebook' => 'gradebook',
            'Gradebook' => 'gradebook',
            'wiki' => 'wiki',
            'scorm' => 'scorm',
            'scorm_documents' => 'scorm_documents',
            'thematic' => 'thematic',
        ];

        foreach ($all as $rawKey => $bucket) {
            if (!\is_array($bucket)) {
                continue;
            }

            $key = (string) $rawKey;
            $normalized = strtolower(trim(strtr($key, ['\\' => '/', '-' => '_'])));
            $normalizedUnderscore = str_replace('/', '_', $normalized);
            $canonical = $aliases[$normalized] ?? $aliases[$normalizedUnderscore] ?? null;

            if ($canonical && $canonical !== $rawKey) {
                $out[$canonical] = isset($out[$canonical]) && \is_array($out[$canonical])
                    ? $merge($out[$canonical], $bucket)
                    : $bucket;

                unset($out[$rawKey]);
            }
        }

        if (!isset($out['document'])) {
            if (isset($all['documents']) && \is_array($all['documents'])) {
                $out['document'] = $all['documents'];
            } elseif (isset($all['Document']) && \is_array($all['Document'])) {
                $out['document'] = $all['Document'];
            }
        }

        $order = [
            'announcement',
            'document',
            'link',
            'link_category',
            'forum',
            'forum_category',
            'forum_topic',
            'forum_post',
            'quiz',
            'Exercise_Question',
            'survey',
            'survey_question',
            'event',
            'course_description',
            'glossary',
            'work',
            'learnpath_category',
            'learnpath',
            'tool_intro',
            'attendance',
            'gradebook',
            'wiki',
            'thematic',
            'scorm',
            'scorm_documents',
            'asset',
        ];
        $weights = [];

        foreach ($order as $index => $key) {
            $weights[$key] = $index;
        }

        uksort($out, static function ($left, $right) use ($weights): int {
            $leftWeight = $weights[$left] ?? 9999;
            $rightWeight = $weights[$right] ?? 9999;

            return $leftWeight <=> $rightWeight ?: strcasecmp((string) $left, (string) $right);
        });

        $course->resources = $meta + $out;
    }

    private function enableUserLanguageContent(Course $course): void
    {
        $repository = $this->entityManager->getRepository(CCourseSetting::class);
        $items = $repository->findBy([
            'cId' => (int) $course->getId(),
            'variable' => 'show_course_in_user_language',
        ], ['iid' => 'ASC']);

        if ([] === $items) {
            $setting = (new CCourseSetting())
                ->setCId((int) $course->getId())
                ->setVariable('show_course_in_user_language')
                ->setTitle('show_course_in_user_language')
                ->setCategory('')
                ->setValue('1')
            ;
            $this->entityManager->persist($setting);

            return;
        }

        foreach ($items as $setting) {
            if (!$setting instanceof CCourseSetting) {
                continue;
            }

            $setting->setValue('1');
            $this->entityManager->persist($setting);
        }
    }

    private function keepOnlyToolVisible(Course $course, string $visibleTool): void
    {
        $visibleTool = strtolower($this->toolChain->normalizeCourseToolName($visibleTool));

        foreach ($course->getTools() as $courseTool) {
            $toolName = strtolower($this->toolChain->normalizeCourseToolName($courseTool->getTitle()));
            $courseTool->setVisibility($visibleTool === $toolName);
            $this->entityManager->persist($courseTool);
        }
    }

    private function ensureFileOverwriteConstant(): void
    {
        if (!\defined('FILE_OVERWRITE')) {
            \define('FILE_OVERWRITE', 3);
        }
    }
}
