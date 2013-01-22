<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCLpItem
 *
 * @Table(name="c_lp_item")
 * @Entity
 */
class EntityCLpItem
{
    /**
     * @var integer
     *
     * @Column(name="c_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="lp_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $lpId;

    /**
     * @var string
     *
     * @Column(name="item_type", type="string", length=32, precision=0, scale=0, nullable=false, unique=false)
     */
    private $itemType;

    /**
     * @var string
     *
     * @Column(name="ref", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $ref;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=511, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="string", length=511, precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="path", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $path;

    /**
     * @var float
     *
     * @Column(name="min_score", type="float", precision=0, scale=0, nullable=false, unique=false)
     */
    private $minScore;

    /**
     * @var float
     *
     * @Column(name="max_score", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxScore;

    /**
     * @var float
     *
     * @Column(name="mastery_score", type="float", precision=0, scale=0, nullable=true, unique=false)
     */
    private $masteryScore;

    /**
     * @var integer
     *
     * @Column(name="parent_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $parentItemId;

    /**
     * @var integer
     *
     * @Column(name="previous_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $previousItemId;

    /**
     * @var integer
     *
     * @Column(name="next_item_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $nextItemId;

    /**
     * @var integer
     *
     * @Column(name="display_order", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $displayOrder;

    /**
     * @var string
     *
     * @Column(name="prerequisite", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $prerequisite;

    /**
     * @var string
     *
     * @Column(name="parameters", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $parameters;

    /**
     * @var string
     *
     * @Column(name="launch_data", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $launchData;

    /**
     * @var string
     *
     * @Column(name="max_time_allowed", type="string", length=13, precision=0, scale=0, nullable=true, unique=false)
     */
    private $maxTimeAllowed;

    /**
     * @var string
     *
     * @Column(name="terms", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $terms;

    /**
     * @var integer
     *
     * @Column(name="search_did", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $searchDid;

    /**
     * @var string
     *
     * @Column(name="audio", type="string", length=250, precision=0, scale=0, nullable=true, unique=false)
     */
    private $audio;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCLpItem
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
     * @return EntityCLpItem
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
     * Set lpId
     *
     * @param integer $lpId
     * @return EntityCLpItem
     */
    public function setLpId($lpId)
    {
        $this->lpId = $lpId;

        return $this;
    }

    /**
     * Get lpId
     *
     * @return integer 
     */
    public function getLpId()
    {
        return $this->lpId;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     * @return EntityCLpItem
     */
    public function setItemType($itemType)
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return string 
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Set ref
     *
     * @param string $ref
     * @return EntityCLpItem
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
     * Set title
     *
     * @param string $title
     * @return EntityCLpItem
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntityCLpItem
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
     * @return EntityCLpItem
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
     * Set minScore
     *
     * @param float $minScore
     * @return EntityCLpItem
     */
    public function setMinScore($minScore)
    {
        $this->minScore = $minScore;

        return $this;
    }

    /**
     * Get minScore
     *
     * @return float 
     */
    public function getMinScore()
    {
        return $this->minScore;
    }

    /**
     * Set maxScore
     *
     * @param float $maxScore
     * @return EntityCLpItem
     */
    public function setMaxScore($maxScore)
    {
        $this->maxScore = $maxScore;

        return $this;
    }

    /**
     * Get maxScore
     *
     * @return float 
     */
    public function getMaxScore()
    {
        return $this->maxScore;
    }

    /**
     * Set masteryScore
     *
     * @param float $masteryScore
     * @return EntityCLpItem
     */
    public function setMasteryScore($masteryScore)
    {
        $this->masteryScore = $masteryScore;

        return $this;
    }

    /**
     * Get masteryScore
     *
     * @return float 
     */
    public function getMasteryScore()
    {
        return $this->masteryScore;
    }

    /**
     * Set parentItemId
     *
     * @param integer $parentItemId
     * @return EntityCLpItem
     */
    public function setParentItemId($parentItemId)
    {
        $this->parentItemId = $parentItemId;

        return $this;
    }

    /**
     * Get parentItemId
     *
     * @return integer 
     */
    public function getParentItemId()
    {
        return $this->parentItemId;
    }

    /**
     * Set previousItemId
     *
     * @param integer $previousItemId
     * @return EntityCLpItem
     */
    public function setPreviousItemId($previousItemId)
    {
        $this->previousItemId = $previousItemId;

        return $this;
    }

    /**
     * Get previousItemId
     *
     * @return integer 
     */
    public function getPreviousItemId()
    {
        return $this->previousItemId;
    }

    /**
     * Set nextItemId
     *
     * @param integer $nextItemId
     * @return EntityCLpItem
     */
    public function setNextItemId($nextItemId)
    {
        $this->nextItemId = $nextItemId;

        return $this;
    }

    /**
     * Get nextItemId
     *
     * @return integer 
     */
    public function getNextItemId()
    {
        return $this->nextItemId;
    }

    /**
     * Set displayOrder
     *
     * @param integer $displayOrder
     * @return EntityCLpItem
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
     * Set prerequisite
     *
     * @param string $prerequisite
     * @return EntityCLpItem
     */
    public function setPrerequisite($prerequisite)
    {
        $this->prerequisite = $prerequisite;

        return $this;
    }

    /**
     * Get prerequisite
     *
     * @return string 
     */
    public function getPrerequisite()
    {
        return $this->prerequisite;
    }

    /**
     * Set parameters
     *
     * @param string $parameters
     * @return EntityCLpItem
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return string 
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Set launchData
     *
     * @param string $launchData
     * @return EntityCLpItem
     */
    public function setLaunchData($launchData)
    {
        $this->launchData = $launchData;

        return $this;
    }

    /**
     * Get launchData
     *
     * @return string 
     */
    public function getLaunchData()
    {
        return $this->launchData;
    }

    /**
     * Set maxTimeAllowed
     *
     * @param string $maxTimeAllowed
     * @return EntityCLpItem
     */
    public function setMaxTimeAllowed($maxTimeAllowed)
    {
        $this->maxTimeAllowed = $maxTimeAllowed;

        return $this;
    }

    /**
     * Get maxTimeAllowed
     *
     * @return string 
     */
    public function getMaxTimeAllowed()
    {
        return $this->maxTimeAllowed;
    }

    /**
     * Set terms
     *
     * @param string $terms
     * @return EntityCLpItem
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;

        return $this;
    }

    /**
     * Get terms
     *
     * @return string 
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * Set searchDid
     *
     * @param integer $searchDid
     * @return EntityCLpItem
     */
    public function setSearchDid($searchDid)
    {
        $this->searchDid = $searchDid;

        return $this;
    }

    /**
     * Get searchDid
     *
     * @return integer 
     */
    public function getSearchDid()
    {
        return $this->searchDid;
    }

    /**
     * Set audio
     *
     * @param string $audio
     * @return EntityCLpItem
     */
    public function setAudio($audio)
    {
        $this->audio = $audio;

        return $this;
    }

    /**
     * Get audio
     *
     * @return string 
     */
    public function getAudio()
    {
        return $this->audio;
    }
}
