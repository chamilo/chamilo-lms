<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use const PHP_INT_MAX;

#[AsController]
class UpdatePositionLink extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(CLink $link, Request $request): CLink
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];

        $newPosition = isset($payload['position']) ? (int) $payload['position'] : 0;
        if ($newPosition < 0) {
            $newPosition = 0;
        }

        // Context is mandatory for ordering
        $cid = (int) $request->query->get('cid', 0);
        $sid = (int) $request->query->get('sid', 0);

        if ($cid <= 0) {
            throw new BadRequestHttpException('Missing or invalid "cid" for link move operation.');
        }

        $course = $this->em->find(Course::class, $cid);
        if (!$course) {
            throw new BadRequestHttpException('Course not found for provided "cid".');
        }

        $session = null;
        if ($sid > 0) {
            $session = $this->em->find(Session::class, $sid);
            if (!$session) {
                throw new BadRequestHttpException('Session not found for provided "sid".');
            }
        }

        $resourceNode = $link->getResourceNode();
        if (!$resourceNode) {
            throw new BadRequestHttpException('Link has no resource node.');
        }

        $parentNode = $resourceNode->getParent();

        $oldCategory = $link->getCategory();
        $oldCategoryId = (int) $oldCategory?->getIid();

        $targetCategoryId = $oldCategoryId;
        $targetCategory = $oldCategory;

        // assign/move link to a category (DnD between lists)
        if (\array_key_exists('categoryId', $payload)) {
            $targetCategoryId = (int) ($payload['categoryId'] ?? 0);

            if ($targetCategoryId <= 0) {
                $targetCategoryId = 0;
                $targetCategory = null;
            } else {
                /** @var CLinkCategory|null $category */
                $category = $this->em->getRepository(CLinkCategory::class)->find($targetCategoryId);
                if (!$category) {
                    throw new BadRequestHttpException('Target category not found.');
                }

                $catNode = $category->getResourceNode();
                if (!$catNode) {
                    throw new BadRequestHttpException('Target category has no resource node.');
                }

                // Validate same parent node (prevents "vanishing" when category is from another folder/parent)
                if ($catNode->getParent()?->getId() !== $parentNode?->getId()) {
                    throw new BadRequestHttpException('Target category is not under the same parent node.');
                }

                // Validate category exists in the same course/session context
                $catRl = $catNode->getResourceLinkByContext($course, $session);
                if (!$catRl) {
                    throw new BadRequestHttpException('Target category is not available in the current course/session context.');
                }

                $targetCategory = $category;
            }

            $link->setCategory($targetCategory);

            // Flush now so subsequent bucket queries reflect the new category assignment
            $this->em->flush();
        }

        // Reindex destination bucket (with moved link inserted at desired position)
        $this->reindexBucket(
            $parentNode,
            $targetCategoryId,
            $course,
            $session,
            $link,
            $newPosition
        );

        // If moved across categories, reindex source bucket too
        if ($oldCategoryId !== $targetCategoryId) {
            $this->reindexBucket(
                $parentNode,
                $oldCategoryId,
                $course,
                $session,
                null,
                null
            );
        }

        $this->em->flush();

        return $link;
    }

    /**
     * Reshows stable ordering by reindexing ResourceLink.displayOrder for the given bucket & context.
     * Bucket = same parent node + same category (or NULL category).
     */
    private function reindexBucket(
        ?ResourceNode $parentNode,
        int $categoryId,
        Course $course,
        ?Session $session,
        ?CLink $movedLink,
        ?int $insertAt
    ): void {
        $qb = $this->em->getRepository(CLink::class)->createQueryBuilder('l')
            ->join('l.resourceNode', 'rn')
        ;

        if ($parentNode) {
            $qb->andWhere('rn.parent = :parent')
                ->setParameter('parent', $parentNode->getId())
            ;
        } else {
            $qb->andWhere('rn.parent IS NULL');
        }

        if ($categoryId > 0) {
            $qb->andWhere('l.category = :cat')
                ->setParameter('cat', $this->em->getReference(CLinkCategory::class, $categoryId))
            ;
        } else {
            $qb->andWhere('l.category IS NULL');
        }

        /** @var CLink[] $links */
        $links = $qb->getQuery()->getResult();

        // Sort by current displayOrder in this context (stable base before reinserting)
        usort($links, function (CLink $a, CLink $b) use ($course, $session): int {
            $aRl = $a->getResourceNode()?->getResourceLinkByContext($course, $session);
            $bRl = $b->getResourceNode()?->getResourceLinkByContext($course, $session);

            $aOrder = $aRl ? (int) $aRl->getDisplayOrder() : PHP_INT_MAX;
            $bOrder = $bRl ? (int) $bRl->getDisplayOrder() : PHP_INT_MAX;

            if ($aOrder === $bOrder) {
                return $a->getIid() <=> $b->getIid();
            }

            return $aOrder <=> $bOrder;
        });

        // If we are handling the moved link, place it at insertAt
        if ($movedLink && null !== $insertAt) {
            $movedId = (int) $movedLink->getIid();

            $links = array_values(array_filter($links, static fn (CLink $x): bool => (int) $x->getIid() !== $movedId));

            $pos = $insertAt;
            if ($pos < 0) {
                $pos = 0;
            }
            if ($pos > \count($links)) {
                $pos = \count($links);
            }

            array_splice($links, $pos, 0, [$movedLink]);
        }

        // Assign sequential displayOrder in THIS context only
        foreach ($links as $idx => $item) {
            $rn = $item->getResourceNode();
            if (!$rn) {
                continue;
            }

            $rl = $rn->getResourceLinkByContext($course, $session);
            if (!$rl) {
                throw new BadRequestHttpException('ResourceLink not found for link in the current context.');
            }

            $rl->setDisplayOrder($idx);
        }
    }
}
