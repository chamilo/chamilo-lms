<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelCourseCategory.
 *
 * @ORM\Table(name="access_url_rel_course_category")
 * @ORM\Entity
 */
class AccessUrlRelCourseCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AccessUrl
     *
     * @ORM\ManyToOne(targetEntity="AccessUrl", inversedBy="courseCategory", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected $url;

    /**
     * @var CourseCategory
     *
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\CourseCategory", inversedBy="urls", cascade={"persist"})
     * @ORM\JoinColumn(name="course_category_id", referencedColumnName="id")
     */
    protected $courseCategory;

    /**
     * @return AccessUrl
     */
    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    /**
     * @param AccessUrl $url
     *
     * @return AccessUrlRelCourseCategory
     */
    public function setUrl(AccessUrl $url): AccessUrlRelCourseCategory
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return CourseCategory
     */
    public function getCourseCategory(): CourseCategory
    {
        return $this->courseCategory;
    }

    /**
     * @param CourseCategory $courseCategory
     *
     * @return AccessUrlRelCourseCategory
     */
    public function setCourseCategory(CourseCategory $courseCategory): AccessUrlRelCourseCategory
    {
        $this->courseCategory = $courseCategory;

        return $this;
    }
}
