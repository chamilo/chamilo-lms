<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use APY\DataGridBundle\Grid\Mapping as GRID;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @ORM\EntityListeners({"Chamilo\CoreBundle\Entity\Listener\ResourceListener"})
 */
abstract class AbstractResource
{
    /**
     * @GRID\Column(field="resourceNode.createdAt", title="Date added", type="datetime")
     *
     * @ORM\OneToOne(
     *     targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", mappedBy="resource", cascade={"remove"}, orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public $resourceNode;
    //abstract public function getResourceName(): string;

    /**
     * @return $this
     */
    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
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
        return ResourceLink::VISIBILITY_PUBLISHED === $this->getCourseSessionResourceLink($course, $session);
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
        if ($resourceNode && $resourceNode->getResourceLinks()) {
            $result = $resourceNode->getResourceLinks()->matching($criteria)->first();
            if ($result) {
                return $result;
            }
        }

        return null;
    }
}
