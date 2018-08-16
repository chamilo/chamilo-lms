<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\Component as BaseComponent;

/**
 * Class Component.
 *
 * @package Chamilo\TimelineBundle\Entity
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
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
