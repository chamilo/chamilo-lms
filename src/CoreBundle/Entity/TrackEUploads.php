<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEUploads.
 *
 * @ORM\Table(
 *     name="track_e_uploads",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="upload_user_id", columns={"upload_user_id"}),
 *         @ORM\Index(name="upload_session_id", columns={"upload_session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class TrackEUploads
{
    /**
     * @ORM\Column(name="upload_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $uploadId;

    /**
     * @ORM\Column(name="upload_user_id", type="integer", nullable=true)
     */
    protected ?int $uploadUserId = null;

    /**
     * @ORM\Column(name="upload_date", type="datetime", nullable=false)
     */
    protected DateTime $uploadDate;

    /**
     * @ORM\Column(name="c_id", type="integer", nullable=true)
     */
    protected ?int $cId = null;

    /**
     * @ORM\Column(name="upload_work_id", type="integer", nullable=false)
     */
    protected int $uploadWorkId;

    /**
     * @ORM\Column(name="upload_session_id", type="integer", nullable=false)
     */
    protected int $uploadSessionId;

    /**
     * Set uploadUserId.
     *
     * @return TrackEUploads
     */
    public function setUploadUserId(int $uploadUserId)
    {
        $this->uploadUserId = $uploadUserId;

        return $this;
    }

    /**
     * Get uploadUserId.
     *
     * @return int
     */
    public function getUploadUserId()
    {
        return $this->uploadUserId;
    }

    /**
     * Set uploadDate.
     *
     * @return TrackEUploads
     */
    public function setUploadDate(DateTime $uploadDate)
    {
        $this->uploadDate = $uploadDate;

        return $this;
    }

    /**
     * Get uploadDate.
     *
     * @return DateTime
     */
    public function getUploadDate()
    {
        return $this->uploadDate;
    }

    /**
     * Set cId.
     *
     * @return TrackEUploads
     */
    public function setCId(int $cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set uploadWorkId.
     *
     * @return TrackEUploads
     */
    public function setUploadWorkId(int $uploadWorkId)
    {
        $this->uploadWorkId = $uploadWorkId;

        return $this;
    }

    /**
     * Get uploadWorkId.
     *
     * @return int
     */
    public function getUploadWorkId()
    {
        return $this->uploadWorkId;
    }

    /**
     * Set uploadSessionId.
     *
     * @return TrackEUploads
     */
    public function setUploadSessionId(int $uploadSessionId)
    {
        $this->uploadSessionId = $uploadSessionId;

        return $this;
    }

    /**
     * Get uploadSessionId.
     *
     * @return int
     */
    public function getUploadSessionId()
    {
        return $this->uploadSessionId;
    }

    /**
     * Get uploadId.
     *
     * @return int
     */
    public function getUploadId()
    {
        return $this->uploadId;
    }
}
