<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradebookEvaluationType
 *
 * @Table(name="gradebook_evaluation_type")
 * @Entity
 */
class EntityGradebookEvaluationType
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
     * @var string
     *
     * @Column(name="name", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var integer
     *
     * @Column(name="external_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $externalId;


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
     * Set name
     *
     * @param string $name
     * @return EntityGradebookEvaluationType
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
     * Set externalId
     *
     * @param integer $externalId
     * @return EntityGradebookEvaluationType
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Get externalId
     *
     * @return integer 
     */
    public function getExternalId()
    {
        return $this->externalId;
    }
}
