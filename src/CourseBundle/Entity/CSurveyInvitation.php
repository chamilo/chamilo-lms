<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\Course;
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @ORM\Column(name="survey_invitation_id", type="integer")
     */
    protected int $surveyInvitationId;

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
     * @ORM\Column(name="invitation_date", type="datetime", nullable=false)
     */
    protected DateTime $invitationDate;

    /**
     * @ORM\Column(name="reminder_date", type="datetime", nullable=true)
     */
    protected ?DateTime $reminderDate;

    /**
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    protected int $answered;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected int $groupId;

    /**
     * @ORM\Column(name="answered_at", type="datetime", nullable=true)
     */
    protected ?DateTime $answeredAt;

    /**
     * Set surveyCode.
     *
     * @param string $surveyCode
     *
     * @return CSurveyInvitation
     */
    public function setSurveyCode($surveyCode)
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

    /**
     * Set user.
     *
     * @param string $user
     *
     * @return CSurveyInvitation
     */
    public function setUser($user)
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

    /**
     * Set invitationCode.
     *
     * @param string $invitationCode
     *
     * @return CSurveyInvitation
     */
    public function setInvitationCode($invitationCode)
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

    /**
     * Set invitationDate.
     *
     * @param DateTime $invitationDate
     *
     * @return CSurveyInvitation
     */
    public function setInvitationDate($invitationDate)
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

    /**
     * Set reminderDate.
     *
     * @param DateTime $reminderDate
     *
     * @return CSurveyInvitation
     */
    public function setReminderDate($reminderDate)
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

    /**
     * Set answered.
     *
     * @param int $answered
     *
     * @return CSurveyInvitation
     */
    public function setAnswered($answered)
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

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CSurveyInvitation
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set groupId.
     *
     * @param int $groupId
     *
     * @return CSurveyInvitation
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId.
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set surveyInvitationId.
     *
     * @param int $surveyInvitationId
     *
     * @return CSurveyInvitation
     */
    public function setSurveyInvitationId($surveyInvitationId)
    {
        $this->surveyInvitationId = $surveyInvitationId;

        return $this;
    }

    /**
     * Get surveyInvitationId.
     *
     * @return int
     */
    public function getSurveyInvitationId()
    {
        return $this->surveyInvitationId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CSurveyInvitation
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
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
}
