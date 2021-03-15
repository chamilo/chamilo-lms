<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Doctrine\ORM\Mapping as ORM;

/**
 * UsergroupRelQuestion.
 *
 * @ORM\Table(name="usergroup_rel_question")
 * @ORM\Entity
 */
class UsergroupRelQuestion
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestion")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="iid", onDelete="CASCADE")
     */
    protected CQuizQuestion $question;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Usergroup", inversedBy="questions")
     * @ORM\JoinColumn(name="usergroup_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected Usergroup $usergroup;

    /**
     * @ORM\Column(name="coefficient", type="float", precision=6, scale=2, nullable=true)
     */
    protected ?float $coefficient = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setCoefficient(float $coefficient): self
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    /**
     * Get coefficient.
     *
     * @return float
     */
    public function getCoefficient()
    {
        return $this->coefficient;
    }

    public function getQuestion(): CQuizQuestion
    {
        return $this->question;
    }

    public function setQuestion(CQuizQuestion $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getUsergroup(): Usergroup
    {
        return $this->usergroup;
    }

    public function setUsergroup(Usergroup $usergroup): self
    {
        $this->usergroup = $usergroup;

        return $this;
    }
}
