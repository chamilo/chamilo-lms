<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCourseCategory.
 *
 * @ORM\Table(name="user_course_category", indexes={@ORM\Index(name="idx_user_c_cat_uid", columns={"user_id"})})
 * @ORM\Entity
 */
class UserCourseCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

//    /**
//     * @var int
//     *
//     * @ORM\Column(name="user_id", type="integer", nullable=false)
//     */
//    protected $userId;
    /**
     * @ORM\OneToOne (targetEntity="Chamilo\CoreBundle\Entity\User",
     *      inversedBy="user_course_category")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
    /**
     * @var string
     *
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected $title;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    protected $sort;

    /**
     * @var bool
     *
     * @ORM\Column(name="collapsed", type="boolean", nullable=true)
     */
    protected $isCollapsed;

//    /**
//     * Set userId.
//     *
//     * @param int $userId
//     *
//     * @return UserCourseCategory
//     */
//    public function setUserId($userId)
//    {
//        $this->userId = $userId;
//
//        return $this;
//    }
//
//    /**
//     * Get userId.
//     *
//     * @return int
//     */
//    public function getUserId()
//    {
//        return $this->userId;
//    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return UserCourseCategory
     */
    public function setTitle($title)
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
        return $this->title;
    }

    /**
     * Set sort.
     *
     * @param int $sort
     *
     * @return UserCourseCategory
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
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
}
