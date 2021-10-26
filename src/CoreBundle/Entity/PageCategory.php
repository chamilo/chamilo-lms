<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="page_category",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')",
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('ROLE_USER')",
        ],
        'put' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'delete' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    denormalizationContext: [
        'groups' => ['page_category:write'],
    ],
    normalizationContext: [
        'groups' => ['page_category:read'],
    ],
)]
class PageCategory
{
    use TimestampableTypedEntity;

    /**
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[Groups(['page_category:read', 'page_category:write'])]
    protected ?int $id = null;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    #[Assert\NotBlank]
    #[Groups(['page_category:read', 'page_category:write'])]
    protected string $title;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    #[Assert\NotNull]
    protected User $creator;

    /**
     * @ORM\Column(name="type", type="string")
     */
    #[Groups(['page_category:read', 'page_category:write', 'page:read'])]
    #[Assert\NotBlank]
    protected string $type;

    /**
     * @var Collection|Page[]
     * @ORM\OneToMany(targetEntity="Chamilo\CoreBundle\Entity\Page", mappedBy="category", cascade={"persist"})
     */
    protected Collection $pages;

    public function __construct()
    {
        $this->pages = new ArrayCollection();
    }

    public function getId(): ?int
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

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function setPages($pages): self
    {
        $this->pages = $pages;

        return $this;
    }
}
