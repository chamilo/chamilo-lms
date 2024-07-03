<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceResultComment.
 *
 * @ORM\Table(
 *  name="c_attendance_result_comment",
 *  indexes={
 *      @ORM\Index(name="attendance_sheet_id", columns={"attendance_sheet_id"}),
 *      @ORM\Index(name="user_id", columns={"user_id"})
 *  }
 * )
 * ORM\Entity
 */
class CAttendanceResultComment
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_sheet_id", type="integer", nullable=false)
     */
    protected $attendanceSheetId;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    protected $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text", nullable=true)
     */
    protected $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt;

    /**
     * @var int
     *
     * @ORM\Column(name="author_user_id", type="integer", nullable=false)
     */
    protected $authorUserId;

    /**
     * Project constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get attendanceSheetId.
     */
    public function getAttendanceSheetId(): int
    {
        return $this->attendanceSheetId;
    }

    /**
     * Set attendanceSheetId.
     *
     * @return CAttendanceResultComment
     */
    public function setAttendanceSheetId(int $attendanceSheetId)
    {
        $this->attendanceSheetId = $attendanceSheetId;

        return $this;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return CAttendanceResultComment
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return CAttendanceResultComment
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return CAttendanceResultComment
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set comment.
     *
     * @param string $comment
     *
     * @return CAttendanceResultComment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment.
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set authorUserId.
     *
     * @param int $authorUserId
     *
     * @return CAttendanceResultComment
     */
    public function setAuthorUserId($authorUserId)
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
}
