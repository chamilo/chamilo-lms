<?php
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="translation_var", type="string", length=40, nullable=true)
     */
    protected $translationVar;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="props", type="text", nullable=true)
     */
    protected $props;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CourseType
     */
    public function setName($name)
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
     * Set translationVar.
     *
     * @param string $translationVar
     *
     * @return CourseType
     */
    public function setTranslationVar($translationVar)
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

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return CourseType
     */
    public function setDescription($description)
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

    /**
     * Set props.
     *
     * @param string $props
     *
     * @return CourseType
     */
    public function setProps($props)
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
