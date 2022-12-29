<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Class PortfolioAttachmentRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 */
class PortfolioAttachmentRepository extends EntityRepository
{
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function removeFromComment(PortfolioComment $comment)
    {
        $comments = $this->findFromComment($comment);

        foreach ($comments as $comment) {
            $this->_em->remove($comment);
        }
    }
}
