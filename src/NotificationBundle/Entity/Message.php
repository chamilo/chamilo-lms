<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\NotificationBundle\Entity;

use Sonata\NotificationBundle\Entity\BaseMessage;

/**
 * Class Message.
 *
 * @package Chamilo\NotificationBundle\Entity
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
     * @return int $id
     */
    public function getId()
    {
        return $this->id;
    }
}
