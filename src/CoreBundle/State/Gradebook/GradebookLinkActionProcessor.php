<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLinkAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\GradebookLinkevalLog;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\EventLoggerHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookLinkAction, GradebookLinkAction>
 */
final readonly class GradebookLinkActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_link_action';

    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const ACTION_DELETE = 'delete';
    private const ACTION_MOVE = 'move';
    private const ACTION_SET_VISIBILITY = 'set_visibility';

    private const SYNC_CREATE = 'create';
    private const SYNC_EDIT = 'edit';
    private const SYNC_SAVE = 'save';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private GradebookLinkResourceResolver $resourceResolver,
        private EventLoggerHelper $eventLoggerHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookLinkAction
    {
        if (!$data instanceof GradebookLinkAction) {
            throw new BadRequestHttpException('Invalid Gradebook online activity action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $this->validateCourseResourceNode($request, $course);
        $this->validateGroupContext($operation, $course);
        $user = $this->getCurrentUser();

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage Gradebook online activities in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);

        $rootCategory = $this->findRootCategory($course, $session);
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $action = strtolower(trim($data->action));
        $link = match ($action) {
            self::ACTION_CREATE => $this->createLink($data, $rootCategory, $course, $session, $user),
            self::ACTION_UPDATE => $this->updateLink($data, $rootCategory, $course, $session, $user),
            self::ACTION_DELETE => $this->deleteLink($data, $rootCategory, $course, $session),
            self::ACTION_MOVE => $this->moveLink($data, $rootCategory, $course, $session, $user),
            self::ACTION_SET_VISIBILITY => $this->setLinkVisibility($data, $rootCategory, $course, $session, $user),
            default => throw new BadRequestHttpException('Unsupported Gradebook online activity action.'),
        };

        $this->entityManager->flush();

        $response = new GradebookLinkAction();
        $response->action = $action;
        $response->linkId = $link instanceof GradebookLink ? (int) $link->getId() : $data->linkId;
        $response->success = true;
        $response->message = match ($action) {
            self::ACTION_CREATE => 'Online activity added.',
            self::ACTION_UPDATE => 'Online activity saved.',
            self::ACTION_DELETE => 'Online activity deleted.',
            self::ACTION_MOVE => 'Online activity moved.',
            self::ACTION_SET_VISIBILITY => 'Online activity visibility changed.',
            default => '',
        };

        return $response;
    }

    private function createLink(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookLink {
        $category = $this->getCategoryInGradebook((int) ($data->categoryId ?? 0), $rootCategory, $course, $session);
        $this->assertCategoryCanContainLink($category);
        $this->assertCategoryEditable($category);

        $type = (int) ($data->type ?? 0);
        $refId = (int) ($data->refId ?? 0);
        $resource = $this->resourceResolver->requireResource($type, $refId, $course, $session);
        [$weight, $minScore, $pointsOne, $pointsMany] = $this->validateLinkForm($data, $type);

        $duplicate = $this->entityManager->getRepository(GradebookLink::class)->findOneBy([
            'type' => $type,
            'refId' => $refId,
            'course' => $course,
            'category' => $category,
        ]);
        if ($duplicate instanceof GradebookLink) {
            throw new BadRequestHttpException('This online activity is already linked to the selected Gradebook category.');
        }

        $link = new GradebookLink();
        $link
            ->setType($type)
            ->setRefId($refId)
            ->setCourse($course)
            ->setCategory($category)
            ->setWeight($weight)
            ->setVisible(1)
            ->setLocked(0)
            ->setMinScore($minScore)
            ->setPointsOne(null !== $pointsOne ? (string) $pointsOne : null)
            ->setPointsMany(null !== $pointsMany ? (string) $pointsMany : null)
        ;

        $this->entityManager->persist($link);
        $this->synchronizeLinkedResource($link, $resource, self::SYNC_CREATE);

        $this->entityManager->flush();
        $this->eventLoggerHelper->addEvent(
            'gradebook_link_created',
            'link',
            (int) $link->getId(),
            null,
            (int) $user->getId(),
            (int) $course->getId(),
            (int) ($session?->getId() ?? 0),
        );

        return $link;
    }

    private function updateLink(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookLink {
        $link = $this->requireLink($data, $rootCategory, $course, $session);
        $this->assertLinkEditable($link);

        $category = $this->getCategoryInGradebook(
            (int) ($data->categoryId ?? $link->getCategory()->getId()),
            $rootCategory,
            $course,
            $session,
        );
        $this->assertCategoryCanContainLink($category);
        $this->assertCategoryEditable($category);

        $type = (int) $link->getType();
        $refId = (int) $link->getRefId();
        $resource = $this->resourceResolver->requireResource($type, $refId, $course, $session);
        [$weight, $minScore, $pointsOne, $pointsMany] = $this->validateLinkForm($data, $type);

        $this->logLink($link, $course, $session, $user);
        $link
            ->setCategory($category)
            ->setWeight($weight)
            ->setVisible(1)
            ->setMinScore($minScore)
            ->setPointsOne(null !== $pointsOne ? (string) $pointsOne : null)
            ->setPointsMany(null !== $pointsMany ? (string) $pointsMany : null)
        ;

        $this->synchronizeLinkedResource($link, $resource, self::SYNC_EDIT);

        return $link;
    }

    private function deleteLink(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): ?GradebookLink {
        $link = $this->requireLink($data, $rootCategory, $course, $session);
        $this->assertLinkEditable($link);

        try {
            $resource = $this->resourceResolver->requireResource(
                (int) $link->getType(),
                (int) $link->getRefId(),
                $course,
                $session,
            );
            $this->cleanupLinkedResource($link, $resource);
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            // Keep deletion possible when the linked course resource has already been removed.
        }

        $this->entityManager->remove($link);

        return null;
    }

    private function moveLink(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookLink {
        $link = $this->requireLink($data, $rootCategory, $course, $session);
        $this->assertLinkEditable($link);
        $target = $this->getCategoryInGradebook((int) ($data->targetCategoryId ?? 0), $rootCategory, $course, $session);
        $this->assertCategoryCanContainLink($target);
        $this->assertCategoryEditable($target);

        $this->logLink($link, $course, $session, $user);
        $link->setCategory($target);
        $this->synchronizeExistingLinkedResource($link, $course, $session);

        return $link;
    }

    private function setLinkVisibility(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookLink {
        $link = $this->requireLink($data, $rootCategory, $course, $session);
        $this->assertLinkEditable($link);
        if (null === $data->visible) {
            throw new BadRequestHttpException('A visibility value is required.');
        }

        $this->logLink($link, $course, $session, $user);
        $link->setVisible($data->visible ? 1 : 0);
        $this->synchronizeExistingLinkedResource($link, $course, $session);

        return $link;
    }

    /**
     * @return array{0: float, 1: float, 2: ?float, 3: ?float}
     */
    private function validateLinkForm(GradebookLinkAction $data, int $type): array
    {
        $minScore = $data->minScore;
        if (null === $minScore || $minScore < 0) {
            throw new BadRequestHttpException('The minimum score must be zero or greater.');
        }

        if (GradebookLinkResourceResolver::LINK_FORUM_PARTICIPATION === $type) {
            $pointsOne = $data->pointsOne;
            $pointsMany = $data->pointsMany;
            if (null === $pointsOne || $pointsOne < 0) {
                throw new BadRequestHttpException('Points for one message must be zero or greater.');
            }
            if (null !== $pointsMany && $pointsMany < 0) {
                throw new BadRequestHttpException('Points for two or more messages must be zero or greater.');
            }

            $effectiveMany = null !== $pointsMany && $pointsMany > 0 ? $pointsMany : $pointsOne;

            return [max($pointsOne, $effectiveMany), (float) $minScore, $pointsOne, $pointsMany];
        }

        $weight = $data->weight;
        if (null === $weight || $weight < 0) {
            throw new BadRequestHttpException('The online activity weight must be zero or greater.');
        }

        return [(float) $weight, (float) $minScore, null, null];
    }

    private function synchronizeLinkedResource(GradebookLink $link, object $resource, string $mode): void
    {
        $type = (int) $link->getType();
        $weight = (float) $link->getWeight();

        if (GradebookLinkResourceResolver::LINK_FORUM_THREAD === $type && $resource instanceof CForumThread) {
            if (self::SYNC_CREATE === $mode) {
                $resource
                    ->setThreadQualifyMax($weight)
                    ->setThreadTitleQualify($resource->getTitle())
                ;
            }
            $resource->setThreadWeight($weight);

            return;
        }

        if (GradebookLinkResourceResolver::LINK_ATTENDANCE === $type
            && $resource instanceof CAttendance
            && self::SYNC_EDIT === $mode
        ) {
            $resource->setAttendanceWeight($weight);

            return;
        }

        if (GradebookLinkResourceResolver::LINK_STUDENT_PUBLICATION === $type
            && $resource instanceof CStudentPublication
            && self::SYNC_CREATE !== $mode
        ) {
            $resource->setWeight($weight);
        }
    }

    private function synchronizeExistingLinkedResource(GradebookLink $link, Course $course, ?Session $session): void
    {
        try {
            $resource = $this->resourceResolver->requireResource(
                (int) $link->getType(),
                (int) $link->getRefId(),
                $course,
                $session,
            );
        } catch (AccessDeniedHttpException|BadRequestHttpException|NotFoundHttpException) {
            return;
        }

        $this->synchronizeLinkedResource($link, $resource, self::SYNC_SAVE);
    }

    private function cleanupLinkedResource(GradebookLink $link, object $resource): void
    {
        $type = (int) $link->getType();

        if (GradebookLinkResourceResolver::LINK_FORUM_THREAD === $type && $resource instanceof CForumThread) {
            $resource
                ->setThreadQualifyMax(0.0)
                ->setThreadWeight(0.0)
                ->setThreadTitleQualify('')
            ;

            return;
        }

        if (GradebookLinkResourceResolver::LINK_ATTENDANCE === $type && $resource instanceof CAttendance) {
            $resource
                ->setAttendanceWeight(0.0)
                ->setAttendanceQualifyTitle('')
            ;
        }
    }

    private function logLink(GradebookLink $link, Course $course, ?Session $session, User $user): void
    {
        $summary = $this->resourceResolver->normalizeLink($link, $course, $session, 0, true);

        $log = new GradebookLinkevalLog();
        $log
            ->setIdLinkevalLog((int) $link->getId())
            ->setTitle((string) ($summary['title'] ?? $course->getTitle()))
            ->setDescription((string) ($summary['description'] ?? ''))
            ->setWeight((int) round((float) $link->getWeight()))
            ->setVisible(1 === (int) $link->getVisible())
            ->setType('link')
            ->setUser($user)
            ->setCreatedAt(new DateTime())
        ;
        $this->entityManager->persist($log);
    }

    private function requireLink(
        GradebookLinkAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookLink {
        $linkId = (int) ($data->linkId ?? 0);
        if ($linkId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook online activity id is required.');
        }

        $link = $this->entityManager->getRepository(GradebookLink::class)->find($linkId);
        if (!$link instanceof GradebookLink) {
            throw new NotFoundHttpException('The requested Gradebook online activity was not found.');
        }
        if ((int) $link->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity belongs to another course.');
        }

        $category = $link->getCategory();
        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity is outside the current Gradebook.');
        }

        return $link;
    }

    private function getCategoryInGradebook(
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
        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        return $category;
    }

    private function assertCategoryCanContainLink(GradebookCategory $category): void
    {
        if (null !== $category->getGradeModel()) {
            throw new BadRequestHttpException('Online activities cannot be added to a Gradebook category using a grade model.');
        }
    }

    private function assertCategoryEditable(GradebookCategory $category): void
    {
        if (1 === (int) $category->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested Gradebook category is locked.');
        }
    }

    private function assertLinkEditable(GradebookLink $link): void
    {
        if (1 === (int) $link->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested Gradebook online activity is locked.');
        }
        $this->assertCategoryEditable($link->getCategory());
    }

    private function assertCategoryContext(GradebookCategory $category, Course $course, ?Session $session): void
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another course.');
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = $session instanceof Session ? (int) $session->getId() : 0;
        if ($categorySessionId !== $sessionId) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another session context.');
        }
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

    private function findRootCategory(Course $course, ?Session $session): ?GradebookCategory
    {
        return $this->entityManager->getRepository(GradebookCategory::class)->findOneBy(
            ['course' => $course, 'session' => $session, 'parent' => null],
            ['id' => 'ASC'],
        );
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        $resourceNode = $course->getResourceNode();
        if ($nodeId <= 0 || null === $resourceNode || (int) $resourceNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Operation $operation, Course $course): void
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return;
        }

        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }
}
