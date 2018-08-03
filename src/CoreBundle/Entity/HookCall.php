<?php
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
     * @var int
     *
     * @ORM\Column(name="hook_event_id", type="integer", nullable=false)
     */
    protected $hookEventId;

    /**
     * @var int
     *
     * @ORM\Column(name="hook_observer_id", type="integer", nullable=false)
     */
    protected $hookObserverId;

    /**
     * @var bool
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    protected $type;

    /**
     * @var int
     *
     * @ORM\Column(name="hook_order", type="integer", nullable=false)
     */
    protected $hookOrder;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false)
     */
    protected $enabled;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set hookEventId.
     *
     * @param int $hookEventId
     *
     * @return HookCall
     */
    public function setHookEventId($hookEventId)
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
     * @param int $hookObserverId
     *
     * @return HookCall
     */
    public function setHookObserverId($hookObserverId)
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
     * @param bool $type
     *
     * @return HookCall
     */
    public function setType($type)
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
     * @param int $hookOrder
     *
     * @return HookCall
     */
    public function setHookOrder($hookOrder)
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
     * @param bool $enabled
     *
     * @return HookCall
     */
    public function setEnabled($enabled)
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
