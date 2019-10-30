<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Entity\Resource;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class AbstractResource implements ResourceInterface
{
    /**
     * @ORM\OneToOne(targetEntity="Chamilo\CoreBundle\Entity\Resource\ResourceNode", cascade={"remove"}, orphanRemoval=true)
     * @ORM\JoinColumn(name="resource_node_id", referencedColumnName="id", onDelete="CASCADE")
     */
    public $resourceNode;

    /**
     * @return string
     */
    abstract public function getResourceName(): string;

    /**
     * @ORM\PostUpdate()
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        // Updates resource node name with the resource name.
        $node = $this->getResourceNode();
        $name = $this->getResourceName();
        $node->setName($name);

        if ($node->hasResourceFile()) {
            // Update file name if exists too.
            $node->getResourceFile()->setOriginalName($name);
        }

        $em->persist($node);
        $em->flush();
    }

    /**
     * @param ResourceNode $resourceNode
     *
     * @return $this
     */
    public function setResourceNode(ResourceNode $resourceNode): self
    {
        $this->resourceNode = $resourceNode;

        return $this;
    }

    /**
     * @return ResourceNode
     */
    public function getResourceNode(): ResourceNode
    {
        return $this->resourceNode;
    }
}
