<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     name="extra_field_rel_tag",
 *     indexes={
 *         @ORM\Index(name="field", columns={"field_id"}),
 *         @ORM\Index(name="item", columns={"item_id"}),
 *         @ORM\Index(name="tag", columns={"tag_id"}),
 *         @ORM\Index(name="field_item_tag", columns={"field_id", "item_id", "tag_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ExtraFieldRelTagRepository")
 */
class ExtraFieldRelTag
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected ExtraField $field;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tag", inversedBy="extraFieldRelTags")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Tag $tag;

    /**
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    protected int $itemId;

    public function setItemId(int $itemId): self
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
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

    public function getField(): ExtraField
    {
        return $this->field;
    }

    public function setField(ExtraField $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function setTag(Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }
}
