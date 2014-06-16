<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxFeedback
 *
 * @ORM\Table(name="c_dropbox_feedback", indexes={@ORM\Index(name="file_id", columns={"file_id"}), @ORM\Index(name="author_user_id", columns={"author_user_id"})})
 * @ORM\Entity
 */
class CDropboxFeedback
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="feedback_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackId;

    /**
     * @var integer
     *
     * @ORM\Column(name="file_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fileId;

    /**
     * @var integer
     *
     * @ORM\Column(name="author_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorUserId;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedback;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="feedback_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackDate;


    /**
     * Get iid
     *
     * @return integer
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CDropboxFeedback
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set feedbackId
     *
     * @param integer $feedbackId
     * @return CDropboxFeedback
     */
    public function setFeedbackId($feedbackId)
    {
        $this->feedbackId = $feedbackId;

        return $this;
    }

    /**
     * Get feedbackId
     *
     * @return integer
     */
    public function getFeedbackId()
    {
        return $this->feedbackId;
    }

    /**
     * Set fileId
     *
     * @param integer $fileId
     * @return CDropboxFeedback
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;

        return $this;
    }

    /**
     * Get fileId
     *
     * @return integer
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Set authorUserId
     *
     * @param integer $authorUserId
     * @return CDropboxFeedback
     */
    public function setAuthorUserId($authorUserId)
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    /**
     * Get authorUserId
     *
     * @return integer
     */
    public function getAuthorUserId()
    {
        return $this->authorUserId;
    }

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return CDropboxFeedback
     */
    public function setFeedback($feedback)
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * Get feedback
     *
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * Set feedbackDate
     *
     * @param \DateTime $feedbackDate
     * @return CDropboxFeedback
     */
    public function setFeedbackDate($feedbackDate)
    {
        $this->feedbackDate = $feedbackDate;

        return $this;
    }

    /**
     * Get feedbackDate
     *
     * @return \DateTime
     */
    public function getFeedbackDate()
    {
        return $this->feedbackDate;
    }
}
