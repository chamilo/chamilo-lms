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
 *  name="c_quiz_question",
 *  indexes={
 *      @ORM\Index(name="position", columns={"position"})
 *  }
 * )
 * @ORM\Entity()
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
    protected ?string $description;

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
    protected ?string $picture;

    /**
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    protected int $level;

    /**
     * @ORM\Column(name="feedback", type="text", nullable=true)
     */
    protected ?string $feedback;

    /**
     * @ORM\Column(name="extra", type="string", length=255, nullable=true)
     */
    protected ?string $extra;

    /**
     * @ORM\Column(name="question_code", type="string", length=10, nullable=true)
     */
    protected ?string $questionCode;

    /**
     * @var Collection|CQuizQuestionCategory[]
     *
     * @ORM\ManyToMany(targetEntity="Chamilo\CourseBundle\Entity\CQuizQuestionCategory", inversedBy="questions")
     * @ORM\JoinTable(name="c_quiz_question_rel_category",
     *      joinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="iid")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="question_id", referencedColumnName="iid")}
     * )
     */
    protected $categories;

    /**
     * @var Collection|CQuizRelQuestion[]
     *
     * @ORM\OneToMany(targetEntity="CQuizRelQuestion", mappedBy="question", cascade={"persist"})
     */
    protected $relQuizzes;

    /**
     * @ORM\Column(name="mandatory", type="integer")
     */
    protected int $mandatory;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
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

    /**
     * Set question.
     *
     * @param string $question
     */
    public function setQuestion($question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription($description): self
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
     * Set ponderation.
     *
     * @param float $ponderation
     */
    public function setPonderation($ponderation): self
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

    /**
     * Set position.
     *
     * @param int $position
     */
    public function setPosition($position): self
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

    /**
     * Set type.
     *
     * @param int $type
     */
    public function setType($type): self
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

    /**
     * Set picture.
     *
     * @param string $picture
     *
     * @return CQuizQuestion
     */
    public function setPicture($picture)
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

    /**
     * Set level.
     *
     * @param int $level
     *
     * @return CQuizQuestion
     */
    public function setLevel($level)
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

    /**
     * Set extra.
     *
     * @param string $extra
     *
     * @return CQuizQuestion
     */
    public function setExtra($extra)
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

    /**
     * Set questionCode.
     *
     * @param string $questionCode
     *
     * @return CQuizQuestion
     */
    public function setQuestionCode($questionCode)
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

    /**
     * @return string
     */
    public function getFeedback()
    {
        return $this->feedback;
    }

    /**
     * @param string $feedback
     */
    public function setFeedback($feedback): self
    {
        $this->feedback = $feedback;

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
