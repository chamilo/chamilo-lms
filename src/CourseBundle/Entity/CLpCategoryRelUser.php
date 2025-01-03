<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * CLpCategoryRelUser.
 */
#[ORM\Table(name: 'c_lp_category_rel_user')]
#[ORM\Entity]
class CLpCategoryRelUser implements Stringable
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CLpCategory::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'iid')]
    protected CLpCategory $category;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;

    public function __toString(): string
    {
        return (string) $this->getId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCategory(): CLpCategory
    {
        return $this->category;
    }

    public function setCategory(CLpCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
