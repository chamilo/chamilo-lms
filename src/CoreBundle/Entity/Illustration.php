<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\PersonalResourceTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="illustration")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\Node\IllustrationRepository")
 */
#[ApiResource(
    normalizationContext: [
        'groups' => ['illustration:read'],
    ],
)]
class Illustration extends AbstractResource implements ResourceInterface
{
    use PersonalResourceTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="uuid")
     * @ORM\Id
     */
    protected Uuid $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    #[Assert\NotBlank]
    protected string $name;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->name = 'illustration';
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getResourceIdentifier(): Uuid
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
