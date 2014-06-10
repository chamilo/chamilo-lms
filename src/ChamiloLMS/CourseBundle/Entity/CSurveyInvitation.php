<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurveyInvitation
 *
 * @ORM\Table(name="c_survey_invitation")
 * @ORM\Entity
 */
class CSurveyInvitation
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
     * @ORM\Column(name="survey_invitation_id", type="integer", nullable=false)
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
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
