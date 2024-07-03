<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XApiAttachment>
 *
 * @method XApiAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method XApiAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method XApiAttachment[]    findAll()
 * @method XApiAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class XApiAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XApiAttachment::class);
    }
}
