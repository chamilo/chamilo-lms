<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Attribute\Model\AttributeTypes;
use Chamilo\UserBundle\Entity\User;

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
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id")
     */
    protected $author;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User", cascade={"persist"}, inversedBy="extraFields")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user  = $user;

        return $this;
    }

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
     * Get author
     *
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get author
     *
     * @param $author User
     *
     * @return $this
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;

        return $this;
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

    /**
     * @return string
     */
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
