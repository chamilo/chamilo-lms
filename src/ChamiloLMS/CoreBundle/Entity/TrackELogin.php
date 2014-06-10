<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELogin
 *
 * @ORM\Table(name="track_e_login", indexes={@ORM\Index(name="login_user_id", columns={"login_user_id"})})
 * @ORM\Entity
 */
class TrackELogin
{
    /**
     * @var integer
     *
     * @ORM\Column(name="login_user_id", type="integer", nullable=false)
     */
    private $loginUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="login_date", type="datetime", nullable=false)
     */
    private $loginDate;

    /**
     * @var string
     *
     * @ORM\Column(name="login_ip", type="string", length=39, nullable=false)
     */
    private $loginIp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logout_date", type="datetime", nullable=true)
     */
    private $logoutDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $loginId;


}
