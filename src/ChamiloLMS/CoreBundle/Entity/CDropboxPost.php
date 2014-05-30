<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CDropboxPost
 *
 * @ORM\Table(name="c_dropbox_post")
 * @ORM\Entity
 */
class CDropboxPost
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
     * @ORM\Column(name="file_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $fileId;

    /**
     * @var integer
     *
     * @ORM\Column(name="dest_user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $destUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="feedback_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $feedbackDate;

    /**
     * @var string
     *
     * @ORM\Column(name="feedback", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $feedback;

    /**
     * @var integer
     *
     * @ORM\Column(name="cat_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $catId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="dropBoxReceivedFiles")
     * @ORM\JoinColumn(name="dest_user_id", referencedColumnName="id")
     **/
    private $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="CDropboxFile", inversedBy="file")
     *
     * @ORM\JoinColumns({
     *      @ORM\JoinColumn(name="file_id", referencedColumnName="iid")
     * })
     **/
    private $file;

    /**
     * @return CDropboxFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CDropboxPost
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
     * Set fileId
     *
     * @param integer $fileId
     * @return CDropboxPost
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
     * Set destUserId
     *
     * @param integer $destUserId
     * @return CDropboxPost
     */
    public function setDestUserId($destUserId)
    {
        $this->destUserId = $destUserId;

        return $this;
    }

    /**
     * Get destUserId
     *
     * @return integer
     */
    public function getDestUserId()
    {
        return $this->destUserId;
    }

    /**
     * Set feedbackDate
     *
     * @param \DateTime $feedbackDate
     * @return CDropboxPost
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

    /**
     * Set feedback
     *
     * @param string $feedback
     * @return CDropboxPost
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
     * Set catId
     *
     * @param integer $catId
     * @return CDropboxPost
     */
    public function setCatId($catId)
    {
        $this->catId = $catId;

        return $this;
    }

    /**
     * Get catId
     *
     * @return integer
     */
    public function getCatId()
    {
        return $this->catId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CDropboxPost
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
}
