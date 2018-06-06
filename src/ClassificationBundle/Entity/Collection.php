<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle\Entity;

use Sonata\ClassificationBundle\Entity\BaseCollection;

/**
 * Class Collection.
 *
 * @package Chamilo\ClassificationBundle\Entity
 */
class Collection extends BaseCollection
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int $id
     */
    public function getId(): int
    {
        return $this->id;
    }
}
