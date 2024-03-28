<?php

/* For license terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\TopLinks\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class TopLinkRepository.
 *
 * @package Chamilo\PluginBundle\Entity\TopLinks
 */
class TopLinkRepository extends EntityRepository
{
    public function all($offset, $limit, $column, $direction): array
    {
        $orderBy = ['title' => $direction];

        if (1 == $column) {
            $orderBy = ['url' => $direction];
        }

        return parent::findBy([], $orderBy, $limit, $offset);
    }
}
