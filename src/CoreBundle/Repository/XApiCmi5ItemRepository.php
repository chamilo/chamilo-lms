<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\XApiCmi5Item;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

/**
 * @extends NestedTreeRepository<XApiCmi5Item>
 */
class XApiCmi5ItemRepository extends NestedTreeRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(XApiCmi5Item::class));
    }
}
