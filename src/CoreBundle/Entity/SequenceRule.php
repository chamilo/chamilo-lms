<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class SequenceRule.
 *
 * @ORM\Table(name="sequence_rule")
 * @ORM\Entity
 */
class SequenceRule
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return SequenceRule
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }
}
