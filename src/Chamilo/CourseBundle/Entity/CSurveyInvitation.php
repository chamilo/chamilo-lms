<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyInvitation
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
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $iid;

    /**
     * @var integer
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;


    /**
     * @var integer
     *
     * @ORM\Column(name="survey_invitation_id", type="integer")
     */
    private $surveyInvitationId;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_code", type="string", length=20, nullable=false)
     */
    private $surveyCode;

    /**
     * @var string
     *
     * @ORM\Column(name="user", type="string", length=250, nullable=false)
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="invitation_code", type="string", length=250, nullable=false)
     */
    private $invitationCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="invitation_date", type="datetime", nullable=false)
     */
    private $invitationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="reminder_date", type="datetime", nullable=false)
     */
    private $reminderDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    private $answered;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="group_id", type="integer", nullable=false)
     */
    private $groupId;

    /**
     * Set surveyCode
     *
     * @param string $surveyCode
     * @return CSurveyInvitation
     */
    public function setSurveyCode($surveyCode)
    {
        $this->surveyCode = $surveyCode;

        return $this;
    }

    /**
     * Get surveyCode
     *
     * @return string
     */
    public function getSurveyCode()
    {
        return $this->surveyCode;
    }

    /**
     * Set user
     *
     * @param string $user
     * @return CSurveyInvitation
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set invitationCode
     *
     * @param string $invitationCode
     * @return CSurveyInvitation
     */
    public function setInvitationCode($invitationCode)
    {
        $this->invitationCode = $invitationCode;

        return $this;
    }

    /**
     * Get invitationCode
     *
     * @return string
     */
    public function getInvitationCode()
    {
        return $this->invitationCode;
    }

    /**
     * Set invitationDate
     *
     * @param \DateTime $invitationDate
     * @return CSurveyInvitation
     */
    public function setInvitationDate($invitationDate)
    {
        $this->invitationDate = $invitationDate;

        return $this;
    }

    /**
     * Get invitationDate
     *
     * @return \DateTime
     */
    public function getInvitationDate()
    {
        return $this->invitationDate;
    }

    /**
     * Set reminderDate
     *
     * @param \DateTime $reminderDate
     * @return CSurveyInvitation
     */
    public function setReminderDate($reminderDate)
    {
        $this->reminderDate = $reminderDate;

        return $this;
    }

    /**
     * Get reminderDate
     *
     * @return \DateTime
     */
    public function getReminderDate()
    {
        return $this->reminderDate;
    }

    /**
     * Set answered
     *
     * @param integer $answered
     * @return CSurveyInvitation
     */
    public function setAnswered($answered)
    {
        $this->answered = $answered;

        return $this;
    }

    /**
     * Get answered
     *
     * @return integer
     */
    public function getAnswered()
    {
        return $this->answered;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CSurveyInvitation
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return integer
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set groupId
     *
     * @param integer $groupId
     * @return CSurveyInvitation
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set surveyInvitationId
     *
     * @param integer $surveyInvitationId
     * @return CSurveyInvitation
     */
    public function setSurveyInvitationId($surveyInvitationId)
    {
        $this->surveyInvitationId = $surveyInvitationId;

        return $this;
    }

    /**
     * Get surveyInvitationId
     *
     * @return integer
     */
    public function getSurveyInvitationId()
    {
        return $this->surveyInvitationId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CSurveyInvitation
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }
}
