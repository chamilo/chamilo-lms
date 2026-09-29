<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupCategory;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Repository\CGroupCategoryRepository;
use Chamilo\CourseBundle\Repository\CGroupRepository;
use CourseManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use GroupManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UserGroupModel;

use const INVITEE;
use const PHP_INT_MAX;

final readonly class CourseGroupManager
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CGroupRepository $groupRepository,
        private CGroupCategoryRepository $categoryRepository,
        private UrlGeneratorInterface $router,
    ) {}

    /**
     * @return array{0: Course, 1: Session|null}
     */
    public function resolveContext(): array
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not contain the current course.');
        }

        return [$course, $session];
    }

    public function canManage(Course $course, ?Session $session): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($course->hasUserAsTeacher($user) || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$session instanceof Session) {
            return false;
        }

        return $session->hasUserAsGeneralCoach($user)
            || $session->hasCourseCoachInCourse($user, $course)
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    public function canRead(Course $course, ?Session $session): bool
    {
        if ($this->canManage($course, $session)) {
            return true;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $session instanceof Session
            ? $session->hasUserInCourse($user, $course)
            : $course->hasSubscriptionByUser($user);
    }

    public function assertCanManage(Course $course, ?Session $session): void
    {
        if (!$this->canManage($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage groups in this course context.');
        }
    }

    public function assertCanRead(Course $course, ?Session $session): void
    {
        if (!$this->canRead($course, $session)) {
            throw new AccessDeniedHttpException('You are not allowed to view groups in this course context.');
        }
    }

    public function findGroup(int $groupId, Course $course, ?Session $session): CGroup
    {
        if ($groupId <= 0) {
            throw new BadRequestHttpException('A valid group id is required.');
        }

        $queryBuilder = $this->groupRepository->getResourcesByCourse($course, $session);
        $queryBuilder
            ->andWhere('resource.iid = :groupId')
            ->setParameter('groupId', $groupId, Types::INTEGER)
        ;
        $group = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$group instanceof CGroup) {
            throw new NotFoundHttpException('The requested group was not found in this course context.');
        }

        return $group;
    }

    public function findCategory(int $categoryId, Course $course, ?Session $session): CGroupCategory
    {
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid category id is required.');
        }

        $queryBuilder = $this->categoryRepository->getResourcesByCourse($course, $session);
        $queryBuilder
            ->andWhere('resource.iid = :categoryId')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
        ;
        $category = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$category instanceof CGroupCategory) {
            throw new NotFoundHttpException('The requested category was not found in this course context.');
        }

        return $category;
    }

    /**
     * @return array<string, mixed>
     */
    public function getListData(Request $request): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanRead($course, $session);

        $canManage = $this->canManage($course, $session);
        $keyword = trim((string) $request->query->get('search', ''));
        $allowCategories = $this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true));
        $currentUser = $this->security->getUser();
        $currentUserId = $currentUser instanceof User ? (int) $currentUser->getId() : 0;
        $hideGroupsWithoutAvailableTools = $this->isEnabled(
            $this->settingsManager->getSetting('hide_course_group_if_no_tools_available', true),
        );
        $showTutorEmail = $canManage || $this->isEnabled(
            $this->settingsManager->getSetting('show_email_addresses', true),
        );

        $groupQueryBuilder = $this->groupRepository->getResourcesByCourse($course, $session);
        $groupQueryBuilder->orderBy('resource.title', 'ASC');

        /** @var CGroup[] $groups */
        $groups = $groupQueryBuilder->getQuery()->getResult();

        $categoryQueryBuilder = $this->categoryRepository->getResourcesByCourse($course, $session);

        /** @var CGroupCategory[] $categories */
        $categories = $categoryQueryBuilder->getQuery()->getResult();
        if ([] === $categories && $allowCategories && $canManage) {
            GroupManager::create_category(
                get_lang('Default groups'),
                '',
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                CGroup::TOOL_NOT_AVAILABLE,
                false,
                false,
                1,
            );

            /** @var CGroupCategory[] $categories */
            $categories = $this->categoryRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        }
        $categoryCount = \count($categories);
        $categoryOrder = [];
        foreach (GroupManager::get_categories($course) as $position => $categoryData) {
            $categoryOrder[(int) ($categoryData['iid'] ?? 0)] = $position;
        }
        usort(
            $categories,
            static fn (CGroupCategory $first, CGroupCategory $second): int => ($categoryOrder[(int) $first->getIid()] ?? PHP_INT_MAX)
            <=> ($categoryOrder[(int) $second->getIid()] ?? PHP_INT_MAX)
        );

        $categoryItems = [];
        $categoryMap = [];
        $defaultCategoryId = 0;
        foreach ($categories as $category) {
            $categoryId = (int) $category->getIid();
            if (0 === $defaultCategoryId) {
                $defaultCategoryId = $categoryId;
            }
            $categoryMap[$categoryId] = \count($categoryItems);
            $categoryItems[] = [
                'id' => $categoryId,
                'title' => $category->getTitle(),
                'description' => (string) $category->getDescription(),
                'groups' => [],
                'canEdit' => $canManage && !$session instanceof Session,
                'canDelete' => $canManage && !$session instanceof Session && $categoryCount > 1,
            ];
        }

        $uncategorized = [
            'id' => 0,
            'title' => 'Groups',
            'description' => '',
            'groups' => [],
            'canEdit' => false,
            'canDelete' => false,
        ];

        foreach ($groups as $group) {
            $canBrowse = $canManage || ($currentUser instanceof User && GroupManager::userHasAccessToBrowse(
                $currentUserId,
                $group,
                $session?->getId() ?? 0,
            ));
            if (!$canBrowse && $hideGroupsWithoutAvailableTools) {
                continue;
            }

            if (!$canManage
                && !$group->getStatus()
                && (!$currentUser instanceof User || (!$group->hasMember($currentUser) && !$group->hasTutor($currentUser)))
            ) {
                continue;
            }

            if ('' !== $keyword
                && false === mb_stripos($group->getTitle(), $keyword)
                && false === mb_stripos((string) $group->getDescription(), $keyword)
            ) {
                continue;
            }

            $item = $this->normalizeGroup(
                $group,
                $course,
                $session,
                $canManage,
                $canBrowse,
                $showTutorEmail,
                $currentUserId,
            );
            $categoryId = (int) ($group->getCategory()?->getIid() ?? 0);
            if ($allowCategories && $categoryId > 0 && isset($categoryMap[$categoryId])) {
                $categoryItems[$categoryMap[$categoryId]]['groups'][] = $item;
            } elseif ($allowCategories && $defaultCategoryId > 0 && isset($categoryMap[$defaultCategoryId])) {
                // Keep legacy parity: when categories are enabled, groups are displayed in the default category.
                $categoryItems[$categoryMap[$defaultCategoryId]]['groups'][] = $item;
            } else {
                $uncategorized['groups'][] = $item;
            }
        }

        if (!$allowCategories || (0 === $defaultCategoryId && [] !== $uncategorized['groups'])) {
            array_unshift($categoryItems, $uncategorized);
        }

        return [
            'categories' => $categoryItems,
            'totalGroups' => array_sum(array_map(
                static fn (array $category): int => \count((array) $category['groups']),
                $categoryItems,
            )),
            'courseId' => (int) $course->getId(),
            'sessionId' => $session?->getId(),
            'allowCategories' => $allowCategories,
            'canManageCourse' => $canManage,
            'canCreateCategory' => $canManage && null === $session && $allowCategories,
            'defaultCategoryId' => !$allowCategories && null === $session ? $defaultCategoryId : 0,
            'showSubscriptionTabs' => $canManage,
            'showClasses' => $canManage && (!$session instanceof Session || !$this->isEnabled(
                $this->settingsManager->getSetting('session.session_classes_tab_disable', true),
            )),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOverviewData(Request $request): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $keyword = trim((string) $request->query->get('search', ''));
        $groups = $this->groupRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        $result = [];

        foreach ($groups as $group) {
            if (!$group instanceof CGroup) {
                continue;
            }
            $members = $this->getSubscribedUsers($group, $course);
            $tutors = GroupManager::getTutors(['iid' => (int) $group->getIid()]);
            $searchText = $group->getTitle().' '.implode(' ', array_map(
                static fn (array $user): string => (string) ($user['complete_name'] ?? $user['username'] ?? ''),
                array_merge($tutors, $members),
            ));
            if ('' !== $keyword && false === mb_stripos($searchText, $keyword)) {
                continue;
            }

            $result[] = [
                'id' => (int) $group->getIid(),
                'title' => $group->getTitle(),
                'category' => $group->getCategory()?->getTitle() ?? '',
                'tutors' => array_values(array_filter(array_map(
                    static fn (array $user): string => trim((string) ($user['complete_name'] ?? $user['username'] ?? '')),
                    $tutors,
                ))),
                'members' => array_values(array_filter(array_map(
                    static fn (array $user): string => trim((string) ($user['complete_name'] ?? $user['username'] ?? '')),
                    $members,
                ))),
            ];
        }

        usort($result, static fn (array $first, array $second): int => strcasecmp($first['title'], $second['title']));

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroupFormData(Request $request, int $groupId = 0): array
    {
        [$course, $session] = $this->resolveContext();
        $group = $groupId > 0 ? $this->findGroup($groupId, $course, $session) : null;
        if ($group instanceof CGroup) {
            $this->assertCanManageGroup($group, $course, $session);
        } else {
            $this->assertCanManage($course, $session);
        }

        $allowCategories = $this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true));
        $selectedCategoryId = (int) ($group?->getCategory()?->getIid() ?? 0);
        if (!$group instanceof CGroup && $allowCategories) {
            $selectedCategoryId = $request->query->getInt('categoryId');
            if ($selectedCategoryId > 0) {
                $this->findCategory($selectedCategoryId, $course, $session);
            }
        }
        $allowDocumentAccess = $this->isEnabled(
            $this->settingsManager->getSetting('document.group_document_access', true),
        );
        $linkedClass = $group instanceof CGroup ? GroupManager::get_usergroup_link($group) : null;
        $canManage = $this->canManage($course, $session);

        $categories = [];
        if ($allowCategories) {
            foreach (GroupManager::get_categories($course) as $category) {
                $categories[] = [
                    'id' => (int) $category['iid'],
                    'label' => (string) $category['title'],
                ];
            }
            if ($selectedCategoryId <= 0 && [] !== $categories) {
                $selectedCategoryId = (int) $categories[0]['id'];
            }
        }

        $baseGroups = [];
        $allGroups = $this->groupRepository->getResourcesByCourse($course, $session)->getQuery()->getResult();
        foreach ($allGroups as $candidate) {
            if (!$candidate instanceof CGroup || ($group instanceof CGroup && $candidate->getIid() === $group->getIid())) {
                continue;
            }
            $members = \count($this->getSubscribedUsers($candidate, $course));
            if ($members > 0) {
                $baseGroups[] = [
                    'id' => (int) $candidate->getIid(),
                    'label' => $candidate->getTitle(),
                    'members' => $members,
                ];
            }
        }

        $classes = [];
        $classModel = new UserGroupModel();
        $options = ['where' => [' usergroup.course_id = ? ' => (int) $course->getId()]];
        foreach ($classModel->getUserGroupInCourse($options) as $class) {
            $classes[] = [
                'id' => (int) $class['id'],
                'label' => (string) $class['title'],
                'users' => \count($classModel->get_users_by_usergroup((int) $class['id'])),
            ];
        }

        return [
            'groupId' => (int) ($group?->getIid() ?? 0),
            'title' => $group?->getTitle() ?? '',
            'description' => (string) ($group?->getDescription() ?? ''),
            'categoryId' => $selectedCategoryId,
            'maxStudent' => $group?->getMaxStudent() ?? 0,
            'selfRegistrationAllowed' => $group?->getSelfRegistrationAllowed() ?? false,
            'selfUnregistrationAllowed' => $group?->getSelfUnregistrationAllowed() ?? false,
            'docState' => $group?->getDocState() ?? CGroup::TOOL_PRIVATE,
            'workState' => $group?->getWorkState() ?? CGroup::TOOL_PRIVATE,
            'calendarState' => $group?->getCalendarState() ?? CGroup::TOOL_PRIVATE,
            'announcementsState' => $group?->getAnnouncementsState() ?? CGroup::TOOL_PRIVATE,
            'forumState' => $group?->getForumState() ?? CGroup::TOOL_PRIVATE,
            'wikiState' => $group?->getWikiState() ?? CGroup::TOOL_PRIVATE,
            'chatState' => $group?->getChatState() ?? CGroup::TOOL_PRIVATE,
            'documentAccess' => $group?->getDocumentAccess() ?? 0,
            'categories' => $categories,
            'allowCategories' => $allowCategories,
            'allowDocumentAccess' => $allowDocumentAccess,
            'baseGroups' => $baseGroups,
            'classes' => $classes,
            'nextGroupNumber' => \count($allGroups) + 1,
            'linkedToClass' => null !== $linkedClass,
            'linkedClassTitle' => null !== $linkedClass ? $linkedClass->getTitle() : '',
            'canRemoveClassLink' => null !== $linkedClass && $canManage,
        ];
    }

    public function saveGroup(object $data, int $groupId = 0): int
    {
        [$course, $session] = $this->resolveContext();
        $group = $groupId > 0 ? $this->findGroup($groupId, $course, $session) : null;
        if ($group instanceof CGroup) {
            $this->assertCanManageGroup($group, $course, $session);
        } else {
            $this->assertCanManage($course, $session);
        }

        $title = trim((string) $data->title);
        if ('' === $title) {
            throw new BadRequestHttpException('The group title is required.');
        }

        $maxStudent = max(0, (int) $data->maxStudent);
        $categoryId = (int) $data->categoryId;
        $allowCategories = $this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true));
        if ($allowCategories && $categoryId <= 0) {
            $categoryData = GroupManager::get_categories($course);
            $categoryId = (int) ($categoryData[0]['iid'] ?? 0);
        }
        if ($categoryId > 0) {
            $this->findCategory($categoryId, $course, $session);
        }
        if ($groupId <= 0) {
            $createdId = (int) GroupManager::create_group($title, $categoryId, 0, $maxStudent);
            if ($createdId <= 0) {
                throw new BadRequestHttpException('The group could not be created.');
            }
            $groupId = $createdId;
        }

        GroupManager::set_group_properties(
            $groupId,
            $title,
            (string) $data->description,
            $maxStudent,
            $this->normalizeToolState((int) $data->docState),
            $this->normalizeToolState((int) $data->workState),
            $this->normalizeToolState((int) $data->calendarState),
            $this->normalizeToolState((int) $data->announcementsState, true),
            $this->normalizeToolState((int) $data->forumState),
            $this->normalizeToolState((int) $data->wikiState),
            $this->normalizeToolState((int) $data->chatState),
            (bool) $data->selfRegistrationAllowed,
            (bool) $data->selfUnregistrationAllowed,
            $categoryId,
            max(0, min(2, (int) $data->documentAccess)),
        );

        return $groupId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCategoryFormData(int $categoryId = 0): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ($session instanceof Session) {
            throw new AccessDeniedHttpException('Group categories cannot be managed inside a session.');
        }
        $allowCategories = $this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true));
        if (!$allowCategories && $categoryId <= 0) {
            throw new AccessDeniedHttpException('Creating group categories is disabled.');
        }

        $categoryData = [];
        if ($categoryId > 0) {
            $this->findCategory($categoryId, $course, $session);
            $categoryData = GroupManager::get_category($categoryId, $course->getCode());
        }

        return [
            'categoryId' => $categoryId,
            'title' => (string) ($categoryData['title'] ?? ''),
            'description' => (string) ($categoryData['description'] ?? ''),
            'groupsPerUser' => (int) ($categoryData['groups_per_user'] ?? 1),
            'maxStudent' => (int) ($categoryData['max_student'] ?? 0),
            'selfRegistrationAllowed' => 1 === (int) ($categoryData['self_reg_allowed'] ?? 0),
            'selfUnregistrationAllowed' => 1 === (int) ($categoryData['self_unreg_allowed'] ?? 0),
            'docState' => (int) ($categoryData['doc_state'] ?? CGroup::TOOL_PRIVATE),
            'workState' => (int) ($categoryData['work_state'] ?? CGroup::TOOL_PRIVATE),
            'calendarState' => (int) ($categoryData['calendar_state'] ?? CGroup::TOOL_PRIVATE),
            'announcementsState' => (int) ($categoryData['announcements_state'] ?? CGroup::TOOL_PRIVATE),
            'forumState' => (int) ($categoryData['forum_state'] ?? CGroup::TOOL_PRIVATE),
            'wikiState' => (int) ($categoryData['wiki_state'] ?? CGroup::TOOL_PRIVATE),
            'chatState' => (int) ($categoryData['chat_state'] ?? CGroup::TOOL_PRIVATE),
            'documentAccess' => (int) ($categoryData['document_access'] ?? 0),
            'allowDocumentAccess' => $this->isEnabled(
                $this->settingsManager->getSetting('document.group_category_document_access', true),
            ),
            'allowCategories' => $allowCategories,
        ];
    }

    public function saveCategory(object $data, int $categoryId = 0): int
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ($session instanceof Session) {
            throw new AccessDeniedHttpException('Group categories cannot be managed inside a session.');
        }
        $allowCategories = $this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true));
        if (!$allowCategories && $categoryId <= 0) {
            throw new AccessDeniedHttpException('Creating group categories is disabled.');
        }

        $title = trim((string) $data->title);
        if ('' === $title) {
            throw new BadRequestHttpException('The category title is required.');
        }

        $groupsPerUser = max(0, (int) $data->groupsPerUser);
        if ($groupsPerUser > 10) {
            throw new BadRequestHttpException('The maximum number of groups per user must be between 1 and 10, or All.');
        }
        if ($categoryId > 0
            && GroupManager::GROUP_PER_MEMBER_NO_LIMIT !== $groupsPerUser
            && GroupManager::get_current_max_groups_per_user($categoryId, $course->getCode()) > $groupsPerUser
        ) {
            throw new BadRequestHttpException('The proposed group limit is lower than the number of groups currently assigned to a user.');
        }

        $arguments = [
            $title,
            (string) $data->description,
            $this->normalizeToolState((int) $data->docState),
            $this->normalizeToolState((int) $data->workState),
            $this->normalizeToolState((int) $data->calendarState),
            $this->normalizeToolState((int) $data->announcementsState, true),
            $this->normalizeToolState((int) $data->forumState),
            $this->normalizeToolState((int) $data->wikiState),
            $this->normalizeToolState((int) $data->chatState),
            (bool) $data->selfRegistrationAllowed,
            (bool) $data->selfUnregistrationAllowed,
            max(0, (int) $data->maxStudent),
            $groupsPerUser,
            max(0, min(2, (int) $data->documentAccess)),
        ];

        if ($categoryId <= 0) {
            $createdId = (int) GroupManager::create_category(...$arguments);
            if ($createdId <= 0) {
                throw new BadRequestHttpException('The category could not be created.');
            }

            return $createdId;
        }

        $this->findCategory($categoryId, $course, $session);
        GroupManager::update_category($categoryId, ...$arguments);

        return $categoryId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMembersData(int $groupId, string $mode): array
    {
        [$course, $session] = $this->resolveContext();
        $group = $this->findGroup($groupId, $course, $session);
        $this->assertCanManageGroup($group, $course, $session);

        $isTutorMode = 'tutors' === $mode;
        $linkedClass = GroupManager::get_usergroup_link($group);
        $selectedIds = array_values($isTutorMode
            ? array_map(static fn (array $item): int => (int) $item['user_id'], GroupManager::getTutors(['iid' => (int) $group->getIid()]))
            : array_map(static fn (array $item): int => (int) $item['user_id'], $this->getSubscribedUsers($group, $course)));
        $excludedIds = array_values($isTutorMode
            ? array_map(static fn (array $item): int => (int) $item['user_id'], $this->getSubscribedUsers($group, $course))
            : array_map(static fn (array $item): int => (int) $item['user_id'], GroupManager::getTutors(['iid' => (int) $group->getIid()])));

        $users = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
        );
        $options = [];
        $userGroupModel = new UserGroupModel();
        $orderByOfficialCode = $this->isEnabled(
            $this->settingsManager->getSetting('display.order_user_list_by_official_code', true),
        );
        foreach ($users as $userId => $userData) {
            $userId = (int) ($userData['user_id'] ?? $userId);
            if ($userId <= 0
                || INVITEE === (int) ($userData['status'] ?? 0)
                || \in_array($userId, $excludedIds, true)
            ) {
                continue;
            }
            $officialCode = trim((string) ($userData['official_code'] ?? ''));
            $username = trim((string) ($userData['username'] ?? ''));
            $completeName = trim((string) ($userData['complete_name'] ?? ''));
            if ('' === $completeName) {
                $completeName = api_get_person_name(
                    (string) ($userData['firstname'] ?? ''),
                    (string) ($userData['lastname'] ?? ''),
                );
            }
            if ('' === $completeName) {
                $completeName = '' !== $username ? $username : (string) $userId;
            }
            $label = $completeName.('' !== $username ? ' ('.$username.')' : '');
            if ($orderByOfficialCode) {
                $label = ('' !== $officialCode ? $officialCode : '?').' - '.$label;
            } elseif ('' !== $officialCode) {
                $label .= ' - '.$officialCode;
            }
            $classNames = array_values(array_filter(array_map(
                static fn (array $class): string => trim((string) ($class['name'] ?? $class['title'] ?? '')),
                $userGroupModel->getUserGroupListByUser($userId),
            )));
            if ([] !== $classNames) {
                $label .= ' - ['.implode(', ', $classNames).']';
            }
            $options[] = [
                'id' => $userId,
                'name' => $label,
                'officialCode' => $officialCode,
            ];
        }

        usort($options, static function (array $first, array $second) use ($orderByOfficialCode): int {
            if ($orderByOfficialCode) {
                $comparison = strcasecmp((string) $first['officialCode'], (string) $second['officialCode']);
                if (0 !== $comparison) {
                    return $comparison;
                }
            }

            return strcasecmp((string) $first['name'], (string) $second['name']);
        });

        return [
            'groupId' => $groupId,
            'groupTitle' => $group->getTitle(),
            'mode' => $mode,
            'options' => $options,
            'selectedIds' => $selectedIds,
            'maxStudent' => $group->getMaxStudent(),
            'linkedToClass' => null !== $linkedClass,
            'linkedClassTitle' => null !== $linkedClass ? $linkedClass->getTitle() : '',
        ];
    }

    public function saveMembers(Request $request, int $groupId, string $mode, array $selectedIds): void
    {
        [$course, $session] = $this->resolveContext();
        $group = $this->findGroup($groupId, $course, $session);
        $this->assertCanManageGroup($group, $course, $session);

        $selectedIds = array_values(array_unique(array_filter(array_map('intval', $selectedIds))));
        $selectionData = $this->getMembersData($groupId, $mode);
        $allowedIds = array_map(
            static fn (array $item): int => (int) $item['id'],
            (array) $selectionData['options'],
        );
        if ([] !== array_diff($selectedIds, $allowedIds)) {
            throw new AccessDeniedHttpException('One or more selected users are not available in this course context.');
        }
        if ('members' === $mode && GroupManager::isGroupLinkedToUsergroup($group)) {
            throw new AccessDeniedHttpException('Members of a class-linked group cannot be changed manually.');
        }
        if ('members' === $mode && $group->getMaxStudent() > 0 && \count($selectedIds) > $group->getMaxStudent()) {
            throw new BadRequestHttpException('The maximum number of group members was exceeded.');
        }

        if ('members' === $mode) {
            $currentMemberIds = array_map(
                static fn (array $item): int => (int) $item['user_id'],
                $this->getSubscribedUsers($group, $course),
            );
            $category = GroupManager::get_category_from_group((int) $group->getIid(), $course->getCode());
            $groupsPerUser = (int) ($category['groups_per_user'] ?? GroupManager::GROUP_PER_MEMBER_NO_LIMIT);
            if (GroupManager::GROUP_PER_MEMBER_NO_LIMIT !== $groupsPerUser) {
                foreach (array_diff($selectedIds, $currentMemberIds) as $userId) {
                    if (GroupManager::user_in_number_of_groups($userId, (int) ($category['iid'] ?? 0)) >= $groupsPerUser) {
                        throw new BadRequestHttpException('One or more selected users already reached the maximum number of groups allowed in this category.');
                    }
                }
            }
        }

        if ('tutors' === $mode) {
            GroupManager::unsubscribe_all_tutors($groupId);
            if ([] !== $selectedIds) {
                GroupManager::subscribeTutors($selectedIds, $group, (int) $course->getId());
            }

            return;
        }

        GroupManager::unsubscribeAllUsers($groupId);
        if ([] !== $selectedIds) {
            GroupManager::subscribeUsers($selectedIds, $group, (int) $course->getId());
        }

        $savedIds = array_map(
            static fn (array $item): int => (int) $item['user_id'],
            $this->getSubscribedUsers($group, $course),
        );
        sort($savedIds);
        $expectedIds = $selectedIds;
        sort($expectedIds);
        if ($savedIds !== $expectedIds) {
            throw new BadRequestHttpException('The selected group members could not be saved completely. Check the group capacity and category membership limits.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetailData(int $groupId): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanRead($course, $session);
        $group = $this->findGroup($groupId, $course, $session);
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $userId = (int) $user->getId();
        $canManage = $this->canManage($course, $session) || $group->hasTutor($user);
        if (!$canManage && !GroupManager::userHasAccessToBrowse($userId, $group, $session?->getId() ?? 0)) {
            throw new AccessDeniedHttpException('You are not allowed to browse this group.');
        }

        $query = $this->buildContextQuery($course, $session, $groupId);
        $courseRedirect = static fn (string $path, string $query): string => $path.'?'.$query;
        $resourceNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        $tools = [];
        $toolDefinitions = [
            [
                'state' => $group->getDocState(),
                'label' => 'Documents',
                'icon' => 'folder-generic',
                'url' => $courseRedirect($this->router->generate('chamilo_core_course_redirect_tool', [
                    'toolName' => 'document',
                    'cid' => (int) $course->getId(),
                ]), $query),
            ],
            [
                'state' => $group->getCalendarState(),
                'label' => 'Agenda',
                'icon' => 'agenda-event',
                'url' => $courseRedirect($this->router->generate('chamilo_core_course_redirect_tool', [
                    'toolName' => 'agenda',
                    'cid' => (int) $course->getId(),
                ]), $query),
            ],
            [
                'state' => $group->getWorkState(),
                'label' => 'Assignments',
                'icon' => 'file-text',
                'url' => $courseRedirect($this->router->generate('chamilo_core_course_redirect_tool', [
                    'toolName' => 'student_publication',
                    'cid' => (int) $course->getId(),
                ]), $query),
            ],
        ];
        if ($resourceNodeId > 0) {
            $toolDefinitions[] = [
                'state' => $group->getAnnouncementsState(),
                'label' => 'Announcements',
                'icon' => 'announcement',
                'url' => '/resources/announcement/'.$resourceNodeId.'/?'.$query,
            ];
        }
        foreach ($toolDefinitions as $definition) {
            if (CGroup::TOOL_NOT_AVAILABLE === $definition['state']) {
                continue;
            }
            if (!$canManage && !$group->hasMember($user) && CGroup::TOOL_PUBLIC !== $definition['state']) {
                continue;
            }
            $tools[] = $definition;
        }

        return [
            'groupId' => $groupId,
            'title' => $group->getTitle(),
            'description' => (string) $group->getDescription(),
            'canManage' => $canManage,
            'canSelfRegister' => GroupManager::is_self_registration_allowed($userId, $group),
            'canSelfUnregister' => GroupManager::is_self_unregistration_allowed($userId, $group),
            'tools' => $tools,
            'tutors' => array_values(array_map(
                fn (array $item): array => $this->normalizeLegacyUser($item),
                GroupManager::getTutors(['iid' => (int) $group->getIid()]),
            )),
            'members' => array_values(array_map(
                fn (array $item): array => $this->normalizeLegacyUser($item),
                $this->getSubscribedUsers($group, $course),
            )),
        ];
    }

    public function createGroups(array $groups): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $ids = [];
        foreach ($groups as $groupData) {
            $title = trim((string) ($groupData['name'] ?? ''));
            if ('' === $title) {
                continue;
            }
            $categoryId = (int) ($groupData['categoryId'] ?? 0);
            if ($categoryId > 0) {
                $this->findCategory($categoryId, $course, $session);
            }
            $groupId = (int) GroupManager::create_group(
                $title,
                $categoryId,
                0,
                max(0, (int) ($groupData['maxStudent'] ?? 0)),
            );
            if ($groupId > 0) {
                $ids[] = $groupId;
            }
        }

        return $ids;
    }

    public function createSubgroups(int $baseGroupId, int $numberOfGroups): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $this->findGroup($baseGroupId, $course, $session);
        if ($numberOfGroups <= 0) {
            throw new BadRequestHttpException('The number of groups must be greater than zero.');
        }
        GroupManager::create_subgroups($baseGroupId, $numberOfGroups);
    }

    public function createClassGroups(int $categoryId, array $classIds, bool $consistentLink): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ($categoryId > 0) {
            $this->findCategory($categoryId, $course, $session);
        }
        $classIds = array_values(array_unique(array_filter(array_map('intval', $classIds))));
        if ([] === $classIds) {
            throw new BadRequestHttpException('Select at least one class.');
        }
        $classModel = new UserGroupModel();
        $availableClassIds = array_map(
            static fn (array $class): int => (int) $class['id'],
            $classModel->getUserGroupInCourse(['where' => [' usergroup.course_id = ? ' => (int) $course->getId()]]),
        );
        if ([] !== array_diff($classIds, $availableClassIds)) {
            throw new AccessDeniedHttpException('One or more selected classes are not available in this course.');
        }

        return $consistentLink
            ? array_values(array_map('intval', GroupManager::create_usergroup_consistent_groups($categoryId, $classIds)))
            : array_values(array_map('intval', GroupManager::create_class_groups($categoryId, $classIds)));
    }

    public function deleteGroups(array $groupIds): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $affected = [];
        foreach ($this->normalizeIds($groupIds) as $groupId) {
            $group = $this->findGroup($groupId, $course, $session);
            GroupManager::deleteGroup($group);
            $affected[] = $groupId;
        }

        return $affected;
    }

    public function emptyGroups(array $groupIds): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $affected = [];
        foreach ($this->normalizeIds($groupIds) as $groupId) {
            $this->findGroup($groupId, $course, $session);
            GroupManager::unsubscribeAllUsers($groupId);
            $affected[] = $groupId;
        }

        return $affected;
    }

    public function fillGroups(array $groupIds): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $affected = [];
        foreach ($this->normalizeIds($groupIds) as $groupId) {
            $group = $this->findGroup($groupId, $course, $session);
            GroupManager::fillGroupWithUsers($group);
            $affected[] = $groupId;
        }

        return $affected;
    }

    public function toggleVisibility(int $groupId, bool $visible): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $group = $this->findGroup($groupId, $course, $session);
        $visible ? GroupManager::setVisible($group) : GroupManager::setInvisible($group);
    }

    public function selfRegister(int $groupId): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanRead($course, $session);
        $group = $this->findGroup($groupId, $course, $session);
        $user = $this->security->getUser();
        if (!$user instanceof User || !GroupManager::is_self_registration_allowed((int) $user->getId(), $group)) {
            throw new AccessDeniedHttpException('Self-registration is not allowed for this group.');
        }
        GroupManager::subscribeUsers((int) $user->getId(), $group, (int) $course->getId());
    }

    public function selfUnregister(int $groupId): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanRead($course, $session);
        $group = $this->findGroup($groupId, $course, $session);
        $user = $this->security->getUser();
        if (!$user instanceof User || !GroupManager::is_self_unregistration_allowed((int) $user->getId(), $group)) {
            throw new AccessDeniedHttpException('Self-unregistration is not allowed for this group.');
        }
        GroupManager::unsubscribeUsers((int) $user->getId(), $group, (int) $course->getId());
    }

    public function deleteCategory(int $categoryId): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ($session instanceof Session) {
            throw new AccessDeniedHttpException('Categories cannot be deleted inside a session.');
        }
        if (!$this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true))) {
            throw new AccessDeniedHttpException('Group categories are disabled.');
        }
        $this->findCategory($categoryId, $course, $session);
        if (\count(GroupManager::get_categories($course)) <= 1) {
            throw new BadRequestHttpException('You cannot delete the last category.');
        }
        GroupManager::delete_category($categoryId, $course->getCode());
    }

    public function moveCategory(int $categoryId, int $otherCategoryId): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ($session instanceof Session) {
            throw new AccessDeniedHttpException('Categories cannot be reordered inside a session.');
        }
        if (!$this->isEnabled($this->settingsManager->getSetting('allow_group_categories', true))) {
            throw new AccessDeniedHttpException('Group categories are disabled.');
        }
        $this->findCategory($categoryId, $course, $session);
        $this->findCategory($otherCategoryId, $course, $session);
        GroupManager::swap_category_order($categoryId, $otherCategoryId);
    }

    public function removeClassLink(int $groupId): void
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        $group = $this->findGroup($groupId, $course, $session);
        if (!GroupManager::remove_group_consistent_link($group)) {
            throw new BadRequestHttpException('The class link could not be removed.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function import(array $rows, bool $deleteMissing): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if ([] === $rows) {
            throw new BadRequestHttpException('The CSV file is empty or invalid.');
        }

        return $this->sanitizeImportResult(GroupManager::importCategoriesAndGroupsFromArray($rows, $deleteMissing));
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function getExportData(?int $groupId = null, bool $loadUsers = false): array
    {
        [$course, $session] = $this->resolveContext();
        $this->assertCanManage($course, $session);
        if (null !== $groupId && $groupId > 0) {
            $this->findGroup($groupId, $course, $session);
        }

        return GroupManager::exportCategoriesAndGroupsToArray($groupId, $loadUsers);
    }

    private function assertCanManageGroup(CGroup $group, Course $course, ?Session $session): void
    {
        if ($this->canManage($course, $session)) {
            return;
        }
        $user = $this->security->getUser();
        if ($user instanceof User && $group->hasTutor($user)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage this group.');
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeGroup(
        CGroup $group,
        Course $course,
        ?Session $session,
        bool $canManage,
        bool $canBrowse,
        bool $showTutorEmail,
        int $currentUserId,
    ): array {
        $members = $this->getSubscribedUsers($group, $course);
        $tutors = GroupManager::getTutors(['iid' => (int) $group->getIid()]);
        $linkedClass = GroupManager::get_usergroup_link($group);
        $isTutor = $this->legacyUserListContains($tutors, $currentUserId);

        return [
            'id' => (int) $group->getIid(),
            'title' => $group->getTitle(),
            'description' => (string) $group->getDescription(),
            'categoryId' => (int) ($group->getCategory()?->getIid() ?? 0),
            'status' => $group->getStatus(),
            'maxStudent' => $group->getMaxStudent(),
            'memberCount' => \count($members),
            'tutorCount' => \count($tutors),
            'tutorsLabel' => implode(', ', array_filter(array_map(
                static fn (array $tutor): string => trim((string) ($tutor['complete_name'] ?? $tutor['name'] ?? $tutor['username'] ?? '')),
                $tutors,
            ))),
            'tutors' => array_values(array_filter(array_map(
                static function (array $tutor) use ($showTutorEmail): array {
                    $name = trim((string) ($tutor['complete_name'] ?? $tutor['name'] ?? $tutor['username'] ?? ''));

                    return [
                        'name' => $name,
                        'email' => $showTutorEmail ? (string) ($tutor['email'] ?? '') : '',
                        'username' => (string) ($tutor['username'] ?? ''),
                    ];
                },
                $tutors,
            ), static fn (array $tutor): bool => '' !== $tutor['name'])),
            'membersLabel' => 0 === $group->getMaxStudent()
                ? (string) \count($members)
                : \count($members).'/'.$group->getMaxStudent(),
            'isMember' => $this->legacyUserListContains($members, $currentUserId),
            'isTutor' => $isTutor,
            'canManage' => $canManage || $isTutor,
            'canBrowse' => $canBrowse,
            'canSelfRegister' => GroupManager::is_self_registration_allowed($currentUserId, $group),
            'canSelfUnregister' => GroupManager::is_self_unregistration_allowed($currentUserId, $group),
            'linkedToClass' => null !== $linkedClass,
            'linkedClassTitle' => null !== $linkedClass ? $linkedClass->getTitle() : '',
            'spaceUrl' => $this->buildGroupSpaceUrl($course, $session, (int) $group->getIid()),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function legacyUserListContains(array $items, int $userId): bool
    {
        foreach ($items as $item) {
            if ((int) ($item['user_id'] ?? $item['id'] ?? 0) === $userId) {
                return true;
            }
        }

        return false;
    }

    private function buildGroupSpaceUrl(Course $course, ?Session $session, int $groupId): string
    {
        $resourceNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($resourceNodeId <= 0) {
            return '';
        }

        return '/resources/course-users/'.$resourceNodeId.'/groups/'.$groupId
            .'?'.$this->buildContextQuery($course, $session, $groupId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSubscribedUsers(CGroup $group, Course $course): array
    {
        /** @var CGroupRelUser[] $relations */
        $relations = $this->entityManager->getRepository(CGroupRelUser::class)->findBy([
            'group' => $group,
            'cId' => (int) $course->getId(),
        ]);
        $users = [];
        foreach ($relations as $relation) {
            $user = $relation->getUser();
            $userId = (int) $user->getId();
            $users[$userId] = [
                'user_id' => $userId,
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'complete_name' => api_get_person_name($user->getFirstname(), $user->getLastname()),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'official_code' => (string) $user->getOfficialCode(),
            ];
        }

        $sortByFirstName = api_sort_by_first_name();
        uasort($users, static function (array $first, array $second) use ($sortByFirstName): int {
            if ($sortByFirstName) {
                $comparison = strcasecmp((string) $first['firstname'], (string) $second['firstname']);
                if (0 !== $comparison) {
                    return $comparison;
                }
            } else {
                $comparison = strcasecmp((string) $first['lastname'], (string) $second['lastname']);
                if (0 !== $comparison) {
                    return $comparison;
                }
            }

            return strcasecmp((string) $first['username'], (string) $second['username']);
        });

        return $users;
    }

    /**
     * @return array{id: int, name: string, username: string, pictureUrl: string}
     */
    private function normalizeLegacyUser(array $item): array
    {
        $userId = (int) ($item['user_id'] ?? $item['id'] ?? 0);
        $userInfo = api_get_user_info($userId);

        return [
            'id' => $userId,
            'name' => (string) ($item['complete_name'] ?? $userInfo['complete_name'] ?? $item['username'] ?? ''),
            'username' => (string) ($item['username'] ?? $userInfo['username'] ?? ''),
            'pictureUrl' => (string) ($userInfo['avatar'] ?? ''),
        ];
    }

    private function sanitizeImportResult(mixed $value): mixed
    {
        if ($value instanceof CGroup) {
            return [
                'id' => (int) $value->getIid(),
                'title' => $value->getTitle(),
            ];
        }

        if (\is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->sanitizeImportResult($item);
            }

            return $result;
        }

        if (\is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : $value::class;
        }

        return $value;
    }

    /**
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    }

    private function normalizeToolState(int $state, bool $allowPrivateBetweenUsers = false): int
    {
        $maximum = $allowPrivateBetweenUsers ? CGroup::TOOL_PRIVATE_BETWEEN_USERS : CGroup::TOOL_PRIVATE;

        return max(CGroup::TOOL_NOT_AVAILABLE, min($maximum, $state));
    }

    private function buildContextQuery(Course $course, ?Session $session, int $groupId = 0): string
    {
        $query = ['cid' => (int) $course->getId()];
        if ($session instanceof Session) {
            $query['sid'] = (int) $session->getId();
        }
        if ($groupId > 0) {
            $query['gid'] = $groupId;
        }

        return http_build_query($query);
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
