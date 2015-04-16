<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SessionFieldOptions
 *
 * @ORM\Table(name="session_field_options")
 * @ORM\Entity
 */
class SessionFieldOptions
{
    /**
     * @var integer
     *
     * @ORM\Column(name="field_id", type="integer", nullable=false)
     */
    private $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="option_value", type="text", nullable=true)
     */
    private $optionValue;

    /**
     * @var string
     *
     * @ORM\Column(name="option_display_text", type="string", length=64, nullable=true)
     */
    private $optionDisplayText;

    /**
     * @var integer
     *
     * @ORM\Column(name="option_order", type="integer", nullable=true)
     */
    private $optionOrder;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", nullable=true)
     */
    private $tms;

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

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
