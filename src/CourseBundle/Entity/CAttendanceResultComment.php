<?php

declare(strict_types=1);
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceResultComment.
 *
 * @ORM\Table(
 *     name="c_attendance_result_comment",
 *     indexes={
 *         @ORM\Index(name="attendance_sheet_id", columns={"attendance_sheet_id"}),
 *         @ORM\Index(name="user_id", columns={"user_id"})
 *     }
 * )
 * @ORM\Entity
 */
class CAttendanceResultComment
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[ORM\Column(name: 'attendance_sheet_id', type: 'integer', nullable: false)]
    protected int $attendanceSheetId;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: false)]
    protected int $userId;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    protected DateTime $updatedAt;

    #[ORM\Column(name: 'author_user_id', type: 'integer', nullable: false)]
    protected int $authorUserId;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getAttendanceSheetId(): int
    {
        return $this->attendanceSheetId;
    }

    public function setAttendanceSheetId(int $attendanceSheetId): self
    {
        $this->attendanceSheetId = $attendanceSheetId;

        return $this;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setAuthorUserId(int $authorUserId): self
    {
        $this->authorUserId = $authorUserId;

        return $this;
    }

    public function getAuthorUserId(): int
    {
        return $this->authorUserId;
    }
}
