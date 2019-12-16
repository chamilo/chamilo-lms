<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\ResourceFactory;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Symfony\Component\HttpFoundation\Request;

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
}
