<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Traits\Scim\ResourceTypeSchemaControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/scim/v2/ResourceTypes', name: 'scim_resource_types', methods: ['GET'])]
class ResourceTypeCollectionController extends AbstractScimController
{
    use ResourceTypeSchemaControllerTrait;

    public function __invoke(): JsonResponse
    {
        $resourceTypes = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'totalResults' => 1,
            'itemsPerPage' => 1,
            'startIndex' => 1,
            'Resources' => [
                $this->getUserResource(),
            ],
        ];

        return new JsonResponse(
            $resourceTypes,
            Response::HTTP_OK,
            ['Content-Type' => self::SCIM_CONTENT_TYPE]
        );
    }
}
