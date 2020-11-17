<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CourseBundle\Traits\PersonalResourceTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Illustration.
 *
 * @ApiResource(
 *      normalizationContext={"groups"={"illustration:read"}}
 * )
 * @ORM\Table(name="illustration")
 * @ORM\Entity
 */
class Illustration extends AbstractResource implements ResourceInterface
{
    use PersonalResourceTrait;
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
     * @Groups({"illustration:read"})
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * Illustration constructor.
     */
    public function __construct()
    {
        $this->name = 'illustration';
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

    public function getResourceIdentifier(): int
    {
        return $this->getId();
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
