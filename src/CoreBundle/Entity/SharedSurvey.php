<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * SharedSurvey.
 *
 * @ORM\Table(name="shared_survey", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="id", columns={"survey_id"})
 * })
 * @ORM\Entity
 */
class SharedSurvey
{
    /**
     * @ORM\Column(name="survey_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $surveyId;

    /**
     * @ORM\Column(name="code", type="string", length=20, nullable=true)
     */
    protected ?string $code = null;

    /**
     * @ORM\Column(name="title", type="text", nullable=true)
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="subtitle", type="text", nullable=true)
     */
    protected ?string $subtitle = null;

    /**
     * @ORM\Column(name="author", type="string", length=250, nullable=true)
     */
    protected ?string $author = null;

    /**
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    protected ?string $lang = null;

    /**
     * @ORM\Column(name="template", type="string", length=20, nullable=true)
     */
    protected ?string $template = null;

    /**
     * @ORM\Column(name="intro", type="text", nullable=true)
     */
    protected ?string $intro = null;

    /**
     * @ORM\Column(name="surveythanks", type="text", nullable=true)
     */
    protected ?string $surveythanks = null;

    /**
     * @ORM\Column(name="creation_date", type="datetime", nullable=false)
     */
    protected DateTime $creationDate;

    /**
     * @ORM\Column(name="course_code", type="string", length=40, nullable=false)
     */
    protected string $courseCode;

    /**
     * Set code.
     *
     * @return SharedSurvey
     */
    public function setCode(string $code)
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
     * @return SharedSurvey
     */
    public function setTitle(string $title)
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
     * @return SharedSurvey
     */
    public function setSubtitle(string $subtitle)
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
     * @return SharedSurvey
     */
    public function setAuthor(string $author)
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
     * @return SharedSurvey
     */
    public function setLang(string $lang)
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
     * @return SharedSurvey
     */
    public function setTemplate(string $template)
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
     * @return SharedSurvey
     */
    public function setIntro(string $intro)
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
     * @return SharedSurvey
     */
    public function setSurveyThanks(string $value)
    {
        $this->surveythanks = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getSurveyThanks()
    {
        return $this->surveythanks;
    }

    /**
     * Set creationDate.
     *
     * @return SharedSurvey
     */
    public function setCreationDate(DateTime $creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * Get creationDate.
     *
     * @return DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set courseCode.
     *
     * @return SharedSurvey
     */
    public function setCourseCode(string $courseCode)
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
