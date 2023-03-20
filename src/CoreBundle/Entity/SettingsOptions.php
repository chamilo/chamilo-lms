<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SettingsOptions.
 *
 * @ORM\Table(
 *     name="settings_options",
 *     options={"row_format"="DYNAMIC"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_setting_option", columns={"variable", "value"})
 *     }
 * )
 * @ORM\Entity
 */
class SettingsOptions
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="variable", type="string", length=190, nullable=false)
     */
    protected string $variable;

    /**
     * @ORM\Column(name="value", type="string", length=190, nullable=true)
     */
    protected ?string $value = null;

    /**
     * @ORM\Column(name="display_text", type="string", length=255, nullable=false)
     */
    protected string $displayText;

    public function setVariable(string $variable): self
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Get variable.
     *
     * @return string
     */
    public function getVariable()
    {
        return $this->variable;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function setDisplayText(string $displayText): self
    {
        $this->displayText = $displayText;

        return $this;
    }

    /**
     * Get displayText.
     *
     * @return string
     */
    public function getDisplayText()
    {
        return $this->displayText;
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
