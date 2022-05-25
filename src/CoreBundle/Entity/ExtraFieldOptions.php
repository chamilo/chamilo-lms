<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

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
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField", inversedBy="options")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    #[Assert\NotNull]
    protected ExtraField $field;

    /**
     * @ORM\Column(name="option_value", type="text", nullable=true)
     */
    protected ?string $value = null;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="display_text", type="string", length=255, nullable=true)
     */
    protected ?string $displayText = null;

    /**
     * @Gedmo\Locale
     */
    protected ?string $locale = null;

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

    public function setOptionOrder(int $optionOrder): self
    {
        $this->optionOrder = $optionOrder;

        return $this;
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

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayText()
    {
        return $this->displayText;
    }

    public function setDisplayText(string $displayText): self
    {
        $this->displayText = $displayText;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriorityMessage(): ?string
    {
        return $this->priorityMessage;
    }

    public function setPriorityMessage(string $priorityMessage): self
    {
        $this->priorityMessage = $priorityMessage;

        return $this;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTranslatableLocale()
    {
        return $this->locale;
    }
}
