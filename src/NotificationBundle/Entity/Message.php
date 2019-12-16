<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\NotificationBundle\Entity;

use Sonata\NotificationBundle\Entity\BaseMessage;

/**
 * Class Message.
 */
class Message extends BaseMessage
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
