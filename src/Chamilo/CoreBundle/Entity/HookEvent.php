<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookEvent.
 *
 * @ORM\Table(name="hook_event", uniqueConstraints={@ORM\UniqueConstraint(name="class_name", columns={"class_name"})})
 * @ORM\Entity
 */
class HookEvent
{
    /**
     * @var string
     *
     * @ORM\Column(name="class_name", type="string", length=255, nullable=true)
     */
    protected $className;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Set className.
     *
     * @param string $className
     *
     * @return HookEvent
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return HookEvent
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
