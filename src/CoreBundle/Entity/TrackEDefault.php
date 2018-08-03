<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDefault.
 *
 * @ORM\Table(
 *  name="track_e_default",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class TrackEDefault
{
    /**
     * @var int
     *
     * @ORM\Column(name="default_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $defaultId;

    /**
     * @var int
     *
     * @ORM\Column(name="default_user_id", type="integer", nullable=false)
     */
    protected $defaultUserId;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected $cId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="default_date", type="datetime", nullable=true)
     */
    protected $defaultDate;

    /**
     * @var string
     *
     * @ORM\Column(name="default_event_type", type="string", length=255, nullable=false)
     */
    protected $defaultEventType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value_type", type="string", length=255, nullable=false)
     */
    protected $defaultValueType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="text", nullable=false)
     */
    protected $defaultValue;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * Set defaultUserId.
     *
     * @param int $defaultUserId
     *
     * @return TrackEDefault
     */
    public function setDefaultUserId($defaultUserId)
    {
        $this->defaultUserId = $defaultUserId;

        return $this;
    }

    /**
     * Get defaultUserId.
     *
     * @return int
     */
    public function getDefaultUserId()
    {
        return $this->defaultUserId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return TrackEDefault
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set defaultDate.
     *
     * @param \DateTime $defaultDate
     *
     * @return TrackEDefault
     */
    public function setDefaultDate($defaultDate)
    {
        $this->defaultDate = $defaultDate;

        return $this;
    }

    /**
     * Get defaultDate.
     *
     * @return \DateTime
     */
    public function getDefaultDate()
    {
        return $this->defaultDate;
    }

    /**
     * Set defaultEventType.
     *
     * @param string $defaultEventType
     *
     * @return TrackEDefault
     */
    public function setDefaultEventType($defaultEventType)
    {
        $this->defaultEventType = $defaultEventType;

        return $this;
    }

    /**
     * Get defaultEventType.
     *
     * @return string
     */
    public function getDefaultEventType()
    {
        return $this->defaultEventType;
    }

    /**
     * Set defaultValueType.
     *
     * @param string $defaultValueType
     *
     * @return TrackEDefault
     */
    public function setDefaultValueType($defaultValueType)
    {
        $this->defaultValueType = $defaultValueType;

        return $this;
    }

    /**
     * Get defaultValueType.
     *
     * @return string
     */
    public function getDefaultValueType()
    {
        return $this->defaultValueType;
    }

    /**
     * Set defaultValue.
     *
     * @param string $defaultValue
     *
     * @return TrackEDefault
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get defaultValue.
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return TrackEDefault
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Get defaultId.
     *
     * @return int
     */
    public function getDefaultId()
    {
        return $this->defaultId;
    }
}
