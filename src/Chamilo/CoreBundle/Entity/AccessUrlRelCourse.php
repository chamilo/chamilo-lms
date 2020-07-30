<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelCourse.
 *
 * @ORM\Table(name="access_url_rel_course")
 * @ORM\Entity
 */
class AccessUrlRelCourse
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", precision=0, scale=0, nullable=false, unique=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Course",
     *     inversedBy="urls",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected $course;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\AccessUrl",
     *     inversedBy="courses",
     *     cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected $url;

    /**
     * @return string
     */
    public function __toString()
    {
        return '-';
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
     * Set url.
     *
     * @param $url
     *
     * @return AccessUrlRelCourse
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param Course $course
     *
     * @return AccessUrlRelCourse
     */
    public function setCourse($course)
    {
        $this->course = $course;
        $this->course->getUrls()->add($this);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCourse()
    {
        return $this->course;
    }
}
