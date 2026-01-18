<?php

/* For licensing terms,
see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Traits\Scim\UserSchemaControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/scim/v2/Schemas', name: 'scim_schemas', methods: ['GET'])]
class SchemaCollectionController extends AbstractScimController
{
    use UserSchemaControllerTrait;

    public function __invoke(): JsonResponse
    {
        $schemas = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:ListResponse'],
            'itemsPerPage' => 1,
            'startIndex' => 1,
            'totalResults' => 1,
            'Resources' => [
                $this->getUserCoreSchema(),
            ],
        ];

        return new JsonResponse(
            $schemas,
            Response::HTTP_OK,
            ['Content-Type' => self::SCIM_CONTENT_TYPE]
        );
    }
}
