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
     * @ORM\Column(name="default_user_id", type="integer", nullable=false)
     */
    private $defaultUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="default_cours_code", type="string", length=40, nullable=false)
     */
    private $defaultCoursCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="default_date", type="datetime", nullable=false)
     */
    private $defaultDate;

    /**
     * @var string
     *
     * @ORM\Column(name="default_event_type", type="string", length=255, nullable=false)
     */
    private $defaultEventType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value_type", type="string", length=255, nullable=false)
     */
    private $defaultValueType;

    /**
     * @var string
     *
     * @ORM\Column(name="default_value", type="text", nullable=false)
     */
    private $defaultValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $defaultId;


}
