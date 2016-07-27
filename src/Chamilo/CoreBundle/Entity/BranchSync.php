<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * BranchSync
 *
 * @ORM\Table(name="branch_sync")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\BranchSyncRepository")
 * @Gedmo\Tree(type="nested")
 */
class BranchSync
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", nullable=false, unique=false)
     */
    private $accessUrlId;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_id", type="string", length=50, nullable=false, unique=true)
     */
    private $uniqueId;

    /**
     * @var string
     *
     * @ORM\Column(name="branch_name", type="string", length=250, nullable=false, unique=false)
     */
    private $branchName;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="branch_ip", type="string", length=40, nullable=true, unique=false)
     */
    private $branchIp;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="decimal", nullable=true, unique=false)
     */
    private $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="decimal", nullable=true, unique=false)
     */
    private $longitude;

    /**
     * @var integer
     *
     * @ORM\Column(name="dwn_speed", type="integer", nullable=true, unique=false)
     */
    private $dwnSpeed;

    /**
     * @var integer
     *
     * @ORM\Column(name="up_speed", type="integer", nullable=true, unique=false)
     */
    private $upSpeed;

    /**
     * @var integer
     *
     * @ORM\Column(name="delay", type="integer", nullable=true, unique=false)
     */
    private $delay;

    /**
     * @var string
     *
     * @ORM\Column(name="admin_mail", type="string", length=250, nullable=true, unique=false)
     */
    private $adminMail;

    /**
     * @var string
     *
     * @ORM\Column(name="admin_name", type="string", length=250, nullable=true, unique=false)
     */
    private $adminName;

    /**
     * @var string
     *
     * @ORM\Column(name="admin_phone", type="string", length=250, nullable=true, unique=false)
     */
    private $adminPhone;

    /**
     * @var integer
     *
     * @ORM\Column(name="last_sync_trans_id", type="bigint", nullable=true, unique=false)
     */
    private $lastSyncTransId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_sync_trans_date", type="datetime", nullable=true, unique=false)
     */
    private $lastSyncTransDate;

    /**
     * @var string
     *
     * @ORM\Column(name="last_sync_type", type="string", length=20, nullable=true, unique=false)
     */
    private $lastSyncType;

     /**
     * @var string
     *
     * @ORM\Column(name="ssl_pub_key", type="string", length=250, nullable=true, unique=false)
     */
    private $sslPubKey;

    /**
     * @var string
     *
     * @ORM\Column(name="branch_type", type="string", length=250, nullable=true, unique=false)
     */
    private $branchType;

    /**
     * @var integer
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true, unique=false)
     */
    private $lft;

    /**
     * @var integer
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true, unique=false)
     */
    private $rgt;

    /**
     * @var integer
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true, unique=false)
     */
    private $lvl;

    /**
     * @var integer
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true, unique=false)
     */
    private $root;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer", nullable=true, unique=false)
     */
    private $parentId;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="BranchSync", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="BranchSync", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->uniqueId = sha1(uniqid());
        $this->sslPubKey = sha1(uniqid());
        // $this->lastSyncTransDate = new \DateTime();
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
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return BranchSync
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
     * Set branchName
     *
     * @param string $branchName
     * @return BranchSync
     */
    public function setBranchName($branchName)
    {
        $this->branchName = $branchName;

        return $this;
    }

    /**
     * Get branchName
     *
     * @return string
     */
    public function getBranchName()
    {
        return $this->branchName;
    }

    /**
     * Set branchIp
     *
     * @param string $branchIp
     * @return BranchSync
     */
    public function setBranchIp($branchIp)
    {
        $this->branchIp = $branchIp;

        return $this;
    }

    /**
     * Get branchIp
     *
     * @return string
     */
    public function getBranchIp()
    {
        return $this->branchIp;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     * @return BranchSync
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     * @return BranchSync
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set dwnSpeed
     *
     * @param integer $dwnSpeed
     * @return BranchSync
     */
    public function setDwnSpeed($dwnSpeed)
    {
        $this->dwnSpeed = $dwnSpeed;

        return $this;
    }

    /**
     * Get dwnSpeed
     *
     * @return integer
     */
    public function getDwnSpeed()
    {
        return $this->dwnSpeed;
    }

    /**
     * Set upSpeed
     *
     * @param integer $upSpeed
     * @return BranchSync
     */
    public function setUpSpeed($upSpeed)
    {
        $this->upSpeed = $upSpeed;

        return $this;
    }

    /**
     * Get upSpeed
     *
     * @return integer
     */
    public function getUpSpeed()
    {
        return $this->upSpeed;
    }

    /**
     * Set delay
     *
     * @param integer $delay
     * @return BranchSync
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Get delay
     *
     * @return integer
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set adminMail
     *
     * @param string $adminMail
     * @return BranchSync
     */
    public function setAdminMail($adminMail)
    {
        $this->adminMail = $adminMail;

        return $this;
    }

    /**
     * Get adminMail
     *
     * @return string
     */
    public function getAdminMail()
    {
        return $this->adminMail;
    }

    /**
     * Set adminName
     *
     * @param string $adminName
     * @return BranchSync
     */
    public function setAdminName($adminName)
    {
        $this->adminName = $adminName;

        return $this;
    }

    /**
     * Get adminName
     *
     * @return string
     */
    public function getAdminName()
    {
        return $this->adminName;
    }

    /**
     * Set adminPhone
     *
     * @param string $adminPhone
     * @return BranchSync
     */
    public function setAdminPhone($adminPhone)
    {
        $this->adminPhone = $adminPhone;

        return $this;
    }

    /**
     * Get adminPhone
     *
     * @return string
     */
    public function getAdminPhone()
    {
        return $this->adminPhone;
    }

    /**
     * Set lastSyncTransId
     *
     * @param integer $lastSyncTransId
     * @return BranchSync
     */
    public function setLastSyncTransId($lastSyncTransId)
    {
        $this->lastSyncTransId = $lastSyncTransId;

        return $this;
    }

    /**
     * Get lastSyncTransId
     *
     * @return integer
     */
    public function getLastSyncTransId()
    {
        return $this->lastSyncTransId;
    }

    /**
     * Set lastSyncTransDate
     *
     * @param \DateTime $lastSyncTransDate
     * @return BranchSync
     */
    public function setLastSyncTransDate($lastSyncTransDate)
    {
        $this->lastSyncTransDate = $lastSyncTransDate;

        return $this;
    }

    /**
     * Set sslPubKey
     *
     * @param string $sslPubKey
     * @return BranchSync
     */
    public function setSslPubKey($sslPubKey)
    {
        $this->sslPubKey = $sslPubKey;

        return $this;
    }

    /**
     * Get sslPubKey
     *
     * @return string
     */
    public function getSslPubKey()
    {
        return $this->sslPubKey;
    }

     /**
     * Set sslPubKey
     *
     * @param string $sslPubKey
     * @return BranchSync
     */
    public function setBranchType($branchType)
    {
        $this->branchType = $branchType;

        return $this;
    }

    /**
     * Get sslPubKey
     *
     * @return string
     */
    public function getBranchType()
    {
        return $this->branchType;
    }

    /**
     * Get lastSyncTransDate
     *
     * @return \DateTime
     */
    public function getLastSyncTransDate()
    {
        return $this->lastSyncTransDate;
    }

    /**
     * Set lastSyncType
     *
     * @param string $lastSyncType
     * @return BranchSync
     */
    public function setLastSyncType($lastSyncType)
    {
        $this->lastSyncType = $lastSyncType;

        return $this;
    }

    /**
     * Get lastSyncType
     *
     * @return string
     */
    public function getLastSyncType()
    {
        return $this->lastSyncType;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return BranchSync
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft
     *
     * @return integer
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return BranchSync
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set lvl
     *
     * @param integer $lvl
     * @return BranchSync
     */
    public function setLvl($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl
     *
     * @return integer
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set root
     *
     * @param integer $root
     * @return BranchSync
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root
     *
     * @return integer
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return BranchSync
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param BranchSync $parent
     *
     * @return $this
     */
    public function setParent(BranchSync $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     *
     * @return $this
     */
    public function setUniqueId($uniqueId)
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }
}
