<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDefault.
 *
 * @ORM\Table(
 *     name="track_e_default",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session", columns={"session_id"}),
 *         @ORM\Index(name="idx_default_user_id", columns={"default_user_id"})
 *     }
 * )
 * @ORM\Entity
 */
class TrackEDefault
{
    /**
     * @ORM\Column(name="default_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $defaultId;

    /**
     * @ORM\Column(name="default_user_id", type="integer", nullable=false)
     */
    protected int $defaultUserId;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected ?int $cId = null;

    /**
     * @ORM\Column(name="default_date", type="datetime", nullable=false)
     */
    protected DateTime $defaultDate;

    /**
     * @ORM\Column(name="default_event_type", type="string", length=255, nullable=false)
     */
    protected string $defaultEventType;

    /**
     * @ORM\Column(name="default_value_type", type="string", length=255, nullable=false)
     */
    protected string $defaultValueType;

    /**
     * @ORM\Column(name="default_value", type="text", nullable=false)
     */
    protected string $defaultValue;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected ?int $sessionId = null;

    /**
     * Set defaultUserId.
     *
     * @return TrackEDefault
     */
    public function setDefaultUserId(int $defaultUserId)
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

    public function setCId(int $cId): self
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

    public function setDefaultDate(DateTime $defaultDate): self
    {
        $this->defaultDate = $defaultDate;

        return $this;
    }

    /**
     * Get defaultDate.
     *
     * @return DateTime
     */
    public function getDefaultDate()
    {
        return $this->defaultDate;
    }

    public function setDefaultEventType(string $defaultEventType): self
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

    public function setDefaultValueType(string $defaultValueType): self
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

    public function setDefaultValue(string $defaultValue): self
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

    public function setSessionId(int $sessionId): self
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
