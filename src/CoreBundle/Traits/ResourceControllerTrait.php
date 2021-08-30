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

    public function denyAccessUnlessValidResource(ResourceInterface $resource = null): void
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
                $user = $this->getUser();
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

    /**
     * @return User|null
     */
    protected function getUser()
    {
        /*if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }*/

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        /** @var User $user */
        $user = $token->getUser();

        if (!\is_object($user)) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

    private function setBreadCrumb(Request $request, ResourceNode $resourceNode)
    {
        return false;
        /*$tool = $request->get('tool');
        $resourceNodeId = $request->get('id');
        $routeParams = $this->getResourceParams($request);
        $baseNodeId = $this->getCourse()->getResourceNode()->getId();

        if (!empty($resourceNodeId)) {
            $breadcrumb = $this->getBreadCrumb();
            $toolParams = $routeParams;
            $toolParams['id'] = null;

            // Root tool link
            $breadcrumb->addChild(
                $this->trans($tool),
                [
                    'uri' => $this->generateUrl('chamilo_core_resource_index', $toolParams),
                ]
            );

            $repo = $this->getRepositoryFromRequest($request);
            $settings = $repo->getResourceSettings();

            //$originalResource = $repo->findOneBy(['resourceNode' => $resourceNodeId]);
            if (null === $resourceNode) {
                return;
            }
            $parentList = $resourceNode->getPathForDisplayToArray($baseNodeId);*/
//            var_dump($originalParent->getPath(), $originalParent->getPathForDisplay());
//            $parentList = [];
            /*          while (null !== $parent) {
                          if ($type !== $parent->getResourceType()->getName()) {
                              break;
                          }
                          $parent = $parent->getParent();
                          if ($parent) {
                              $resource = $repo->findOneBy(['resourceNode' => $parent->getId()]);
                              if ($resource) {
                                  $parentList[] = $resource;
                              }
                          }
                      }
                      $parentList = array_reverse($parentList);
                      foreach ($parentList as $item) {
                          $params = $routeParams;
                          $params['id'] = $item->getResourceNode()->getId();
                          $breadcrumb->addChild(
                              $item->getResourceName(),
                              [
                                  'uri' => $this->generateUrl('chamilo_core_resource_list', $params),
                              ]
                          );
                      }*/

            /*foreach ($parentList as $id => $title) {
                $params = $routeParams;
                $params['id'] = $id;
                $breadcrumb->addChild(
                    $title,
                    [
                        'uri' => $this->generateUrl('chamilo_core_resource_list', $params),
                    ]
                );
            }

            $params = $routeParams;
            $params['id'] = $resourceNode->getId();
            if (false === $settings->isAllowNodeCreation() || $resourceNode->hasResourceFile()) {
                $breadcrumb->addChild($resourceNode->getTitle());
            } else {
                $breadcrumb->addChild(
                    $resourceNode->getTitle(),
                    [
                        'uri' => $this->generateUrl('chamilo_core_resource_list', $params),
                    ]
                );
            }
        }*/
    }
}
