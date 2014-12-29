<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestionFieldOptions
 *
 * @ORM\Table(name="question_field_options", indexes={@ORM\Index(name="idx_question_field_options_field_id", columns={"field_id"})})
 * @ORM\Entity
 */
class QuestionFieldOptions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="field_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_value", type="text", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $optionValue;

    /**
     * @var string
     *
     * @ORM\Column(name="option_display_text", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
     */
    protected $optionDisplayText;

    /**
     * @var integer
     *
     * @ORM\Column(name="option_order", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $optionOrder;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer", nullable=true)
     */
    protected $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="priority_message", type="string", length=255, nullable=true)
     */
    protected $priorityMessage;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $tms;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     * @return SessionFieldOptions
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

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
     * Set optionValue
     *
     * @param string $optionValue
     * @return SessionFieldOptions
     */
    public function setOptionValue($optionValue)
    {
        $this->optionValue = $optionValue;

        return $this;
    }

    /**
     * Get optionValue
     *
     * @return string
     */
    public function getOptionValue()
    {
        return $this->optionValue;
    }

    /**
     * Set optionDisplayText
     *
     * @param string $optionDisplayText
     * @return SessionFieldOptions
     */
    public function setOptionDisplayText($optionDisplayText)
    {
        $this->optionDisplayText = $optionDisplayText;

        return $this;
    }

    /**
     * Get optionDisplayText
     *
     * @return string
     */
    public function getOptionDisplayText()
    {
        return $this->optionDisplayText;
    }

    /**
     * Set optionOrder
     *
     * @param integer $optionOrder
     * @return SessionFieldOptions
     */
    public function setOptionOrder($optionOrder)
    {
        $this->optionOrder = $optionOrder;

        return $this;
    }

    /**
     * Get optionOrder
     *
     * @return integer
     */
    public function getOptionOrder()
    {
        return $this->optionOrder;
    }

    /**
     * Set tms
     *
     * @param \DateTime $tms
     * @return SessionFieldOptions
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms
     *
     * @return \DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }
}
