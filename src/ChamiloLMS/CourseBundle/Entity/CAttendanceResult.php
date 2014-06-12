<?php

namespace ChamiloLMS\CourseBundle\Entity;

<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceResult
 *
 * @ORM\Table(name="c_attendance_result", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "id"})}, indexes={@ORM\Index(name="attendance_id", columns={"attendance_id"}), @ORM\Index(name="user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class CAttendanceResult
{
    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
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
     * @ORM\Column(name="user_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $attendanceId;

    /**
     * @var integer
     *
     * @ORM\Column(name="score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $score;


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
     * @return CAttendanceResult
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
     * @return CAttendanceResult
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
     * Set userId
     *
     * @param integer $userId
     * @return CAttendanceResult
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set attendanceId
     *
     * @param integer $attendanceId
     * @return CAttendanceResult
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
     * Set score
     *
     * @param integer $score
     * @return CAttendanceResult
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score
     *
     * @return integer 
     */
    public function getScore()
    {
        return $this->score;
    }
}
