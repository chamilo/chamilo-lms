<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\Action as BaseAction;

/**
 * Class Action.
 *
 * @package Chamilo\TimelineBundle\Entity
 */
class Action extends BaseAction
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
