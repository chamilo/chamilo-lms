<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySessionCategory
 *
 * @Table(name="session_category")
 * @Entity
 */
class EntitySessionCategory
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
     * @Column(name="name", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @Column(name="date_start", type="date", precision=0, scale=0, nullable=true, unique=false)
     */
    private $dateStart;

    /**
     * @var \DateTime
     *
     * @Column(name="date_end", type="date", precision=0, scale=0, nullable=true, unique=false)
     */
    private $dateEnd;

    /**
     * @var integer
     *
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;


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
     * @return EntitySessionCategory
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
     * Set dateStart
     *
     * @param \DateTime $dateStart
     * @return EntitySessionCategory
     */
    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    /**
     * Get dateStart
     *
     * @return \DateTime 
     */
    public function getDateStart()
    {
        return $this->dateStart;
    }

    /**
     * Set dateEnd
     *
     * @param \DateTime $dateEnd
     * @return EntitySessionCategory
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    /**
     * Get dateEnd
     *
     * @return \DateTime 
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntitySessionCategory
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
}
