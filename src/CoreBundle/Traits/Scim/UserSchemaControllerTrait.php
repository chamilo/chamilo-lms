<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Traits\Scim;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait UserSchemaControllerTrait
{
    protected function getUserCoreSchema(): array
    {
        $location = $this->generateUrl(
            'scim_schema',
            ['schemaId' => 'urn:ietf:params:scim:schemas:core:2.0:User'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return [
            'id' => 'urn:ietf:params:scim:schemas:core:2.0:User',
            'name' => 'User',
            'description' => 'User Schema',
            'attributes' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'Unique identifier for the resource as defined by Chamilo.',
                    'required' => true,
                    'caseExact' => true,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'server',
                ],
                [
                    'name' => 'externalId',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'Unique identifier for the resource as defined by the provisioning client.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'userName',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'Unique identifier for the User, typically used by the user to directly authenticate to Chamilo.',
                    'required' => true,
                    'caseExact' => false,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'uniqueness' => 'server',
                ],
                [
                    'name' => 'displayName',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => 'The name of the User, suitable for display to end-users.',
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'name',
                    'type' => 'complex',
                    'multiValued' => false,
                    'description' => "The components of the user's real name.",
                    'required' => false,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'subAttributes' => [
                        [
                            'name' => 'familyName',
                            'type' => 'string',
                            'description' => "The family name of the User, or last name in most Western languages (e.g., 'Jensen' given the full name 'Ms. Barbara J Jensen, III').",
                            'multiValued' => false,
                            'required' => true,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                        ],
                        [
                            'name' => 'givenName',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => "The given name of the User, or first name in most Western languages (e.g., 'Barbara' given the full name 'Ms. Barbara J Jensen, III').",
                            'required' => true,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                        ],
                    ],
                ],
                [
                    'name' => 'emails',
                    'type' => 'complex',
                    'multiValued' => true,
                    'description' => 'Email addresses for the user.',
                    'required' => true,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'subAttributes' => [
                        [
                            'name' => 'value',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => 'Email addresses for the user.',
                            'required' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                        ],
                        [
                            'name' => 'type',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => "A label indicating the attribute's function.",
                            'required' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                            'canonicalValues' => [
                                'work',
                            ],
                        ],
                        [
                            'name' => 'primary',
                            'type' => 'boolean',
                            'multiValued' => false,
                            'description' => "A Boolean value indicating the 'primary' or preferred attribute value for this attribute",
                            'required' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                        ],
                    ],
                ],
                [
                    'name' => 'phoneNumbers',
                    'type' => 'complex',
                    'multiValued' => true,
                    'description' => 'Phone numbers for the User.',
                    'required' => false,
                    'subAttributes' => [
                        [
                            'name' => 'value',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => 'Phone number of the User.',
                            'required' => false,
                            'caseExact' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                            'uniqueness' => 'none',
                        ],
                        [
                            'name' => 'type',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => "A label indicating the attribute's function.",
                            'required' => false,
                            'caseExact' => false,
                            'canonicalValues' => [
                                'work',
                            ],
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                            'uniqueness' => 'none',
                        ],
                        [
                            'name' => 'primary',
                            'type' => 'boolean',
                            'multiValued' => false,
                            'description' => "A Boolean value indicating the 'primary' or preferred attribute value for this attribute.",
                            'required' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                        ],
                    ],
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                ],
                [
                    'name' => 'addresses',
                    'type' => 'complex',
                    'multiValued' => true,
                    'description' => 'A physical mailing address for this User.',
                    'required' => false,
                    'subAttributes' => [
                        [
                            'name' => 'formatted',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => 'The full mailing address, formatted for display or use with a mailing label.',
                            'required' => false,
                            'caseExact' => false,
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                            'uniqueness' => 'none',
                        ],
                        [
                            'name' => 'type',
                            'type' => 'string',
                            'multiValued' => false,
                            'description' => "A label indicating the attribute's function.",
                            'required' => false,
                            'caseExact' => false,
                            'canonicalValues' => [
                                'work',
                            ],
                            'mutability' => 'readWrite',
                            'returned' => 'default',
                            'uniqueness' => 'none',
                        ],
                    ],
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'timezone',
                    'type' => 'string',
                    'multiValued' => false,
                    'description' => "The User's time zone in the 'Olson' time zone database format, e.g., 'America/Los_Angeles'.",
                    'required' => false,
                    'caseExact' => false,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                    'uniqueness' => 'none',
                ],
                [
                    'name' => 'active',
                    'type' => 'boolean',
                    'multiValued' => false,
                    'description' => "A Boolean value indicating the User's administrative status.",
                    'required' => false,
                    'mutability' => 'readWrite',
                    'returned' => 'default',
                ],
                [
                    'name' => 'meta',
                    'type' => 'complex',
                    'multiValued' => false,
                    'required' => false,
                    'mutability' => 'readOnly',
                    'returned' => 'default',
                    'subAttributes' => [
                        [
                            'name' => 'resourceType',
                            'type' => 'string',
                            'multiValued' => false,
                            'mutability' => 'readOnly',
                            'returned' => 'default',
                        ],
                        [
                            'name' => 'created',
                            'type' => 'dateTime',
                            'multiValued' => false,
                            'mutability' => 'readOnly',
                            'returned' => 'default',
                        ],
                        [
                            'name' => 'lastModified',
                            'type' => 'dateTime',
                            'multiValued' => false,
                            'mutability' => 'readOnly',
                            'returned' => 'default',
                        ],
                        [
                            'name' => 'location',
                            'type' => 'string',
                            'multiValued' => false,
                            'mutability' => 'readOnly',
                            'returned' => 'default',
                        ],
                    ],
                ],
            ],
            'meta' => [
                'resourceType' => 'Schema',
                'location' => $location,
            ],
        ];
    }
}
