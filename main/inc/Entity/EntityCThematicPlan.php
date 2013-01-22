<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCThematicPlan
 *
 * @Table(name="c_thematic_plan")
 * @Entity
 */
class EntityCThematicPlan
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
     * @Column(name="thematic_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $thematicId;

    /**
     * @var string
     *
     * @Column(name="title", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $title;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @Column(name="description_type", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $descriptionType;


    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCThematicPlan
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
     * @return EntityCThematicPlan
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
     * Set thematicId
     *
     * @param integer $thematicId
     * @return EntityCThematicPlan
     */
    public function setThematicId($thematicId)
    {
        $this->thematicId = $thematicId;

        return $this;
    }

    /**
     * Get thematicId
     *
     * @return integer 
     */
    public function getThematicId()
    {
        return $this->thematicId;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return EntityCThematicPlan
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
     * @return EntityCThematicPlan
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
     * Set descriptionType
     *
     * @param integer $descriptionType
     * @return EntityCThematicPlan
     */
    public function setDescriptionType($descriptionType)
    {
        $this->descriptionType = $descriptionType;

        return $this;
    }

    /**
     * Get descriptionType
     *
     * @return integer 
     */
    public function getDescriptionType()
    {
        return $this->descriptionType;
    }
}
