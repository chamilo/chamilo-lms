<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
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
}
