<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseModule.
 *
 * @ORM\Table(name="course_module")
 * @ORM\Entity
 */
class CourseModule
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=false)
     */
    protected $link;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=100, nullable=true)
     */
    protected $image;

    /**
     * @var int
     *
     * @ORM\Column(name="row", type="integer", nullable=false)
     */
    protected $row;

    /**
     * @var int
     *
     * @ORM\Column(name="column", type="integer", nullable=false)
     */
    protected $column;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=20, nullable=false)
     */
    protected $position;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CourseModule
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
     * Set link.
     *
     * @param string $link
     *
     * @return CourseModule
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return CourseModule
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set row.
     *
     * @param int $row
     *
     * @return CourseModule
     */
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    /**
     * Get row.
     *
     * @return int
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Set column.
     *
     * @param int $column
     *
     * @return CourseModule
     */
    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Get column.
     *
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set position.
     *
     * @param string $position
     *
     * @return CourseModule
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
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
