<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Chamilo\CoreBundle\Filter\CidFilter;
use Chamilo\CoreBundle\Filter\SidFilter;
use Chamilo\CoreBundle\State\CToolIntroCurrentProvider;
use Chamilo\CoreBundle\State\CToolIntroStateProcessor;
use Chamilo\CoreBundle\State\CToolIntroStateProvider;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/c_tool_intros/current.{_format}',
            normalizationContext: [
                'groups' => ['c_tool_intro:current'],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            provider: CToolIntroCurrentProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                    required: false,
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                    required: false,
                ),
                'tool' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Course tool title (defaults to course_homepage)',
                    required: false,
                ),
            ],
        ),
        new Get(security: "is_granted('VIEW', object.resourceNode)"),
        new Put(
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                    required: false,
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                    required: false,
                ),
            ],
        ),
        new GetCollection(
            openapi: new Operation(
                parameters: [
                    new OpenApiParameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course identifier',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                    required: false,
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                    required: false,
                ),
            ],
        ),
        new Post(
            denormalizationContext: [
                'groups' => ['c_tool_intro:create'],
            ],
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                    required: false,
                ),
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                    required: false,
                ),
            ],
        ),
    ],
    normalizationContext: [
        'groups' => ['c_tool_intro:read'],
    ],
    denormalizationContext: [
        'groups' => ['c_tool_intro:update'],
    ],
    provider: CToolIntroStateProvider::class,
    processor: CToolIntroStateProcessor::class,
)]
#[ORM\Table(name: 'c_tool_intro')]
#[ORM\Entity(repositoryClass: CToolIntroRepository::class)]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['courseTool' => 'exact'])]
#[ApiFilter(filterClass: CidFilter::class)]
#[ApiFilter(filterClass: SidFilter::class)]
class CToolIntro extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[Groups(['c_tool_intro:read', 'c_tool_intro:current'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $iid = null;

    #[Assert\NotNull]
    #[Groups(['c_tool_intro:read', 'c_tool_intro:update', 'c_tool_intro:create', 'c_tool_intro:current'])]
    #[ORM\Column(name: 'intro_text', type: 'text', nullable: false)]
    protected string $introText;

    /**
     * Transient flag (not persisted): the active intro is inherited from the base
     * course, and editing it inside a session should create a session-specific fork.
     */
    #[Groups(['c_tool_intro:current'])]
    private bool $createInSession = false;

    #[Groups(['c_tool_intro:read'])]
    #[ORM\ManyToOne(targetEntity: CTool::class)]
    #[ORM\JoinColumn(name: 'c_tool_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CTool $courseTool;

    #[Groups(['c_tool_intro:create'])]
    private string $toolName;

    public function __toString(): string
    {
        return $this->getIntroText();
    }

    public function getIid(): ?int
    {
        return $this->iid;
    }

    public function getCourseTool(): CTool
    {
        return $this->courseTool;
    }

    public function setCourseTool(CTool $courseTool): self
    {
        $this->courseTool = $courseTool;

        return $this;
    }

    public function setIntroText(string $introText): self
    {
        $this->introText = $introText;

        return $this;
    }

    public function getIntroText(): string
    {
        return $this->introText;
    }

    public function getResourceIdentifier(): int|Uuid
    {
        return $this->getIid();
    }

    public function getResourceName(): string
    {
        return $this->getCourseTool()->getTitle();
    }

    public function setResourceName(string $name): self
    {
        return $this;
    }

    public function isCreateInSession(): bool
    {
        return $this->createInSession;
    }

    public function setCreateInSession(bool $createInSession): self
    {
        $this->createInSession = $createInSession;

        return $this;
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function setToolName(string $toolName): self
    {
        $this->toolName = $toolName;

        return $this;
    }
}
