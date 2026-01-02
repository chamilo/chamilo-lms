<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Chamilo\CoreBundle\Traits\Scim\ResourceTypeSchemaControllerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/scim/v2/ResourceTypes/{resourceType}', name: 'scim_resource_type', methods: ['GET'])]
class ResourceTypeItemController extends AbstractScimController
{
    use ResourceTypeSchemaControllerTrait;

    public function __invoke(string $resourceType): JsonResponse
    {
        if ('User' !== $resourceType) {
            throw $this->createNotFoundException('Resource Type not supported');
        }

        $userResourceType = $this->getUserResource();

        return new JsonResponse(
            $userResourceType,
            Response::HTTP_OK,
            ['Content-Type' => self::SCIM_CONTENT_TYPE]
        );
    }
}
