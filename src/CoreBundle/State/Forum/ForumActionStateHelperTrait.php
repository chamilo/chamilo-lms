<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const FILTER_VALIDATE_BOOLEAN;

trait ForumActionStateHelperTrait
{
    private function assertEditableForumResource(?ResourceNode $resourceNode, Security $security): void
    {
        if (null === $resourceNode || !$security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this forum resource.');
        }
    }

    private function assertResourceNodeInForumContext(
        ?ResourceNode $resourceNode,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $message,
    ): void {
        if (!$resourceNode instanceof ResourceNode) {
            throw new AccessDeniedHttpException($message);
        }

        $link = $resourceNode->getResourceLinkByContext($course, $session, $group);
        $link ??= $resourceNode->getResourceLinkByContext($course, $session);
        $link ??= $resourceNode->getResourceLinkByContext($course);

        if (!$link instanceof ResourceLink) {
            throw new AccessDeniedHttpException($message);
        }
    }

    private function assertEditableResourceNodeInForumContext(
        ?ResourceNode $resourceNode,
        Security $security,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $message,
    ): void {
        $this->assertResourceNodeInForumContext($resourceNode, $course, $session, $group, $message);

        if (!$security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException($message);
        }
    }

    private function assertParentResourceNodeIsWritableInForumContext(
        EntityManagerInterface $entityManager,
        Security $security,
        int $parentResourceNodeId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ResourceNode {
        $parentResourceNode = $entityManager->getRepository(ResourceNode::class)->find($parentResourceNodeId);
        if (!$parentResourceNode instanceof ResourceNode) {
            throw new NotFoundHttpException('Parent forum resource node not found.');
        }

        $courseResourceNode = $course->getResourceNode();
        if ($courseResourceNode instanceof ResourceNode && $courseResourceNode->getId() === $parentResourceNode->getId()) {
            if ($security->isGranted('ROLE_ADMIN')
                || $security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
                || $security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            ) {
                return $parentResourceNode;
            }

            throw new AccessDeniedHttpException('You are not allowed to create forum resources in this context.');
        }

        $this->assertEditableResourceNodeInForumContext(
            $parentResourceNode,
            $security,
            $course,
            $session,
            $group,
            'You are not allowed to create forum resources in this context.',
        );

        return $parentResourceNode;
    }

    private function setForumResourceVisibility(
        AbstractResource $resource,
        ResourceRepository $repository,
        Course $course,
        ?Session $session,
        bool $visible,
    ): bool {
        if ($visible) {
            $repository->setVisibilityPublished($resource, $course, $session);
        } else {
            $repository->setVisibilityDraft($resource, $course, $session);
        }

        return $resource->isVisible($course, $session);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getTargetVisibility(array $data, AbstractResource $resource, Course $course, ?Session $session): bool
    {
        if (\array_key_exists('visible', $data)) {
            return filter_var($data['visible'], FILTER_VALIDATE_BOOLEAN);
        }

        return !$resource->isVisible($course, $session);
    }

    private function moveForumResource(
        AbstractResource $resource,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        string $direction,
    ): int {
        $link = $resource->getResourceNode()?->getResourceLinkByContext($course, $session, $group);
        $link ??= $resource->getResourceNode()?->getResourceLinkByContext($course, $session);
        $link ??= $resource->getResourceNode()?->getResourceLinkByContext($course);

        if (!$link instanceof ResourceLink) {
            throw new BadRequestHttpException('Resource link not found in this context.');
        }

        if ('down' === $direction) {
            $link->moveDownPosition();
        } elseif ('up' === $direction) {
            $link->moveUpPosition();
        } else {
            throw new BadRequestHttpException('Invalid move direction.');
        }

        return $link->getDisplayOrder();
    }
}
