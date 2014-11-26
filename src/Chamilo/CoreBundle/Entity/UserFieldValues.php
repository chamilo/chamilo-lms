<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Attribute\Model\AttributeTypes;

/**
 * UserFieldValues
 *
 * @ORM\Table(name="user_field_values", indexes={@ORM\Index(name="user_id", columns={"user_id", "field_id"})})
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class UserFieldValues extends ExtraFieldValues
{
    /**
     * The current user
     * @var integer
     *
     * @ORM\Column(name="author_id", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $authorId;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $fieldValue;

    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\UserField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * Set fieldValue
     *
     * @param string $fieldValue
     * @return ExtraFieldValues
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set questionId
     *
     * @param integer $id
     *
     * @return QuestionFieldValues
     */
    public function setAuthorId($id)
    {
        $this->authorId = $id;
        return $this;
    }

    /**
     * Get questionId
     *
     * @return integer
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @return ExtraField
     */
    public function getExtraField()
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtraField($attribute)
    {
        $this->field = $attribute;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if ($this->fieldValue && AttributeTypes::CHECKBOX === $this->getExtraField()->getType()) {
            return (Boolean) $this->fieldValue;
        }

        return $this->fieldValue;
    }

    public function getType()
    {
        return $this->getExtraField()->getFieldTypeToString();
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->setFieldValue($value);

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getExtraField()->getFieldVariable();
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->getExtraField()->getConfiguration();
    }

    /**
     * @return UserField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param UserField $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function getAttribute()
    {
        return $this->getField();
    }
}
