<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['blog_rating:read']],
    denormalizationContext: ['groups' => ['blog_rating:write']],
    paginationEnabled: true
)]
#[ApiFilter(SearchFilter::class, properties: [
    'blog' => 'exact',
    'post' => 'exact',
    'ratingType' => 'exact',
    'user' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['iid' => 'ASC'])]
#[ORM\Table(name: 'c_blog_rating')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CBlogRating
{
    use UserTrait;

    #[Groups(['blog_rating:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    // IMPORTANT: this must be in write group so Api Platform will set it
    #[Groups(['blog_rating:read','blog_rating:write'])]
    #[ORM\Column(name: 'rating_type', type: 'string', length: 40, nullable: false)]
    protected string $ratingType = 'post';

    #[Groups(['blog_rating:read','blog_rating:write'])]
    #[ORM\Column(name: 'rating', type: 'integer', nullable: false)]
    protected int $rating;

    #[Groups(['blog_rating:read','blog_rating:write'])]
    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[Groups(['blog_rating:read','blog_rating:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?User $user = null;

    #[Groups(['blog_rating:read','blog_rating:write'])]
    #[ORM\ManyToOne(targetEntity: CBlogPost::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CBlogPost $post = null;

    #[ORM\PrePersist]
    public function ensureDefaults(): void
    {
        // Ensure legacy-safe default if client omits it
        if (empty($this->ratingType)) {
            $this->ratingType = 'post';
        }
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getRatingType(): string
    {
        return $this->ratingType;
    }

    public function setRatingType(string $ratingType): self
    {
        $this->ratingType = $ratingType;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getBlog(): ?CBlog
    {
        return $this->blog;
    }

    public function setBlog(?CBlog $blog): self
    {
        $this->blog = $blog;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getPost(): ?CBlogPost
    {
        return $this->post;
    }

    public function setPost(?CBlogPost $post): self
    {
        $this->post = $post;
        return $this;
    }
}
