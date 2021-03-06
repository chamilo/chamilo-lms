<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * BranchSync.
 *
 * @ORM\Table(name="branch_sync")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\BranchSyncRepository")
 * @Gedmo\Tree(type="nested")
 */
class BranchSync
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="AccessUrl", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected AccessUrl $url;

    /**
     * @ORM\Column(name="unique_id", type="string", length=50, nullable=false, unique=true)
     */
    protected string $uniqueId;

    /**
     * @ORM\Column(name="branch_name", type="string", length=250)
     */
    protected string $branchName;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="branch_ip", type="string", length=40, nullable=true, unique=false)
     */
    protected ?string $branchIp = null;

    /**
     * @ORM\Column(name="latitude", type="decimal", nullable=true, unique=false)
     */
    protected ?float $latitude = null;

    /**
     * @ORM\Column(name="longitude", type="decimal", nullable=true, unique=false)
     */
    protected ?float $longitude = null;

    /**
     * @ORM\Column(name="dwn_speed", type="integer", nullable=true, unique=false)
     */
    protected ?int $dwnSpeed = null;

    /**
     * @ORM\Column(name="up_speed", type="integer", nullable=true, unique=false)
     */
    protected ?int $upSpeed = null;

    /**
     * @ORM\Column(name="delay", type="integer", nullable=true, unique=false)
     */
    protected ?int $delay = null;

    /**
     * @ORM\Column(name="admin_mail", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $adminMail = null;

    /**
     * @ORM\Column(name="admin_name", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $adminName = null;

    /**
     * @ORM\Column(name="admin_phone", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $adminPhone = null;

    /**
     * @ORM\Column(name="last_sync_trans_id", type="bigint", nullable=true, unique=false)
     */
    protected ?int $lastSyncTransId = null;

    /**
     * @ORM\Column(name="last_sync_trans_date", type="datetime", nullable=true, unique=false)
     */
    protected ?DateTime $lastSyncTransDate = null;

    /**
     * @ORM\Column(name="last_sync_type", type="string", length=20, nullable=true, unique=false)
     */
    protected ?string $lastSyncType = null;

    /**
     * @ORM\Column(name="ssl_pub_key", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $sslPubKey;

    /**
     * @ORM\Column(name="branch_type", type="string", length=250, nullable=true, unique=false)
     */
    protected ?string $branchType = null;

    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", nullable=true, unique=false)
     */
    protected ?int $lft = null;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", nullable=true, unique=false)
     */
    protected ?int $rgt = null;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer", nullable=true, unique=false)
     */
    protected ?int $lvl = null;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true, unique=false)
     */
    protected ?int $root = null;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="BranchSync", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected ?BranchSync $parent = null;

    /**
     * @ORM\OneToMany(targetEntity="BranchSync", mappedBy="parent")
     * @ORM\OrderBy({"lft"="ASC"})
     *
     * @var BranchSync[]|Collection
     */
    protected Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->uniqueId = sha1(uniqid());
        $this->sslPubKey = sha1(uniqid());
        // $this->lastSyncTransDate = new \DateTime();
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
     * Set branchName.
     *
     * @return BranchSync
     */
    public function setBranchName(string $branchName)
    {
        $this->branchName = $branchName;

        return $this;
    }

    /**
     * Get branchName.
     *
     * @return string
     */
    public function getBranchName()
    {
        return $this->branchName;
    }

    /**
     * Set branchIp.
     *
     * @return BranchSync
     */
    public function setBranchIp(string $branchIp)
    {
        $this->branchIp = $branchIp;

        return $this;
    }

    /**
     * Get branchIp.
     *
     * @return string
     */
    public function getBranchIp()
    {
        return $this->branchIp;
    }

    /**
     * Set latitude.
     *
     * @return BranchSync
     */
    public function setLatitude(float $latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude.
     *
     * @return BranchSync
     */
    public function setLongitude(float $longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set dwnSpeed.
     *
     * @return BranchSync
     */
    public function setDwnSpeed(int $dwnSpeed)
    {
        $this->dwnSpeed = $dwnSpeed;

        return $this;
    }

    /**
     * Get dwnSpeed.
     *
     * @return int
     */
    public function getDwnSpeed()
    {
        return $this->dwnSpeed;
    }

    /**
     * Set upSpeed.
     *
     * @return BranchSync
     */
    public function setUpSpeed(int $upSpeed)
    {
        $this->upSpeed = $upSpeed;

        return $this;
    }

    /**
     * Get upSpeed.
     *
     * @return int
     */
    public function getUpSpeed()
    {
        return $this->upSpeed;
    }

    /**
     * Set delay.
     *
     * @return BranchSync
     */
    public function setDelay(int $delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Get delay.
     *
     * @return int
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set adminMail.
     *
     * @return BranchSync
     */
    public function setAdminMail(string $adminMail)
    {
        $this->adminMail = $adminMail;

        return $this;
    }

    /**
     * Get adminMail.
     *
     * @return string
     */
    public function getAdminMail()
    {
        return $this->adminMail;
    }

    /**
     * Set adminName.
     *
     * @return BranchSync
     */
    public function setAdminName(string $adminName)
    {
        $this->adminName = $adminName;

        return $this;
    }

    /**
     * Get adminName.
     *
     * @return string
     */
    public function getAdminName()
    {
        return $this->adminName;
    }

    /**
     * Set adminPhone.
     *
     * @return BranchSync
     */
    public function setAdminPhone(string $adminPhone)
    {
        $this->adminPhone = $adminPhone;

        return $this;
    }

    /**
     * Get adminPhone.
     *
     * @return string
     */
    public function getAdminPhone()
    {
        return $this->adminPhone;
    }

    /**
     * Set lastSyncTransId.
     *
     * @return BranchSync
     */
    public function setLastSyncTransId(int $lastSyncTransId)
    {
        $this->lastSyncTransId = $lastSyncTransId;

        return $this;
    }

    /**
     * Get lastSyncTransId.
     *
     * @return int
     */
    public function getLastSyncTransId()
    {
        return $this->lastSyncTransId;
    }

    /**
     * Set lastSyncTransDate.
     *
     * @return BranchSync
     */
    public function setLastSyncTransDate(DateTime $lastSyncTransDate)
    {
        $this->lastSyncTransDate = $lastSyncTransDate;

        return $this;
    }

    /**
     * Set sslPubKey.
     *
     * @return BranchSync
     */
    public function setSslPubKey(string $sslPubKey)
    {
        $this->sslPubKey = $sslPubKey;

        return $this;
    }

    /**
     * Get sslPubKey.
     *
     * @return string
     */
    public function getSslPubKey()
    {
        return $this->sslPubKey;
    }

    /**
     * Set sslPubKey.
     *
     * @return BranchSync
     */
    public function setBranchType(string $branchType)
    {
        $this->branchType = $branchType;

        return $this;
    }

    /**
     * Get sslPubKey.
     *
     * @return string
     */
    public function getBranchType()
    {
        return $this->branchType;
    }

    /**
     * Get lastSyncTransDate.
     *
     * @return DateTime
     */
    public function getLastSyncTransDate()
    {
        return $this->lastSyncTransDate;
    }

    /**
     * Set lastSyncType.
     *
     * @return BranchSync
     */
    public function setLastSyncType(string $lastSyncType)
    {
        $this->lastSyncType = $lastSyncType;

        return $this;
    }

    /**
     * Get lastSyncType.
     *
     * @return string
     */
    public function getLastSyncType()
    {
        return $this->lastSyncType;
    }

    /**
     * Set lft.
     *
     * @return BranchSync
     */
    public function setLft(int $lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @return BranchSync
     */
    public function setRgt(int $rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set lvl.
     *
     * @return BranchSync
     */
    public function setLvl(int $lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * Get lvl.
     *
     * @return int
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     * Set root.
     *
     * @return BranchSync
     */
    public function setRoot(int $root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return int
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param BranchSync $parent
     */
    public function setParent(self $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

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
     * @return $this
     */
    public function setUniqueId(string $uniqueId)
    {
        $this->uniqueId = $uniqueId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return BranchSync[]|Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}
