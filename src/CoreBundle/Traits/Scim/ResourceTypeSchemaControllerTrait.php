<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits\Scim;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait ResourceTypeSchemaControllerTrait
{
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
