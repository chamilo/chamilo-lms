<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use Chamilo\CoreBundle\Traits\CourseTrait;
use Doctrine\ORM\Mapping as ORM;
use Stringable;

/**
 * AccessUrlRelCourse.
 */
#[ORM\Table(name: 'access_url_rel_course')]
#[ORM\Entity]
class AccessUrlRelCourse implements EntityAccessUrlInterface, Stringable
{
    use CourseTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Course::class, cascade: ['persist'], inversedBy: 'urls')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id')]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: AccessUrl::class, cascade: ['persist'], inversedBy: 'courses')]
    #[ORM\JoinColumn(name: 'access_url_id', referencedColumnName: 'id')]
    protected ?AccessUrl $url;

    public function __toString(): string
    {
        return '-';
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?AccessUrl
    {
        return $this->url;
    }

    public function setUrl(?AccessUrl $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }
}
