<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEUploads
 *
 * @ORM\Table(name="track_e_uploads", indexes={@ORM\Index(name="upload_user_id", columns={"upload_user_id"}), @ORM\Index(name="upload_cours_id", columns={"upload_cours_id"}), @ORM\Index(name="upload_session_id", columns={"upload_session_id"})})
 * @ORM\Entity
 */
class TrackEUploads
{
    /**
     * @var integer
     *
     * @ORM\Column(name="upload_user_id", type="integer", nullable=true)
     */
    private $uploadUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="upload_date", type="datetime", nullable=false)
     */
    private $uploadDate;

    /**
     * @var string
     *
     * @ORM\Column(name="upload_cours_id", type="string", length=40, nullable=false)
     */
    private $uploadCoursId;

    /**
     * @var integer
     *
     * @ORM\Column(name="upload_work_id", type="integer", nullable=false)
     */
    private $uploadWorkId;

    /**
     * @var integer
     *
     * @ORM\Column(name="upload_session_id", type="integer", nullable=false)
     */
    private $uploadSessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="upload_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $uploadId;


}
