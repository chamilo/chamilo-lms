<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseFieldOptions
 *
 * @ORM\Table(name="course_field_options")
 * @ORM\Entity
 */
class CourseFieldOptions
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
     * @ORM\Column(name="priority", type="integer", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $priority;

    /**
     * @var string
     *
     * @ORM\Column(name="priority_message", type="string", length=255, precision=0, scale=0, nullable=true, unique=false)
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
     * @return CourseFieldOptions
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
     * @return CourseFieldOptions
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
     * @return CourseFieldOptions
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
     * @return CourseFieldOptions
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
     * Set priority
     *
     * @param integer $priority
     * @return CourseFieldOptions
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set priorityMessage
     *
     * @param string $priorityMessage
     * @return CourseFieldOptions
     */
    public function setPriorityMessage($priorityMessage)
    {
        $this->priorityMessage = $priorityMessage;

        return $this;
    }

    /**
     * Get priorityMessage
     *
     * @return string
     */
    public function getPriorityMessage()
    {
        return $this->priorityMessage;
    }

    /**
     * Set tms
     *
     * @param \DateTime $tms
     * @return CourseFieldOptions
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

