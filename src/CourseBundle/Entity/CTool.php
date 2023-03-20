<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tool;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *     name="c_tool",
 *     indexes={
 *         @ORM\Index(name="course", columns={"c_id"}),
 *         @ORM\Index(name="session_id", columns={"session_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Chamilo\CourseBundle\Repository\CToolRepository")
 */
#[ApiResource(
    attributes: [
        'security' => "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')",
    ],
    denormalizationContext: [
        'groups' => ['ctool:write'],
    ],
    normalizationContext: [
        'groups' => ['ctool:read'],
    ],
)]
class CTool extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[Groups(['ctool:read'])]
    protected ?int $iid = null;

    /**
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    #[Assert\NotBlank]
    #[Groups(['ctool:read'])]
    protected string $name;

    /**
     * @ORM\Column(name="visibility", type="boolean", nullable=true)
     */
    #[Groups(['ctool:read'])]
    protected ?bool $visibility = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Course", inversedBy="tools")
     * @ORM\JoinColumn(name="c_id", referencedColumnName="id", nullable=false)
     */
    protected Course $course;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Session")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected ?Session $session = null;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CoreBundle\Entity\Tool")
     * @ORM\JoinColumn(name="tool_id", referencedColumnName="id", nullable=false)
     */
    protected Tool $tool;

    /**
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer")
     */
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

    public function getNameToTranslate(): string
    {
        return ucfirst(str_replace('_', ' ', $this->name));
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

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getCourse(): Course
    {
        return $this->course;
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

    public function setVisibility(bool $visibility): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function getVisibility(): ?bool
    {
        return $this->visibility;
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
