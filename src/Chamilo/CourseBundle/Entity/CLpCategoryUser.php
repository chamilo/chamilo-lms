<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * CLpCategoryUser.
 *
 * @ORM\Table(name="c_lp_category_user")
 * @ORM\Entity
 */
class CLpCategoryUser
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CLpCategory", inversedBy="users")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="iid")
     */
    protected $category;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return CLpCategoryUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return CLpCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param CLpCategory $category
     *
     * @return CLpCategoryUser
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return CLpCategoryUser
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
