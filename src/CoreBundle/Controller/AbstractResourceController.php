<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Entity\Resource\ResourceNode;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AbstractResourceController.
 */
abstract class AbstractResourceController extends BaseController
{
    protected $resourceRepositoryFactory;

    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceRepositoryFactory = $resourceFactory;
    }

    public function getRepositoryFromRequest(Request $request): ResourceRepository
    {
        $tool = $request->get('tool');
        $type = $request->get('type');

        return $this->getRepository($tool, $type);
    }

    public function getRepository($tool, $type): ResourceRepository
    {
        return $this->resourceRepositoryFactory->createRepository($tool, $type);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['glide'] = Glide::class;
        //$services['storage'] = FlysystemStorage::class;

        return $services;
    }

    public function denyAccessUnlessValidResource(AbstractResource $resource)
    {
        if (null === $resource) {
            throw new NotFoundHttpException($this->trans('Resource doesn\'t exists.'));
        }

        $resourceNode = $resource->getResourceNode();

        if (null === $resourceNode) {
            throw new NotFoundHttpException($this->trans('Resource doesn\'t have a node.'));
        }
    }

    protected function getParentResourceNode(Request $request): ResourceNode
    {
        $parentNodeId = $request->get('id');

        $parentResourceNode = null;
        if (empty($parentNodeId)) {
            if ($this->hasCourse()) {
                $parentResourceNode = $this->getCourse()->getResourceNode();
            } else {
                if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
                    /** @var User $user */
                    $parentResourceNode = $this->getUser()->getResourceNode();
                }
            }
        } else {
            $repo = $this->getDoctrine()->getRepository('ChamiloCoreBundle:Resource\ResourceNode');
            $parentResourceNode = $repo->find($parentNodeId);
        }

        if (null === $parentResourceNode) {
            throw new AccessDeniedException();
        }

        return $parentResourceNode;
    }

    /**
     * @return Glide
     */
    public function getGlide()
    {
        return $this->container->get('glide');
    }
}
