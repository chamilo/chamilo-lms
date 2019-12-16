<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * PersonalFiles.
 *
 * @ORM\Table(name="personal_file")
 * @ORM\Entity
 * @GRID\Source(columns="id, name, resourceNode.createdAt", filterable=false, groups={"resource"})
 */
class PersonalFile extends AbstractResource implements ResourceInterface
{
    use TimestampableEntity;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return (string) $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }
}
