<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CLp
 *
 * @ORM\Table(
 *  name="c_lp",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *     @ORM\Index(name="session", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CLp
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
     * @ORM\Column(name="id", type="integer", nullable=true)
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="lp_type", type="integer", nullable=false)
     */
    private $lpType;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ref", type="text", nullable=true)
     */
    private $ref;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="text", nullable=false)
     */
    private $path;

    /**
     * @var boolean
     *
     * @ORM\Column(name="force_commit", type="boolean", nullable=false)
     */
    private $forceCommit;

    /**
     * @var string
     *
     * @ORM\Column(name="default_view_mod", type="string", length=32, nullable=false, options={"default":"embedded"})
     */
    private $defaultViewMod;

    /**
     * @var string
     *
     * @ORM\Column(name="default_encoding", type="string", length=32, nullable=false, options={"default":"UTF-8"})
     */
    private $defaultEncoding;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_order", type="integer", nullable=false, options={"default":"0"})
     */
    private $displayOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="content_maker", type="text", nullable=false)
     */
    private $contentMaker;

    /**
     * @var string
     *
     * @ORM\Column(name="content_local", type="string", length=32, nullable=false, options={"default":"local"})
     */
    private $contentLocal;

    /**
     * @var string
     *
     * @ORM\Column(name="content_license", type="text", nullable=false)
     */
    private $contentLicense;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prevent_reinit", type="boolean", nullable=false, options={"default":"1"})
     */
    private $preventReinit;

    /**
     * @var string
     *
     * @ORM\Column(name="js_lib", type="text", nullable=false)
     */
    private $jsLib;

    /**
     * @var boolean
     *
     * @ORM\Column(name="debug", type="boolean", nullable=false)
     */
    private $debug;

    /**
     * @var string
     *
     * @ORM\Column(name="theme", type="string", length=255, nullable=false)
     */
    private $theme;

    /**
     * @var string
     *
     * @ORM\Column(name="preview_image", type="string", length=255, nullable=false)
     */
    private $previewImage;

    /**
     * @var string
     *
     * @ORM\Column(name="author", type="string", length=255, nullable=false)
     */
    private $author;

    /**
     * @var integer
     *
     * @ORM\Column(name="session_id", type="integer", nullable=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @ORM\Column(name="prerequisite", type="integer", nullable=false)
     */
    private $prerequisite;

    /**
     * @var boolean
     *
     * @ORM\Column(name="hide_toc_frame", type="boolean", nullable=false)
     */
    private $hideTocFrame;

    /**
     * @var boolean
     *
     * @ORM\Column(name="seriousgame_mode", type="boolean", nullable=false)
     */
    private $seriousgameMode;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_max_score", type="integer", nullable=false, options={"default":"1"})
     */
    private $useMaxScore;

    /**
     * @var integer
     *
     * @ORM\Column(name="autolaunch", type="integer", nullable=false)
     */
    private $autolaunch;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $categoryId;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_attempts", type="integer", nullable=false)
     */
    private $maxAttempts;

    /**
     * @var integer
     *
     * @ORM\Column(name="subscribe_users", type="integer", nullable=false)
     */
    private $subscribeUsers;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    private $createdOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="modified_on", type="datetime", nullable=false)
     */
    private $modifiedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publicated_on", type="datetime", nullable=true)
     */
    private $publicatedOn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expired_on", type="datetime", nullable=true)
     */
    private $expiredOn;

    /**
     * @var string
     *
     * @ORM\Column(name="accumulate_scorm_time", type="integer", nullable=false, options={"default":1})
     */
    private $accumulateScormTime;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->defaultViewMod = 'embedded';
        $this->defaultEncoding = 'UTF-8';
        $this->displayOrder = 0;
        $this->contentLocal = 'local';
        $this->preventReinit = 1;
        $this->useMaxScore = 1;
        $this->createdOn = new \DateTime();
    }

    /**
     * Set lpType
     *
     * @param integer $lpType
     * @return CLp
     */
    public function setLpType($lpType)
    {
        $this->lpType = $lpType;

        return $this;
    }

    /**
     * Get lpType
     *
     * @return integer
     */
    public function getLpType()
    {
        return $this->lpType;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CLp
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
     * Set ref
     *
     * @param string $ref
     * @return CLp
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    /**
     * Get ref
     *
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return CLp
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
     * Set path
     *
     * @param string $path
     * @return CLp
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set forceCommit
     *
     * @param boolean $forceCommit
     * @return CLp
     */
    public function setForceCommit($forceCommit)
    {
        $this->forceCommit = $forceCommit;

        return $this;
    }

    /**
     * Get forceCommit
     *
     * @return boolean
     */
    public function getForceCommit()
    {
        return $this->forceCommit;
    }

    /**
     * Set defaultViewMod
     *
     * @param string $defaultViewMod
     * @return CLp
     */
    public function setDefaultViewMod($defaultViewMod)
    {
        $this->defaultViewMod = $defaultViewMod;

        return $this;
    }

    /**
     * Get defaultViewMod
     *
     * @return string
     */
    public function getDefaultViewMod()
    {
        return $this->defaultViewMod;
    }

    /**
     * Set defaultEncoding
     *
     * @param string $defaultEncoding
     * @return CLp
     */
    public function setDefaultEncoding($defaultEncoding)
    {
        $this->defaultEncoding = $defaultEncoding;

        return $this;
    }

    /**
     * Get defaultEncoding
     *
     * @return string
     */
    public function getDefaultEncoding()
    {
        return $this->defaultEncoding;
    }

    /**
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return CLp
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder
     *
     * @return integer
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set contentMaker
     *
     * @param string $contentMaker
     * @return CLp
     */
    public function setContentMaker($contentMaker)
    {
        $this->contentMaker = $contentMaker;

        return $this;
    }

    /**
     * Get contentMaker
     *
     * @return string
     */
    public function getContentMaker()
    {
        return $this->contentMaker;
    }

    /**
     * Set contentLocal
     *
     * @param string $contentLocal
     * @return CLp
     */
    public function setContentLocal($contentLocal)
    {
        $this->contentLocal = $contentLocal;

        return $this;
    }

    /**
     * Get contentLocal
     *
     * @return string
     */
    public function getContentLocal()
    {
        return $this->contentLocal;
    }

    /**
     * Set contentLicense
     *
     * @param string $contentLicense
     * @return CLp
     */
    public function setContentLicense($contentLicense)
    {
        $this->contentLicense = $contentLicense;

        return $this;
    }

    /**
     * Get contentLicense
     *
     * @return string
     */
    public function getContentLicense()
    {
        return $this->contentLicense;
    }

    /**
     * Set preventReinit
     *
     * @param boolean $preventReinit
     * @return CLp
     */
    public function setPreventReinit($preventReinit)
    {
        $this->preventReinit = $preventReinit;

        return $this;
    }

    /**
     * Get preventReinit
     *
     * @return boolean
     */
    public function getPreventReinit()
    {
        return $this->preventReinit;
    }

    /**
     * Set jsLib
     *
     * @param string $jsLib
     * @return CLp
     */
    public function setJsLib($jsLib)
    {
        $this->jsLib = $jsLib;

        return $this;
    }

    /**
     * Get jsLib
     *
     * @return string
     */
    public function getJsLib()
    {
        return $this->jsLib;
    }

    /**
     * Set debug
     *
     * @param boolean $debug
     * @return CLp
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Get debug
     *
     * @return boolean
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set theme
     *
     * @param string $theme
     * @return CLp
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * Get theme
     *
     * @return string
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * Set previewImage
     *
     * @param string $previewImage
     * @return CLp
     */
    public function setPreviewImage($previewImage)
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * Get previewImage
     *
     * @return string
     */
    public function getPreviewImage()
    {
        return $this->previewImage;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return CLp
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set sessionId
     *
     * @param integer $sessionId
     * @return CLp
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
     * Set prerequisite
     *
     * @param integer $prerequisite
     * @return CLp
     */
    public function setPrerequisite($prerequisite)
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    /**
     * Get prerequisite
     *
     * @return integer
     */
    public function getPrerequisite()
    {
        return $this->prerequisite;
    }

    /**
     * Set hideTocFrame
     *
     * @param boolean $hideTocFrame
     * @return CLp
     */
    public function setHideTocFrame($hideTocFrame)
    {
        $this->hideTocFrame = $hideTocFrame;

        return $this;
    }

    /**
     * Get hideTocFrame
     *
     * @return boolean
     */
    public function getHideTocFrame()
    {
        return $this->hideTocFrame;
    }

    /**
     * Set seriousgameMode
     *
     * @param boolean $seriousgameMode
     * @return CLp
     */
    public function setSeriousgameMode($seriousgameMode)
    {
        $this->seriousgameMode = $seriousgameMode;

        return $this;
    }

    /**
     * Get seriousgameMode
     *
     * @return boolean
     */
    public function getSeriousgameMode()
    {
        return $this->seriousgameMode;
    }

    /**
     * Set useMaxScore
     *
     * @param integer $useMaxScore
     * @return CLp
     */
    public function setUseMaxScore($useMaxScore)
    {
        $this->useMaxScore = $useMaxScore;

        return $this;
    }

    /**
     * Get useMaxScore
     *
     * @return integer
     */
    public function getUseMaxScore()
    {
        return $this->useMaxScore;
    }

    /**
     * Set autolaunch
     *
     * @param integer $autolaunch
     * @return CLp
     */
    public function setAutolaunch($autolaunch)
    {
        $this->autolaunch = $autolaunch;

        return $this;
    }

    /**
     * Get autolaunch
     *
     * @return integer
     */
    public function getAutolaunch()
    {
        return $this->autolaunch;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return CLp
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set modifiedOn
     *
     * @param \DateTime $modifiedOn
     * @return CLp
     */
    public function setModifiedOn($modifiedOn)
    {
        $this->modifiedOn = $modifiedOn;

        return $this;
    }

    /**
     * Get modifiedOn
     *
     * @return \DateTime
     */
    public function getModifiedOn()
    {
        return $this->modifiedOn;
    }

    /**
     * Set publicatedOn
     *
     * @param \DateTime $publicatedOn
     * @return CLp
     */
    public function setPublicatedOn($publicatedOn)
    {
        $this->publicatedOn = $publicatedOn;

        return $this;
    }

    /**
     * Get publicatedOn
     *
     * @return \DateTime
     */
    public function getPublicatedOn()
    {
        return $this->publicatedOn;
    }

    /**
     * Set expiredOn
     *
     * @param \DateTime $expiredOn
     * @return CLp
     */
    public function setExpiredOn($expiredOn)
    {
        $this->expiredOn = $expiredOn;

        return $this;
    }

    /**
     * Get expiredOn
     *
     * @return \DateTime
     */
    public function getExpiredOn()
    {
        return $this->expiredOn;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return CLp
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set cId
     *
     * @param integer $cId
     * @return CLp
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

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param int $categoryId
     * @return CLp
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccumulateScormTime()
    {
        return $this->accumulateScormTime;
    }

    /**
     * @param string $accumulateScormTime
     * @return CLp
     */
    public function setAccumulateScormTime($accumulateScormTime)
    {
        $this->accumulateScormTime = $accumulateScormTime;

        return $this;
    }

    /**
     * Get iid
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    /**
     * Get subscribeUsers
     * @return int
     */
    public function getSubscribeUsers()
    {
        return $this->subscribeUsers;
    }
}
