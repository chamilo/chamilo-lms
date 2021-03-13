<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseType.
 *
 * @ORM\Table(name="course_type")
 * @ORM\Entity
 */
class CourseType
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(name="translation_var", type="string", length=40, nullable=true)
     */
    protected ?string $translationVar = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="props", type="text", nullable=true)
     */
    protected ?string $props = null;

    public function setName(string $name): self
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

    public function setTranslationVar(string $translationVar): self
    {
        $this->translationVar = $translationVar;

        return $this;
    }

    /**
     * Get translationVar.
     *
     * @return string
     */
    public function getTranslationVar()
    {
        return $this->translationVar;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function setProps(string $props): self
    {
        $this->props = $props;

        return $this;
    }

    /**
     * Get props.
     *
     * @return string
     */
    public function getProps()
    {
        return $this->props;
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
