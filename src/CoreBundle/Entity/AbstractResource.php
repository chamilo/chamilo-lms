<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Traits\UserCreatorTrait;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 */
abstract class AbstractResource
{
    use UserCreatorTrait;

    /**
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"resource_file:read", "resource_node:read", "document:read", "media_object_read"})
     */
    public ?string $contentUrl = null;

    /**
     * Download URL of the Resource File Property set by ResourceNormalizer.php.
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"resource_file:read", "resource_node:read", "document:read", "media_object_read"})
     */
    public ?string $downloadUrl = null;

    /**
     * Content from ResourceFile - Property set by ResourceNormalizer.php.
     *
     * @Groups({"resource_file:read", "resource_node:read", "document:read", "document:write", "media_object_read"})
     */
    public ?string $contentFile = null;

    /**
     * Resource illustration URL - Property set by ResourceNormalizer.php.
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({
     *     "resource_node:read",
     *     "document:read",
     *     "media_object_read",
     *     "course:read",
     *     "session:read",
     *     "course_rel_user:read",
     *     "session_rel_course_rel_user:read"
     * })
     */
    public ?string $illustrationUrl = null;

    /**
     * @Assert\Valid()
     * @ApiSubresource()
     * @Groups({"resource_node:read", "resource_node:write", "personal_file:write", "document:write", "ctool:read", "course:read", "illustration:read"})
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\ResourceNode",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public ?ResourceNode $resourceNode = null;

    /**
     * This property is used when using api platform.
     *
     * @Groups({"resource_node:read", "resource_node:write", "document:read", "document:write"})
     */
    public ?int $parentResourceNode = 0;

    /**
     * @ApiProperty(iri="http://schema.org/image")
     */
    public ?UploadedFile $uploadFile = null;

    /**
     * @var AbstractResource|ResourceInterface
     */
    public $parentResource;

    /**
     * @Groups({"resource_node:read", "document:read"})
     */
    public ?array $resourceLinkListFromEntity = null;

    /**
     * Use when sending a request to Api platform.
     * Temporal array that saves the resource link list that will be filled by CreateDocumentFileAction.php.
     */
    public array $resourceLinkList = [];

    /**
     * Use when sending request to Chamilo.
     * Temporal array of objects locates the resource link list that will be filled by CreateDocumentFileAction.php.
     *
     * @var ResourceLink[]
     */
    public array $resourceLinkEntityList = [];

    abstract public function getResourceName(): string;

    abstract public function setResourceName(string $name);

    //abstract public function setResourceProperties(FormInterface $form, $course, $session, $fileType);

    /**
     * @return ResourceLink[]
     */
    public function getResourceLinkEntityList()
    {
        return $this->resourceLinkEntityList;
    }

    /**
     * $this->resourceLinkEntityList will be loaded in the ResourceListener in the setLinks() function.
     */
    public function addLink(ResourceLink $link)
    {
        $this->resourceLinkEntityList[] = $link;

        return $this;
    }

    public function addCourseLink(Course $course, Session $session = null, CGroup $group = null, int $visibility = ResourceLink::VISIBILITY_PUBLISHED)
    {
        if (null === $this->getParent()) {
            throw new Exception('$resource->addCourseLink requires to set the parent first.');
        }

        $resourceLink = (new ResourceLink())
            ->setVisibility($visibility)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
        ;

        $rights = [];
        switch ($visibility) {
            case ResourceLink::VISIBILITY_PENDING:
            case ResourceLink::VISIBILITY_DRAFT:
                $editorMask = ResourceNodeVoter::getEditorMask();
                $resourceRight = new ResourceRight();
                $resourceRight
                    ->setMask($editorMask)
                    ->setRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER)
                ;
                $rights[] = $resourceRight;

                break;
        }

