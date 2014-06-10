<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheet
 *
 * @ORM\Table(name="c_attendance_sheet", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "user_id", "attendance_calendar_id"})}, indexes={@ORM\Index(name="presence", columns={"presence"})})
 * @ORM\Entity
 */
class CAttendanceSheet
{
    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer", nullable=false)
     */
    private $cId;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     */
    private $userId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_calendar_id", type="integer", nullable=false)
     */
    private $attendanceCalendarId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="presence", type="boolean", nullable=false)
     */
    private $presence;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
