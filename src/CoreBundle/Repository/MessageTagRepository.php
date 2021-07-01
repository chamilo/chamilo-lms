<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\MessageTag;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

class MessageTagRepository extends SortableRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(MessageTag::class));
    }

    public function update(MessageTag $message, $andFlush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($andFlush) {
            $this->getEntityManager()->flush();
        }
    }
}
