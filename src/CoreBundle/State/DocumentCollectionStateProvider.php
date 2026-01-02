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

        $query = $request->query->all();
        $cid = (int) ($query['cid'] ?? 0);
        $sid = (int) ($query['sid'] ?? 0);
        $gid = (int) ($query['gid'] ?? 0);

        $hasContext = $cid > 0 || $sid > 0 || $gid > 0;

        // Gradebook mode (e.g. gradebook=1)
        $isGradebook = !empty($query['gradebook']) && 1 === (int) $query['gradebook'];
        $rawFiletype = $query['filetype'] ?? null;
        $filetypes = [];

        if (\is_array($rawFiletype)) {
            $filetypes = $rawFiletype;
        } elseif (null !== $rawFiletype && '' !== (string) $rawFiletype) {
            $filetypes = [(string) $rawFiletype];
        }

        // Normalize & unique
        $filetypes = array_values(array_unique(array_filter(array_map('strval', $filetypes))));

        // Compatibility: treat "html" as a subtype of "file"
        if (\in_array('file', $filetypes, true) && !\in_array('html', $filetypes, true)) {
            $filetypes[] = 'html';
        }

        if (!empty($filetypes)) {
            $qb
                ->andWhere($qb->expr()->in('d.filetype', ':filetypes'))
                ->setParameter('filetypes', $filetypes)
            ;
        }

        $wantsCertificateList = \in_array('certificate', $filetypes, true);
        $showSystemCertificates = !empty($query['showSystemCertificates']) && 1 === (int) $query['showSystemCertificates'];

        if (!$showSystemCertificates && !($isGradebook && $wantsCertificateList)) {
            $meta = $this->entityManager->getClassMetadata(ResourceNode::class);
            if ($meta->hasField('path')) {
                $qb
                    ->andWhere('rn.path IS NULL OR rn.path NOT LIKE :certificatesPath')
                    ->setParameter('certificatesPath', '%/certificates-%')
                ;
            }
        }

        $loadNode = !empty($query['loadNode']) && 1 === (int) $query['loadNode'];

        $parentNodeId = 0;
        if (isset($query['resourceNode.parent'])) {
            $parentNodeId = (int) $query['resourceNode.parent'];
        } elseif (isset($query['resourceNode_parent'])) {
            $parentNodeId = (int) $query['resourceNode_parent'];
        }

        if ($hasContext) {
            // Contextual hierarchy based on ResourceLink.parent
            $qb->innerJoin('rn.resourceLinks', 'rl');

            if ($cid > 0) {
                $qb->andWhere('IDENTITY(rl.course) = :cid')->setParameter('cid', $cid);
            }
            if ($sid > 0) {
                $qb->andWhere('IDENTITY(rl.session) = :sid')->setParameter('sid', $sid);
            }
            if ($gid > 0) {
                $qb->andWhere('IDENTITY(rl.group) = :gid')->setParameter('gid', $gid);
            }

            if ($loadNode) {
                if ($parentNodeId > 0) {
                    $folderNode = $this->entityManager->getRepository(ResourceNode::class)->find($parentNodeId);
                    if (null === $folderNode) {
                        // Folder not found -> nothing to list
                        return [];
                    }

                    /** @var ResourceLinkRepository $linkRepo */
                    $linkRepo = $this->entityManager->getRepository(ResourceLink::class);

                    $courseEntity = $cid > 0 ? $this->entityManager->getRepository(Course::class)->find($cid) : null;
                    $sessionEntity = $sid > 0 ? $this->entityManager->getRepository(Session::class)->find($sid) : null;
                    $groupEntity = $gid > 0 ? $this->entityManager->getRepository(CGroup::class)->find($gid) : null;

                    // Find the folder link in current context
                    $parentLink = $linkRepo->findParentLinkForContext(
                        $folderNode,
                        $courseEntity,
                        $sessionEntity,
                        $groupEntity,
                        null,
                        null
                    );

                    if (null !== $parentLink) {
                        $qb
                            ->andWhere(
                                $qb->expr()->orX(
                                    'IDENTITY(rl.parent) = :parentLinkId',
                                    'IDENTITY(rn.parent) = :parentNodeId'
                                )
                            )
                            ->setParameter('parentLinkId', (int) $parentLink->getId())
                            ->setParameter('parentNodeId', $parentNodeId)
                        ;
                    } else {
                        // No parent link in this context -> fallback to legacy tree
                        $qb
                            ->andWhere('IDENTITY(rn.parent) = :parentNodeId')
                            ->setParameter('parentNodeId', $parentNodeId)
                        ;
                    }
                } else {
                    // Context root
                    $qb->andWhere('rl.parent IS NULL');
                }
            }

            $qb->distinct();
        } else {
            // No context: legacy behavior
            if ($parentNodeId > 0) {
                $qb
                    ->andWhere('IDENTITY(rn.parent) = :parentId')
                    ->setParameter('parentId', $parentNodeId)
                ;
            }
        }

        $qb->orderBy('rn.title', 'ASC');

        $page = isset($query['page']) ? (int) $query['page'] : 1;
        $itemsPerPage = isset($query['itemsPerPage']) ? (int) $query['itemsPerPage'] : 20;

        if ($page < 1) {
            $page = 1;
        }

        // Prevent extreme values (frontend sometimes sends huge numbers)
        if ($itemsPerPage <= 0) {
            $itemsPerPage = 20;
        }
        if ($itemsPerPage > 5000) {
            $itemsPerPage = 5000;
        }

        $qb
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;

        return $qb->getQuery()->getResult();
    }
}
