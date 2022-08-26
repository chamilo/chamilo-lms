<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="extra_field_values",
 *     indexes={
 *         @ORM\Index(name="idx_efv_fiii", columns={"field_id", "item_id"}),
 *         @ORM\Index(name="idx_efv_item", columns={"item_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository")
 * @ORM\MappedSuperclass
 */
#[ApiResource(
    collectionOperations:[
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'post' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    itemOperations:[
        'get' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
        'put' => [
            'security' => "is_granted('ROLE_ADMIN')",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_ADMIN')",
    ],
    denormalizationContext: [
        'groups' => ['extra_field_values:write'],
    ],
    normalizationContext: [
        'groups' => ['extra_field_values:read'],
    ],
)]
#[ApiFilter(SearchFilter::class, properties: ['field' => 'exact', 'value' => 'exact'])]
class ExtraFieldValues
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    #[Groups(['extra_field_values:read'])]
    protected ?int $id = null;

    /**
     * @ORM\Column(name="field_value", type="text", nullable=true, unique=false)
     */
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    protected ?string $fieldValue = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    #[Assert\NotBlank]
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    protected ExtraField $field;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Asset")
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ?Asset $asset = null;

    /**
     * Item id can be: userId, courseId, sessionId, etc.
     *
     * @ORM\Column(name="item_id", type="integer")
     */
    #[Assert\NotBlank]
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
    protected int $itemId;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true, unique=false)
     */
    #[Groups(['extra_field_values:read', 'extra_field_values:write'])]
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
