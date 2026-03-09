<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Traits\PersonalResourceTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Stringable;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['illustration:read']],
    security: "is_granted('ROLE_USER')"
)]
#[ORM\Table(name: 'illustration')]
#[ORM\Entity(repositoryClass: IllustrationRepository::class)]
class Illustration extends AbstractResource implements ResourceInterface, Stringable
{
    use PersonalResourceTrait;
    use TimestampableEntity;
    #[ORM\Column(name: 'id', type: 'uuid')]
    #[ORM\Id]
    protected Uuid $id;
    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;
    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->title = 'illustration';
    }
    public function __toString(): string
    {
        return $this->getTitle();
    }
    public function getId(): Uuid
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
    public function getResourceIdentifier(): Uuid
    {
        return $this->getId();
    }
    public function getResourceName(): string
    {
        return $this->getTitle();
    }
    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
