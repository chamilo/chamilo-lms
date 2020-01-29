<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Entity\Resource\AbstractResource;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vich\UploaderBundle\Storage\FlysystemStorage;

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

    /**
     * @return Glide
     */
    public function getGlide()
    {
        return $this->container->get('glide');
    }
}
