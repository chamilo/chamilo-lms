<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\Timeline as BaseTimeline;

/**
 * Class Timeline.
 *
 * @package Chamilo\TimelineBundle\Entity
 */
class Timeline extends BaseTimeline
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
