<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * TrackEAccessComplete.
 *
 * @ApiResource(
 *      attributes={"security"="is_granted('ROLE_ADMIN')"},
 *      iri="http://schema.org/trackEAccessComplete",
 *      normalizationContext={"groups"={"user:read"}},
 *      denormalizationContext={"groups"={"user:write"}},
 *      collectionOperations={"get"},
 *      itemOperations={"get"}
 * )
 * @ORM\Table(name="track_e_access_complete")
 * @ORM\Entity
 */
class TrackEAccessComplete
{
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
     * @ApiProperty(iri="http://schema.org/Person")
     * @ORM\OneToOne (
     *    targetEntity="Chamilo\CoreBundle\Entity\User",
     *    inversedBy="trackEAccessComplete"
     * )
     * @ORM\JoinColumn(
     *    name="user_id",
     *    referencedColumnName="id",
     *    onDelete="CASCADE"
     * )
     */
    protected $user;

    /**
     * Get user.
     *
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

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
