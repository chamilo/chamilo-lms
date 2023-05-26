<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'c_quiz_question_category')]
#[ORM\Entity(repositoryClass: \Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository::class)]
class CQuizQuestionCategory extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: false)]
    protected string $title;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    /**
     * @var Collection|CQuizQuestion[]
     */
    #[ORM\ManyToMany(targetEntity: \Chamilo\CourseBundle\Entity\CQuizQuestion::class, mappedBy: 'categories')]
    protected Collection $questions;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function addQuestion(CQuizQuestion $question): void
    {
        if ($this->questions->contains($question)) {
            return;
        }

        $this->questions->add($question);
        $question->addCategory($this);
    }

    public function removeQuestion(CQuizQuestion $question): void
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

    public function getTitle(): string
    {
        return $this->title;
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

    /**
     * @return Collection|CQuizQuestion[]
     */
    public function getQuestions(): Collection|array
    {
        return $this->questions;
    }

    /**
     * @param Collection|CQuizQuestion[] $questions
     */
    public function setQuestions(Collection|array $questions): self
    {
        $this->questions = $questions;

        return $this;
    }

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
