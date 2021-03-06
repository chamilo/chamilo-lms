<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpecificField.
 *
 * @ORM\Table(
 *     name="specific_field",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_specific_field__code", columns={"code"})
 *     })
 *     @ORM\Entity
 */
class SpecificField
{
    /**
     * @ORM\Column(name="code", type="string", length=1, nullable=false)
     */
    protected string $code;

    /**
     * @ORM\Column(name="name", type="string", length=200, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * Set code.
     *
     * @return SpecificField
     */
    public function setCode(string $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @return SpecificField
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
