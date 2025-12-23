<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Scim;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/scim/v2/ResourceTypes')]
class ResourceTypeController extends AbstractScimController
{
    #[Route('', name: 'scim_resource_types', methods: ['GET'])]
    public function listResourceTypes(): JsonResponse
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

    #[Route('/{resourceType}', name: 'scim_resource_type', methods: ['GET'])]
    public function resourceType(string $resourceType): JsonResponse
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

    private function getUserResource(): array
    {
        $location = $this->generateUrl(
            'scim_resource_types',
            ['resourceType' => 'User'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return [
            'schemas' => [
                'urn:ietf:params:scim:schemas:core:2.0:ResourceType',
            ],
            'id' => 'User',
            'name' => 'User',
            'endpoint' => '/Users',
            'description' => 'User Account',
            'schema' => 'urn:ietf:params:scim:schemas:core:2.0:User',
            'meta' => [
                'resourceType' => 'ResourceType',
                'location' => $location,
            ],
        ];
    }
}
