<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\Repository\RepositoryQueryBuilderTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    use RepositoryQueryBuilderTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function update(Message $message, bool $andFlush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(Message $message): void
    {
        $this->getEntityManager()->remove($message);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Message[]
     */
    public function getMessageByUser(User $user, int $type)
    {
        $qb = $this->addReceiverQueryBuilder($user);
        $qb = $this->addMessageTypeQueryBuilder($type, $qb);

        return $qb->getQuery()->getResult();
    }

    protected function addReceiverQueryBuilder(User $user, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'm');
        $qb
            ->join('m.receivers', 'r')
        ;
        $qb
            ->andWhere('r.receiver = :user')
            ->setParameter('user', $user)
        ;

        return $qb;
    }

    protected function addMessageTypeQueryBuilder(int $type, QueryBuilder $qb = null): QueryBuilder
    {
        $qb = $this->getOrCreateQueryBuilder($qb, 'm');
        $qb
            ->andWhere('m.msgType = :type')
            ->setParameter('type', $type)
        ;

        return $qb;
    }
}
