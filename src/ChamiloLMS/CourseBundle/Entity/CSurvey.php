<?php

namespace ChamiloLMS\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CSurvey
 *
 * @ORM\Table(name="c_survey", indexes={@ORM\Index(name="session_id", columns={"session_id"})})
 * @ORM\Entity
 */
class CSurvey
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
     * @ORM\Column(name="survey_id", type="integer", nullable=false)
     */
    private $surveyId;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    private $subtitle;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=20, nullable=true)
     */
    private $author;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    private $lang;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="avail_from", type="date", nullable=true)
     */
    private $availFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="avail_till", type="date", nullable=true)
     */
    private $availTill;

    /**
     * @var string
     *
     * @ORM\Column(name="is_shared", type="string", length=1, nullable=true)
     */
    private $isShared;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=20, nullable=true)
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    private $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="surveythanks", type="text", nullable=true)
     */
    private $surveythanks;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    private $creationDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="invited", type="integer", nullable=false)
     */
    private $invited;

    /**
     * @var integer
     *
     * @ORM\Column(name="answered", type="integer", nullable=false)
     */
    private $answered;

    /**
     * @var string
     *
     * @ORM\Column(name="invite_mail", type="text", nullable=false)
     */
    private $inviteMail;

    /**
     * @var string
     *
     * @ORM\Column(name="reminder_mail", type="text", nullable=false)
     */
    private $reminderMail;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_subject", type="string", length=255, nullable=false)
     */
    private $mailSubject;

    /**
     * @var string
     *
     * @ORM\Column(name="anonymous", type="string", length=255, nullable=false)
     */
    private $anonymous;

    /**
     * @var string
     *
     * @ORM\Column(name="access_condition", type="text", nullable=true)
     */
    private $accessCondition;

    /**
     * @var boolean
     *
     * @ORM\Column(name="shuffle", type="boolean", nullable=false)
     */
    private $shuffle;

    /**
     * @var boolean
     *
     * @ORM\Column(name="one_question_per_page", type="boolean", nullable=false)
     */
    private $oneQuestionPerPage;

    /**
     * @var string
     *
     * @ORM\Column(name="survey_version", type="string", length=255, nullable=false)
     */
    private $surveyVersion;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=false)
     */
    private $parentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="survey_type", type="integer", nullable=false)
     */
    private $surveyType;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_form_profile", type="integer", nullable=false)
     */
    private $showFormProfile;

    /**
     * @var string
     *
     * @ORM\Column(name="form_fields", type="text", nullable=false)
     */
    private $formFields;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $iid;


}
