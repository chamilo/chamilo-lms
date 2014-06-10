<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceCalendar
 *
 * @ORM\Table(name="c_attendance_calendar", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "id"})}, indexes={@ORM\Index(name="attendance_id", columns={"attendance_id"}), @ORM\Index(name="done_attendance", columns={"done_attendance"})})
 * @ORM\Entity
 */
class CAttendanceCalendar
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
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", nullable=false)
     */
    private $attendanceId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time", type="datetime", nullable=false)
     */
    private $dateTime;

    /**
     * @var boolean
     *
     * @ORM\Column(name="done_attendance", type="boolean", nullable=false)
     */
    private $doneAttendance;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
