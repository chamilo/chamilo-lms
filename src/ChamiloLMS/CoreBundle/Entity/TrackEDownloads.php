<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEDownloads
 *
 * @ORM\Table(name="track_e_downloads", indexes={@ORM\Index(name="down_session_id", columns={"down_session_id"})})
 * @ORM\Entity
 */
class TrackEDownloads
{
    /**
     * @var integer
     *
     * @ORM\Column(name="down_user_id", type="integer", nullable=true)
     */
    private $downUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="down_date", type="datetime", nullable=false)
     */
    private $downDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="down_doc_path", type="string", length=255, nullable=false)
     */
    private $downDocPath;

    /**
     * @var integer
     *
     * @ORM\Column(name="down_session_id", type="integer", nullable=false)
     */
    private $downSessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="down_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $downId;


}
