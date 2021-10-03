<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceShowCourseResourcesInSessionInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     name="c_tool_intro",
 *     indexes={
 *     }
 * )
 * @ORM\Entity
 */
#[ApiResource(
    collectionOperations: [
        'get' => [
            //'security' => "is_granted('VIEW', object)",  // the get collection is also filtered by MessageExtension.php
            'security' => "is_granted('ROLE_USER')",
        ],
        'post' => [
            'security_post_denormalize' => "is_granted('CREATE', object)",
        ],
    ],
    itemOperations: [
        'get' => [
            'security' => "is_granted('VIEW', object)",
        ],
        'put' => [
            'security' => "is_granted('EDIT', object)",
        ],
        'delete' => [
            'security' => "is_granted('DELETE', object)",
        ],
    ],
    attributes: [
        'security' => "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER')",
    ],
    denormalizationContext: [
        'groups' => ['c_tool_intro:write'],
    ],
    normalizationContext: [
        'groups' => ['c_tool_intro:read'],
    ],
)]

#[ApiFilter(SearchFilter::class, properties: [
    'courseTool' => 'exact',
])]
class CToolIntro extends AbstractResource implements ResourceInterface, ResourceShowCourseResourcesInSessionInterface
{
    /**
     * @ORM\Column(name="iid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[Groups(['c_tool_intro:read'])]
    protected int $iid;

    /**
     * @ORM\Column(name="intro_text", type="text", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['c_tool_intro:read', 'c_tool_intro:write'])]
    protected string $introText;

    /**
     * @ORM\ManyToOne(targetEntity="Chamilo\CourseBundle\Entity\CTool")
     * @ORM\JoinColumn(name="c_tool_id", referencedColumnName="iid", nullable=false)
     */
    #[Assert\NotNull]
    #[Groups(['c_tool_intro:read', 'c_tool_intro:write'])]
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
        return substr(strip_tags($this->getIntroText()), 0, 10);
    }

    public function setResourceName(string $name): self
    {
        return $this;
    }
}
