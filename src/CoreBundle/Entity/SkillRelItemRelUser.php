<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'skill_rel_item_rel_user')]
#[ORM\Entity]
class SkillRelItemRelUser
{
    use TimestampableEntity;
    use UserTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SkillRelItem::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'skill_rel_item_id', referencedColumnName: 'id', nullable: false)]
    protected SkillRelItem $skillRelItem;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected User $user;

    #[ORM\Column(name: 'result_id', type: 'integer', nullable: true)]
    protected int $resultId;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_by', type: 'integer', nullable: false)]
    protected int $createdBy;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_by', type: 'integer', nullable: false)]
    protected int $updatedBy;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkillRelItem(): SkillRelItem
    {
        return $this->skillRelItem;
    }

    public function setSkillRelItem(SkillRelItem $skillRelItem): static
    {
        $this->skillRelItem = $skillRelItem;

        return $this;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function setCreatedBy(int $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getResultId(): int
    {
        return $this->resultId;
    }

    public function setResultId(int $resultId): static
    {
        $this->resultId = $resultId;

        return $this;
    }

    public function getUserItemResultUrl(string $cidReq): string
    {
        $resultId = $this->getResultId();

        return $this->getSkillRelItem()->getItemResultUrl($cidReq).'&id='.$resultId;
    }
}
