<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\UserConnectionTimeStateProvider;

/**
 * Port of the 1.11.x get_user_total_connexion_time webservice: the time a user spent
 * connected to the platform, summed from track_e_login.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/users/{id}/connection-time',
            openapi: new Operation(
                summary: 'Total time a user spent connected to the platform',
                parameters: [
                    new Parameter(
                        name: 'startDate',
                        in: 'query',
                        description: 'Only count connections that started at or after this date-time (ISO 8601)',
                        required: false,
                        schema: ['type' => 'string', 'format' => 'date-time'],
                    ),
                    new Parameter(
                        name: 'endDate',
                        in: 'query',
                        description: 'Only count connections that ended at or before this date-time (ISO 8601)',
                        required: false,
                        schema: ['type' => 'string', 'format' => 'date-time'],
                    ),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or (user and user.getId() == id)",
            name: 'get_user_connection_time',
            provider: UserConnectionTimeStateProvider::class,
        ),
    ],
)]
final class UserConnectionTime
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $username,
        /**
         * Total connection time, in seconds.
         */
        public int $totalConnectionTime,
        /**
         * Same total as HH:MM:SS, the format the legacy webservice returned.
         */
        public string $totalConnectionTimeFormatted,
    ) {}
}
