<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\Component as BaseComponent;

/**
 * Class Component.
 */
class Component extends BaseComponent
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
