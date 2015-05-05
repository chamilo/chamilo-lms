<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class ExtraField
 *
 * @ORM\Entity
 * @ORM\Table(name="extra_field_options")
 *
 * @ORM\MappedSuperclass
 */
class ExtraFieldOptions
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\ExtraField", inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="display_text", type="string", length=64, nullable=true)
     */
    private $displayText;

    /**
     * @var integer
     *
     * @ORM\Column(name="optionOrder", type="integer", nullable=true)
     */
    private $optionOrder;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getOptionOrder()
    {
        return $this->optionOrder;
    }

    /**
     * @param int $optionOrder
     */
    public function setOptionOrder($optionOrder)
    {
        $this->optionOrder = $optionOrder;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getDisplayText()
    {
        return $this->displayText;
    }

    /**
     * @param string $displayText
     */
    public function setDisplayText($displayText)
    {
        $this->displayText = $displayText;
    }
}
