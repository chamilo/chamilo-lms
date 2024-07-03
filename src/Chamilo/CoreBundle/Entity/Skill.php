<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\SkillBundle\Entity\Profile;
use Chamilo\SkillBundle\Entity\SkillRelCourse;
use Chamilo\SkillBundle\Entity\SkillRelItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Skill.
 *
 * @ORM\Table(name="skill")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\SkillRepository")
 */
class Skill
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\SkillBundle\Entity\Profile", inversedBy="skills")
     * @ORM\JoinColumn(name="profile_id", referencedColumnName="id")
     */
    protected $profile;

    /**
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\SkillRelUser", mappedBy="skill", cascade={"persist"})
     */
    protected $issuedSkills;

    /**
     * // uncomment if api_get_configuration_value('allow_skill_rel_items')
     * ORM\OneToMany(targetEntity="Chamilo\SkillBundle\Entity\SkillRelItem", mappedBy="skill", cascade={"persist"}).
     */
    protected $items;

    /**
     * // uncomment if api_get_configuration_value('allow_skill_rel_items')
     * ORM\OneToMany(targetEntity="Chamilo\SkillBundle\Entity\SkillRelCourse", mappedBy="skill", cascade={"persist"}).
     */
    protected $courses;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="short_code", type="string", length=100, nullable=false)
     */
    protected $shortCode;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="access_url_id", type="integer", nullable=false)
     */
    protected $accessUrlId;

    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255, nullable=false)
     */
    protected $icon;

    /**
     * @var string
     *
     * @ORM\Column(name="criteria", type="text", nullable=true)
     */
    protected $criteria;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=false, options={"default": 1})
     */
    protected $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getName();
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Skill
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @param bool $translated Optional. Get the name translated when is it exists in a sub-language. By default is true
     *
     * @return string
     */
    public function getName($translated = true)
    {
        if ($translated) {
            $variable = ChamiloApi::getLanguageVar($this->name, 'Skill');

            return isset($GLOBALS[$variable]) ? $GLOBALS[$variable] : $this->name;
        }

        return $this->name;
    }

    /**
     * Set shortCode.
     *
     * @param string $shortCode
     *
     * @return Skill
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * Get shortCode.
     *
     * @param bool $translated Optional. Get the code translated when is it exists in a sub-language. By default is true
     *
     * @return string
     */
    public function getShortCode($translated = true)
    {
        if ($translated && !empty($this->shortCode)) {
            $variable = ChamiloApi::getLanguageVar($this->shortCode, 'SkillCode');

            return isset($GLOBALS[$variable]) ? $GLOBALS[$variable] : $this->shortCode;
        }

        return $this->shortCode;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Skill
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
     * Set accessUrlId.
     *
     * @param int $accessUrlId
     *
     * @return Skill
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId.
     *
     * @return int
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set icon.
     *
     * @param string $icon
     *
     * @return Skill
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon.
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Set criteria.
     *
     * @param string $criteria
     *
     * @return Skill
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * Get criteria.
     *
     * @return string
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return Skill
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt The update datetime
     *
     * @return Skill
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
     * Get issuedSkills.
     *
     * @return ArrayCollection
     */
    public function getIssuedSkills()
    {
        return $this->issuedSkills;
    }

    /**
     * @return ArrayCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param ArrayCollection $items
     *
     * @return Skill
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param int $itemId
     *
     * @return bool
     */
    public function hasItem($typeId, $itemId)
    {
        if (null !== $this->getItems()) {
            if ($this->getItems()->count()) {
                $found = false;
                /** @var SkillRelItem $item */
                foreach ($this->getItems() as $item) {
                    if ($item->getItemId() == $itemId && $item->getItemType() == $typeId) {
                        $found = true;
                        break;
                    }
                }

                return $found;
            }
        }

        return false;
    }

    public function addItem(SkillRelItem $skillRelItem)
    {
        $skillRelItem->setSkill($this);
        $this->items[] = $skillRelItem;
    }

    public function hasCourses()
    {
        return null !== $this->courses;
    }

    /**
     * @return ArrayCollection
     */
    public function getCourses()
    {
        return $this->courses;
    }

    /**
     * @param ArrayCollection $courses
     *
     * @return Skill
     */
    public function setCourses($courses)
    {
        $this->courses = $courses;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasCourseAndSession(SkillRelCourse $searchItem)
    {
        if ($this->getCourses()->count()) {
            $found = false;
            /** @var SkillRelCourse $item */
            foreach ($this->getCourses() as $item) {
                $sessionPassFilter = false;
                $session = $item->getSession();
                $sessionId = !empty($session) ? $session->getId() : 0;
                $searchSessionId = !empty($searchItem->getSession()) ? $searchItem->getSession()->getId() : 0;

                if ($sessionId === $searchSessionId) {
                    $sessionPassFilter = true;
                }
                if ($item->getCourse()->getId() == $searchItem->getCourse()->getId() &&
                    $sessionPassFilter
                ) {
                    $found = true;
                    break;
                }
            }

            return $found;
        }

        return false;
    }

    public function addToCourse(SkillRelCourse $item)
    {
        $item->setSkill($this);
        $this->courses[] = $item;
    }
}
