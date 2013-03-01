<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;

use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PreUpdate;


/**
 * EntityCLp
 *
 * @Table(name="c_lp")
 * @Entity(repositoryClass="Entity\Repository\LpRepository")
 */
class EntityCLp
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)

     */
    private $cId;


    /**
     * @var integer
     *
     * @Column(name="lp_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpType;

    /**
     * @var string
     *
     * @Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="ref", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $ref;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="path", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $path;

    /**
     * @var boolean
     *
     * @Column(name="force_commit", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $forceCommit;

    /**
     * @var string
     *
     * @Column(name="default_view_mod", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultViewMod;

    /**
     * @var string
     *
     * @Column(name="default_encoding", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $defaultEncoding;

    /**
     * @var integer
     *
     * @Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;

    /**
     * @var string
     *
     * @Column(name="content_maker", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $contentMaker;

    /**
     * @var string
     *
     * @Column(name="content_local", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $contentLocal;

    /**
     * @var string
     *
     * @Column(name="content_license", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $contentLicense;

    /**
     * @var boolean
     *
     * @Column(name="prevent_reinit", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $preventReinit;

    /**
     * @var string
     *
     * @Column(name="js_lib", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $jsLib;

    /**
     * @var boolean
     *
     * @Column(name="debug", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $debug;

    /**
     * @var string
     *
     * @Column(name="theme", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $theme;

    /**
     * @var string
     *
     * @Column(name="preview_image", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $previewImage;

    /**
     * @var string
     *
     * @Column(name="author", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $author;

    /**
     * @var integer
     *
     * @Column(name="session_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionId;

    /**
     * @var integer
     *
     * @Column(name="prerequisite", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $prerequisite;

    /**
     * @var boolean
     *
     * @Column(name="hide_toc_frame", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $hideTocFrame;

    /**
     * @var boolean
     *
     * @Column(name="seriousgame_mode", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $seriousgameMode;

    /**
     * @var integer
     *
     * @Column(name="use_max_score", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $useMaxScore;

    /**
     * @var integer
     *
     * @Column(name="autolunch", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $autolunch;

    /**
     * @var \DateTime
     *
     * @Column(name="created_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdOn;

    /**
     * @var \DateTime
     *
     * @Column(name="modified_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $modifiedOn;

    /**
     * @var \DateTime
     *
     * @Column(name="publicated_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $publicatedOn;

    /**
     * @var \DateTime
     *
     * @Column(name="expired_on", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiredOn;

    public function __construct() {

    }

    public function getLps() {
        /*$query = $this->em->createQuery('SELECT u FROM Entity\Entry e WHERE :region_id MEMBER OF e.regions');
        $query->setParameter('region_id',  1);
        return $query->getResult();*/
    }




    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCLp
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
     * Set id
     *
     * @param integer $id
     * @return EntityCLp
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
     * Set lpType
     *
     * @param integer $lpType
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * Set autolunch
     *
     * @param integer $autolunch
     * @return EntityCLp
     */
    public function setAutolunch($autolunch)
    {
        $this->autolunch = $autolunch;

        return $this;
    }

    /**
     * Get autolunch
     *
     * @return integer
     */
    public function getAutolunch()
    {
        return $this->autolunch;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
     * @return EntityCLp
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
}
