<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tag.
 *
 * @ORM\Table(name="tag")
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Entity\Repository\TagRepository")
 */
class Tag
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="tag", type="string", length=255, nullable=false)
     */
    protected $tag;

    /**
     * @var int
     *
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    protected $fieldId;

    /**
     * @var int
     *
     * @ORM\Column(name="count", type="integer", nullable=false)
     */
    protected $count;

    /**
     * Set tag.
     *
     * @param string $tag
     *
     * @return Tag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set fieldId.
     *
     * @param int $fieldId
     *
     * @return Tag
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId.
     *
     * @return int
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set count.
     *
     * @param int $count
     *
     * @return Tag
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count.
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
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
}
