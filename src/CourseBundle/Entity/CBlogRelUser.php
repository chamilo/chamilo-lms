<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Delete(security: "is_granted('ROLE_USER')"),
    ],
    normalizationContext: ['groups' => ['blog_rel_user:read']],
    denormalizationContext: ['groups' => ['blog_rel_user:write']],
    paginationEnabled: true
)]
#[ApiFilter(SearchFilter::class, properties: [
    'blog' => 'exact',
    'user' => 'exact',
])]
#[ORM\Table(name: 'c_blog_rel_user')]
#[ORM\UniqueConstraint(name: 'uniq_blog_user', columns: ['blog_id', 'user_id'])]
#[ORM\Entity]
class CBlogRelUser
{
    #[Groups(['blog_rel_user:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Groups(['blog_rel_user:read', 'blog_rel_user:write'])]
    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[Groups(['blog_rel_user:read', 'blog_rel_user:write'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?User $user = null;

    public function getIid(): ?int
    {
        return $this->iid;
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
}
