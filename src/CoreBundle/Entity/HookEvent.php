<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * HookEvent.
 *
 * @ORM\Table(
 *     name="hook_event",
 *     options={"row_format"="DYNAMIC"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="class_name", columns={"class_name"})
 *     }
 * )
 * @ORM\Entity
 */
class HookEvent
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $id = null;

    /**
     * @ORM\Column(name="class_name", type="string", length=190, nullable=true)
     */
    protected ?string $className = null;

    /**
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    protected ?string $description = null;

    /**
     * Set className.
     *
     * @return HookEvent
     */
    public function setClassName(string $className)
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
     * @return HookEvent
     */
    public function setDescription(string $description)
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
