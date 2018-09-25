<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrl.
 *
 * @ORM\Table(name="access_url")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\AccessUrlRepository")
 */
class AccessUrl
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="AccessUrlRelCourse", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $course;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false, unique=false)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", unique=false)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="active", type="integer", nullable=false, unique=false)
     */
    protected $active;

    /**
     * @var int
     *
     * @ORM\Column(name="created_by", type="integer", nullable=false, unique=false)
     */
    protected $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=true)
     */
    protected $tms;

    /**
     * @var bool
     *
     * @ORM\Column(name="url_type", type="boolean", nullable=true)
     */
    protected $urlType;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SettingsCurrent", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    //protected $settings;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SessionCategory", mappedBy="url", cascade={"persist"}, orphanRemoval=true)
     */
    protected $sessionCategory;

    /**
     * AccessUrl constructor.
     */
    public function __construct()
    {
        $this->tms = new \DateTime();
        $this->createdBy = 1;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getUrl();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return AccessUrl
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return AccessUrl
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set active.
     *
     * @param int $active
     *
     * @return AccessUrl
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active.
     *
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set createdBy.
     *
     * @param int $createdBy
     *
     * @return AccessUrl
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set tms.
     *
     * @param \DateTime $tms
     *
     * @return AccessUrl
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms.
     *
     * @return \DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }

    /**
     * Set urlType.
     *
     * @param bool $urlType
     *
     * @return AccessUrl
     */
    public function setUrlType($urlType)
    {
        $this->urlType = $urlType;

        return $this;
    }

    /**
     * Get urlType.
     *
     * @return bool
     */
    public function getUrlType()
    {
        return $this->urlType;
    }
}
