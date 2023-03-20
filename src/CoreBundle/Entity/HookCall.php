<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookCall.
 *
 * @ORM\Table(name="hook_call")
 * @ORM\Entity
 */
class HookCall
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="hook_event_id", type="integer", nullable=false)
     */
    protected int $hookEventId;

    /**
     * @ORM\Column(name="hook_observer_id", type="integer", nullable=false)
     */
    protected int $hookObserverId;

    /**
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    protected bool $type;

    /**
     * @ORM\Column(name="hook_order", type="integer", nullable=false)
     */
    protected int $hookOrder;

    /**
     * @ORM\Column(name="enabled", type="boolean", nullable=false)
     */
    protected bool $enabled;

    /**
     * Set hookEventId.
     *
     * @return HookCall
     */
    public function setHookEventId(int $hookEventId)
    {
        $this->hookEventId = $hookEventId;

        return $this;
    }

    /**
     * Get hookEventId.
     *
     * @return int
     */
    public function getHookEventId()
    {
        return $this->hookEventId;
    }

    /**
     * Set hookObserverId.
     *
     * @return HookCall
     */
    public function setHookObserverId(int $hookObserverId)
    {
        $this->hookObserverId = $hookObserverId;

        return $this;
    }

    /**
     * Get hookObserverId.
     *
     * @return int
     */
    public function getHookObserverId()
    {
        return $this->hookObserverId;
    }

    /**
     * Set type.
     *
     * @return HookCall
     */
    public function setType(bool $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return bool
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set hookOrder.
     *
     * @return HookCall
     */
    public function setHookOrder(int $hookOrder)
    {
        $this->hookOrder = $hookOrder;

        return $this;
    }

    /**
     * Get hookOrder.
     *
     * @return int
     */
    public function getHookOrder()
    {
        return $this->hookOrder;
    }

    /**
     * Set enabled.
     *
     * @return HookCall
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled.
     *
     * @return bool
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

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
