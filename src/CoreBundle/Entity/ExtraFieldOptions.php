<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Repository\ExtraFieldOptionsRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'extra_field_options')]
#[ORM\Entity(repositoryClass: ExtraFieldOptionsRepository::class)]
#[ORM\MappedSuperclass]
class ExtraFieldOptions
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne(targetEntity: ExtraField::class, inversedBy: 'options')]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id')]
    protected ExtraField $field;

    #[ORM\Column(name: 'option_value', type: 'text', nullable: true)]
    protected ?string $value = null;

    #[Gedmo\Translatable]
    #[ORM\Column(name: 'display_text', type: 'string', length: 255, nullable: true)]
    protected ?string $displayText = null;

    #[ORM\Column(name: 'priority', type: 'string', length: 255, nullable: true)]
    protected ?string $priority = null;

    #[ORM\Column(name: 'priority_message', type: 'string', length: 255, nullable: true)]
    protected ?string $priorityMessage = null;

    #[ORM\Column(name: 'option_order', type: 'integer', nullable: true)]
    protected ?int $optionOrder = null;

    #[Gedmo\Locale]
    private ?string $locale = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOptionOrder(): ?int
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDisplayText(): ?string
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

    /**
     * Backward-compatibility alias for legacy code calling setLocale().
     * Gedmo Translatable expects the locale to be injected through the "Locale" field.
     */
    public function setLocale(string $locale): self
    {
        return $this->setTranslatableLocale($locale);
    }

    /**
     * Sets the locale used by Gedmo Translatable for this entity.
     */
    public function setTranslatableLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}
