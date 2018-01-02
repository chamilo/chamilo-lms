<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookCall
 *
 * @ORM\Table(name="hook_call")
 * @ORM\Entity
 */
class HookCall
{
    /**
     * @var integer
     *
     * @ORM\Column(name="hook_event_id", type="integer", nullable=false)
     */
    private $hookEventId;

    /**
     * @var integer
     *
     * @ORM\Column(name="hook_observer_id", type="integer", nullable=false)
     */
    private $hookObserverId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="type", type="boolean", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="hook_order", type="integer", nullable=false)
     */
    private $hookOrder;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=false)
     */
    private $enabled;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set hookEventId
     *
     * @param integer $hookEventId
     * @return HookCall
     */
    public function setHookEventId($hookEventId)
    {
        $this->hookEventId = $hookEventId;

        return $this;
    }

    /**
     * Get hookEventId
     *
     * @return integer
     */
    public function getHookEventId()
    {
        return $this->hookEventId;
    }

    /**
     * Set hookObserverId
     *
     * @param integer $hookObserverId
     * @return HookCall
     */
    public function setHookObserverId($hookObserverId)
    {
        $this->hookObserverId = $hookObserverId;

        return $this;
    }

    /**
     * Get hookObserverId
     *
     * @return integer
     */
    public function getHookObserverId()
    {
        return $this->hookObserverId;
    }

    /**
     * Set type
     *
     * @param boolean $type
     * @return HookCall
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return boolean
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set hookOrder
     *
     * @param integer $hookOrder
     * @return HookCall
     */
    public function setHookOrder($hookOrder)
    {
        $this->hookOrder = $hookOrder;

        return $this;
    }

    /**
     * Get hookOrder
     *
     * @return integer
     */
    public function getHookOrder()
    {
        return $this->hookOrder;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     * @return HookCall
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
