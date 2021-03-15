<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyInvitation.
 *
 * @ORM\Table(
 *     name="c_survey_invitation",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="idx_survey_inv_code", columns={"survey_code"})
 *     }
 * )
 * @ORM\Entity
 */
class CSurveyInvitation
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Course $course = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", inversedBy="resourceLinks")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CGroup")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="iid", nullable=true, onDelete="CASCADE")
     */
    protected ?CGroup $group = null;

    /**
     * @ORM\Column(name="survey_code", type="string", length=20, nullable=false)
     */
    protected string $surveyCode;

    /**
     * @ORM\Column(name="user", type="string", length=250, nullable=false)
     */
    protected string $user;

    /**
     * @ORM\Column(name="invitation_code", type="string", length=250, nullable=false)
     */
    protected string $invitationCode;

    /**
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    protected int $answered;

    /**
     * @ORM\Column(name="invitation_date", type="datetime", nullable=false)
     */
    protected DateTime $invitationDate;

    /**
     * @ORM\Column(name="reminder_date", type="datetime", nullable=true)
     */
    protected ?DateTime $reminderDate = null;

    /**
     * @ORM\Column(name="answered_at", type="datetime", nullable=true)
     */
    protected ?DateTime $answeredAt = null;

    public function setSurveyCode(string $surveyCode): self
    {
        $this->surveyCode = $surveyCode;

        return $this;
    }

    /**
     * Get surveyCode.
     *
     * @return string
     */
    public function getSurveyCode()
    {
        return $this->surveyCode;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setInvitationCode(string $invitationCode): self
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }

    /**
     * Get invitationCode.
     *
     * @return string
     */
    public function getInvitationCode()
    {
        return $this->invitationCode;
    }

    public function setInvitationDate(DateTime $invitationDate): self
    {
        $this->invitationDate = $invitationDate;

        return $this;
    }

    /**
     * Get invitationDate.
     *
     * @return DateTime
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    public function setReminderDate(DateTime $reminderDate): self
    {
        $this->reminderDate = $reminderDate;

        return $this;
    }

    /**
     * Get reminderDate.
     *
     * @return DateTime
     */
    public function getReminderDate()
    {
        return $this->reminderDate;
    }

    public function setAnswered(int $answered): self
    {
        $this->answered = $answered;

        return $this;
    }

    /**
     * Get answered.
     *
     * @return int
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    public function getAnsweredAt(): DateTime
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(DateTime $answeredAt): self
    {
        $this->answeredAt = $answeredAt;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;

        return $this;
    }

    public function getGroup(): ?CGroup
    {
        return $this->group;
    }

    public function setGroup(?CGroup $group): self
    {
        $this->group = $group;

        return $this;
    }
}
