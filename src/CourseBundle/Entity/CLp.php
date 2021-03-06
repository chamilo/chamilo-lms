<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="c_lp"
 * )
 * @ORM\Entity
 */
class CLp extends AbstractResource implements ResourceInterface
{
    public const LP_TYPE = 1;
    public const SCORM_TYPE = 2;
    public const AICC_TYPE = 3;

    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @ORM\Column(name="c_id", type="integer")
     */
    protected int $cId;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="lp_type", type="integer", nullable=false)
     */
    protected int $lpType;

    /**
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="ref", type="text", nullable=true)
     */
    protected ?string $ref;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description;

    /**
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    protected string $path;

    /**
     * @ORM\Column(name="force_commit", type="boolean", nullable=false)
     */
    protected bool $forceCommit;

    /**
     * @ORM\Column(name="default_view_mod", type="string", length=32, nullable=false, options={"default":"embedded"})
     */
    protected string $defaultViewMod;

    /**
     * @ORM\Column(name="default_encoding", type="string", length=32, nullable=false, options={"default":"UTF-8"})
     */
    protected string $defaultEncoding;

    /**
     * @ORM\Column(name="display_order", type="integer", nullable=false, options={"default":"0"})
     */
    protected int $displayOrder;

    /**
     * @ORM\Column(name="content_maker", type="text", nullable=false)
     */
    protected string $contentMaker;

    /**
     * @ORM\Column(name="content_local", type="string", length=32, nullable=false, options={"default":"local"})
     */
    protected string $contentLocal;

    /**
     * @ORM\Column(name="content_license", type="text", nullable=false)
     */
    protected string $contentLicense;

    /**
     * @ORM\Column(name="prevent_reinit", type="boolean", nullable=false, options={"default":"1"})
     */
    protected bool $preventReinit;

    /**
     * @ORM\Column(name="js_lib", type="text", nullable=false)
     */
    protected string $jsLib;

    /**
     * @ORM\Column(name="debug", type="boolean", nullable=false)
     */
    protected bool $debug;

    /**
     * @ORM\Column(name="theme", type="string", length=255, nullable=false)
     */
    protected string $theme;

    /**
     * @ORM\Column(name="author", type="text", nullable=false)
     */
    protected string $author;

    /**
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    protected int $sessionId;

    /**
     * @ORM\Column(name="prerequisite", type="integer", nullable=false)
     */
    protected int $prerequisite;

    /**
     * @ORM\Column(name="hide_toc_frame", type="boolean", nullable=false)
     */
    protected bool $hideTocFrame;

    /**
     * @ORM\Column(name="seriousgame_mode", type="boolean", nullable=false)
     */
    protected bool $seriousgameMode;

    /**
     * @ORM\Column(name="use_max_score", type="integer", nullable=false, options={"default":"1"})
     */
    protected int $useMaxScore;

    /**
     * @ORM\Column(name="autolaunch", type="integer", nullable=false)
     */
    protected int $autolaunch;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpCategory", inversedBy="lps")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     */
    protected ?CLpCategory $category;

    /**
     * @ORM\Column(name="max_attempts", type="integer", nullable=false)
     */
    protected int $maxAttempts;

    /**
     * @ORM\Column(name="subscribe_users", type="integer", nullable=false)
     */
    protected int $subscribeUsers;

    /**
     * @Gedmo\Timestampable(on="create")
     *
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    protected DateTime $createdOn;

    /**
     * @Gedmo\Timestampable(on="update")
     *
     * @ORM\Column(name="modified_on", type="datetime", nullable=false)
     */
    protected DateTime $modifiedOn;

    /**
     * @ORM\Column(name="publicated_on", type="datetime", nullable=true)
     */
    protected ?DateTime $publicatedOn;

    /**
     * @ORM\Column(name="expired_on", type="datetime", nullable=true)
     */
    protected ?DateTime $expiredOn;

    /**
     * @ORM\Column(name="accumulate_scorm_time", type="integer", nullable=false, options={"default":1})
     */
    protected int $accumulateScormTime;

    /**
     * @ORM\Column(name="accumulate_work_time", type="integer", nullable=false, options={"default":0})
     */
    protected int $accumulateWorkTime;

    /**
     * @var ArrayCollection|CLpItem[]
     *
     * @ORM\OneToMany(targetEntity="CLpItem", mappedBy="lp", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $items;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CourseBundle\Entity\CForumForum", mappedBy="lp")
     */
    protected CForumForum $forum;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Asset", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id")
     */
    protected ?Asset $asset = null;

    public function __construct()
    {
        $this->accumulateScormTime = 1;
        $this->accumulateWorkTime = 0;
        $this->author = '';
        $this->autolaunch = 0;
        $this->contentLocal = 'local';
        $this->contentMaker = 'chamilo';
        $this->contentLicense = '';
        $this->createdOn = new DateTime();
        $this->modifiedOn = new DateTime();
        $this->publicatedOn = new DateTime();
        $this->defaultEncoding = 'UTF-8';
        $this->defaultViewMod = 'embedded';
        $this->description = '';
        $this->displayOrder = 0;
        $this->debug = false;
        $this->forceCommit = false;
        $this->hideTocFrame = false;
        $this->jsLib = '';
        $this->maxAttempts = 0;
        $this->preventReinit = true;
        $this->path = '';
        $this->prerequisite = 0;
        $this->seriousgameMode = false;
        $this->subscribeUsers = 0;
        $this->useMaxScore = 1;
        $this->theme = '';
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * Set lpType.
     *
     * @param int $lpType
     *
     * @return CLp
     */
    public function setLpType($lpType)
    {
        $this->lpType = $lpType;

        return $this;
    }

    /**
     * Get lpType.
     *
     * @return int
     */
    public function getLpType()
    {
        return $this->lpType;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CLp
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set ref.
     *
     * @param string $ref
     *
     * @return CLp
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref.
     *
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return CLp
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set forceCommit.
     *
     * @param bool $forceCommit
     *
     * @return CLp
     */
    public function setForceCommit($forceCommit)
    {
        $this->forceCommit = $forceCommit;

        return $this;
    }

    /**
     * Get forceCommit.
     *
     * @return bool
     */
    public function getForceCommit()
    {
        return $this->forceCommit;
    }

    /**
     * Set defaultViewMod.
     *
     * @param string $defaultViewMod
     *
     * @return CLp
     */
    public function setDefaultViewMod($defaultViewMod)
    {
        $this->defaultViewMod = $defaultViewMod;

        return $this;
    }

    /**
     * Get defaultViewMod.
     *
     * @return string
     */
    public function getDefaultViewMod()
    {
        return $this->defaultViewMod;
    }

    /**
     * Set defaultEncoding.
     *
     * @param string $defaultEncoding
     *
     * @return CLp
     */
    public function setDefaultEncoding($defaultEncoding)
    {
        $this->defaultEncoding = $defaultEncoding;

        return $this;
    }

    /**
     * Get defaultEncoding.
     *
     * @return string
     */
    public function getDefaultEncoding()
    {
        return $this->defaultEncoding;
    }

    /**
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CLp
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set contentMaker.
     *
     * @param string $contentMaker
     *
     * @return CLp
     */
    public function setContentMaker($contentMaker)
    {
        $this->contentMaker = $contentMaker;

        return $this;
    }

    /**
     * Get contentMaker.
     *
     * @return string
     */
    public function getContentMaker()
    {
        return $this->contentMaker;
    }

    /**
     * Set contentLocal.
     *
     * @param string $contentLocal
     *
     * @return CLp
     */
    public function setContentLocal($contentLocal)
    {
        $this->contentLocal = $contentLocal;

        return $this;
    }

    /**
     * Get contentLocal.
     *
     * @return string
     */
    public function getContentLocal()
    {
        return $this->contentLocal;
    }

    /**
     * Set contentLicense.
     *
     * @param string $contentLicense
     *
     * @return CLp
     */
    public function setContentLicense($contentLicense)
    {
        $this->contentLicense = $contentLicense;

        return $this;
    }

    /**
     * Get contentLicense.
     *
     * @return string
     */
    public function getContentLicense()
    {
        return $this->contentLicense;
    }

    /**
     * Set preventReinit.
     *
     * @param bool $preventReinit
     *
     * @return CLp
     */
    public function setPreventReinit($preventReinit)
    {
        $this->preventReinit = $preventReinit;

        return $this;
    }

    /**
     * Get preventReinit.
     *
     * @return bool
     */
    public function getPreventReinit()
    {
        return $this->preventReinit;
    }

    /**
     * Set jsLib.
     *
     * @param string $jsLib
     *
     * @return CLp
     */
    public function setJsLib($jsLib)
    {
        $this->jsLib = $jsLib;

        return $this;
    }

    /**
     * Get jsLib.
     *
     * @return string
     */
    public function getJsLib()
    {
        return $this->jsLib;
    }

    /**
     * Set debug.
     *
     * @param bool $debug
     *
     * @return CLp
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug.
     *
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set theme.
     *
     * @param string $theme
     *
     * @return CLp
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme.
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set author.
     *
     * @param string $author
     *
     * @return CLp
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
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CLp
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
     * Set prerequisite.
     *
     * @param int $prerequisite
     *
     * @return CLp
     */
    public function setPrerequisite($prerequisite)
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    /**
     * Get prerequisite.
     *
     * @return int
     */
    public function getPrerequisite()
    {
        return $this->prerequisite;
    }

    /**
     * Set hideTocFrame.
     *
     * @param bool $hideTocFrame
     *
     * @return CLp
     */
    public function setHideTocFrame($hideTocFrame)
    {
        $this->hideTocFrame = $hideTocFrame;

        return $this;
    }

    /**
     * Get hideTocFrame.
     *
     * @return bool
     */
    public function getHideTocFrame()
    {
        return $this->hideTocFrame;
    }

    /**
     * Set seriousgameMode.
     *
     * @param bool $seriousgameMode
     *
     * @return CLp
     */
    public function setSeriousgameMode($seriousgameMode)
    {
        $this->seriousgameMode = $seriousgameMode;

        return $this;
    }

    /**
     * Get seriousgameMode.
     *
     * @return bool
     */
    public function getSeriousgameMode()
    {
        return $this->seriousgameMode;
    }

    /**
     * Set useMaxScore.
     *
     * @param int $useMaxScore
     *
     * @return CLp
     */
    public function setUseMaxScore($useMaxScore)
    {
        $this->useMaxScore = $useMaxScore;

        return $this;
    }

    /**
     * Get useMaxScore.
     *
     * @return int
     */
    public function getUseMaxScore()
    {
        return $this->useMaxScore;
    }

    /**
     * Set autolaunch.
     *
     * @param int $autolaunch
     *
     * @return CLp
     */
    public function setAutolaunch($autolaunch)
    {
        $this->autolaunch = $autolaunch;

        return $this;
    }

    /**
     * Get autolaunch.
     *
     * @return int
     */
    public function getAutolaunch()
    {
        return $this->autolaunch;
    }

    /**
     * Set createdOn.
     *
     * @param DateTime $createdOn
     *
     * @return CLp
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn.
     *
     * @return DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set modifiedOn.
     *
     * @param DateTime $modifiedOn
     *
     * @return CLp
     */
    public function setModifiedOn($modifiedOn)
    {
        $this->modifiedOn = $modifiedOn;

        return $this;
    }

    /**
     * Get modifiedOn.
     *
     * @return DateTime
     */
    public function getModifiedOn()
    {
        return $this->modifiedOn;
    }

    /**
     * Set publicatedOn.
     *
     * @param DateTime $publicatedOn
     *
     * @return CLp
     */
    public function setPublicatedOn($publicatedOn)
    {
        $this->publicatedOn = $publicatedOn;

        return $this;
    }

    /**
     * Get publicatedOn.
     *
     * @return DateTime
     */
    public function getPublicatedOn()
    {
        return $this->publicatedOn;
    }

    /**
     * Set expiredOn.
     *
     * @param DateTime $expiredOn
     *
     * @return CLp
     */
    public function setExpiredOn($expiredOn)
    {
        $this->expiredOn = $expiredOn;

        return $this;
    }

    /**
     * Get expiredOn.
     *
     * @return DateTime
     */
    public function getExpiredOn()
    {
        return $this->expiredOn;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLp
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

    public function getCategory(): ?CLpCategory
    {
        return $this->category;
    }

    public function setCategory(CLpCategory $category = null): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccumulateScormTime()
    {
        return $this->accumulateScormTime;
    }

    /**
     * @param int $accumulateScormTime
     *
     * @return CLp
     */
    public function setAccumulateScormTime($accumulateScormTime)
    {
        $this->accumulateScormTime = $accumulateScormTime;

        return $this;
    }

    public function getAccumulateWorkTime(): int
    {
        return $this->accumulateWorkTime;
    }

    public function setAccumulateWorkTime(int $accumulateWorkTime): self
    {
        $this->accumulateWorkTime = $accumulateWorkTime;

        return $this;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Get subscribeUsers.
     *
     * @return int
     */
    public function getSubscribeUsers()
    {
        return $this->subscribeUsers;
    }

    public function setSubscribeUsers(int $value): self
    {
        $this->subscribeUsers = $value;

        return $this;
    }

    public function getForum(): ?CForumForum
    {
        return $this->forum;
    }

    public function hasForum(): bool
    {
        return null !== $this->forum;
    }

    public function setForum(CForumForum $forum): self
    {
        $this->forum = $forum;

        return $this;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function hasAsset(): bool
    {
        return null !== $this->asset;
    }

    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
