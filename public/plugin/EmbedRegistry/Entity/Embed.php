<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Entity\EmbedRegistry;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'plugin_embed_registry_embed')]
class Embed
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(name: 'title', type: 'text')]
    private string $title;

    #[ORM\Column(name: 'display_start_date', type: 'datetime')]
    private \DateTime $displayStartDate;

    #[ORM\Column(name: 'display_end_date', type: 'datetime')]
    private \DateTime $displayEndDate;

    #[ORM\Column(name: 'html_code', type: 'text')]
    private string $htmlCode;

    #[ORM\ManyToOne(targetEntity: Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false)]
    private Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    private ?Session $session;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDisplayStartDate(): \DateTime
    {
        return $this->displayStartDate;
    }

    public function setDisplayStartDate(\DateTime $displayStartDate): static
    {
        $this->displayStartDate = $displayStartDate;

        return $this;
    }

    public function getDisplayEndDate(): \DateTime
    {
        return $this->displayEndDate;
    }

    public function setDisplayEndDate(\DateTime $displayEndDate): static
    {
        $this->displayEndDate = $displayEndDate;

        return $this;
    }

    public function getHtmlCode(): string
    {
        return $this->htmlCode;
    }

    public function setHtmlCode(string $htmlCode): static
    {
        $this->htmlCode = $htmlCode;

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

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session = null): static
    {
        $this->session = $session;

        return $this;
    }
}
