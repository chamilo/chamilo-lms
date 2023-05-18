<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new Get(security: 'is_granted(\'ROLE_ADMIN\')'), new Put(security: 'is_granted(\'ROLE_ADMIN\')'), new GetCollection(security: 'is_granted(\'ROLE_ADMIN\')'), new Post(security: 'is_granted(\'ROLE_ADMIN\')')], security: 'is_granted(\'ROLE_ADMIN\')', denormalizationContext: ['groups' => ['extra_field_values:write']], normalizationContext: ['groups' => ['extra_field_values:read']])]
#[ORM\Table(name: 'extra_field_values')]
#[ORM\Index(name: 'idx_efv_fiii', columns: ['field_id', 'item_id'])]
#[ORM\Index(name: 'idx_efv_item', columns: ['item_id'])]
#[ORM\Entity(repositoryClass: ExtraFieldValuesRepository::class)]
#[ORM\MappedSuperclass]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['field' => 'exact', 'value' => 'exact'])]
class ExtraFieldValues
{
    use TimestampableEntity;
    #[Groups(['extra_field_values:read'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    #[ORM\Column(name: 'field_value', type: 'text', nullable: true, unique: false)]
    protected ?string $fieldValue = null;
    #[Assert\NotBlank]
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    #[ORM\ManyToOne(targetEntity: ExtraField::class)]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id')]
    protected ExtraField $field;
    #[ORM\ManyToOne(targetEntity: Asset::class)]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Asset $asset = null;
    /**
     * Item id can be: userId, courseId, sessionId, etc.
     */
    #[Assert\NotBlank]
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    #[ORM\Column(name: 'item_id', type: 'integer')]
    protected int $itemId;
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    #[ORM\Column(name: 'comment', type: 'text', nullable: true, unique: false)]
    protected ?string $comment;
    public function __construct()
    {
        $this->comment = '';
    }
    public function getField(): ExtraField
    {
        return $this->field;
    }
    public function setField(ExtraField $field): self
    {
        $this->field = $field;

        return $this;
    }
    public function getItemId(): int
    {
        return $this->itemId;
    }
    public function setItemId(int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }
    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    public function getFieldValue(): ?string
    {
        return $this->fieldValue;
    }
    public function setFieldValue(?string $fieldValue): self
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }
    public function getAsset(): ?Asset
    {
        return $this->asset;
    }
    public function setAsset(?Asset $asset): self
    {
        $this->asset = $asset;

        return $this;
    }
}
