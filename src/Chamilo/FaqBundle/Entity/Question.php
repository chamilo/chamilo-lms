<?php

namespace Chamilo\FaqBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Question
 *
 * @ORM\MappedSuperclass
 * @ORM\Entity(repositoryClass="Chamilo\FaqBundle\Entity\QuestionRepository")
 * @ORM\Table(name="faq_question")
 *
 * @package Genj\FaqBundle\Entity
 */
class Question
{
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
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $headline;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $body;

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
     * @Gedmo\Slug(fields={"headline"}, updatable=false)
     * @ORM\Column(type="string", length=50, nullable=false)
     */
    protected $slug;

    /**
     * @var boolean
     * @ORM\Column(name="only_auth_users", type="boolean", nullable=false)
     */
    protected $onlyAuthUsers;

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
     * Set headline
     *
     * @param string $headline
     *
     * @return Question
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * Get headline
     *
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Question
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get rank
     *
     * @return string
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set rank
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
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set createdAt
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
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
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
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Question
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set category
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
     * Get category
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getHeadline();
    }

    /**
     * Returns the route name for url generation
     *
     * @return string
     */
    public function getRouteName()
    {
        return 'faq';
    }

    /**
     * Returns the route parameters for url generation
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return array(
            'categorySlug' => $this->getCategory()->getSlug(),
            'questionSlug' => $this->getSlug()
        );
    }

    /**
     * Returns a string representation of the entity build out of BundleName + EntityName + EntityId
     *
     * @return string
     */
    public function getEntityIdentifier()
    {
        return 'GenjFaqBundle:Question:' . $this->getId();
    }

    /**
     * @return boolean
     */
    public function isOnlyAuthUsers()
    {
        return $this->onlyAuthUsers;
    }

    /**
     * @param boolean $onlyAuthUsers
     * @return Question
     */
    public function setOnlyAuthUsers($onlyAuthUsers)
    {
        $this->onlyAuthUsers = $onlyAuthUsers;

        return $this;
    }


}
