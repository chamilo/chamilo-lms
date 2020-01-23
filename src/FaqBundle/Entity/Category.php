<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;

/**
 * Class Category.
 *
 * @ORM\Entity(repositoryClass="Chamilo\FaqBundle\Repository\CategoryRepository")
 * @ORM\Table(
 *     name="faq_category",
 *     indexes={@ORM\Index(name="is_active_idx", columns={"is_active"})}
 * )
 */
class Category
{
    use TimestampableEntity;
    use TranslationTrait;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Question", mappedBy="category")
     */
    protected $questions;

    /**
     * @Gedmo\SortablePosition
     *
     * @ORM\Column(name="`rank`", type="integer")
     */
    protected $rank;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @param $method
     * @param $arguments
     */
    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

    /**
     * Returns a string representation of this object.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getHeadline();
    }

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
     * Get rank.
     *
     * @return string
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set rank.
     *
     * @param string $rank
     *
     * @return Question
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Set is_active.
     *
     * @param bool $isActive
     *
     * @return Category
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add question.
     *
     * @return Category
     */
    public function addQuestion(Question $question)
    {
        $this->questions[] = $question;

        return $this;
    }

    /**
     * Remove question.
     */
    public function removeQuestion(Question $question)
    {
        $this->questions->removeElement($question);
    }

    /**
     * Get questions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Returns the route name for url generation.
     *
     * @return string
     */
    public function getRouteName()
    {
        return 'faq';
    }

    /**
     * Returns the route parameters for url generation.
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return [
            'categorySlug' => $this->getSlug(),
        ];
    }
}
