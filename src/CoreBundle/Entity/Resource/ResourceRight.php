<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_right")
 */
class ResourceRight
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceLink", inversedBy="resourceRight")
     * @ORM\JoinColumn(name="resource_link_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $resourceLink;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=255, nullable=false)
     */
    protected $role;

    /**
     * @var string
     *
     * @ORM\Column(name="mask", type="integer", nullable=false)
     */
    protected $mask;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * @param string $mask
     *
     * @return $this
     */
    public function setMask($mask)
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * @return ResourceLink
     */
    public function getResourceLink()
    {
        return $this->resourceLink;
    }

    /**
     * @param ResourceLink $resourceLink
     *
     * @return $this
     */
    public function setResourceLink($resourceLink)
    {
        $this->resourceLink = $resourceLink;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
