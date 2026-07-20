<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourse;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const JSON_THROW_ON_ERROR;

trait LearningPathStateHelperTrait
{
    private const ACTION_TOKEN_INTENTION = 'learning_path_action';

    /**
     * @return array<string, mixed>
     */
    private function getJsonData(Request $request): array
    {
        try {
            $data = json_decode($request->getContent() ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        if (!\is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        return $data;
    }

    private function validateActionToken(CsrfTokenManagerInterface $csrfTokenManager, mixed $token): void
    {
        if (!\is_string($token) || '' === trim($token)) {
            throw new BadRequestHttpException('Missing CSRF token.');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::ACTION_TOKEN_INTENTION, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function isStudentViewRequest(RequestStack $requestStack): bool
    {
        $value = strtolower(trim((string) $requestStack->getCurrentRequest()?->query->get('isStudentView', 'false')));

        return \in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function assertLearningPathTeacher(Security $security): void
    {
        if ($security->isGranted('ROLE_ADMIN')
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage learning paths in this context.');
    }

    private function canManageLearningPaths(Security $security): bool
    {
        return $security->isGranted('ROLE_ADMIN')
            || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function getContextCourse(EntityManagerInterface $entityManager, Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('Missing course id.');
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        return $course;
    }

    private function getContextSession(EntityManagerInterface $entityManager, Request $request, Course $course): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new NotFoundHttpException('Session not found.');
        }

        $sessionCourse = $entityManager->getRepository(SessionRelCourse::class)->findOneBy([
            'course' => $course,
            'session' => $session,
        ]);

        if (!$sessionCourse instanceof SessionRelCourse) {
            throw new AccessDeniedHttpException('The requested session is not linked to this course.');
        }

        return $session;
    }

    private function getContextGroup(EntityManagerInterface $entityManager, Request $request, Course $course): ?CGroup
    {
        $groupId = $request->query->getInt('gid');

        return $groupId > 0 ? $this->findValidatedGroup($entityManager, $groupId, $course) : null;
    }

    private function getValidatedGroupFromContext(
        EntityManagerInterface $entityManager,
        CidReqHelper $cidReqHelper,
        Course $course,
    ): ?CGroup {
        $groupId = (int) ($cidReqHelper->getGroupId() ?? 0);

        return $groupId > 0 ? $this->findValidatedGroup($entityManager, $groupId, $course) : null;
    }

    private function findValidatedGroup(
        EntityManagerInterface $entityManager,
        int $groupId,
        Course $course,
    ): CGroup {
        $group = $entityManager->getRepository(CGroup::class)->find($groupId);
        if (!$group instanceof CGroup) {
            throw new NotFoundHttpException('Group not found.');
        }

        $courseNode = $course->getResourceNode();
        $groupParent = $group->getResourceNode()?->getParent();
        if (!$courseNode instanceof ResourceNode
            || !$groupParent instanceof ResourceNode
            || $courseNode->getId() !== $groupParent->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group is not linked to this course.');
        }

        return $group;
    }

    private function getContextResourceLink(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        $resourceNode = $resource->getResourceNode();
        if (!$resourceNode instanceof ResourceNode) {
            return null;
        }

        $resourceLink = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if ($resourceLink instanceof ResourceLink) {
            return $resourceLink;
        }

        if (null !== $session && null === $group) {
            return $resourceNode->getResourceLinkByContext($course);
        }

        return null;
    }

    private function getEditableResourceLink(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Security $security,
    ): ResourceLink {
        $resourceNode = $resource->getResourceNode();
        if (!$resourceNode instanceof ResourceNode || !$security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this learning path resource.');
        }

        $link = $resourceNode->getResourceLinkByContext($course, $session, $group);
        if ($link instanceof ResourceLink) {
            return $link;
        }

        if (null !== $session && null === $group) {
            $baseCourseLink = $resourceNode->getResourceLinkByContext($course);
            if ($baseCourseLink instanceof ResourceLink
                && ($security->isGranted('ROLE_ADMIN') || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER'))
            ) {
                return $baseCourseLink;
            }
        }

        throw new AccessDeniedHttpException('The learning path resource is not linked to the current context.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getTargetVisibility(array $payload, ResourceLink $resourceLink): bool
    {
        if (\array_key_exists('visible', $payload)) {
            if (!\is_bool($payload['visible'])) {
                throw new BadRequestHttpException('The visible value must be a boolean.');
            }

            return $payload['visible'];
        }

        return ResourceLink::VISIBILITY_PUBLISHED !== $resourceLink->getVisibility();
    }
}
