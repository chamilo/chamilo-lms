<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\ActionComponent as BaseActionComponent;

/**
 * Class ActionComponent.
 *
 * @package Chamilo\TimelineBundle\Entity
 */
class ActionComponent extends BaseActionComponent
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
