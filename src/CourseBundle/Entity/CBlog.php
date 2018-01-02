<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CBlog
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
     * @ORM\Column(name="blog_id", type="integer")
     */
    private $blogId;

    /**
     * @var string
     *
     * @ORM\Column(name="blog_name", type="string", length=250, nullable=false)
     */
    private $blogName;

    /**
     * @var string
     *
     * @ORM\Column(name="blog_subtitle", type="string", length=250, nullable=true)
     */
    private $blogSubtitle;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    private $dateCreation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visibility", type="boolean", nullable=false)
     */
    private $visibility;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    private $sessionId;

    /**
     * Set blogName
     *
     * @param string $blogName
     * @return CBlog
     */
    public function setBlogName($blogName)
    {
        $this->blogName = $blogName;

        return $this;
    }

    /**
     * Get blogName
     *
     * @return string
     */
    public function getBlogName()
    {
        return $this->blogName;
    }

    /**
     * Set blogSubtitle
     *
     * @param string $blogSubtitle
     * @return CBlog
     */
    public function setBlogSubtitle($blogSubtitle)
    {
        $this->blogSubtitle = $blogSubtitle;

        return $this;
    }

    /**
     * Get blogSubtitle
     *
     * @return string
     */
    public function getBlogSubtitle()
    {
        return $this->blogSubtitle;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return CBlog
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     * @return CBlog
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CBlog
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
     * Set blogId
     *
     * @param integer $blogId
     * @return CBlog
     */
    public function setBlogId($blogId)
    {
        $this->blogId = $blogId;

        return $this;
    }

    /**
     * Get blogId
     *
     * @return integer
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CBlog
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
