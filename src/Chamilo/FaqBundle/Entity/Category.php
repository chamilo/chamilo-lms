<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Category.
 *
 * @ORM\Entity(repositoryClass="Chamilo\FaqBundle\Entity\CategoryRepository")
 * @ORM\Table(
 *     name="faq_category",
 *     indexes={@ORM\Index(name="is_active_idx", columns={"is_active"})}
 * )
 *
 * @package Chamilo\FaqBundle\Entity
 */
class Category
{
    use ORMBehaviors\Translatable\Translatable;

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
     * @ORM\Column(name="rank", type="integer")
     */
    protected $rank;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed
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
        return $this->getHeadline();
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
     * Set createdAt.
     *
     * @return Category
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt.
     *
     * @return Category
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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
