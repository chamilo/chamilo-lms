<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\CourseHomeNotify\Entity;

use Chamilo\CoreBundle\Entity\Course;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'course_home_notify_notification')]
#[ORM\Entity]
class Notification
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(name: 'content', type: 'text')]
    private string $content;

    #[ORM\Column(name: 'expiration_link', type: 'string')]
    private string $expirationLink;

    #[ORM\Column(name: 'hash', type: 'string')]
    private string $hash;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getExpirationLink(): string
    {
        return $this->expirationLink;
    }

    public function setExpirationLink(string $expirationLink): static
    {
        $this->expirationLink = $expirationLink;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): static
    {
        $this->hash = $hash;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): static
    {
        $this->course = $course;

        return $this;
    }
}
