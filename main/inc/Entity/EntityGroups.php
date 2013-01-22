<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityGroups
 *
 * @Table(name="groups")
 * @Entity
 */
class EntityGroups
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
     * @Column(name="description", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $description;

    /**
     * @var string
     *
     * @Column(name="picture_uri", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $pictureUri;

    /**
     * @var string
     *
     * @Column(name="url", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $url;

    /**
     * @var integer
     *
     * @Column(name="visibility", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var string
     *
     * @Column(name="updated_on", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $updatedOn;

    /**
     * @var string
     *
     * @Column(name="created_on", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $createdOn;


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
     * @return EntityGroups
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
     * @return EntityGroups
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
     * Set pictureUri
     *
     * @param string $pictureUri
     * @return EntityGroups
     */
    public function setPictureUri($pictureUri)
    {
        $this->pictureUri = $pictureUri;

        return $this;
    }

    /**
     * Get pictureUri
     *
     * @return string 
     */
    public function getPictureUri()
    {
        return $this->pictureUri;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return EntityGroups
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set visibility
     *
     * @param integer $visibility
     * @return EntityGroups
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return integer 
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set updatedOn
     *
     * @param string $updatedOn
     * @return EntityGroups
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Get updatedOn
     *
     * @return string 
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set createdOn
     *
     * @param string $createdOn
     * @return EntityGroups
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get createdOn
     *
     * @return string 
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }
}
