<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const JSON_THROW_ON_ERROR;

trait LearningPathStateHelperTrait
{
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

    /**
     * SessionVoter proves the course/session pairing for students and course coaches, but not
     * for general coaches or admins. Assert it before persisting a resource link, so an
     * unrelated pair can never be written.
     */
    private function assertSessionBelongsToCourse(?Session $session, Course $course): void
    {
        if (!$session instanceof Session || $session->hasCourse($course)) {
            return;
        }

        throw new AccessDeniedHttpException('The requested session is not linked to this course.');
    }

    private function getContextGroup(
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
