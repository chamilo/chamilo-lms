<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

trait ResourceControllerTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function getRepositoryFromRequest(Request $request): ResourceRepository
    {
        $tool = $request->get('tool');
        $type = $request->get('type');

        return $this->getRepository($tool, $type);
    }

    public function getResourceNodeRepository(): ResourceNodeRepository
    {
        return $this->container->get(ResourceNodeRepository::class);
    }

    public function getResourceRepositoryFactory(): ResourceFactory
    {
        return $this->container->get(ResourceFactory::class);
    }

    public function getRepository(string $tool, string $type): ResourceRepository
    {
        return $this->getResourceRepositoryFactory()->getRepositoryService($tool, $type);
    }

    public function denyAccessUnlessValidResource(?ResourceInterface $resource = null): void
    {
        if (null === $resource) {
            throw new EntityNotFoundException($this->trans("Resource doesn't exists."));
        }

        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new EntityNotFoundException($this->trans("Resource doesn't have a node."));
        }
    }

    public function getResourceParams(Request $request): array
    {
        $tool = $request->get('tool');
        $type = $request->get('type');
        $id = (int) $request->get('id');

        $courseId = null;
        $sessionId = null;

        if ($this->hasCourse()) {
            $courseId = $this->getCourse()->getId();
            $session = $this->getCourseSession();
            $sessionId = $session ? $session->getId() : 0;
        }

        return [
            'id' => $id,
            'tool' => $tool,
            'type' => $type,
            'cid' => $courseId,
            'sid' => $sessionId,
        ];
    }

    protected function getParentResourceNode(Request $request): ResourceNode
    {
        $parentNodeId = $request->get('id');

        $parentResourceNode = null;
        if (empty($parentNodeId)) {
            if ($this->hasCourse()) {
                $parentResourceNode = $this->getCourse()->getResourceNode();
            } elseif ($this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                /** @var User $user */
                $user = $this->userHelper->getCurrent();
                if ($user) {
                    $parentResourceNode = $user->getResourceNode();
                }
            }
        } else {
            $repo = $this->container->get('doctrine')->getRepository(ResourceNode::class);
            $parentResourceNode = $repo->find($parentNodeId);
        }

        if (null === $parentResourceNode) {
            throw new AccessDeniedException();
        }

        return $parentResourceNode;
    }
}
