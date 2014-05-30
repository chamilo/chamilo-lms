<?php

namespace ChamiloLMS\CoreBundle\Entity;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * CLpCategory
 *
 * @ORM\Table(name="c_lp_category")
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class CLpCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(name="c_id", type="integer")
     */
    private $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="name")
     */
    private $name;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    private $position;

    /**
     * Set cId
     *
     * @param integer $cId
     * @return CLpCategory
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
     * @return CLpCategory
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
     *
     * @return CLpCategory
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
