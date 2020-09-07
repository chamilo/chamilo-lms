<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CAttendance.
 *
 * @ORM\Table(
 *  name="c_attendance",
 *  indexes={
 *      @ORM\Index(name="active", columns={"active"})
 *  }
 * )
 * @ORM\Entity
 */
class CAttendance extends AbstractResource implements ResourceInterface
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
     * @var string
     * @Assert\NotBlank
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="active", type="integer", nullable=false)
     */
    protected $active;

    /**
     * @var string
     *
     * @ORM\Column(name="attendance_qualify_title", type="string", length=255, nullable=true)
     */
    protected $attendanceQualifyTitle;

    /**
     * @var int
     *
     * @ORM\Column(name="attendance_qualify_max", type="integer", nullable=false)
     */
    protected $attendanceQualifyMax;

    /**
     * @var float
     *
     * @ORM\Column(name="attendance_weight", type="float", precision=6, scale=2, nullable=false)
     */
    protected $attendanceWeight;

    /**
     * @var int
     *
     * @ORM\Column(name="locked", type="integer", nullable=false)
     */
    protected $locked;

    public function __construct()
    {
        $this->active = 1;
        $this->attendanceQualifyMax = 0;
        $this->locked = 0;
    }

    public function __toString(): string
    {
        return (string) $this->getIid();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CAttendance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CAttendance
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set active.
     */
    public function setActive(int $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     */
    public function getActive(): int
    {
        return (int) $this->active;
    }

    /**
     * Set attendanceQualifyTitle.
     *
     * @param string $attendanceQualifyTitle
     *
     * @return CAttendance
     */
    public function setAttendanceQualifyTitle($attendanceQualifyTitle)
    {
        $this->attendanceQualifyTitle = $attendanceQualifyTitle;

        return $this;
    }

    /**
     * Get attendanceQualifyTitle.
     *
     * @return string
     */
    public function getAttendanceQualifyTitle()
    {
        return $this->attendanceQualifyTitle;
    }

    /**
     * Set attendanceQualifyMax.
     *
     * @param int $attendanceQualifyMax
     */
    public function setAttendanceQualifyMax($attendanceQualifyMax): self
    {
        $this->attendanceQualifyMax = $attendanceQualifyMax;

        return $this;
    }

    /**
     * Get attendanceQualifyMax.
     *
     * @return int
     */
    public function getAttendanceQualifyMax()
    {
        return $this->attendanceQualifyMax;
    }

    /**
     * Set attendanceWeight.
     *
     * @param float $attendanceWeight
     */
    public function setAttendanceWeight($attendanceWeight): self
    {
        $this->attendanceWeight = $attendanceWeight;

        return $this;
    }

    /**
     * Get attendanceWeight.
     *
     * @return float
     */
    public function getAttendanceWeight()
    {
        return $this->attendanceWeight;
    }

    /**
     * Set locked.
     */
    public function setLocked(int $locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return int
     */
    public function getLocked()
    {
        return $this->locked;
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
