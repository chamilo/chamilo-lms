<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\UserTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserCourseCategory.
 *
 * @ORM\Table(name="user_course_category", indexes={
 *     @ORM\Index(name="idx_user_c_cat_uid", columns={"user_id"})
 * })
 * @ORM\Entity
 */
class UserCourseCategory
{
    use UserTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\User", inversedBy="userCourseCategories")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected User $user;

    /**
     * @ORM\Column(name="title", type="text", nullable=false)
     */
    protected string $title;

    /**
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    protected ?int $sort = null;

    /**
     * @ORM\Column(name="collapsed", type="boolean", nullable=true)
     */
    protected ?bool $isCollapsed = null;

    /**
     * Set title.
     *
     * @return UserCourseCategory
     */
    public function setTitle(string $title)
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
     * @return UserCourseCategory
     */
    public function setSort(int $sort)
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
