<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ClassificationBundle\Entity;

use Sonata\ClassificationBundle\Entity\BaseContext;

/**
 * Class Context.
 *
 * @package Chamilo\ClassificationBundle\Entity
 */
class Context extends BaseContext
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
    public function getId()
    {
        return $this->id;
    }
}
