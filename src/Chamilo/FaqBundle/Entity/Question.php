<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\FaqBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;

/**
 * Class Question.
 *
 * @ORM\Entity(repositoryClass="Chamilo\FaqBundle\Entity\QuestionRepository")
 * @ORM\Table(name="faq_question")
 *
 * @package Chamilo\FaqBundle\Entity
 */
class Question
{
    use ORMBehaviors\Translatable\Translatable;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="questions")
     * @ORM\OrderBy({"rank" = "asc"})
     */
    protected $category;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="rank", type="integer")
     */
    protected $rank;

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
     * @var bool
     * @ORM\Column(name="only_auth_users", type="boolean", nullable=false)
     */
    protected $onlyAuthUsers;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive;

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
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Question
     */
    public function setCreatedAt($createdAt)
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
     * @param \DateTime $updatedAt
     *
     * @return Question
     */
    public function setUpdatedAt($updatedAt)
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
     * Set category.
     *
     * @param Category $category
     *
     * @return Question
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
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
            'categorySlug' => $this->getCategory()->getSlug(),
            'questionSlug' => $this->getSlug(),
        ];
    }

    /**
     * Returns a string representation of the entity build out of BundleName + EntityName + EntityId.
     *
     * @return string
     */
    public function getEntityIdentifier()
    {
        return 'GenjFaqBundle:Question:'.$this->getId();
    }

    /**
     * @return bool
     */
    public function isOnlyAuthUsers()
    {
        return $this->onlyAuthUsers;
    }

    /**
     * @param bool $onlyAuthUsers
     *
     * @return Question
     */
    public function setOnlyAuthUsers($onlyAuthUsers)
    {
        $this->onlyAuthUsers = $onlyAuthUsers;

        return $this;
    }

    /**
     * Set is_active.
     *
     * @param bool $isActive
     *
     * @return Question
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
}
