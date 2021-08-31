<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CQuizQuestion.
 *
 * @ORM\Table(
 *     name="c_quiz_question",
 *     indexes={
 *         @ORM\Index(name="position", columns={"position"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CQuizQuestionRepository")
 */
class CQuizQuestion extends AbstractResource implements ResourceInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $iid;

    /**
     * @Assert\NotBlank()
     *
     * @ORM\Column(name="question", type="text", nullable=false)
     */
    protected string $question;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected ?string $description = null;

    /**
     * @ORM\Column(name="ponderation", type="float", precision=6, scale=2, nullable=false, options={"default": 0})
     */
    protected float $ponderation;

    /**
     * @ORM\Column(name="position", type="integer", nullable=false)
     */
    protected int $position;

    /**
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    protected int $type;

    /**
     * @ORM\Column(name="picture", type="string", length=50, nullable=true)
     */
    protected ?string $picture = null;

    /**
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected int $level;

    /**
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    protected ?string $feedback = null;

    /**
     * @ORM\Column(name="extra", type="string", length=255, nullable=true)
     */
    protected ?string $extra = null;

    /**
     * @ORM\Column(name="question_code", type="string", length=10, nullable=true)
     */
    protected ?string $questionCode = null;

    /**
     * @var Collection|CQuizQuestionCategory[]
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestionCategory", inversedBy="questions")
     * @ORM\JoinTable(name="c_quiz_question_rel_category",
     *     joinColumns={
     *         @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="question_id", referencedColumnName="iid")
     *     }
     * )
     */
    protected Collection $categories;

    /**
     * @var Collection|CQuizRelQuestion[]
     *
     * @ORM\OneToMany(targetEntity="CQuizRelQuestion", mappedBy="question", cascade={"persist"})
     */
    protected Collection $relQuizzes;

    /**
     * @var Collection|CQuizAnswer[]
     *
     * @ORM\OneToMany(targetEntity="CQuizAnswer", mappedBy="question", cascade={"persist"})
     */
    protected Collection $answers;

    /**
     * @var Collection|CQuizQuestionOption[]
     *
     * @ORM\OneToMany(targetEntity="CQuizQuestionOption", mappedBy="question", cascade={"persist"})
     */
    protected Collection $options;

    /**
     * @ORM\Column(name="mandatory", type="integer")
     */
    protected int $mandatory;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->relQuizzes = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->options = new ArrayCollection();
        $this->ponderation = 0.0;
        $this->mandatory = 0;
    }

    public function __toString(): string
    {
        return $this->getQuestion();
    }

    public function addCategory(CQuizQuestionCategory $category): void
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories->add($category);
        $category->addQuestion($this);
    }

    public function updateCategory(CQuizQuestionCategory $category): void
    {
        if (0 === $this->categories->count()) {
            $this->addCategory($category);
        }

        if ($this->categories->contains($category)) {
            return;
        }

        foreach ($this->categories as $item) {
            $this->categories->removeElement($item);
        }

        $this->addCategory($category);
    }

    public function removeCategory(CQuizQuestionCategory $category): void
    {
        if (!$this->categories->contains($category)) {
            return;
        }

        $this->categories->removeElement($category);
        $category->removeQuestion($this);
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
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

    public function setPonderation(float $ponderation): self
    {
        $this->ponderation = $ponderation;

        return $this;
    }

    /**
     * Get ponderation.
     *
     * @return float
     */
    public function getPonderation()
    {
        return $this->ponderation;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function setPicture(string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    /**
     * Get picture.
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    public function setExtra(string $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra.
     *
     * @return string
     */
    public function getExtra()
    {
        return $this->extra;
    }

    public function setQuestionCode(string $questionCode): self
    {
        $this->questionCode = $questionCode;

        return $this;
    }

    /**
     * Get questionCode.
     *
     * @return string
     */
    public function getQuestionCode()
    {
        return $this->questionCode;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }

    /**
     * @return CQuizQuestionCategory[]|Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return CQuizRelQuestion[]|Collection
     */
    public function getRelQuizzes()
    {
        return $this->relQuizzes;
    }

    /**
     * @return CQuizAnswer[]|Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    public function getMandatory(): int
    {
        return $this->mandatory;
    }

    /**
     * @return CQuizQuestionOption[]|Collection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param CQuizQuestionOption[]|Collection $options
     */
    public function setOptions(Collection $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get iid.
     *
     * @return int
     */
    public function getIid()
    {
        return $this->iid;
    }

    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getQuestion();
    }

    public function setResourceName(string $name): self
    {
        return $this->setQuestion($name);
    }
}
