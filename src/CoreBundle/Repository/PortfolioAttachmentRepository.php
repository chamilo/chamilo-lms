<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PortfolioAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioAttachment::class);
    }

    public function findFromItem(Portfolio $item): array
    {
        return $this->findBy(
            [
                'origin' => $item->getId(),
                'originType' => PortfolioAttachment::TYPE_ITEM,
            ]
        );
    }

    /**
     * @return array<int, PortfolioComment>
     */
    public function findFromComment(PortfolioComment $comment): array
    {
        return $this->findBy(
            [
                'origin' => $comment->getId(),
                'originType' => PortfolioAttachment::TYPE_COMMENT,
            ]
        );
    }

    public function removeFromComment(PortfolioComment $comment): void
    {
        $comments = $this->findFromComment($comment);

        foreach ($comments as $comment) {
            $this->_em->remove($comment);
        }
    }
}
