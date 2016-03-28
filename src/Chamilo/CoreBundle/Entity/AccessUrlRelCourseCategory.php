<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

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
     * @ORM\Column(name="access_url_id", type="integer")
     */
    private $accessUrlId;

    /**
     * @var integer
     *
     * @ORM\Column(name="course_category_id", type="integer")
     */
    private $courseCategoryId;

    /**
     * Set accessUrlId
     *
     * @param integer $accessUrlId
     * @return AccessUrlRelSession
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
     * @return int
     */
    public function getCourseCategoryId()
    {
        return $this->courseCategoryId;
    }

    /**
     * @param int $courseCategoryId
     * @return AccessUrlRelCourseCategory
     */
    public function setCourseCategoryId($courseCategoryId)
    {
        $this->courseCategoryId = $courseCategoryId;

        return $this;
    }
}
