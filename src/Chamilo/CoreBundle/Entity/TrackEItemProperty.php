<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TrackEItemProperty.
 *
 * @ORM\Table(name="track_e_item_property", indexes={
 *     @ORM\Index(name="course_id", columns={"course_id", "item_property_id", "session_id"})
 * })
 * @ORM\Entity
 */
class TrackEItemProperty
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="course_id", type="integer", nullable=false)
     */
    protected $courseId;

    /**
     * @var int
     *
     * @ORM\Column(name="item_property_id", type="integer", nullable=false)
     */
    protected $itemPropertyId;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @var int
     *
     * @ORM\Column(name="progress", type="integer", nullable=false)
     */
    protected $progress;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    protected $lasteditDate;

    /**
     * @var int
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", nullable=false)
     */
    protected $lasteditUserId;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * Set courseId.
     *
     * @param int $courseId
     *
     * @return TrackEItemProperty
     */
    public function setCourseId($courseId)
    {
        $this->courseId = $courseId;

        return $this;
    }

    /**
     * Get courseId.
     *
     * @return int
     */
    public function getCourseId()
    {
        return $this->courseId;
    }

    /**
     * Set itemPropertyId.
     *
     * @param int $itemPropertyId
     *
     * @return TrackEItemProperty
     */
    public function setItemPropertyId($itemPropertyId)
    {
        $this->itemPropertyId = $itemPropertyId;

        return $this;
    }

    /**
     * Get itemPropertyId.
     *
     * @return int
     */
    public function getItemPropertyId()
    {
        return $this->itemPropertyId;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return TrackEItemProperty
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return TrackEItemProperty
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set progress.
     *
     * @param int $progress
     *
     * @return TrackEItemProperty
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress.
     *
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set lasteditDate.
     *
     * @param \DateTime $lasteditDate
     *
     * @return TrackEItemProperty
     */
    public function setLasteditDate($lasteditDate)
    {
        $this->lasteditDate = $lasteditDate;

        return $this;
    }

    /**
     * Get lasteditDate.
     *
     * @return \DateTime
     */
    public function getLasteditDate()
    {
        return $this->lasteditDate;
    }

    /**
     * Set lasteditUserId.
     *
     * @param int $lasteditUserId
     *
     * @return TrackEItemProperty
     */
    public function setLasteditUserId($lasteditUserId)
    {
        $this->lasteditUserId = $lasteditUserId;

        return $this;
    }

    /**
     * Get lasteditUserId.
     *
     * @return int
     */
    public function getLasteditUserId()
    {
        return $this->lasteditUserId;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return TrackEItemProperty
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
