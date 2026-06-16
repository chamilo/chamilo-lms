<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const FILTER_VALIDATE_BOOLEAN;

trait ForumActionStateHelperTrait
{
    private function assertEditableForumResource(?ResourceNode $resourceNode, Security $security): void
    {
        if (null === $resourceNode || !$security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this forum resource.');
        }
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
