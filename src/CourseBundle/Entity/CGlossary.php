<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CGlossary.
 *
 * @ORM\Table(
 *  name="c_glossary",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"}),
 *      @ORM\Index(name="session_id", columns={"session_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CGlossary
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var int
     *
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var int
     *
     * @ORM\Column(name="glossary_id", type="integer")
     */
    protected $glossaryId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="display_order", type="integer", nullable=true)
     */
    protected $displayOrder;

    /**
     * @var int
     *
     * @ORM\Column(name="session_id", type="integer", nullable=true)
     */
    protected $sessionId;

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return CGlossary
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
     * Set description.
     *
     * @param string $description
     *
     * @return CGlossary
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
     * Set displayOrder.
     *
     * @param int $displayOrder
     *
     * @return CGlossary
     */
    public function setDisplayOrder($displayOrder)
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    /**
     * Get displayOrder.
     *
     * @return int
     */
    public function getDisplayOrder()
    {
        return $this->displayOrder;
    }

    /**
     * Set sessionId.
     *
     * @param int $sessionId
     *
     * @return CGlossary
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId.
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set glossaryId.
     *
     * @param int $glossaryId
     *
     * @return CGlossary
     */
    public function setGlossaryId($glossaryId)
    {
        $this->glossaryId = $glossaryId;

        return $this;
    }

    /**
     * Get glossaryId.
     *
     * @return int
     */
    public function getGlossaryId()
    {
        return $this->glossaryId;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CGlossary
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }
}
