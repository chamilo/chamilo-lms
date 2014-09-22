<?php

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CItemVisibility
 *
 * @ORM\Table(name="c_item_visibility")
 * @ORM\Entity
 */
class CItemVisibility
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
     * @var integer
     *
     * @ORM\Column(name="property_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $propertyId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visibility", type="boolean", precision=0, scale=0, nullable=false, unique=false)
     */
    private $visibility;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_visible", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $startVisible;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_visible", type="datetime", precision=0, scale=0, nullable=true, unique=false)
     */
    private $endVisible;

    /**
     *
     */
    public function __construct()
    {

    }

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return CItemProperty
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * Set ref
     *
     * @param integer $itemId
     *
     * @return CItemVisibility
     */
    public function setPropertyId($itemId)
    {
        $this->propertyId = $itemId;

        return $this;
    }

    /**
     * Get ref
     *
     * @return integer
     */
    public function getPropertyId()
    {
        return $this->propertyId;
    }

    /**
     * Set visibility
     *
     * @param boolean $visibility
     *
     * @return CItemProperty
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get visibility
     *
     * @return boolean
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * Set startVisible
     *
     * @param \DateTime $startVisible
     *
     * @return CItemProperty
     */
    public function setStartVisible($startVisible)
    {
        $this->startVisible = $startVisible;

        return $this;
    }

    /**
     * Get startVisible
     *
     * @return \DateTime
     */
    public function getStartVisible()
    {
        return $this->startVisible;
    }

    /**
     * Set endVisible
     *
     * @param \DateTime $endVisible
     *
     * @return CItemProperty
     */
    public function setEndVisible($endVisible)
    {
        $this->endVisible = $endVisible;

        return $this;
    }

    /**
     * Get endVisible
     *
     * @return \DateTime
     */
    public function getEndVisible()
    {
        return $this->endVisible;
    }
}
