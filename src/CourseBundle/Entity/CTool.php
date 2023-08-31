<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['ctool:read']],
    denormalizationContext: ['groups' => ['ctool:write']],
    security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')"
)]
#[ORM\Table(name: 'c_tool')]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CToolRepository::class)]
class CTool extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[Groups(['ctool:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[Groups(['ctool:read'])]
    #[ORM\Column(name: 'name', type: 'text', nullable: false)]
    protected string $name;

    #[Groups(['ctool:read'])]
    #[ORM\Column(name: 'visibility', type: 'boolean', nullable: true)]
    protected ?bool $visibility = null;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'tools')]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Gedmo\SortableGroup]
    protected Course $course;

    #[ORM\ManyToOne(targetEntity: Session::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    #[Gedmo\SortableGroup]
    protected ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: Tool::class)]
    #[ORM\JoinColumn(name: 'tool_id', referencedColumnName: 'id', nullable: false)]
    protected Tool $tool;

    #[Gedmo\SortablePosition]
    #[ORM\Column(name: 'position', type: 'integer')]
    protected int $position;

    #[Groups(['ctool:read'])]
    protected string $nameToTranslate;

    public function __construct()
    {
        $this->visibility = true;
        $this->position = 0;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameToTranslate(): string
    {
        return ucfirst(str_replace('_', ' ', $this->name));
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(Session $session = null): self
    {
        $this->session = $session;

        return $this;
    }

    public function getVisibility(): ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getTool(): Tool
    {
        return $this->tool;
    }

    public function setTool(Tool $tool): self
    {
        $this->tool = $tool;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getResourceIdentifier(): int
    {
        return $this->iid;
    }

    public function getResourceName(): string
    {
        return $this->getName();
    }

    public function setResourceName(string $name): self
    {
        return $this->setName($name);
    }
}
