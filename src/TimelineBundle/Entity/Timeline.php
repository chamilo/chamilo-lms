<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\TimelineBundle\Entity;

use Sonata\TimelineBundle\Entity\Timeline as BaseTimeline;

/**
 * Class Timeline.
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
