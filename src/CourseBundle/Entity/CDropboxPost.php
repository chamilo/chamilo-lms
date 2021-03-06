<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxPost.
 *
 * @ORM\Table(
 *     name="c_dropbox_post",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="dest_user", columns={"dest_user_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CDropboxPost
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="feedback_date", type="datetime", nullable=false)
     */
    protected DateTime $feedbackDate;

    /**
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    protected ?string $feedback;

    /**
     * @ORM\Column(name="cat_id", type="integer", nullable=false)
     */
    protected int $catId;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="file_id", type="integer")
     */
    protected int $fileId;

    /**
     * @ORM\Column(name="dest_user_id", type="integer")
     */
    protected int $destUserId;

    /**
     * Set feedbackDate.
     *
     * @param DateTime $feedbackDate
     *
     * @return CDropboxPost
     */
    public function setFeedbackDate($feedbackDate)
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
     * Set feedback.
     *
     * @param string $feedback
     *
     * @return CDropboxPost
     */
    public function setFeedback($feedback)
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
     * Set catId.
     *
     * @param int $catId
     *
     * @return CDropboxPost
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId.
     *
     * @return int
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CDropboxPost
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CDropboxPost
     */
    public function setCId($cId)
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
     * Set fileId.
     *
     * @param int $fileId
     *
     * @return CDropboxPost
     */
    public function setFileId($fileId)
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
     * Set destUserId.
     *
     * @param int $destUserId
     *
     * @return CDropboxPost
     */
    public function setDestUserId($destUserId)
    {
        $this->destUserId = $destUserId;

        return $this;
    }

    /**
     * Get destUserId.
     *
     * @return int
     */
    public function getDestUserId()
    {
        return $this->destUserId;
    }
}
