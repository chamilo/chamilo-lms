<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Doctrine\ORM\EntityRepository;

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

    public function findFromComment(PortfolioComment $comment): array
    {
        return $this->findBy(
            [
                'origin' => $comment->getId(),
                'originType' => PortfolioAttachment::TYPE_COMMENT,
            ]
        );
    }
}
