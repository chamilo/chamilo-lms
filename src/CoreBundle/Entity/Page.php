<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    collectionOperations: [
        'get' => [
            //'security' => "is_granted('ROLE_USER')",
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
        'groups' => ['page:write'],
    ],
    normalizationContext: [
        'groups' => ['page:read', 'timestampable_created:read', 'timestampable_updated:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'locale' => 'exact',
    'url' => 'exact',
    'enabled' => 'exact',
    'category' => 'exact',
    'category.title' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'title',
])]
#[ORM\Table(name: 'page')]
#[ORM\Entity]
class Page
{
    use TimestampableTypedEntity;

    #[ORM\Column(name: 'id', type: 'bigint')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[Groups(['page:read', 'page:write'])]
    #[ORM\Column(name: 'title', type: 'string', length: 255)]
    protected string $title;

    #[Groups(['page:read', 'page:write'])]
    #[Assert\NotBlank]
    #[ORM\Column(name: 'content', type: 'text')]
    protected string $content;

    #[Gedmo\Slug(fields: ['title'], updatable: true, unique: true)]
    #[ORM\Column(name: 'slug', type: 'string', length: 255)]
    protected string $slug;

    #[Groups(['page:read', 'page:write'])]
    #[ORM\Column(name: 'enabled', type: 'boolean', nullable: false)]
    protected bool $enabled;

    #[Groups(['page:read', 'page:write'])]
    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: 'integer')]
    protected int $position;

    #[Groups(['page:read', 'page:write'])]
    #[ORM\Column(name: 'locale', type: 'string', length: 10)]
    protected string $locale;

    #[Assert\NotNull]
    #[Groups(['page:read', 'page:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\AccessUrl::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected AccessUrl $url;

    #[Assert\NotNull]
    #[Groups(['page:read', 'page:write'])]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected User $creator;

    #[Groups(['page:read', 'page:write'])]
    #[Gedmo\SortableGroup]
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\PageCategory::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?PageCategory $category = null;

    public function __construct()
    {
        $this->enabled = false;
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getCategory(): ?PageCategory
    {
        return $this->category;
    }

    public function setCategory(PageCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
