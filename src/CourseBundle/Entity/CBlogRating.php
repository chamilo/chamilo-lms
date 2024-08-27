<?php

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'c_blog_rating')]
#[ORM\Entity]
class CBlogRating
{
    use UserTrait;

    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[ORM\Column(name: 'rating_type', type: 'string', length: 40, nullable: false)]
    protected string $ratingType;

    #[ORM\Column(name: 'rating', type: 'integer', nullable: false)]
    protected int $rating;

    #[ORM\ManyToOne(targetEntity: CBlog::class)]
    #[ORM\JoinColumn(name: 'blog_id', referencedColumnName: 'iid', onDelete: 'CASCADE')]
    protected ?CBlog $blog = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

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
}
