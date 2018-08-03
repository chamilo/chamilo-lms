<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlog.
 *
 * @ORM\Table(
 *  name="c_blog",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CBlog
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
     * @ORM\Column(name="blog_id", type="integer")
     */
    protected $blogId;

    /**
     * @var string
     *
     * @ORM\Column(name="blog_name", type="string", length=250, nullable=false)
     */
    protected $blogName;

    /**
     * @var string
     *
     * @ORM\Column(name="blog_subtitle", type="string", length=250, nullable=true)
     */
    protected $blogSubtitle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    protected $dateCreation;

    /**
     * @var bool
     *
     * @ORM\Column(name="visibility", type="boolean", nullable=false)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * Set blogName.
     *
     * @param string $blogName
     *
     * @return CBlog
     */
    public function setBlogName($blogName)
    {
        $this->blogName = $blogName;

        return $this;
    }

    /**
     * Get blogName.
     *
     * @return string
     */
    public function getBlogName()
    {
        return $this->blogName;
    }

    /**
     * Set blogSubtitle.
     *
     * @param string $blogSubtitle
     *
     * @return CBlog
     */
    public function setBlogSubtitle($blogSubtitle)
    {
        $this->blogSubtitle = $blogSubtitle;

        return $this;
    }

    /**
     * Get blogSubtitle.
     *
     * @return string
     */
    public function getBlogSubtitle()
    {
        return $this->blogSubtitle;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return CBlog
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set visibility.
     *
     * @param bool $visibility
     *
     * @return CBlog
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility.
     *
     * @return bool
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CBlog
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
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return CBlog
     */
    public function setBlogId($blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId.
     *
     * @return int
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CBlog
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
