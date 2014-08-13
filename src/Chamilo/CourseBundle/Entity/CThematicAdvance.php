<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CThematicAdvance
 *
 * @ORM\Table(name="c_thematic_advance", indexes={@ORM\Index(name="thematic_id", columns={"thematic_id"})})
 * @ORM\Entity
 */
class CThematicAdvance
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
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="thematic_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $thematicId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $duration;

    /**
     * @var boolean
     *
     * @ORM\Column(name="done_advance", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $doneAdvance;


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
     * @return CThematicAdvance
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
     * Set id
     *
     * @param integer $id
     * @return CThematicAdvance
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * Set thematicId
     *
     * @param integer $thematicId
     * @return CThematicAdvance
     */
    public function setThematicId($thematicId)
    {
        $this->thematicId = $thematicId;

        return $this;
    }

    /**
     * Get thematicId
     *
     * @return integer
     */
    public function getThematicId()
    {
        return $this->thematicId;
    }

    /**
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return CThematicAdvance
     */
    public function setAttendanceId($attendanceId)
    {
        $this->attendanceId = $attendanceId;

        return $this;
    }

    /**
     * Get attendanceId
     *
     * @return integer
     */
    public function getAttendanceId()
    {
        return $this->attendanceId;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return CThematicAdvance
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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return CThematicAdvance
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return CThematicAdvance
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set doneAdvance
     *
     * @param boolean $doneAdvance
     * @return CThematicAdvance
     */
    public function setDoneAdvance($doneAdvance)
    {
        $this->doneAdvance = $doneAdvance;

        return $this;
    }

    /**
     * Get doneAdvance
     *
     * @return boolean
     */
    public function getDoneAdvance()
    {
        return $this->doneAdvance;
    }
}
