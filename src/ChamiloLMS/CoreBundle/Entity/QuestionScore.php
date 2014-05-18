<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="question_score")
 * @ORM\Entity(repositoryClass="ChamiloLMS\CoreBundle\Entity\Repository\QuestionScoreRepository")
 */
class QuestionScore
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * cascade options remove: Cascades remove operations to the associated entities.
     *                 detach: Cascades detach operations to the associated entities.
     * @ORM\OneToMany(targetEntity="QuestionScoreName", mappedBy="questionScore", cascade={"persist", "remove"} )
     */
    private $items;

    public function __construct()
    {
    }

    public function getItems()
    {
        return $this->items;
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
     * Set name
     *
     * @param string $name
     * @return QuestionScore
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
}
