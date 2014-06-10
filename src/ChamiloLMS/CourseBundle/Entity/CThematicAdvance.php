<?php

namespace ChamiloLMS\CourseBundle\Entity;

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
     * @ORM\Column(name="thematic_id", type="integer", nullable=false)
     */
    private $thematicId;

    /**
     * @var integer
     *
     * @ORM\Column(name="attendance_id", type="integer", nullable=false)
     */
    private $attendanceId;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    private $startDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer", nullable=false)
     */
    private $duration;

    /**
     * @var boolean
     *
     * @ORM\Column(name="done_advance", type="boolean", nullable=false)
     */
    private $doneAdvance;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
