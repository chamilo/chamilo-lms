<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CAttendanceSheetLog
 *
 * @ORM\Table(name="c_attendance_sheet_log", uniqueConstraints={@ORM\UniqueConstraint(name="c_id", columns={"c_id", "id"})})
 * @ORM\Entity
 */
class CAttendanceSheetLog
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
     * @ORM\Column(name="lastedit_date", type="datetime", nullable=false)
     */
    private $lasteditDate;

    /**
     * @var string
     *
     * @ORM\Column(name="lastedit_type", type="string", length=200, nullable=false)
     */
    private $lasteditType;

    /**
     * @var integer
     *
     * @ORM\Column(name="lastedit_user_id", type="integer", nullable=false)
     */
    private $lasteditUserId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="calendar_date_value", type="datetime", nullable=true)
     */
    private $calendarDateValue;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
