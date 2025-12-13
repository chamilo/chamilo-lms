<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<CDocument>
 */
final class DocumentCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<CDocument>|CDocument|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        $qb = $this->entityManager
            ->getRepository(CDocument::class)
            ->createQueryBuilder('d')
            ->innerJoin('d.resourceNode', 'rn')
            ->addSelect('rn')
        ;

        // Filetype filtering: filetype[]=file&filetype[]=folder&filetype[]=video OR filetype=folder
        $filetypes = $request->query->all('filetype');

        if (empty($filetypes)) {
            $singleFiletype = $request->query->get('filetype');
            if (null !== $singleFiletype && '' !== $singleFiletype) {
                $filetypes = [$singleFiletype];
            }
        }

        if (!empty($filetypes)) {
            if (!\is_array($filetypes)) {
                $filetypes = [$filetypes];
            }

            $qb
                ->andWhere($qb->expr()->in('d.filetype', ':filetypes'))
                ->setParameter('filetypes', $filetypes)
            ;
        }

        // Context (course / session / group)
        $cid = $request->query->getInt('cid', 0);
        $sid = $request->query->getInt('sid', 0);
        $gid = $request->query->getInt('gid', 0);

        $hasContext = $cid > 0 || $sid > 0 || $gid > 0;

        // loadNode=1 -> documents list wants children of a folder
        $loadNode = (bool) $request->query->get('loadNode', false);

        // Current folder node (comes from Vue as resourceNode.parent=XX)
        $parentNodeId = (int) $request->query->get('resourceNode.parent', 0);
        if (0 === $parentNodeId) {
            $parentNodeId = (int) $request->query->get('resourceNode_parent', 0);
        }

        if ($hasContext) {
            // Contextual hierarchy based on ResourceLink.parent
            $qb->innerJoin('rn.resourceLinks', 'rl');

            if ($cid > 0) {
                $qb
                    ->andWhere('rl.course = :cid')
                    ->setParameter('cid', $cid)
                ;
            }

            if ($sid > 0) {
                $qb
                    ->andWhere('rl.session = :sid')
                    ->setParameter('sid', $sid)
                ;
            }

            if ($gid > 0) {
                $qb
                    ->andWhere('rl.group = :gid')
                    ->setParameter('gid', $gid)
                ;
            }

            if ($loadNode) {
                // We are browsing "inside" a folder in this context
                if ($parentNodeId > 0) {
                    $resourceNode = $this->entityManager
                        ->getRepository(ResourceNode::class)
                        ->find($parentNodeId)
                    ;

                    if (null === $resourceNode) {
                        // Folder node not found -> nothing to list
                        return [];
                    }

                    /** @var ResourceLinkRepository $linkRepo */
                    $linkRepo = $this->entityManager->getRepository(ResourceLink::class);

                    $courseEntity = $cid > 0
                        ? $this->entityManager->getRepository(Course::class)->find($cid)
                        : null;

                    $sessionEntity = $sid > 0
                        ? $this->entityManager->getRepository(Session::class)->find($sid)
                        : null;

                    $groupEntity = $gid > 0
                        ? $this->entityManager->getRepository(CGroup::class)->find($gid)
                        : null;

                    // Find the link of this folder in the current context
                    $parentLink = $linkRepo->findParentLinkForContext(
                        $resourceNode,
                        $courseEntity,
                        $sessionEntity,
                        $groupEntity,
                        null,
                        null
                    );

                    if (null === $parentLink) {
                        // No link for this node in this context:
                        // treat it as context root → children have rl.parent IS NULL
                        $qb->andWhere('rl.parent IS NULL');
                    } else {
                        // Children inside this folder in this context
                        $qb
                            ->andWhere('rl.parent = :parentLink')
                            ->setParameter('parentLink', $parentLink)
                        ;
                    }
                } else {
                    // No parentNodeId -> root of the context (course root)
                    $qb->andWhere('rl.parent IS NULL');
                }
            }

            // When the same document is linked multiple times in this context
            $qb->distinct();
        } else {
            // No course / session / group context:
            // keep legacy behavior using resource_node.parent (global docs, if any)
            if ($parentNodeId > 0) {
                $qb
                    ->andWhere('rn.parent = :parentId')
                    ->setParameter('parentId', $parentNodeId)
                ;
            }
        }

        // Ordering & pagination
        $qb->orderBy('rn.title', 'ASC');

        $page = (int) $request->query->get('page', 1);
        $itemsPerPage = (int) $request->query->get('itemsPerPage', 20);

        if ($page < 1) {
            $page = 1;
        }

        if ($itemsPerPage > 0) {
            $qb
                ->setFirstResult(($page - 1) * $itemsPerPage)
                ->setMaxResults($itemsPerPage)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
