<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiSubresource;
use APY\DataGridBundle\Grid\Mapping as GRID;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 */
abstract class AbstractResource
{
    /**
     * @var string|null
     *
     * @ApiProperty(iri="http://schema.org/contentUrl")
     * @Groups({"resource_file:read", "resource_node:read", "document:read", "media_object_read"})
     */
    public $contentUrl;

    /**
     * @Assert\Valid()
     * @ApiSubresource()
     * @Groups({"resource_node:read", "resource_node:write"})
     * @GRID\Column(field="resourceNode.createdAt", title="Date added", type="datetime")
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\ResourceNode", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public $resourceNode;

    /**
     * @Groups({"resource_node:read", "resource_node:write", "document:read","document:write"})
     */
    public $parentResourceNode;

    /**
     * @ApiProperty(iri="http://schema.org/image")
     * @Groups({"resource_node:read", "resource_node:write", "document:read","document:write"})
     */
    public $resourceFile;

    public function hasParentResourceNode(): bool
    {
        return null !== $this->parentResourceNode;
    }

    public function setParentResourceNode($resourceNode): self
    {
        $this->parentResourceNode = $resourceNode;

        return $this;
    }

    public function getParentResourceNode()
    {
        return $this->parentResourceNode;
    }

    public function hasResourceFile(): bool
    {
        return null !== $this->resourceFile;
    }

    public function getResourceFile()
    {
        return $this->resourceFile;
    }

    public function setResourceFile($file)
    {
        $this->resourceFile = $file;
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

    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }

    public function getCourseSessionResourceLink(Course $course, Session $session = null): ?ResourceLink
    {
        return $this->getFirstResourceLinkFromCourseSession($course, $session);
    }

    public function getFirstResourceLink(): ?ResourceLink
    {
        $resourceNode = $this->getResourceNode();

        if ($resourceNode && $resourceNode->getResourceLinks()) {
            $result = $resourceNode->getResourceLinks()->first();
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * See ResourceLink to see the visibility constants. Example: ResourceLink::VISIBILITY_DELETED.
     *
     * @return int
     */
    public function getLinkVisibility(Course $course, Session $session = null)
    {
        return $this->getCourseSessionResourceLink($course, $session)->getVisibility();
    }

    public function isVisible(Course $course, Session $session = null): bool
    {
        $link = $this->getCourseSessionResourceLink($course, $session);
        if (null === $link) {
            return false;
        }

        return ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();
    }

    public function getFirstResourceLinkFromCourseSession(Course $course, Session $session = null): ?ResourceLink
    {
        $criteria = Criteria::create();
        $criteria
            ->where(Criteria::expr()->eq('course', $course))
            ->andWhere(
                Criteria::expr()->eq('session', $session)
            );
        $resourceNode = $this->getResourceNode();

        $result = null;
        if ($resourceNode && $resourceNode->getResourceLinks()->count() > 0) {
            //var_dump($resourceNode->getResourceLinks()->count());
            foreach ($resourceNode->getResourceLinks() as $link) {
                //var_dump(get_class($link));
            }
            $result = $resourceNode->getResourceLinks()->matching($criteria)->first();
            //var_dump($result);
            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
