<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\SkillBundle\Entity\Profile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Skill
 *
 * @ORM\Table(name="skill")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SkillRepository")
 */
class Skill
{
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="short_code", type="string", length=100, nullable=false)
     */
    private $shortCode;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    private $accessUrlId;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255, nullable=false)
     */
    private $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="criteria", type="text", nullable=true)
     */
    private $criteria;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default": 1})
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\Profile", inversedBy="skills")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     **/
    protected $profile;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="skill", cascade={"persist"})
     */
    protected $issuedSkills;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Skill
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set shortCode
     *
     * @param string $shortCode
     * @return Skill
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * Get shortCode
     *
     * @return string
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Skill
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return Skill
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set icon
     *
     * @param string $icon
     * @return Skill
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get the icon (badge image) URL
     * @param boolean $getSmall Optional. Allow get the small image
     * @return string
     */
    public function getWebIconPath($getSmall = false)
    {
        if ($getSmall) {
            if (empty($this->icon)) {
                return \Display::return_icon('badges-default.png', null, null, ICON_SIZE_BIG, null, true);
            }

            return api_get_path(WEB_UPLOAD_PATH).'badges/'.sha1($this->name).'-small.png';
        }

        if (empty($this->icon)) {
            return \Display::return_icon('badges-default.png', null, null, ICON_SIZE_HUGE, null, true);
        }

        return api_get_path(WEB_UPLOAD_PATH)."badges/{$this->icon}";
    }

    /**
     * Set criteria
     *
     * @param string $criteria
     * @return Skill
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Get criteria
     *
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Set status
     * @param integer $status
     * @return Skill
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set updatedAt
     * @param \DateTime $updatedAt The update datetime
     * @return Skill
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * @param Profile $profile
     *
     * @return Skill
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get issuedSkills
     *
     * @return ArrayCollection
     */
    public function getIssuedSkills()
    {
        return $this->issuedSkills;
    }
}
