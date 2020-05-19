<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CLpCategory.
 *
 * @ORM\Table(
 *  name="c_lp_category",
 *  indexes={
 *      @ORM\Index(name="course", columns={"c_id"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 */
class CLpCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $iid;

    /**
     * @Gedmo\SortableGroup
     * @ORM\Column(name="c_id", type="integer")
     */
    protected $cId;

    /**
     * @var string
     *
     * @ORM\Column(name="name")
     */
    protected $name;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
    protected $position;

    /**
     * @var CLpCategoryUser[]
     *
     * @ORM\OneToMany(targetEntity="Chamilo\CourseBundle\Entity\CLpCategoryUser", mappedBy="category", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $users;

    /**
     * @var int
     */
    protected $sessionId;

    /**
     * CLpCategory constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->sessionId = 0;
    }

    /**
     * Set cId.
     *
     * @param int $cId
     *
     * @return CLpCategory
     */
    public function setCId($cId)
    {
        $this->cId = $cId;

        return $this;
    }

    /**
     * Get cId.
     *
     * @return int
     */
    public function getCId()
    {
        return $this->cId;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CLpCategory
     */
    public function setId($id)
    {
        $this->iid = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->iid;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get category name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $position
     *
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param int $sessionId
     *
     * @return CLpCategory
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return ArrayCollection|CLpCategoryUser[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param $users
     */
    public function setUsers($users)
    {
        $this->users = new ArrayCollection();

        foreach ($users as $user) {
            $this->addUser($user);
        }
    }

    public function addUser(CLpCategoryUser $categoryUser)
    {
        $categoryUser->setCategory($this);

        if (!$this->hasUser($categoryUser)) {
            $this->users->add($categoryUser);
        }
    }

    /**
     * @return bool
     */
    public function hasUser(CLpCategoryUser $categoryUser)
    {
        if ($this->getUsers()->count()) {
            $criteria = Criteria::create()->where(
                Criteria::expr()->eq("user", $categoryUser->getUser())
            )->andWhere(
                Criteria::expr()->eq("category", $categoryUser->getCategory())
            );

            $relation = $this->getUsers()->matching($criteria);

            return $relation->count() > 0;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasUserAdded($user)
    {
        if ($this->getUsers()->count()) {
            $categoryUser = new CLpCategoryUser();
            $categoryUser->setCategory($this);
            $categoryUser->setUser($user);

            return $this->hasUser($categoryUser);
        }

        return false;
    }

    /**
     * @return $this
     */
    public function removeUsers(CLpCategoryUser $user)
    {
        $this->users->removeElement($user);

        return $this;
    }
}
