<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_right")
 */
class ResourceRight
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceLink", inversedBy="resourceRights")
     * @ORM\JoinColumn(name="resource_link_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?ResourceLink $resourceLink = null;

    /**
     * @ORM\Column(name="role", type="string", length=255, nullable=false)
     */
    protected string $role;

    /**
     * @ORM\Column(name="mask", type="integer", nullable=false)
     */
    protected int $mask;

    public function __toString(): string
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
     * @return int
     */
    public function getMask()
    {
        return $this->mask;
    }

    public function setMask(int $mask): self
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

    public function setResourceLink(ResourceLink $resourceLink): self
    {
        $this->resourceLink = $resourceLink;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }
}
