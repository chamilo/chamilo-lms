<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Traits\ShowCourseResourcesInSessionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * CQuizQuestionCategory.
 *
 * @ORM\Table(
 *  name="c_quiz_question_category",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity
 */
class CQuizQuestionCategory extends AbstractResource implements ResourceInterface
{
    use ShowCourseResourcesInSessionTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session", cascade={"persist"})
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true)
     */
    protected $session;

    /**
     * @var Collection|CQuizQuestion[]
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestion", mappedBy="categories")
     */
    protected $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function addQuestion(CQuizQuestion $question)
    {
        if ($this->questions->contains($question)) {
            return;
        }

        $this->questions->add($question);
        $question->addCategory($this);
    }

    public function removeQuestion(CQuizQuestion $question)
    {
        if (!$this->questions->contains($question)) {
            return;
        }

        $this->questions->removeElement($question);
        $question->removeCategory($this);
    }

    public function getIid(): int
    {
        return $this->iid;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->title;
    }

    public function setDescription(string $description): self
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

    public function getCourse()
    {
        return $this->course;
    }

    /**
     * @return CQuizQuestionCategory
     */
    public function setCourse($course)
    {
        $this->course = $course;

        return $this;
    }

    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     *
     * @return CQuizQuestionCategory
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSession()
    {
        return null !== $this->session;
    }

    /**
     * @ORM\PostPersist()
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        // Update id with iid value
        /*$em = $args->getEntityManager();
        $em->persist($this);
        $em->flush();*/
    }

    /**
     * @return CQuizQuestion[]|Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param CQuizQuestion[]|Collection $questions
     *
     * @return CQuizQuestionCategory
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;

        return $this;
    }

    /**
     * Resource identifier.
     */
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }
}
