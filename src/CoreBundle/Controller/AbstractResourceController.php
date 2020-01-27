<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Utils\Glide;
use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @return Glide
     */
    public function getGlide()
    {
        return $this->container->get('glide');
    }
}
