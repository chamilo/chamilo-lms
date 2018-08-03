<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClassItem.
 *
 * @ORM\Table(name="class_item")
 * @ORM\Entity
 */
class ClassItem
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=40, nullable=true)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return ClassItem
     */
    public function setCode($code)
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
     * @param string $name
     *
     * @return ClassItem
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
