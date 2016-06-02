<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FieldRelTag
 *
 * @ORM\Table(
 *  name="extra_field_rel_tag",
 *  indexes={
 *      @ORM\Index(name="field", columns={"field_id"}),
 *      @ORM\Index(name="item", columns={"item_id"}),
 *      @ORM\Index(name="tag", columns={"tag_id"}),
 *      @ORM\Index(name="field_item_tag", columns={"field_id", "item_id", "tag_id"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\ExtraFieldRelTagRepository")
 */
class ExtraFieldRelTag
{
    /**
     * @var integer
     *
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var integer
     *
     * @ORM\Column(name="tag_id", type="integer", nullable=false)
     */
    private $tagId;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer", nullable=false)
     */
    private $itemId;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Set fieldId
     * @param integer $fieldId
     * @return ExtraFieldRelTag
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Set tagId
     * @param integer $tagId
     * @return ExtraFieldRelTag
     */
    public function setTagId($tagId)
    {
        $this->tagId = $tagId;

        return $this;
    }

    /**
     * Set itemId
     * @param integer $itemId
     * @return ExtraFieldRelTag
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return integer
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Get tagId
     * @return integer
     */
    public function getTagId()
    {
        return $this->tagId;
    }

    /**
     * Get itemId
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

}
