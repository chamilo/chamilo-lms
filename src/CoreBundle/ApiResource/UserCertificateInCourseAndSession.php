<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\UserCertificatesInCourseAndSessionStateProvider;

#[ApiResource(
    shortName: 'UserCertificateInCourseAndSession',
    operations: [
        new GetCollection(
            uriTemplate: '/tracking/user_certificates_in_course_and_session',
            openapi: new Operation(
                summary: 'Certificates for a user in a course and optional session',
                parameters: [
                    new Parameter(name: 'userId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'courseId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sessionId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            name: 'tracking_user_certificates_in_course_and_session',
            provider: UserCertificatesInCourseAndSessionStateProvider::class,
        ),
    ],
)]
final class UserCertificateInCourseAndSession
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $title,
        public string $issuedAt,
        public ?string $downloadUrl,
    ) {}
}
