<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MoveDocumentAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private ResourceLinkRepository $linkRepo,
    ) {}

    public function __invoke(CDocument $document, Request $request): CDocument
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON body.');
        }

        $rawParent = $payload['parentResourceNodeId'] ?? null;
        if (null === $rawParent || '' === $rawParent) {
            throw new BadRequestHttpException('Missing "parentResourceNodeId".');
        }

        $destNodeId = $this->normalizeNodeId($rawParent);
        if (null === $destNodeId) {
            throw new BadRequestHttpException('Invalid "parentResourceNodeId".');
        }

        $cid = $request->query->getInt('cid', 0);
        $sid = $request->query->getInt('sid', 0);
        $gid = $request->query->getInt('gid', 0);

        $hasContext = $cid > 0 || $sid > 0 || $gid > 0;

        /** @var ResourceNode|null $destNode */
        $destNode = $this->em->getRepository(ResourceNode::class)->find($destNodeId);
        if (!$destNode) {
            throw new BadRequestHttpException('Destination folder node not found.');
        }

        $docNode = $document->getResourceNode();
        if (!$docNode) {
            throw new BadRequestHttpException('Document resource node not found.');
        }

        if ($docNode->getId() === $destNode->getId()) {
            throw new BadRequestHttpException('Cannot move into itself.');
        }

        // Always keep ResourceNode.parent in sync for providers still relying on resourceNode.parent.
        $docNode->setParent($destNode);
        $docNode->setUpdatedAt(new DateTime());
        $this->em->persist($docNode);

        if ($hasContext) {
            $course = $cid > 0 ? $this->em->getRepository(Course::class)->find($cid) : null;
            $session = $sid > 0 ? $this->em->getRepository(Session::class)->find($sid) : null;
            $group = $gid > 0 ? $this->em->getRepository(CGroup::class)->find($gid) : null;

            // Find the current link for THIS resource in THIS context.
            $docLink = $this->linkRepo->findLinkForResourceInContext(
                $document,
                $course,
                $session,
                $group,
                null,
                null
            );

            // Backward-compatible fallback (older repos sometimes used node-based lookup).
            if (!$docLink instanceof ResourceLink) {
                $docLink = $this->linkRepo->findParentLinkForContext(
                    $docNode,
                    $course,
                    $session,
                    $group,
                    null,
                    null
                );
            }

            if (!$docLink instanceof ResourceLink) {
                throw new BadRequestHttpException('Document has no link in this context.');
            }

            $destLink = $this->linkRepo->findParentLinkForContext(
                $destNode,
                $course,
                $session,
                $group,
                null,
                null
            );

            if (!$destLink instanceof ResourceLink) {
                // Safer behavior: do not silently "lose" the item in the UI.
                // If destination folder has no link in this context, we keep it as root-level in link-tree
                // but still update ResourceNode.parent, so the item is not lost for node-based providers.
                $docLink->setParent(null);
                $this->em->persist($docLink);
                $this->em->flush();

                return $document;
            }

            // Moving folder/file under a folder link.
            $docLink->setParent($destLink);
            $this->em->persist($docLink);
        }

        $this->em->flush();

        return $document;
    }

    private function normalizeNodeId(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            if (ctype_digit($value)) {
                return (int) $value;
            }

            if (preg_match('#/api/resource_nodes/(\d+)#', $value, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }
}