        if (!empty($rights)) {
            foreach ($rights as $right) {
                $resourceLink->addResourceRight($right);
            }
        }

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                function ($key, $element) use ($course, $session, $group) {
                    return $course === $element->getCourse() &&
                        $session === $element->getSession() &&
                        $group === $element->getGroup();
                }
            );

            if ($exists) {
                return $this;
            }
            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function addGroupLink(Course $course, Session $session = null, CGroup $group = null)
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
        ;

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                function ($key, $element) use ($group) {
                    if ($element->getGroup()) {
                        return $group->getIid() === $element->getGroup()->getIid();
                    }
                }
            );

            if ($exists) {
                return $this;
            }
            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function addUserLink(User $user, Course $course = null, Session $session = null, CGroup $group = null)
    {
        $resourceLink = new ResourceLink();
        $resourceLink
            ->setVisibility(ResourceLink::VISIBILITY_PUBLISHED)
            ->setUser($user)
            ->setCourse($course)
            ->setSession($session)
            ->setGroup($group)
        ;

        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $exists = $resourceNode->getResourceLinks()->exists(
                function ($key, $element) use ($user) {
                    if ($element->hasUser()) {
                        return $user->getId() === $element->getUser()->getId();
                    }
                }
            );

            if ($exists) {
                error_log('Link already exist for user: '.$user->getUsername().', skipping');

                return $this;
            }

            error_log('New link can be added for user: '.$user->getUsername());
            $resourceNode->addResourceLink($resourceLink);
        } else {
            $this->addLink($resourceLink);
        }

        return $this;
    }

    public function setParent(ResourceInterface $parent)
    {
        $this->parentResource = $parent;

        return $this;
    }

    public function getParent()
    {
        return $this->parentResource;
    }

    /**
     * @param array $userList User id list
     */
    public function addResourceToUserList(
        array $userList,
        Course $course = null,
        Session $session = null,
        CGroup $group = null
    ) {
        if (!empty($userList)) {
            foreach ($userList as $user) {
                $this->addUserLink($user, $course, $session, $group);
            }
        }

        return $this;
    }

    public function addResourceToGroupList(
        array $groupList,
        Course $course = null,
        Session $session = null,
    ) {
        foreach ($groupList as $group) {
            $this->addGroupLink($course, $session, $group);
        }

        return $this;
    }

    public function setResourceLinkArray(array $links)
    {
        $this->resourceLinkList = $links;

        return $this;
    }

    public function getResourceLinkArray()
    {
        return $this->resourceLinkList;
    }

    public function getResourceLinkListFromEntity()
    {
        return $this->resourceLinkListFromEntity;
    }

    public function setResourceLinkListFromEntity(): void
    {
        $resourceLinkList = [];
        if ($this->hasResourceNode()) {
            $resourceNode = $this->getResourceNode();
            $links = $resourceNode->getResourceLinks();
            foreach ($links as $link) {
                $resourceLinkList[] = [
                    'id' => $link->getId(),
                    'visibility' => $link->getVisibility(),
                    'visibilityName' => $link->getVisibilityName(),
                    'session' => $link->getSession(),
                    'course' => $link->getCourse(),
                    'group' => $link->getGroup(),
                    'userGroup' => $link->getUserGroup(),
                    'user' => $link->getUser(),
                ];
            }
        }
        $this->resourceLinkListFromEntity = $resourceLinkList;
    }

    public function hasParentResourceNode(): bool
    {
        return null !== $this->parentResourceNode && 0 !== $this->parentResourceNode;
    }

    public function setParentResourceNode(?int $resourceNode): self
    {
        $this->parentResourceNode = $resourceNode;

        return $this;
    }

    public function getParentResourceNode(): ?int
    {
        return $this->parentResourceNode;
    }

    public function hasUploadFile(): bool
    {
        return null !== $this->uploadFile;
    }

    public function getUploadFile(): ?UploadedFile
    {
        return $this->uploadFile;
    }

    public function setUploadFile(?UploadedFile $file): self
    {
        $this->uploadFile = $file;

        return $this;
    }

    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    public function hasResourceNode(): bool
    {
        return $this->resourceNode instanceof ResourceNode;
    }

    public function getResourceNode(): ?ResourceNode
    {
        return $this->resourceNode;
    }

    public function getFirstResourceLink(): ?ResourceLink
    {
        $resourceNode = $this->getResourceNode();

        if ($resourceNode && $resourceNode->getResourceLinks()->count()) {
            $result = $resourceNode->getResourceLinks()->first();
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * See ResourceLink to see the visibility constants. Example: ResourceLink::VISIBILITY_DELETED.
     */
    /*public function getLinkVisibility(Course $course, Session $session = null): ?ResourceLink
    {
        return $this->getFirstResourceLinkFromCourseSession($course, $session)->getVisibility();
    }*/

    public function isVisible(Course $course, Session $session = null): bool
    {
        $link = $this->getFirstResourceLinkFromCourseSession($course, $session);
        if (null === $link) {
            return false;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    public function getFirstResourceLinkFromCourseSession(Course $course, Session $session = null): ?ResourceLink
    {
        /*$criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('course', $course->getId()))
            ->andWhere(
                Criteria::expr()->eq('session', $session)
            )
            ->setFirstResult(0)
            ->setMaxResults(1)
        ;*/
        $resourceNode = $this->getResourceNode();
        $result = null;
        if ($resourceNode && $resourceNode->getResourceLinks()->count() > 0) {
            $links = $resourceNode->getResourceLinks();
            $found = false;
            $link = null;
            foreach ($links as $link) {
                if ($link->getCourse() === $course && $link->getSession() === $session) {
                    $found = true;

                    break;
                }
            }
            //$result = $links->matching($criteria)->count();
            //var_dump($result);
            if ($found) {
                return $link;
            }
        }

        return null;
    }

    public function getUsersAndGroupSubscribedToResource(): array
    {
        $users = [];
        $groups = [];
        $everyone = false;
        $links = $this->getResourceNode()->getResourceLinks();
        foreach ($links as $link) {
            if ($link->hasUser()) {
                $users[] = $link->getUser()->getId();

                continue;
            }
            if ($link->hasGroup()) {
                $groups[] = $link->getGroup()->getIid();
            }
        }

        if (empty($users) && empty($groups)) {
            $everyone = true;
        }

        return [
            'everyone' => $everyone,
            'users' => $users,
            'groups' => $groups,
        ];
    }
}
