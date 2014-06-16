<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDefault
 *
 * @ORM\Table(name="track_e_default")
 * @ORM\Entity
 */
class TrackEDefault
{
    /**
     * @var integer
     *
     * @ORM\Column(name="default_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $defaultId;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="default_cours_code", type="string", length=40, precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultCoursCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="default_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultDate;

    /**
     * @var string
     *
     * @ORM\Column(name="default_event_type", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultEventType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value_type", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultValueType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sessionId;


    /**
     * Get defaultId
     *
     * @return integer
     */
    public function getDefaultId()
    {
        return $this->defaultId;
    }

    /**
     * Set defaultUserId
     *
     * @param integer $defaultUserId
     * @return TrackEDefault
     */
    public function setDefaultUserId($defaultUserId)
    {
        $this->defaultUserId = $defaultUserId;

        return $this;
    }

    /**
     * Get defaultUserId
     *
     * @return integer
     */
    public function getDefaultUserId()
    {
        return $this->defaultUserId;
    }

    /**
     * Set defaultCoursCode
     *
     * @param string $defaultCoursCode
     * @return TrackEDefault
     */
    public function setDefaultCoursCode($defaultCoursCode)
    {
        $this->defaultCoursCode = $defaultCoursCode;

        return $this;
    }

    /**
     * Get defaultCoursCode
     *
     * @return string
     */
    public function getDefaultCoursCode()
    {
        return $this->defaultCoursCode;
    }

    /**
     * Set defaultDate
     *
     * @param \DateTime $defaultDate
     * @return TrackEDefault
     */
    public function setDefaultDate($defaultDate)
    {
        $this->defaultDate = $defaultDate;

        return $this;
    }

    /**
     * Get defaultDate
     *
     * @return \DateTime
     */
    public function getDefaultDate()
    {
        return $this->defaultDate;
    }

    /**
     * Set defaultEventType
     *
     * @param string $defaultEventType
     * @return TrackEDefault
     */
    public function setDefaultEventType($defaultEventType)
    {
        $this->defaultEventType = $defaultEventType;

        return $this;
    }

    /**
     * Get defaultEventType
     *
     * @return string
     */
    public function getDefaultEventType()
    {
        return $this->defaultEventType;
    }

    /**
     * Set defaultValueType
     *
     * @param string $defaultValueType
     * @return TrackEDefault
     */
    public function setDefaultValueType($defaultValueType)
    {
        $this->defaultValueType = $defaultValueType;

        return $this;
    }

    /**
     * Get defaultValueType
     *
     * @return string
     */
    public function getDefaultValueType()
    {
        return $this->defaultValueType;
    }

    /**
     * Set defaultValue
     *
     * @param string $defaultValue
     * @return TrackEDefault
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    /**
     * Get defaultValue
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return TrackEDefault
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackEDefault
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
