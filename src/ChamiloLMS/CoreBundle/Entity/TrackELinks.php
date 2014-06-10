<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackELinks
 *
 * @ORM\Table(name="track_e_links", indexes={@ORM\Index(name="links_user_id", columns={"links_user_id"}), @ORM\Index(name="links_session_id", columns={"links_session_id"})})
 * @ORM\Entity
 */
class TrackELinks
{
    /**
     * @var integer
     *
     * @ORM\Column(name="links_user_id", type="integer", nullable=true)
     */
    private $linksUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="links_date", type="datetime", nullable=false)
     */
    private $linksDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="links_link_id", type="integer", nullable=false)
     */
    private $linksLinkId;

    /**
     * @var integer
     *
     * @ORM\Column(name="links_session_id", type="integer", nullable=false)
     */
    private $linksSessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="links_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $linksId;


}
