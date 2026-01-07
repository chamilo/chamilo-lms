<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Chamilo\CoreBundle\ApiResource\CourseTool;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Chamilo\CoreBundle\Filter\CidFilter;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\CToolStateProvider;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            output: CourseTool::class,
            provider: CToolStateProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['ctool:read']],
)]
#[ORM\Table(name: 'c_tool')]
#[ORM\Index(columns: ['c_id'], name: 'course')]
#[ORM\Index(columns: ['session_id'], name: 'session_id')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CToolRepository::class)]
#[ApiFilter(CidFilter::class)]
#[ApiFilter(SidFilter::class)]
#[ApiFilter(OrderFilter::class, properties: ['position' => 'ASC'])]
class CTool extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotBlank]
    #[ORM\Column(name: 'title', type: 'text', nullable: false)]
    protected string $title;

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

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getNameToTranslate(): string
    {
        return ucfirst(str_replace('_', ' ', $this->title));
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
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

    public function setSession(?Session $session = null): self
    {
        $this->session = $session;

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
        return $this->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this->setTitle($name);
    }

    /**
     * Compatibility getter.
     * Source of truth is ResourceLink.visibility (Published/Draft/Pending).
     */
    #[Groups(['ctool:read'])]
    public function getVisibility(): bool
    {
        $link = $this->getContextResourceLink();

        // Backward compatible default: tools were "visible" by default
        if (null === $link) {
            return true;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    /**
     * Compatibility setter.
     *
     * @deprecated Visibility is stored in ResourceLink.visibility.
     */
    public function setVisibility(bool $visibility): self
    {
        $link = $this->getContextResourceLink();
        if (null === $link) {
            // No resource link yet. The caller usually sets it through addCourseLink() next.
            return $this;
        }

        $link->setVisibility(
            $visibility ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT
        );

        return $this;
    }

    private function getContextResourceLink(): ?ResourceLink
    {
        $node = $this->getResourceNode();
        if (null === $node) {
            return null;
        }

        $links = $node->getResourceLinks();
        if ($links->isEmpty()) {
            return null;
        }

        $courseId = $this->course?->getId();
        $sessionId = $this->session?->getId();

        foreach ($links as $link) {
            if (null !== $courseId && $link->getCourse()?->getId() !== $courseId) {
                continue;
            }

            // Session context preferred when CTool.session is set
            if (null !== $sessionId) {
                if ($link->getSession()?->getId() === $sessionId) {
                    return $link;
                }

                continue;
            }

            // Course context (no session)
            if (null === $link->getSession()) {
                return $link;
            }
        }

        // Fallback to first link (should not happen often)
        return $links->first() ?: null;
    }
}
