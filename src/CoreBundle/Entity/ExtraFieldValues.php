<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
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
class ExtraFieldValues
{
    use TimestampableEntity;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\Column(name="value", type="text", nullable=true, unique=false)
     */
    protected ?string $value = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    #[Assert\NotBlank]
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
    protected int $itemId;

    /**
     * @ORM\Column(name="comment", type="text", nullable=true, unique=false)
     */
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

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
