<?php

namespace ChamiloLMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelCourseCategory
 *
 * @ORM\Table(name="access_url_rel_course_category")
 * @ORM\Entity
 */
class AccessUrlRelCourseCategory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_url_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_category_id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     */
    private $courseCategoryId;


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
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return AccessUrlRelCourseCategory
     */
    public function setAccessUrlId($accessUrlId)
    {
        $this->accessUrlId = $accessUrlId;

        return $this;
    }

    /**
     * Get accessUrlId
     *
     * @return integer
     */
    public function getAccessUrlId()
    {
        return $this->accessUrlId;
    }

    /**
     * Set courseCategoryId
     *
     * @param integer $courseCategoryId
     * @return AccessUrlRelCourseCategory
     */
    public function setCourseCategoryId($courseCategoryId)
    {
        $this->courseCategoryId = $courseCategoryId;

        return $this;
    }

    /**
     * Get courseCategoryId
     *
     * @return integer
     */
    public function getCourseCategoryId()
    {
        return $this->courseCategoryId;
    }
}
