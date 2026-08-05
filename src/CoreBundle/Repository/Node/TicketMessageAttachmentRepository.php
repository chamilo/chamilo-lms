<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ResourceRepository<TicketMessageAttachment>
 */
class TicketMessageAttachmentRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TicketMessageAttachment::class);
    }

    /**
     * @param list<int> $resourceNodeIds
     *
     * @return array<int, TicketMessageAttachment>
     */
    public function findIndexedByResourceNodeIds(array $resourceNodeIds): array
    {
        if ([] === $resourceNodeIds) {
            return [];
        }

        /** @var list<TicketMessageAttachment> $attachments */
        $attachments = $this->createQueryBuilder('attachment')
            ->innerJoin('attachment.resourceNode', 'resourceNode')
            ->addSelect('resourceNode')
            ->andWhere('resourceNode.id IN (:resourceNodeIds)')
            ->setParameter('resourceNodeIds', $resourceNodeIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        $indexedAttachments = [];
        foreach ($attachments as $attachment) {
            $resourceNodeId = $attachment->getResourceNode()?->getId();
            if (null !== $resourceNodeId) {
                $indexedAttachments[$resourceNodeId] = $attachment;
            }
        }

        return $indexedAttachments;
    }
}
