<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Document;

use Chamilo\CoreBundle\Cache\DocumentListCacheInvalidator;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Moves documents in a course context without partially updating a selection.
 * Both resource trees must be updated because document listings use ResourceLink.parent.
 */
final class DocumentMoveService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResourceLinkRepository $linkRepo,
        private readonly DocumentListCacheInvalidator $cacheInvalidator,
        private readonly Security $security,
    ) {}

    /**
     * @param list<CDocument> $documents
     */
    public function move(array $documents, int $destinationId, int $courseId, int $sessionId = 0, int $groupId = 0): int
    {
        if ($courseId <= 0 || $destinationId <= 0 || !$documents) {
            throw new BadRequestHttpException('A course, destination folder and documents are required.');
        }

        if (
            !$this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            && !$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
        ) {
            throw new AccessDeniedHttpException('You cannot move documents in this course.');
        }

        $course = $this->em->getRepository(Course::class)->find($courseId);
        $session = $sessionId > 0 ? $this->em->getRepository(Session::class)->find($sessionId) : null;
        $group = $groupId > 0 ? $this->em->getRepository(CGroup::class)->find($groupId) : null;

        if (!$course || ($sessionId > 0 && !$session) || ($groupId > 0 && !$group)) {
            throw new BadRequestHttpException('Invalid course, session or group context.');
        }

        $destination = $this->em->getRepository(ResourceNode::class)->find($destinationId);
        if (!$destination) {
            throw new BadRequestHttpException('Destination folder not found.');
        }

        $root = $course->getResourceNode();
        $isRoot = $root && $root->getId() === $destination->getId();
        $destinationLink = null;

        if (!$isRoot) {
            $folder = $this->em->getRepository(CDocument::class)->findOneBy(['resourceNode' => $destination]);
            if (!$folder || 'folder' !== $folder->getFiletype()) {
                throw new BadRequestHttpException('Destination must be a document folder in this course.');
            }

            $destinationLink = $this->linkRepo->findParentLinkForContext(
                $destination,
                $course,
                $session,
                $group,
                null,
                null
            );
            if (!$destinationLink) {
                throw new BadRequestHttpException('Destination folder is not available in this context.');
            }

            if (!$this->security->isGranted('EDIT', $destination)) {
                throw new AccessDeniedHttpException('You cannot edit the destination folder.');
            }
        }

        $prepared = [];
        $sourceIds = [];
        $sourceLinkIds = [];
        foreach ($documents as $document) {
            $node = $document->getResourceNode();
            if (!$node || null === $node->getId()) {
                throw new BadRequestHttpException('Document resource node not found.');
            }

            $nodeId = (int) $node->getId();
            if (isset($sourceIds[$nodeId])) {
                throw new BadRequestHttpException('Duplicate document in selection.');
            }
            $sourceIds[$nodeId] = true;

            if (!$this->security->isGranted('EDIT', $node)) {
                throw new AccessDeniedHttpException('You cannot edit one of the selected documents.');
            }

            $link = $this->linkRepo->findLinkForResourceInContext(
                $document,
                $course,
                $session,
                $group,
                null,
                null
            );
            if (!$link instanceof ResourceLink) {
                throw new BadRequestHttpException('A selected document is not in this course context.');
            }

            if ($nodeId === $destinationId) {
                throw new BadRequestHttpException('A folder cannot be moved into itself.');
            }

            if ($node->getParent()?->getId() === $destinationId
                && $link->getParent()?->getId() === $destinationLink?->getId()) {
                throw new BadRequestHttpException('A selected document is already in this folder.');
            }

            $sourceLinkIds[$link->getId()] = true;
            $prepared[] = [$node, $link];
        }

        // Detect cycles in both hierarchies, including pre-existing corrupt cycles.
        $ancestors = [];
        $visited = [];
        for ($parent = $destination; null !== $parent; $parent = $parent->getParent()) {
            $id = $parent->getId();
            if (isset($visited[$id])) {
                throw new BadRequestHttpException('Invalid destination folder hierarchy.');
            }
            $visited[$id] = true;
            $ancestors[$id] = true;
        }

        $linkAncestors = [];
        $visited = [];
        for ($parent = $destinationLink; null !== $parent; $parent = $parent->getParent()) {
            $id = $parent->getId();
            if (isset($visited[$id])) {
                throw new BadRequestHttpException('Invalid destination link hierarchy.');
            }
            $visited[$id] = true;
            $linkAncestors[$id] = true;
        }

        foreach ($prepared as [$node, $link]) {
            if (isset($ancestors[$node->getId()]) || isset($linkAncestors[$link->getId()])) {
                throw new BadRequestHttpException('Cannot move a folder into its descendant.');
            }

            // Moving a folder and one of its children together would flatten that subtree.
            $visited = [];
            for ($parent = $node->getParent(); null !== $parent; $parent = $parent->getParent()) {
                $id = $parent->getId();
                if (isset($visited[$id])) {
                    throw new BadRequestHttpException('Invalid source folder hierarchy.');
                }
                $visited[$id] = true;
                if (isset($sourceIds[$id])) {
                    throw new BadRequestHttpException('Select either a folder or its children, not both.');
                }
            }

            $visited = [];
            for ($parent = $link->getParent(); null !== $parent; $parent = $parent->getParent()) {
                $id = $parent->getId();
                if (isset($visited[$id])) {
                    throw new BadRequestHttpException('Invalid source link hierarchy.');
                }
                $visited[$id] = true;
                if (isset($sourceLinkIds[$id])) {
                    throw new BadRequestHttpException('Select either a folder or its children, not both.');
                }
            }
        }

        $this->em->wrapInTransaction(function () use ($prepared, $destination, $destinationLink): void {
            foreach ($prepared as [$node, $link]) {
                $node->setParent($destination);
                $node->setUpdatedAt(new DateTime());
                $link->setParent($destinationLink);
            }
        });

        $this->cacheInvalidator->invalidate();

        return \count($prepared);
    }
}
