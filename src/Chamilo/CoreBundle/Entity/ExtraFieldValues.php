<?php

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Sylius\Component\Attribute\Model\AttributeValue as BaseAttributeValue;

/**
 * ExtraFieldValues
 *
 * @ORM\MappedSuperclass
 */
class ExtraFieldValues extends BaseAttributeValue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tms", type="datetime", precision=0, scale=0, nullable=false, unique=false)
     */
    protected $tms;

    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="comment", type="string", precision=0, scale=0, nullable=true, unique=false)
     */
    protected $comment;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tms = new \DateTime();
    }

     /**
     * Set comment
     *
     * @param string $comment
     * @return ExtraFieldValues
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
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
     * Set tms
     *
     * @param \DateTime $tms
     * @return ExtraFieldValues
     */
    public function setTms($tms)
    {
        $this->tms = $tms;

        return $this;
    }

    /**
     * Get tms
     *
     * @return \DateTime
     */
    public function getTms()
    {
        return $this->tms;
    }
}
