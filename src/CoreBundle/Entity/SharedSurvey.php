<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurvey.
 *
 * @ORM\Table(name="shared_survey", uniqueConstraints={@ORM\UniqueConstraint(name="id", columns={"survey_id"})})
 * @ORM\Entity
 */
class SharedSurvey
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    protected $subtitle;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=250, nullable=true)
     */
    protected $author;

    /**
     * @var string
     *
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    protected $lang;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string", length=20, nullable=true)
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    protected $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="surveythanks", type="text", nullable=true)
     */
    protected $surveythanks;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     * @var string
     *
     * @ORM\Column(name="course_code", type="string", length=40, nullable=false)
     */
    protected $courseCode;

    /**
     * @var int
     *
     * @ORM\Column(name="survey_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $surveyId;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return SharedSurvey
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return SharedSurvey
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set subtitle.
     *
     * @param string $subtitle
     *
     * @return SharedSurvey
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Get subtitle.
     *
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * Set author.
     *
     * @param string $author
     *
     * @return SharedSurvey
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set lang.
     *
     * @param string $lang
     *
     * @return SharedSurvey
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang.
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set template.
     *
     * @param string $template
     *
     * @return SharedSurvey
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set intro.
     *
     * @param string $intro
     *
     * @return SharedSurvey
     */
    public function setIntro($intro)
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get intro.
     *
     * @return string
     */
    public function getIntro()
    {
        return $this->intro;
    }

    /**
     * Set surveythanks.
     *
     * @param string $surveythanks
     *
     * @return SharedSurvey
     */
    public function setSurveythanks($surveythanks)
    {
        $this->surveythanks = $surveythanks;

        return $this;
    }

    /**
     * Get surveythanks.
     *
     * @return string
     */
    public function getSurveythanks()
    {
        return $this->surveythanks;
    }

    /**
     * Set creationDate.
     *
     * @param \DateTime $creationDate
     *
     * @return SharedSurvey
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set courseCode.
     *
     * @param string $courseCode
     *
     * @return SharedSurvey
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Get courseCode.
     *
     * @return string
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * Get surveyId.
     *
     * @return int
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }
}
