<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGradeModel
 *
 * @Table(name="grade_model")
 * @Entity
 */
class EntityGradeModel
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
     * @Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $description;

    /**
     * @var boolean
     *
     * @Column(name="default_lowest_eval_exclude", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultLowestEvalExclude;

    /**
     * @var boolean
     *
     * @Column(name="default_external_eval", type="boolean", precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultExternalEval;

    /**
     * @var string
     *
     * @Column(name="default_external_eval_prefix", type="string", length=140, precision=0, scale=0, nullable=true, unique=false)
     */
    private $defaultExternalEvalPrefix;


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
     * @return EntityGradeModel
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
     * Set description
     *
     * @param string $description
     * @return EntityGradeModel
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
     * Set defaultLowestEvalExclude
     *
     * @param boolean $defaultLowestEvalExclude
     * @return EntityGradeModel
     */
    public function setDefaultLowestEvalExclude($defaultLowestEvalExclude)
    {
        $this->defaultLowestEvalExclude = $defaultLowestEvalExclude;

        return $this;
    }

    /**
     * Get defaultLowestEvalExclude
     *
     * @return boolean 
     */
    public function getDefaultLowestEvalExclude()
    {
        return $this->defaultLowestEvalExclude;
    }

    /**
     * Set defaultExternalEval
     *
     * @param boolean $defaultExternalEval
     * @return EntityGradeModel
     */
    public function setDefaultExternalEval($defaultExternalEval)
    {
        $this->defaultExternalEval = $defaultExternalEval;

        return $this;
    }

    /**
     * Get defaultExternalEval
     *
     * @return boolean 
     */
    public function getDefaultExternalEval()
    {
        return $this->defaultExternalEval;
    }

    /**
     * Set defaultExternalEvalPrefix
     *
     * @param string $defaultExternalEvalPrefix
     * @return EntityGradeModel
     */
    public function setDefaultExternalEvalPrefix($defaultExternalEvalPrefix)
    {
        $this->defaultExternalEvalPrefix = $defaultExternalEvalPrefix;

        return $this;
    }

    /**
     * Get defaultExternalEvalPrefix
     *
     * @return string 
     */
    public function getDefaultExternalEvalPrefix()
    {
        return $this->defaultExternalEvalPrefix;
    }
}
