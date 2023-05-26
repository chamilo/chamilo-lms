<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'usergroup_rel_user')]
#[ORM\Index(name: 'IDX_739515A9A76ED395', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_739515A9D2112630', columns: ['usergroup_id'])]
#[ORM\Entity]
class UsergroupRelUser
{
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class, inversedBy: 'classes', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Usergroup::class, inversedBy: 'users', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected Usergroup $usergroup;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'relation_type', type: 'integer', nullable: false)]
    protected int $relationType;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setUsergroup(Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }

    /**
     * Get usergroup.
     *
     * @return Usergroup
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    public function setRelationType(int $relationType): self
    {
        $this->relationType = $relationType;

        return $this;
    }

    /**
     * Get relationType.
     *
     * @return int
     */
    public function getRelationType()
    {
        return $this->relationType;
    }
}
