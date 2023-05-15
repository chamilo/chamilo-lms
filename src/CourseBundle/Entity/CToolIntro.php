<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(operations: [new Get(security: 'is_granted(\'VIEW\', object)'), new Put(security: 'is_granted(\'EDIT\', object)'), new Delete(security: 'is_granted(\'DELETE\', object)'), new GetCollection(security: 'is_granted(\'ROLE_USER\')'), new Post(securityPostDenormalize: 'is_granted(\'CREATE\', object)')], security: 'is_granted(\'ROLE_ADMIN\') or is_granted(\'ROLE_CURRENT_COURSE_TEACHER\')', denormalizationContext: ['groups' => ['c_tool_intro:write']], normalizationContext: ['groups' => ['c_tool_intro:read']])]
#[ORM\Table(name: 'c_tool_intro')]
#[ORM\Entity]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['courseTool' => 'exact'])]
class CToolIntro extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface, Stringable
{
    #[Groups(['c_tool_intro:read'])]
    #[ORM\Column(name: 'iid', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $iid;
    #[Assert\NotNull]
    #[Groups(['c_tool_intro:read', 'c_tool_intro:write'])]
    #[ORM\Column(name: 'intro_text', type: 'text', nullable: false)]
    protected string $introText;
    #[Assert\NotNull]
    #[Groups(['c_tool_intro:read', 'c_tool_intro:write'])]
    #[ORM\ManyToOne(targetEntity: CTool::class)]
    #[ORM\JoinColumn(name: 'c_tool_id', referencedColumnName: 'iid', nullable: false, onDelete: 'CASCADE')]
    protected CTool $courseTool;
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
    public function getResourceIdentifier(): int
    {
        return $this->getIid();
    }
    public function getResourceName(): string
    {
        return $this->getCourseTool()->getName();
    }
    public function setResourceName(string $name): self
    {
        return $this;
    }
}
