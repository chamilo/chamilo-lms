<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEOnline
 *
 * @ORM\Table(name="track_e_online", indexes={@ORM\Index(name="login_user_id", columns={"login_user_id"}), @ORM\Index(name="course", columns={"course"}), @ORM\Index(name="session_id", columns={"session_id"}), @ORM\Index(name="idx_trackonline_uat", columns={"login_user_id", "access_url_id", "login_date"})})
 * @ORM\Entity
 */
class TrackEOnline
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
     * @var string
     *
     * @ORM\Column(name="course", type="string", length=40, nullable=true)
     */
    private $course;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="login_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $loginId;


}
