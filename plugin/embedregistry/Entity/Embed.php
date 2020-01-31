<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\EmbedRegistry;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class EmbedRegistry.
 *
 * @package Chamilo\PluginBundle\Entity\EmbedRegistry
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_embed_registry_embed")
 */
class Embed
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text")
     */
    private $title;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_start_date", type="datetime")
     */
    private $displayStartDate;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="display_end_date", type="datetime")
     */
    private $displayEndDate;
    /**
     * @var string
     *
     * @ORM\Column(name="html_code", type="text")
     */
    private $htmlCode;
    /**
     * @var Course
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    private $course;
    /**
     * @var Session|null
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     */
    private $session;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Embed
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     *
     * @return Embed
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDisplayStartDate()
    {
        return $this->displayStartDate;
    }

    /**
     * @return Embed
     */
    public function setDisplayStartDate(\DateTime $displayStartDate)
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDisplayEndDate()
    {
        return $this->displayEndDate;
    }

    /**
     * @return Embed
     */
    public function setDisplayEndDate(\DateTime $displayEndDate)
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlCode()
    {
        return $this->htmlCode;
    }

    /**
     * @param string $htmlCode
     *
     * @return Embed
     */
    public function setHtmlCode($htmlCode)
    {
        $this->htmlCode = $htmlCode;

        return $this;
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return Embed
     */
    public function setCourse(Course $course)
    {
        $this->course = $course;

        return $this;
    }

    /**
     * @return Session|null
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return Embed
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;

        return $this;
    }
}
