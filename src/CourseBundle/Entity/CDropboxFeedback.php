<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFeedback.
 *
 * @ORM\Table(
 *     name="c_dropbox_feedback",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="file_id", columns={"file_id"}),
 *         @ORM\Index(name="author_user_id", columns={"author_user_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CDropboxFeedback
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="feedback_id", type="integer")
     */
    protected int $feedbackId;

    /**
     * @ORM\Column(name="file_id", type="integer", nullable=false)
     */
    protected int $fileId;

    /**
     * @ORM\Column(name="author_user_id", type="integer", nullable=false)
     */
    protected int $authorUserId;

    /**
     * @ORM\Column(name="feedback", type="text", nullable=false)
     */
    protected string $feedback;

    /**
     * @ORM\Column(name="feedback_date", type="datetime", nullable=false)
     */
    protected DateTime $feedbackDate;

    /**
     * Set fileId.
     *
     * @return CDropboxFeedback
     */
    public function setFileId(int $fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId.
     *
     * @return int
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set authorUserId.
     *
     * @return CDropboxFeedback
     */
    public function setAuthorUserId(int $authorUserId)
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    /**
     * Get authorUserId.
     *
     * @return int
     */
    public function getAuthorUserId()
    {
        return $this->authorUserId;
    }

    /**
     * Set feedback.
     *
     * @return CDropboxFeedback
     */
    public function setFeedback(string $feedback)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback.
     *
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set feedbackDate.
     *
     * @return CDropboxFeedback
     */
    public function setFeedbackDate(DateTime $feedbackDate)
    {
        $this->feedbackDate = $feedbackDate;

        return $this;
    }

    /**
     * Get feedbackDate.
     *
     * @return DateTime
     */
    public function getFeedbackDate()
    {
        return $this->feedbackDate;
    }

    /**
     * Set feedbackId.
     *
     * @return CDropboxFeedback
     */
    public function setFeedbackId(int $feedbackId)
    {
        $this->feedbackId = $feedbackId;

        return $this;
    }

    /**
     * Get feedbackId.
     *
     * @return int
     */
    public function getFeedbackId()
    {
        return $this->feedbackId;
    }

    /**
     * Set cId.
     *
     * @return CDropboxFeedback
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
}
