<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntitySkill
 *
 * @Table(name="skill")
 * @Entity
 */
class EntitySkill
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
     * @Column(name="short_code", type="string", length=100, precision=0, scale=0, nullable=false, unique=false)
     */
    private $shortCode;

    /**
     * @var string
     *
     * @Column(name="description", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;

    /**
     * @var string
     *
     * @Column(name="icon", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $icon;


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
     * @return EntitySkill
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
     * Set shortCode
     *
     * @param string $shortCode
     * @return EntitySkill
     */
    public function setShortCode($shortCode)
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * Get shortCode
     *
     * @return string 
     */
    public function getShortCode()
    {
        return $this->shortCode;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return EntitySkill
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
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return EntitySkill
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
     * Set icon
     *
     * @param string $icon
     * @return EntitySkill
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string 
     */
    public function getIcon()
    {
        return $this->icon;
    }
}
