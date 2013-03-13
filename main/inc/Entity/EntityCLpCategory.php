<?php

namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;


/**
 * EntityCBlog
 *
 * @Table(name="c_lp_category")
 * @Entity(repositoryClass="\Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class EntityCLpCategory
{
    /**
     * @var integer
     * @Gedmo\SortableGroup
     * @Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @Column(name="name")
     */
    private $name;

    /**
     * @Gedmo\SortablePosition
     * @Column(name="position", type="integer")
     */
    private $position;

    /**
     * Set cId
     *
     * @param integer $cId
     * @return EntityCBlog
     */
    public function setCId($cId)
    {
        $this->cId = $cId;
        return $this;
    }

    /**
     * Get cId
     *
     * @return integer
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return EntityCBlog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get blogId
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set category name
     *
     * @param string $blogName
     * @return EntityCBlog
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get category name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
