<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELastaccess
 *
 * @ORM\Table(name="track_e_lastaccess", indexes={@ORM\Index(name="access_c_id_user_id", columns={"c_id", "access_user_id"}), @ORM\Index(name="access_user_id", columns={"access_user_id"}), @ORM\Index(name="access_cours_code", columns={"c_id"}), @ORM\Index(name="access_session_id", columns={"access_session_id"})})
 * @ORM\Entity
 */
class TrackELastaccess
{
    /**
     * @var integer
     *
     * @ORM\Column(name="access_user_id", type="integer", nullable=true)
     */
    private $accessUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="access_date", type="datetime", nullable=false)
     */
    private $accessDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="access_tool", type="string", length=30, nullable=true)
     */
    private $accessTool;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_session_id", type="integer", nullable=true)
     */
    private $accessSessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $accessId;


}
