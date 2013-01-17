<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCResource
 *
 * @Table(name="c_resource")
 * @Entity
 */
class EntityCResource
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
     * @var string
     *
     * @Column(name="source_type", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $sourceType;

    /**
     * @var integer
     *
     * @Column(name="source_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $sourceId;

    /**
     * @var string
     *
     * @Column(name="resource_type", type="string", length=50, precision=0, scale=0, nullable=true, unique=false)
     */
    private $resourceType;

    /**
     * @var integer
     *
     * @Column(name="resource_id", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    private $resourceId;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCResource
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
     * @return EntityCResource
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
     * Set sourceType
     *
     * @param string $sourceType
     * @return EntityCResource
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return string 
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     * @return EntityCResource
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer 
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * Set resourceType
     *
     * @param string $resourceType
     * @return EntityCResource
     */
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    /**
     * Get resourceType
     *
     * @return string 
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Set resourceId
     *
     * @param integer $resourceId
     * @return EntityCResource
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * Get resourceId
     *
     * @return integer 
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }
}
