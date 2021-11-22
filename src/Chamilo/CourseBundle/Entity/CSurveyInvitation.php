<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyInvitation.
 *
 * @ORM\Table(
 *  name="c_survey_invitation",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CSurveyInvitation
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
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_invitation_id", type="integer")
     */
    protected $surveyInvitationId;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_code", type="string", length=20, nullable=false)
     */
    protected $surveyCode;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=250, nullable=false)
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="invitation_code", type="string", length=250, nullable=false)
     */
    protected $invitationCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invitation_date", type="datetime", nullable=false)
     */
    protected $invitationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reminder_date", type="datetime", nullable=false)
     */
    protected $reminderDate;

    /**
     * @var int
     *
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    protected $answered;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected $sessionId;

    /**
     * @var int
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    protected $groupId;

    /**
     * @var int
     *
     * ORM\Column(name="c_lp_item_id", type="integer", nullable=false)
     */
    protected $lpItemId;

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
     * @param \DateTime $invitationDate
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
     * @return \DateTime
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    /**
     * Set reminderDate.
     *
     * @param \DateTime $reminderDate
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
     * @return \DateTime
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
     * Set LpItemId.
     *
     * @param int $lpItemId
     *
     * @return CSurveyInvitation
     */
    public function setLpItemId($lpItemId)
    {
        $this->lpItemId = $lpItemId;

        return $this;
    }

    /**
     * Get LpItemId.
     *
     * @return int
     */
    public function getLpItemId()
    {
        return $this->lpItemId;
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
}
