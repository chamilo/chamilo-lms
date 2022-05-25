<?php

declare(strict_types=1);

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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="trackEAccessCompleteList")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected User $user;

    /**
     * @ORM\Column(name="date_reg", type="datetime", nullable=false)
     */
    protected int $dateReg;

    /**
     * @ORM\Column(name="tool", type="string", length=255, nullable=false)
     */
    protected string $tool;

    /**
     * @ORM\Column(name="tool_id", type="integer", nullable=false)
     */
    protected int $toolId;

    /**
     * @ORM\Column(name="tool_id_detail", type="integer", nullable=false)
     */
    protected int $toolIdDetail;

    /**
     * @ORM\Column(name="action", type="string", length=255, nullable=false)
     */
    protected string $action;

    /**
     * @ORM\Column(name="action_details", type="string", length=255, nullable=false)
     */
    protected string $actionDetails;

    /**
     * @ORM\Column(name="current_id", type="integer", nullable=false)
     */
    protected int $currentId;

    /**
     * @ORM\Column(name="ip_user", type="string", length=255, nullable=false)
     */
    protected string $ipUser;

    /**
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=false)
     */
    protected string $userAgent;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    protected int $cId;

    /**
     * @ORM\Column(name="ch_sid", type="string", length=255, nullable=false)
     */
    protected string $chSid;

    /**
     * @ORM\Column(name="login_as", type="integer", nullable=false)
     */
    protected int $loginAs;

    /**
     * @ORM\Column(name="info", type="text", nullable=false)
     */
    protected string $info;

    /**
     * @ORM\Column(name="url", type="text", nullable=false)
     */
    protected string $url;
}
