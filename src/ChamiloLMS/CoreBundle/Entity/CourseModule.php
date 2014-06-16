<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CourseModule
 *
 * @ORM\Table(name="course_module")
 * @ORM\Entity
 */
class CourseModule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, precision=0, scale=0, nullable=false, unique=false)
     */
    private $link;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=100, precision=0, scale=0, nullable=true, unique=false)
     */
    private $image;

    /**
     * @var integer
     *
     * @ORM\Column(name="row_module", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $rowModule;

    /**
     * @var integer
     *
     * @ORM\Column(name="column_module", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $columnModule;

    /**
     * @var string
     *
     * @ORM\Column(name="position", type="string", length=20, precision=0, scale=0, nullable=false, unique=false)
     */
    private $position;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CourseModule
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return CourseModule
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set image
     *
     * @param string $image
     * @return CourseModule
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set rowModule
     *
     * @param integer $rowModule
     * @return CourseModule
     */
    public function setRowModule($rowModule)
    {
        $this->rowModule = $rowModule;

        return $this;
    }

    /**
     * Get rowModule
     *
     * @return integer
     */
    public function getRowModule()
    {
        return $this->rowModule;
    }

    /**
     * Set columnModule
     *
     * @param integer $columnModule
     * @return CourseModule
     */
    public function setColumnModule($columnModule)
    {
        $this->columnModule = $columnModule;

        return $this;
    }

    /**
     * Get columnModule
     *
     * @return integer
     */
    public function getColumnModule()
    {
        return $this->columnModule;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return CourseModule
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }
}
