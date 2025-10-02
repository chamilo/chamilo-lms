<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Controller\Api\CreateCBlogAction;
use Chamilo\CoreBundle\Controller\Api\UpdateVisibilityBlog;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Repository\CBlogRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CBlogRepository::class)]
#[ORM\Table(name: 'c_blog')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Post(
            controller: CreateCBlogAction::class,
            openapiContext: [
                'summary' => 'Create a new blog project',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'title' => ['type' => 'string'],
                                    'blogSubtitle' => ['type' => 'string'],
                                    'parentResourceNodeId' => ['type' => 'integer'],
                                    'resourceLinkList' => [
                                        'oneOf' => [
                                            ['type' => 'array'],
                                            ['type' => 'string'],
                                        ],
                                    ],
                                    'showOnHomepage' => ['type' => 'boolean'],
                                ],
                                'required' => ['title'],
                            ],
                        ],
                    ],
                ],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_TEACHER')",
            validationContext: ['groups' => ['Default', 'blog:write']],
            deserialize: false
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Patch(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_USER')"),
        new Put(
            uriTemplate: '/c_blogs/{iid}/toggle_visibility',
            controller: UpdateVisibilityBlog::class,
            security: "is_granted('EDIT', object.resourceNode)",
            deserialize: false
        ),
    ],
    normalizationContext: ['groups' => ['blog:read']],
    denormalizationContext: ['groups' => ['blog:write', 'resource_node:write']],
    paginationEnabled: true
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'blogSubtitle' => 'partial',
])]
#[ApiFilter(OrderFilter::class, properties: [
    'dateCreation',
    'title',
], arguments: ['orderParameterName' => 'order'])]
class CBlog extends AbstractResource implements ResourceInterface, Stringable
{
    #[Groups(['blog:read'])]
    #[ORM\Id, ORM\Column(name: 'iid', type: 'integer'), ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[Groups(['blog:read', 'blog:write'])]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

    #[Groups(['blog:read', 'blog:write'])]
    #[ORM\Column(name: 'blog_subtitle', type: 'string', length: 250, nullable: true)]
    protected ?string $blogSubtitle = null;

    #[Groups(['blog:read'])]
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    protected DateTime $dateCreation;

    #[Groups(['blog:read'])]
    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: CBlogAttachment::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
        $this->dateCreation = new DateTime();
    }

    public function getIid(): ?int
    {
        return $this->iid;
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

    public function getBlogSubtitle(): ?string
    {
        return $this->blogSubtitle;
    }
    public function setBlogSubtitle(?string $blogSubtitle): self
    {
        $this->blogSubtitle = $blogSubtitle;

        return $this;
    }

    public function getDateCreation(): DateTime
    {
        return $this->dateCreation;
    }
    public function setDateCreation(DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * @return Collection<int, CBlogAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }
    public function addAttachment(CBlogAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setBlog($this);
        }

        return $this;
    }
    public function removeAttachment(CBlogAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getBlog() === $this) {
                $attachment->setBlog(null);
            }
        }

        return $this;
    }

    #[Groups(['blog:read'])]
    public function getCreatedAt(): string
    {
        return $this->getDateCreation()->format('Y-m-d');
    }

    #[Groups(['blog:read'])]
    public function getOwner(): array
    {
        if (method_exists($this, 'getCreator') && null !== $this->getCreator()) {
            $u = $this->getCreator();
            $name = method_exists($u, 'getFullName') ? $u->getFullName()
                : (method_exists($u, 'getUsername') ? $u->getUsername() : 'Owner');

            return [
                'id' => method_exists($u, 'getId') ? $u->getId() : null,
                'name' => $name,
            ];
        }

        return ['id' => null, 'name' => 'Owner'];
    }

    #[Groups(['blog:read', 'blog:write'])]
    public function getVisible(): bool
    {
        $link = $this->getFirstResourceLink();
        if (!$link instanceof ResourceLink) {
            return true;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    public function setVisible(bool $visible): self
    {
        $link = $this->getFirstResourceLink();
        if ($link instanceof ResourceLink) {
            $link->setVisibility(
                $visible ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT
            );
        }

        return $this;
    }

    #[Groups(['blog:read'])]
    public function getVisibilityName(): string
    {
        $link = $this->getFirstResourceLink();
        if ($link instanceof ResourceLink) {
            return (string) $link->getVisibilityName();
        }

        return 'published';
    }

    // === ResourceInterface ===
    public function getResourceIdentifier(): int
    {
        return (int) ($this->getIid() ?? 0);
    }
    public function getResourceName(): string
    {
        return $this->getTitle();
    }
    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
