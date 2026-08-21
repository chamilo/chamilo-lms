<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class GradebookContextResolver
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @return array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool}
     */
    public function resolve(
        Request $request,
        bool $requireManage = false,
        bool $validateCourseResourceNode = true,
    ): array {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        if ($validateCourseResourceNode) {
            $this->validateCourseResourceNode($request, $course);
        }
        $groupId = $this->validateGroupContext($course);
        $user = $this->getCurrentUser();
        $canManage = $this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session);

        if ($requireManage && !$canManage) {
            throw new AccessDeniedHttpException('You are not allowed to manage the Gradebook in this context.');
        }

        $rootCategory = $this->entityManager->getRepository(GradebookCategory::class)->findOneBy(
            [
                'course' => $course,
                'session' => $session,
                'parent' => null,
            ],
            ['id' => 'ASC'],
        );

        return [
            'course' => $course,
            'session' => $session,
            'groupId' => $groupId,
            'rootCategory' => $rootCategory instanceof GradebookCategory ? $rootCategory : null,
            'user' => $user,
            'canManage' => $canManage,
        ];
    }

    public function getSelectedCategory(
        Request $request,
        Course $course,
        ?Session $session,
        GradebookCategory $rootCategory,
    ): GradebookCategory {
        $categoryId = $request->query->getInt('categoryId');
        if ($categoryId <= 0 || $categoryId === (int) $rootCategory->getId()) {
            return $rootCategory;
        }

        return $this->getCategoryInGradebook($categoryId, $rootCategory, $course, $session);
    }

    public function getCategoryInGradebook(
        int $categoryId,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook category id is required.');
        }

        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }
        if (!$this->sameCategoryContext($category, $course, $session)
            || !$this->isCategoryDescendantOf($category, $rootCategory)
        ) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        return $category;
    }

    public function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    /**
     * @return list<User>
     */
    public function getStudents(Course $course, ?Session $session): array
    {
        $students = [];

        if ($session instanceof Session) {
            $relations = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->findBy([
                'course' => $course,
                'session' => $session,
                'status' => Session::STUDENT,
            ]);
        } else {
            $relations = $this->entityManager->getRepository(CourseRelUser::class)->findBy([
                'course' => $course,
                'status' => CourseRelUser::STUDENT,
            ]);
        }

        foreach ($relations as $relation) {
            if (!$relation instanceof CourseRelUser && !$relation instanceof SessionRelCourseRelUser) {
                continue;
            }

            $student = $relation->getUser();
            if (!$student instanceof User || User::SOFT_DELETED === $student->getStatus() || null === $student->getId()) {
                continue;
            }

            $students[(int) $student->getId()] = $student;
        }

        return array_values($students);
    }

    public function getStudentInContext(int $userId, Course $course, ?Session $session): User
    {
        if ($userId <= 0) {
            throw new BadRequestHttpException('A valid learner id is required.');
        }

        foreach ($this->getStudents($course, $session) as $student) {
            if ((int) $student->getId() === $userId) {
                return $student;
            }
        }

        throw new AccessDeniedHttpException('The requested learner is outside the current course context.');
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        $courseNode = $course->getResourceNode();
        if ($nodeId <= 0 || null === $courseNode || (int) $courseNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Course $course): int
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return 0;
        }

        $groupId = (int) $group->getIid();

        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }

        return $groupId;
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function sameCategoryContext(GradebookCategory $category, Course $course, ?Session $session): bool
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            return false;
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = null !== $session ? (int) $session->getId() : 0;

        return $categorySessionId === $sessionId;
    }

    private function isCategoryDescendantOf(GradebookCategory $category, GradebookCategory $rootCategory): bool
    {
        $visited = [];
        $current = $category;
        while (null !== $current) {
            $currentId = (int) $current->getId();
            if ($currentId === (int) $rootCategory->getId()) {
                return true;
            }
            if (isset($visited[$currentId])) {
                return false;
            }

            $visited[$currentId] = true;
            $current = $current->getParent();
        }

        return false;
    }
}
