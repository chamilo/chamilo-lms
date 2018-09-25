<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpenidAssociation.
 *
 * @ORM\Table(name="openid_association")
 * @ORM\Entity
 */
class OpenidAssociation
{
    /**
     * @var string
     *
     * @ORM\Column(name="idp_endpoint_uri", type="text", nullable=false)
     */
    protected $idpEndpointUri;

    /**
     * @var string
     *
     * @ORM\Column(name="session_type", type="string", length=30, nullable=false)
     */
    protected $sessionType;

    /**
     * @var string
     *
     * @ORM\Column(name="assoc_handle", type="text", nullable=false)
     */
    protected $assocHandle;

    /**
     * @var string
     *
     * @ORM\Column(name="assoc_type", type="text", nullable=false)
     */
    protected $assocType;

    /**
     * @var int
     *
     * @ORM\Column(name="expires_in", type="bigint", nullable=false)
     */
    protected $expiresIn;

    /**
     * @var string
     *
     * @ORM\Column(name="mac_key", type="text", nullable=false)
     */
    protected $macKey;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="bigint", nullable=false)
     */
    protected $created;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set idpEndpointUri.
     *
     * @param string $idpEndpointUri
     *
     * @return OpenidAssociation
     */
    public function setIdpEndpointUri($idpEndpointUri)
    {
        $this->idpEndpointUri = $idpEndpointUri;

        return $this;
    }

    /**
     * Get idpEndpointUri.
     *
     * @return string
     */
    public function getIdpEndpointUri()
    {
        return $this->idpEndpointUri;
    }

    /**
     * Set sessionType.
     *
     * @param string $sessionType
     *
     * @return OpenidAssociation
     */
    public function setSessionType($sessionType)
    {
        $this->sessionType = $sessionType;

        return $this;
    }

    /**
     * Get sessionType.
     *
     * @return string
     */
    public function getSessionType()
    {
        return $this->sessionType;
    }

    /**
     * Set assocHandle.
     *
     * @param string $assocHandle
     *
     * @return OpenidAssociation
     */
    public function setAssocHandle($assocHandle)
    {
        $this->assocHandle = $assocHandle;

        return $this;
    }

    /**
     * Get assocHandle.
     *
     * @return string
     */
    public function getAssocHandle()
    {
        return $this->assocHandle;
    }

    /**
     * Set assocType.
     *
     * @param string $assocType
     *
     * @return OpenidAssociation
     */
    public function setAssocType($assocType)
    {
        $this->assocType = $assocType;

        return $this;
    }

    /**
     * Get assocType.
     *
     * @return string
     */
    public function getAssocType()
    {
        return $this->assocType;
    }

    /**
     * Set expiresIn.
     *
     * @param int $expiresIn
     *
     * @return OpenidAssociation
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }

    /**
     * Get expiresIn.
     *
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * Set macKey.
     *
     * @param string $macKey
     *
     * @return OpenidAssociation
     */
    public function setMacKey($macKey)
    {
        $this->macKey = $macKey;

        return $this;
    }

    /**
     * Get macKey.
     *
     * @return string
     */
    public function getMacKey()
    {
        return $this->macKey;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OpenidAssociation
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
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
}
