<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * AccessUrlRelCourse.
 *
 * @ORM\Table(name="access_url_rel_course")
 * @ORM\Entity
 */
class AccessUrlRelCourse
{
    use CourseTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="urls", cascade={"persist"})
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id")
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="AccessUrl", inversedBy="courses", cascade={"persist"})
     * @ORM\JoinColumn(name="access_url_id", referencedColumnName="id")
     */
    protected AccessUrl $url;

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

    public function setUrl(AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): AccessUrl
    {
        return $this->url;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }
}
