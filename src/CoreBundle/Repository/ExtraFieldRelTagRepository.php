<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
 */
class ExtraFieldRelTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExtraFieldRelTag::class);
    }
}
