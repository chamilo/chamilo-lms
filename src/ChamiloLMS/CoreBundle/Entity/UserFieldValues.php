<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CourseFieldValues
 *
 * @ORM\Table(name="user_field_values")
 * @ORM\Entity
 * @Gedmo\Loggable
 */
class UserFieldValues extends ExtraFieldValues
{

    /**
     * @var integer
     *
     * @ORM\Column(name="author_id", type="string", precision=0, scale=0, nullable=false, unique=false)
     */
    private $authorId;

    /**
     * @var string
     * @Gedmo\Versioned
     *
     * @ORM\Column(name="field_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    private $fieldValue;

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
     * @param integer $questionId
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
}
