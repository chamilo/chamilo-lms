<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpenidAssociation
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
    private $idpEndpointUri;

    /**
     * @var string
     *
     * @ORM\Column(name="session_type", type="string", length=30, nullable=false)
     */
    private $sessionType;

    /**
     * @var string
     *
     * @ORM\Column(name="assoc_handle", type="text", nullable=false)
     */
    private $assocHandle;

    /**
     * @var string
     *
     * @ORM\Column(name="assoc_type", type="text", nullable=false)
     */
    private $assocType;

    /**
     * @var integer
     *
     * @ORM\Column(name="expires_in", type="bigint", nullable=false)
     */
    private $expiresIn;

    /**
     * @var string
     *
     * @ORM\Column(name="mac_key", type="text", nullable=false)
     */
    private $macKey;

    /**
     * @var integer
     *
     * @ORM\Column(name="created", type="bigint", nullable=false)
     */
    private $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
