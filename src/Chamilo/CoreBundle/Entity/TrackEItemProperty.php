<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEItemProperty
 *
 * @ORM\Table(name="track_e_item_property", indexes={
 *     @ORM\Index(name="course_id", columns={"course_id", "item_property_id", "session_id"})
 * })
 * @ORM\Entity
 */
class TrackEItemProperty
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_id", type="integer", nullable=false)
     */
    private $courseId;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_property_id", type="integer", nullable=false)
     */
    private $itemPropertyId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="progress", type="integer", nullable=false)
     */
    private $progress;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    private $lasteditDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", nullable=false)
     */
    private $lasteditUserId;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * Set courseId
     *
     * @param integer $courseId
     * @return TrackEItemProperty
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId
     *
     * @return integer
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set itemPropertyId
     *
     * @param integer $itemPropertyId
     * @return TrackEItemProperty
     */
    public function setItemPropertyId($itemPropertyId)
    {
        $this->itemPropertyId = $itemPropertyId;

        return $this;
    }

    /**
     * Get itemPropertyId
     *
     * @return integer
     */
    public function getItemPropertyId()
    {
        return $this->itemPropertyId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return TrackEItemProperty
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return TrackEItemProperty
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set progress
     *
     * @param integer $progress
     * @return TrackEItemProperty
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return integer
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set lasteditDate
     *
     * @param \DateTime $lasteditDate
     * @return TrackEItemProperty
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate
     *
     * @return \DateTime
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditUserId
     *
     * @param integer $lasteditUserId
     * @return TrackEItemProperty
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId
     *
     * @return integer
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return TrackEItemProperty
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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
