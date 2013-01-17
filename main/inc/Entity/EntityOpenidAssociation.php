<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * EntityOpenidAssociation
 *
 * @Table(name="openid_association")
 * @Entity
 */
class EntityOpenidAssociation
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
     * @Column(name="idp_endpoint_uri", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $idpEndpointUri;

    /**
     * @var string
     *
     * @Column(name="session_type", type="string", length=30, precision=0, scale=0, nullable=false, unique=false)
     */
    private $sessionType;

    /**
     * @var string
     *
     * @Column(name="assoc_handle", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $assocHandle;

    /**
     * @var string
     *
     * @Column(name="assoc_type", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $assocType;

    /**
     * @var integer
     *
     * @Column(name="expires_in", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $expiresIn;

    /**
     * @var string
     *
     * @Column(name="mac_key", type="text", precision=0, scale=0, nullable=false, unique=false)
     */
    private $macKey;

    /**
     * @var integer
     *
     * @Column(name="created", type="bigint", precision=0, scale=0, nullable=false, unique=false)
     */
    private $created;


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
     * Set idpEndpointUri
     *
     * @param string $idpEndpointUri
     * @return EntityOpenidAssociation
     */
    public function setIdpEndpointUri($idpEndpointUri)
    {
        $this->idpEndpointUri = $idpEndpointUri;

        return $this;
    }

    /**
     * Get idpEndpointUri
     *
     * @return string 
     */
    public function getIdpEndpointUri()
    {
        return $this->idpEndpointUri;
    }

    /**
     * Set sessionType
     *
     * @param string $sessionType
     * @return EntityOpenidAssociation
     */
    public function setSessionType($sessionType)
    {
        $this->sessionType = $sessionType;

        return $this;
    }

    /**
     * Get sessionType
     *
     * @return string 
     */
    public function getSessionType()
    {
        return $this->sessionType;
    }

    /**
     * Set assocHandle
     *
     * @param string $assocHandle
     * @return EntityOpenidAssociation
     */
    public function setAssocHandle($assocHandle)
    {
        $this->assocHandle = $assocHandle;

        return $this;
    }

    /**
     * Get assocHandle
     *
     * @return string 
     */
    public function getAssocHandle()
    {
        return $this->assocHandle;
    }

    /**
     * Set assocType
     *
     * @param string $assocType
     * @return EntityOpenidAssociation
     */
    public function setAssocType($assocType)
    {
        $this->assocType = $assocType;

        return $this;
    }

    /**
     * Get assocType
     *
     * @return string 
     */
    public function getAssocType()
    {
        return $this->assocType;
    }

    /**
     * Set expiresIn
     *
     * @param integer $expiresIn
     * @return EntityOpenidAssociation
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }

    /**
     * Get expiresIn
     *
     * @return integer 
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * Set macKey
     *
     * @param string $macKey
     * @return EntityOpenidAssociation
     */
    public function setMacKey($macKey)
    {
        $this->macKey = $macKey;

        return $this;
    }

    /**
     * Get macKey
     *
     * @return string 
     */
    public function getMacKey()
    {
        return $this->macKey;
    }

    /**
     * Set created
     *
     * @param integer $created
     * @return EntityOpenidAssociation
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer 
     */
    public function getCreated()
    {
        return $this->created;
    }
}
