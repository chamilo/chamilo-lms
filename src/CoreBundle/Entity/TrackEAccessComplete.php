<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEAccessComplete.
 *
 * @ORM\Table(name="track_e_access_complete")
 * @ORM\Entity
 */
class TrackEAccessComplete
{
    use UserTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="trackEAccessCompleteList")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $user;

    /**
     * @var int
     *
     * @ORM\Column(name="date_reg", type="datetime", nullable=false)
     */
    protected $dateReg;

    /**
     * @var string
     *
     * @ORM\Column(name="tool", type="string", length=255, nullable=false)
     */
    protected $tool;

    /**
     * @var int
     *
     * @ORM\Column(name="tool_id", type="integer", nullable=false)
     */
    protected $toolId;

    /**
     * @var int
     *
     * @ORM\Column(name="tool_id_detail", type="integer", nullable=false)
     */
    protected $toolIdDetail;

    /**
     * @var string
     *
     * @ORM\Column(name="action", type="string", length=255, nullable=false)
     */
    protected $action;

    /**
     * @var string
     *
     * @ORM\Column(name="action_details", type="string", length=255, nullable=false)
     */
    protected $actionDetails;

    /**
     * @var int
     *
     * @ORM\Column(name="current_id", type="integer", nullable=false)
     */
    protected $currentId;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_user", type="string", length=255, nullable=false)
     */
    protected $ipUser;

    /**
     * @var string
     *
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=false)
     */
    protected $userAgent;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="ch_sid", type="string", length=255, nullable=false)
     */
    protected $chSid;

    /**
     * @var int
     *
     * @ORM\Column(name="login_as", type="integer", nullable=false)
     */
    protected $loginAs;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="text", nullable=false)
     */
    protected $info;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="text", nullable=false)
     */
    protected $url;
}
