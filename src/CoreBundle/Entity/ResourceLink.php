<?php

declare (strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Chamilo\CoreBundle\Traits\TimestampableTypedEntity;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Symfony\Component\Serializer\Annotation\Groups;
#[ApiResource]
#[ORM\Table(name: 'resource_link')]
#[ORM\Entity]
#[ApiResource(uriTemplate: '/access_urls/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\AccessUrl::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/access_urls/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\AccessUrl::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/courses/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Course::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/courses/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Course::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/illustrations/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Illustration::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/illustrations/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Illustration::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/message_attachments/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\MessageAttachment::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/message_attachments/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\MessageAttachment::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/personal_files/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\PersonalFile::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/personal_files/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\PersonalFile::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/resource_nodes/{id}/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: ['id'])], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/resource_nodes/{id}/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: ['id']), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/track_course_rankings/{id}/course/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\TrackCourseRanking::class, identifiers: ['id']), 'course' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Course::class, identifiers: [], expandedValue: 'course'), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/track_course_rankings/{id}/course/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\TrackCourseRanking::class, identifiers: ['id']), 'course' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Course::class, identifiers: [], expandedValue: 'course'), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/usergroups/{id}/resource_node/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Usergroup::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/usergroups/{id}/resource_node/parent/resource_links.{_format}', uriVariables: ['id' => new Link(fromClass: \Chamilo\CoreBundle\Entity\Usergroup::class, identifiers: ['id']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_calendar_events/{iid}/resource_node/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CCalendarEvent::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_calendar_events/{iid}/resource_node/parent/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CCalendarEvent::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/documents/{iid}/resource_node/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CDocument::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/documents/{iid}/resource_node/parent/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CDocument::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_groups/{iid}/resource_node/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CGroup::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_groups/{iid}/resource_node/parent/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CGroup::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_tools/{iid}/resource_node/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CTool::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_tools/{iid}/resource_node/parent/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CTool::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_tool_intros/{iid}/resource_node/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CToolIntro::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node')], status: 200, operations: [new GetCollection()])]
#[ApiResource(uriTemplate: '/c_tool_intros/{iid}/resource_node/parent/resource_links.{_format}', uriVariables: ['iid' => new Link(fromClass: \Chamilo\CourseBundle\Entity\CToolIntro::class, identifiers: ['iid']), 'resourceNode' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'resource_node'), 'parent' => new Link(fromClass: \Chamilo\CoreBundle\Entity\ResourceNode::class, identifiers: [], expandedValue: 'parent')], status: 200, operations: [new GetCollection()])]
class ResourceLink implements \Stringable
{
    use TimestampableTypedEntity;
    public const VISIBILITY_DRAFT = 0;
    public const VISIBILITY_PENDING = 1;
    public const VISIBILITY_PUBLISHED = 2;
    public const VISIBILITY_DELETED = 3;
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue]
    protected ?int $id = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\ResourceNode::class, inversedBy: 'resourceLinks')]
    #[ORM\JoinColumn(name: 'resource_node_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ResourceNode $resourceNode;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Course::class)]
    #[ORM\JoinColumn(name: 'c_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Course $course = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Session::class, inversedBy: 'resourceLinks')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Session $session = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CourseBundle\Entity\CGroup::class)]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'iid', nullable: true, onDelete: 'CASCADE')]
    protected ?CGroup $group = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\Usergroup::class)]
    #[ORM\JoinColumn(name: 'usergroup_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Usergroup $userGroup = null;
    #[ORM\ManyToOne(targetEntity: \Chamilo\CoreBundle\Entity\User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?User $user = null;
    /**
     * @var Collection|ResourceRight[]
     */
    #[ORM\OneToMany(targetEntity: \Chamilo\CoreBundle\Entity\ResourceRight::class, mappedBy: 'resourceLink', cascade: ['persist', 'remove'], orphanRemoval: true)]
    protected Collection $resourceRights;
    #[Groups(['ctool:read', 'c_tool_intro:read'])]
    #[ORM\Column(name: 'visibility', type: 'integer', nullable: false)]
    protected int $visibility;
    #[Groups(['resource_node:read', 'resource_node:write', 'document:write', 'document:read'])]
    #[ORM\Column(name: 'start_visibility_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $startVisibilityAt = null;
    #[Groups(['resource_node:read', 'resource_node:write', 'document:write', 'document:read'])]
    #[ORM\Column(name: 'end_visibility_at', type: 'datetime', nullable: true)]
    protected ?DateTimeInterface $endVisibilityAt = null;
    public function __construct()
    {
        $this->resourceRights = new ArrayCollection();
        $this->visibility = self::VISIBILITY_DRAFT;
    }
    public function __toString() : string
    {
        return (string) $this->getId();
    }
    public function getStartVisibilityAt() : ?DateTimeInterface
    {
        return $this->startVisibilityAt;
    }
    public function setStartVisibilityAt(?DateTimeInterface $startVisibilityAt) : self
    {
        $this->startVisibilityAt = $startVisibilityAt;
        return $this;
    }
    public function getEndVisibilityAt() : ?DateTimeInterface
    {
        return $this->endVisibilityAt;
    }
    public function setEndVisibilityAt(?DateTimeInterface $endVisibilityAt) : self
    {
        $this->endVisibilityAt = $endVisibilityAt;
        return $this;
    }
    /**
     * @param ResourceRight[]|Collection $rights
     */
    public function setResourceRights(array|\Doctrine\Common\Collections\Collection $rights) : self
    {
        $this->resourceRights = $rights;
        /*foreach ($rights as $right) {
              $this->addResourceRight($right);
          }*/
        return $this;
    }
    public function addResourceRight(ResourceRight $right) : self
    {
        if (!$this->resourceRights->contains($right)) {
            $right->setResourceLink($this);
            $this->resourceRights->add($right);
        }
        return $this;
    }
    /**
     * @return Collection|ResourceRight[]
     */
    public function getResourceRights() : \Doctrine\Common\Collections\Collection|array
    {
        return $this->resourceRights;
    }
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    public function setUser(User $user = null) : self
    {
        $this->user = $user;
        return $this;
    }
    public function getCourse() : ?Course
    {
        return $this->course;
    }
    public function setCourse(Course $course = null) : self
    {
        $this->course = $course;
        return $this;
    }
    public function getSession() : ?Session
    {
        return $this->session;
    }
    public function setSession(Session $session = null) : self
    {
        $this->session = $session;
        return $this;
    }
    public function hasSession() : bool
    {
        return null !== $this->session;
    }
    public function hasCourse() : bool
    {
        return null !== $this->course;
    }
    public function hasGroup() : bool
    {
        return null !== $this->group;
    }
    public function getGroup() : ?CGroup
    {
        return $this->group;
    }
    public function setGroup(CGroup $group = null) : self
    {
        $this->group = $group;
        return $this;
    }
    public function getUserGroup() : ?Usergroup
    {
        return $this->userGroup;
    }
    public function setUserGroup(Usergroup $group = null) : self
    {
        $this->userGroup = $group;
        return $this;
    }
    public function getUser() : ?User
    {
        return $this->user;
    }
    public function hasUser() : bool
    {
        return null !== $this->user;
    }
    public function setResourceNode(ResourceNode $resourceNode) : self
    {
        $this->resourceNode = $resourceNode;
        return $this;
    }
    public function getResourceNode() : ResourceNode
    {
        return $this->resourceNode;
    }
    public function getVisibility() : int
    {
        return $this->visibility;
    }
    public function setVisibility(int $visibility) : self
    {
        if (!\in_array($visibility, self::getVisibilityList(), true)) {
            $message = sprintf('The visibility is not valid. Valid options: %s', print_r(self::getVisibilityList(), true));
            throw new LogicException($message);
        }
        $this->visibility = $visibility;
        return $this;
    }
    public function isPublished() : bool
    {
        return self::VISIBILITY_PUBLISHED === $this->getVisibility();
    }
    public function isPending() : bool
    {
        return self::VISIBILITY_PENDING === $this->getVisibility();
    }
    public function isDraft() : bool
    {
        return self::VISIBILITY_DRAFT === $this->getVisibility();
    }
    public static function getVisibilityList() : array
    {
        return ['Draft' => self::VISIBILITY_DRAFT, 'Pending' => self::VISIBILITY_PENDING, 'Published' => self::VISIBILITY_PUBLISHED, 'Deleted' => self::VISIBILITY_DELETED];
    }
    public function getVisibilityName() : string
    {
        return array_flip(static::getVisibilityList())[$this->getVisibility()];
    }
}
