<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Repository;

use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

/**
 * ExtraFieldRelTagRepository.
 *
 * @package Chamilo\CoreBundle\Entity\Repository
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class ExtraFieldRelTagRepository extends EntityRepository
{
    /**
     * Get the tags for a item.
     *
     * @param ExtraField $extraField The extrafield
     * @param int        $itemId     The item ID
     *
     * @return array
     */
    public function getTags(ExtraField $extraField, $itemId)
    {
        $queryBuilder = $this->createQueryBuilder('ft');

        $queryBuilder->select('t')
            ->innerJoin(
                'ChamiloCoreBundle:Tag',
                't',
                Join::WITH,
                'ft.tagId = t.id'
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('ft.itemId', intval($itemId)),
                    $queryBuilder->expr()->eq('ft.fieldId', $extraField->getId())
                )
            );

        return $queryBuilder->getQuery()->getResult();
    }

    public function getTagsByUserMessages(int $userId)
    {
        $qb = $this->createQueryBuilder('eft');
        $qb
            ->select('t')
            ->distinct(true)
            ->innerJoin('ChamiloCoreBundle:Tag', 't', Join::WITH, 'eft.tagId = t.id AND eft.fieldId = t.fieldId')
            ->innerJoin('ChamiloCoreBundle:ExtraField', 'ef', Join::WITH, 'eft.fieldId = ef.id')
            ->innerJoin('ChamiloCoreBundle:Message', 'm', Join::WITH, 'eft.itemId = m.id')
            ->where($qb->expr()->eq('ef.variable', ':variable'))
            ->andWhere($qb->expr()->eq('ef.extraFieldType', ':extraFieldType'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('m.userReceiverId', ':userId'),
                        $qb->expr()->in('m.msgStatus', [MESSAGE_STATUS_NEW, MESSAGE_STATUS_UNREAD])
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('m.userSenderId', ':userId'),
                        $qb->expr()->in('m.msgStatus', [MESSAGE_STATUS_OUTBOX])
                    )
                )
            )
            ->orderBy('t.tag', 'ASC')
            ->setParameters(
                [
                    'variable' => 'tags',
                    'extraFieldType' => ExtraField::MESSAGE_TYPE,
                    'userId' => $userId,
                ]
            )
        ;

        return $qb->getQuery()->getResult();
    }
}
