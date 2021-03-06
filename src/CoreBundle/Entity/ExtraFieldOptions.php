<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ExtraFieldOption;

/**
 * @ORM\Entity(repositoryClass="Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository")
 * @ORM\Table(name="extra_field_options")
 *
 * @ORM\MappedSuperclass
 */
class ExtraFieldOptions
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField", inversedBy="options")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected ExtraField $field;

    /**
     * @ORM\Column(name="option_value", type="text", nullable=true)
     */
    protected ?string $value = null;

    /**
     * @ORM\Column(name="display_text", type="string", length=255, nullable=true)
     */
    protected ?string $displayText = null;

    /**
     * @ORM\Column(name="priority", type="string", length=255, nullable=true)
     */
    protected ?string $priority = null;

    /**
     * @ORM\Column(name="priority_message", type="string", length=255, nullable=true)
     */
    protected ?string $priorityMessage = null;

    /**
     * @ORM\Column(name="option_order", type="integer", nullable=true)
     */
    protected ?int $optionOrder = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOptionOrder()
    {
        return $this->optionOrder;
    }

    /**
     * @return $this
     */
    public function setOptionOrder(int $optionOrder)
    {
        $this->optionOrder = $optionOrder;

        return $this;
    }

    /**
     * @return ExtraField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return $this
     */
    public function setField(ExtraField $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return $this
     */
    public function setValue(string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param bool $translated Optional. Whether translate the display text
     *
     * @return string
     */
    public function getDisplayText(bool $translated = true)
    {
        if ($translated) {
            return ExtraFieldOption::translateDisplayName($this->displayText);
        }

        return $this->displayText;
    }

    /**
     * @return $this
     */
    public function setDisplayText(string $displayText)
    {
        $this->displayText = $displayText;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return $this
     */
    public function setPriority(string $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return string
     */
    public function getPriorityMessage()
    {
        return $this->priorityMessage;
    }

    /**
     * @return $this
     */
    public function setPriorityMessage(string $priorityMessage)
    {
        $this->priorityMessage = $priorityMessage;

        return $this;
    }
}
