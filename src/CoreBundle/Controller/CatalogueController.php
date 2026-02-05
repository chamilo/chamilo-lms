<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use BuyCoursesPlugin;
use Chamilo\CoreBundle\Entity\Admin;
use Chamilo\CoreBundle\Entity\CatalogueCourseRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\CatalogueSessionRelAccessUrlRelUsergroup;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UsergroupRelUser;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Repository\UserRelCourseVoteRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ExtraField;
use ExtraFieldValue;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/catalogue')]
class CatalogueController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserHelper $userHelper,
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly CourseRepository $courseRepository,
        private readonly SessionRepository $sessionRepository,
        private readonly UserRelCourseVoteRepository $courseVoteRepository,
    ) {}

    #[Route('/api/courses/{id}/rating', name: 'api_course_rating', methods: ['GET'])]
    public function courseRating(Course $course, Request $request): JsonResponse
    {
        $sessionId = $request->query->getInt('session', 0);
        $session = $sessionId > 0 ? $this->sessionRepository->find($sessionId) : null;
        $rating = $this->courseVoteRepository->getCouseRating($course, $session);

        return $this->json($rating);
    }
    #[Route('/api/courses/{id}/visits', name: 'api_course_visits', methods: ['GET'])]
    public function courseVisits(Course $course, Request $request, TrackECourseAccessRepository $courseAccessRepository): JsonResponse
    {
        $sessionId = $request->query->getInt('session', 0);
        $session = $sessionId > 0 ? $this->sessionRepository->find($sessionId) : null;
        $count = $courseAccessRepository->getCourseVisits($course, $session);

        return $this->json(['visits' => $count]);
    }

    #[Route('/courses-list', name: 'chamilo_core_catalogue_courses_list', methods: ['GET'])]
    public function listCourses(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $relRepo = $this->em->getRepository(CatalogueCourseRelAccessUrlRelUsergroup::class);
        $userGroupRepo = $this->em->getRepository(UsergroupRelUser::class);

        $relations = $relRepo->findBy(['accessUrl' => $accessUrl]);

        if (empty($relations)) {
            $courses = $this->courseRepository->findAll();
        } else {
            $userGroups = $userGroupRepo->findBy(['user' => $user]);
            $userGroupIds = array_map(fn ($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleCourses = [];

            foreach ($relations as $rel) {
                $course = $rel->getCourse();
                $usergroup = $rel->getUsergroup();

                if (null === $usergroup || \in_array($usergroup->getId(), $userGroupIds)) {
                    $visibleCourses[$course->getId()] = $course;
                }
            }

            $courses = array_values($visibleCourses);
        }

        $data = array_map(function (Course $course) {
            return [
                'id' => $course->getId(),
                'code' => $course->getCode(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'visibility' => $course->getVisibility(),
            ];
        }, $courses);

        return $this->json($data);
    }

    #[Route('/sessions-list', name: 'chamilo_core_catalogue_sessions_list', methods: ['GET'])]
    public function listSessions(): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        $relRepo = $this->em->getRepository(CatalogueSessionRelAccessUrlRelUsergroup::class);
        $userGroupRepo = $this->em->getRepository(UsergroupRelUser::class);
        $voteRepo = $this->em->getRepository(UserRelCourseVote::class);

        $relations = $relRepo->findBy(['accessUrl' => $accessUrl]);

        if (empty($relations)) {
            $sessions = $this->sessionRepository->findAll();
        } else {
            $userGroups = $userGroupRepo->findBy(['user' => $user]);
            $userGroupIds = array_map(fn ($ug) => $ug->getUsergroup()->getId(), $userGroups);

            $visibleSessions = [];

            foreach ($relations as $rel) {
                $session = $rel->getSession();
                $usergroup = $rel->getUsergroup();

                if (null === $usergroup || \in_array($usergroup->getId(), $userGroupIds)) {
                    $visibleSessions[$session->getId()] = $session;
                }
            }

            $sessions = array_values($visibleSessions);
        }

        $data = array_map(function (Session $session) use ($voteRepo, $user) {
            $courses = [];

            foreach ($session->getCourses() as $rel) {
                $course = $rel->getCourse();
                if (!$course) {
                    continue;
                }

                $teachers = [];
                foreach ($session->getGeneralCoachesSubscriptions() as $coachRel) {
                    $userObj = $coachRel->getUser();
                    if ($userObj) {
                        $teachers[] = [
                            'id' => $userObj->getId(),
                            'fullName' => $userObj->getFullName(),
                        ];
                    }
                }

                $courses[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                    'duration' => $course->getDuration(),
                    'courseLanguage' => $course->getCourseLanguage(),
                    'teachers' => $teachers,
                ];
            }

            $voteCount = (int) $voteRepo->createQueryBuilder('v')
                ->select('COUNT(DISTINCT v.user)')
                ->where('v.session = :session')
                ->andWhere('v.course IS NULL')
                ->setParameter('session', $session->getId())
                ->getQuery()
                ->getSingleScalarResult()
            ;

            $buyCoursesPlugin = BuyCoursesPlugin::create();
            $buyData = $buyCoursesPlugin->getBuyCoursePluginPrice($session);
            $isSubscribed = ($user instanceof User) ? $session->hasUserInSession($user, Session::STUDENT) : false;

            return [
                'id' => $session->getId(),
                'title' => $session->getTitle(),
                'description' => $session->getDescription(),
                'imageUrl' => $session->getImageUrl(),
                'visibility' => $session->getVisibility(),
                'nbrUsers' => $session->getNbrUsers(),
                'nbrCourses' => $session->getNbrCourses(),
                'startDate' => $session->getAccessStartDate()?->format('Y-m-d'),
                'endDate' => $session->getAccessEndDate()?->format('Y-m-d'),
                'courses' => $courses,
                'popularity' => $voteCount,
                'isSubscribed' => $isSubscribed,
                'priceHtml' => $buyData['html'] ?? '',
                'buyButtonHtml' => $buyData['buy_button'] ?? '',
            ];
        }, $sessions);

        return $this->json($data);
    }

    private function readCatalogueSettings(SettingsManager $settingsManager): array
    {
        $raw = $settingsManager->getSetting('catalog.course_catalog_settings');
        if (empty($raw)) {
            return [];
        }

        if (\is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        if (isset($raw['courses']) && \is_array($raw['courses'])) {
            return $raw['courses'];
        }

        return \is_array($raw) ? $raw : [];
    }

    #[Route('/course-extra-fields', name: 'chamilo_core_catalogue_course_extra_fields', methods: ['GET'])]
    public function getCourseExtraFields(SettingsManager $settingsManager): JsonResponse
    {
        $settings = $this->readCatalogueSettings($settingsManager);
        if (empty($settings)) {
            return $this->json([]);
        }

        $allowed = array_map('strval', $settings['extra_fields_in_search_form'] ?? []);

        $ef = new ExtraField('course');
        $raw = $ef->get_all(['filter = ?' => 1, 'AND visible_to_self = ?' => 1], 'option_order');

        $mapped = array_map(function ($f) {
            $type = (int) $f['value_type'];

            $base = [
                'variable' => (string) $f['variable'],
                'title' => (string) ($f['display_text'] ?? $f['variable']),
                'value_type' => $type,
                'defaultValue' => $f['field_default_value'] ?? null,
            ];

            $options = [];
            if (!empty($f['options']) && \is_array($f['options'])) {
                foreach ($f['options'] as $opt) {
                    $options[] = [
                        'id' => isset($opt['id']) ? (int) $opt['id'] : 0,
                        'value' => isset($opt['option_value']) ? (string) $opt['option_value'] : (string) ($opt['id'] ?? ''),
                        'label' => (string) ($opt['display_text'] ?? $opt['option_value'] ?? ''),
                        'parent' => isset($opt['parent_id']) ? (int) $opt['parent_id'] : 0,
                    ];
                }
            }

            $typesWithOptions = [
                ExtraField::FIELD_TYPE_SELECT,
                ExtraField::FIELD_TYPE_SELECT_MULTIPLE,
                ExtraField::FIELD_TYPE_DOUBLE_SELECT,
                ExtraField::FIELD_TYPE_TRIPLE_SELECT,
                ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD,
                ExtraField::FIELD_TYPE_RADIO,
                ExtraField::FIELD_TYPE_CHECKBOX,
                ExtraField::FIELD_TYPE_TAG,
            ];

            if (\in_array($type, $typesWithOptions, true)) {
                $base['options'] = $options;
            }

            return $base;
        }, $raw);

        if (!empty($allowed)) {
            $byVar = [];
            foreach ($mapped as $row) {
                $byVar[$row['variable']] = $row;
            }
            $ordered = [];
            foreach ($allowed as $var) {
                if (isset($byVar[$var])) {
                    $ordered[] = $byVar[$var];
                }
            }
            $mapped = $ordered;
        }

        return $this->json(array_values($mapped));
    }

    #[Route('/course-extra-field-values', name: 'chamilo_core_catalogue_course_extra_field_values', methods: ['GET'])]
    public function getCourseExtraFieldValues(Request $request, SettingsManager $settingsManager): JsonResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query->get('ids', ''))));
        if (!$ids) {
            return $this->json(new stdClass());
        }

        $settings = $this->readCatalogueSettings($settingsManager);

        // Union of allowed variables (search form ∪ course card)
        $allowedSearch = array_map('strval', $settings['extra_fields_in_search_form'] ?? []);
        $allowedCard = array_map('strval', $settings['extra_fields_in_course_block'] ?? []);
        $allowedVars = array_values(array_unique(array_filter(array_merge($allowedSearch, $allowedCard))));

        // Force-include variables that we always want to expose
        $allowedVars = array_values(array_unique(array_merge($allowedVars, ['video_url', 'special_course'])));

        if (!$allowedVars) {
            return $this->json(new stdClass());
        }

        $ef = new ExtraField('course');
        $efv = new ExtraFieldValue('course');

        // Build metadata maps (by variable and by id)
        $allFields = $ef->get_all(); // rows: ['id','variable','value_type','field_default_value', ...]
        $byVar = [];
        $byId = [];

        foreach ($allFields as $f) {
            $var = (string) ($f['variable'] ?? '');
            if (!$var) {
                continue;
            }
            if (!\in_array($var, $allowedVars, true)) {
                continue; // only expose what we explicitly allow
            }

            $type = (int) ($f['value_type'] ?? 0);
            $default = $f['field_default_value'] ?? null;

            $byVar[$var] = [
                'id' => (int) ($f['id'] ?? 0),
                'value_type' => $type,
                'default_raw' => $default,
            ];
            if (!empty($f['id'])) {
                $byId[(int) $f['id']] = $var;
            }
        }

        // If settings reference a variable that doesn't exist, still include it with a null-like default
        foreach ($allowedVars as $var) {
            if (!isset($byVar[$var])) {
                $byVar[$var] = [
                    'id' => 0,
                    'value_type' => 0,     // unknown → treat as text-like
                    'default_raw' => null,
                ];
            }
        }

        $out = [];

        foreach ($ids as $courseId) {
            $values = [];
            $rows = method_exists($efv, 'getAllValuesByItem') ? $efv->getAllValuesByItem($courseId) : null;
            if (!\is_array($rows) || !$rows) {
                $rows = method_exists($efv, 'get_values_by_item') ? $efv->get_values_by_item($courseId) : null;
            }

            if (!\is_array($rows) || !$rows) {
                $rows = method_exists($ef, 'getDataAndFormattedValues')
                    ? $ef->getDataAndFormattedValues($courseId, false, array_keys($byVar))
                    : null;
            }

            // Normalize bulk rows into { var => value }
            if (\is_array($rows)) {
                // Handle both shapes: list-of-rows and map-by-variable
                $hasStringKeys = static function (array $a): bool {
                    foreach (array_keys($a) as $k) {
                        if (\is_string($k)) {
                            return true;
                        }
                    }

                    return false;
                };

                if ($hasStringKeys($rows) && !isset($rows[0])) {
                    // Shape A: map variable => value/row
                    foreach ($rows as $var => $valRaw) {
                        if (!isset($byVar[$var])) {
                            continue;
                        }

                        $type = (int) ($byVar[$var]['value_type'] ?? 0);
                        $val = $valRaw;
                        $arr = null;

                        if (\is_array($valRaw)) {
                            // Common keys across Chamilo providers
                            $val = $valRaw['field_value'] ?? $valRaw['value'] ?? $valRaw['value_raw'] ?? null;
                            $arr = $valRaw['value_as_array'] ?? $valRaw['value_array'] ?? null;

                            // Prefer explicit type if row provides it
                            if (isset($valRaw['value_type']) || isset($valRaw['field_type'])) {
                                $type = (int) ($valRaw['value_type'] ?? $valRaw['field_type']);
                            }
                        }

                        $values[$var] = $this->normaliseValueForType($type, $val, \is_array($arr) ? $arr : null);
                    }
                } else {
                    // Shape B: list of rows (possibly indexed by field ID)
                    foreach ($rows as $key => $r) {
                        // Resolve variable
                        $var = (string) ($r['variable'] ?? $r['field_variable'] ?? '');
                        if (!$var && isset($r['id'], $byId[(int) $r['id']])) {
                            $var = $byId[(int) $r['id']];
                        }
                        if (!$var && isset($r['field_id'], $byId[(int) $r['field_id']])) {
                            $var = $byId[(int) $r['field_id']];
                        }
                        if (!$var || !isset($byVar[$var])) {
                            continue;
                        }

                        $type = (int) ($r['value_type'] ?? $r['field_type'] ?? $byVar[$var]['value_type'] ?? 0);
                        $val = $r['field_value'] ?? $r['value'] ?? null;
                        $arr = $r['value_as_array'] ?? $r['value_array'] ?? null;

                        $values[$var] = $this->normaliseValueForType($type, $val, \is_array($arr) ? $arr : null);
                    }
                }
            }

            $missing = array_diff(array_keys($byVar), array_keys($values));
            foreach ($missing as $var) {
                $meta = $byVar[$var];
                $type = (int) ($meta['value_type'] ?? 0);
                $val = null;
                $row = null;

                // Prefer lookup by field_id when available
                if (!empty($meta['id'])) {
                    $row = $efv->get_values_by_handler_and_field_id($courseId, (int) $meta['id'], false);
                }
                // Fallback by variable
                if (!$row && method_exists($efv, 'get_values_by_handler_and_field_variable')) {
                    $row = $efv->get_values_by_handler_and_field_variable($courseId, $var, false);
                }

                if (\is_array($row)) {
                    // Unify shape
                    $type = (int) ($row['value_type'] ?? $type);
                    $val = $row['field_value'] ?? $row['value'] ?? null;
                    $values[$var] = $this->normaliseValueForType($type, $val, null);
                }
            }

            // Ensure all allowed vars exist with sensible defaults
            $norm = [];
            foreach ($byVar as $var => $meta) {
                if (\array_key_exists($var, $values)) {
                    $norm[$var] = $values[$var];
                } else {
                    $norm[$var] = $this->normaliseDefaultForType(
                        (int) ($meta['value_type'] ?? 0),
                        $meta['default_raw'] ?? null
                    );
                }
            }

            $out[$courseId] = (object) $norm;
        }

        return $this->json($out);
    }

    /**
     * Normalizes a stored value for the given type so the frontend gets consistent shapes:
     * - Checkbox => boolean
     * - Multiselect/Tags => array<string>
     * - Double/Triple/Select+Text => array when applicable (or string)
     *
     * @param mixed $value
     */
    private function normaliseValueForType(int $type, $value, ?array $arrayValue)
    {
        switch ($type) {
            case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
            case ExtraField::FIELD_TYPE_TAG:
                if (\is_array($arrayValue)) {
                    return array_values($arrayValue);
                }
                if (null === $value || '' === $value) {
                    return [];
                }

                return \is_array($value) ? array_values($value) : [(string) $value];

            case ExtraField::FIELD_TYPE_CHECKBOX:
                if (\is_bool($value)) {
                    return $value;
                }
                $v = strtolower((string) $value);

                return \in_array($v, ['1', 'true', 'yes', 'on'], true);

            case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
            case ExtraField::FIELD_TYPE_TRIPLE_SELECT:
            case ExtraField::FIELD_TYPE_SELECT_WITH_TEXT_FIELD:
                if (\is_array($arrayValue)) {
                    return array_values($arrayValue);
                }
                if (\is_array($value)) {
                    return array_values($value);
                }

                return $value;

            default:
                return $value;
        }
    }

    /**
     * Provides a sensible default when the course has no stored value:
     * - Checkbox => false (unless default_raw explicitly says otherwise)
     * - Multiselect/Tags => []
     * - Others => null (or normalized default_raw when present)
     *
     * @param mixed $defaultRaw
     */
    private function normaliseDefaultForType(int $type, $defaultRaw)
    {
        // If a default is set at field level, try to normalize it first.
        if (null !== $defaultRaw && '' !== $defaultRaw) {
            return $this->normaliseValueForType($type, $defaultRaw, \is_array($defaultRaw) ? $defaultRaw : null);
        }

        switch ($type) {
            case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
            case ExtraField::FIELD_TYPE_TAG:
                return [];

            case ExtraField::FIELD_TYPE_CHECKBOX:
                return false;

            default:
                return null;
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/auto-subscribe-course/{courseId}', name: 'chamilo_core_catalogue_auto_subscribe_course', methods: ['POST'])]
    public function autoSubscribeCourse(int $courseId, SettingsManager $settings): JsonResponse
    {
        $user = $this->userHelper->getCurrent();
        $course = $this->em->getRepository(Course::class)->find($courseId);

        if (!$user || !$course) {
            return $this->json(['error' => 'Course or user not found'], 400);
        }

        $isPrivileged = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_SESSION_ADMIN');
        if (!$course->getAllowSelfSignup() && !$isPrivileged) {
            return $this->json(['error' => 'Self sign up not allowed for this course'], 403);
        }

        $useAutoSession = 'true' === $settings->getSetting('catalog.course_subscription_in_user_s_session', true);

        if ($useAutoSession) {
            $session = new Session();
            $timestamp = (new DateTime())->format('Ymd_His');
            $sessionTitle = \sprintf('%s %s - Session %s', $user->getFirstname(), $user->getLastname(), $timestamp);
            $session->setTitle($sessionTitle);

            $session->setAccessStartDate(new DateTime());
            $session->setAccessEndDate(null);
            $session->setCoachAccessEndDate(null);
            $session->setDisplayEndDate(null);
            $session->setSendSubscriptionNotification(false);

            $adminIdSetting = $settings->getSetting('session.session_automatic_creation_user_id');
            $adminId = null;

            if (is_numeric($adminIdSetting) && (int) $adminIdSetting > 0) {
                $adminUser = $this->em->getRepository(User::class)->find((int) $adminIdSetting);
                if ($adminUser) {
                    $adminId = $adminUser->getId();
                }
            }

            if (!$adminId) {
                $adminEntity = $this->em->getRepository(Admin::class)->findOneBy([]);
                if ($adminEntity) {
                    $adminId = $adminEntity->getUser()->getId();
                }
            }

            if ($adminId) {
                $adminUser = $this->em->getRepository(User::class)->find($adminId);
                if ($adminUser) {
                    $session->addSessionAdmin($adminUser);
                }
            }

            $session->addUserInSession(Session::STUDENT, $user);
            $session->addAccessUrl($this->accessUrlHelper->getCurrent());
            $session->addCourse($course);
            $session->addUserInCourse(Session::STUDENT, $user, $course);

            $this->em->persist($session);
            $this->em->flush();
        }

        return $this->json([
            'message' => 'User subscribed successfully.',
            'sessionId' => $session?->getId(),
        ]);
    }
}
