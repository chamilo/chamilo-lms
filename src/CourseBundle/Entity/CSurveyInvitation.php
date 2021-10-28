<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="c_survey_invitation",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
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
     * @ORM\ManyToOne(targetEntity="CSurvey", inversedBy="invitations")
     * @ORM\JoinColumn(name="survey_id", referencedColumnName="iid")
     */
    protected CSurvey $survey;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="surveyInvitations")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    protected string $externalEmail;

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

    public function __construct()
    {
        $this->answered = 0;
        $this->invitationDate = new DateTime();
    }

    public function getSurvey(): CSurvey
    {
        return $this->survey;
    }

    public function setSurvey(CSurvey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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
