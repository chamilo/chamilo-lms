<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificateExpirationsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificateExpirations',
    operations: [
        new Get(
            uriTemplate: '/gradebook/certificate-expirations',
            openapi: new Operation(
                summary: 'Expired and about-to-expire certificates for the current Gradebook category (teachers/admins only)',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'daysAhead', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_certificate_expirations',
            provider: GradebookCertificateExpirationsProvider::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_certificate_expirations:read']],
)]
final class GradebookCertificateExpirations
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_certificate_expirations:read'])]
    public string $id = 'gradebook_certificate_expirations';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_certificate_expirations:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_certificate_expirations:read'])]
    public array $category = [];

    #[Groups(['gradebook_certificate_expirations:read'])]
    public int $daysAhead = 30;

    #[Groups(['gradebook_certificate_expirations:read'])]
    public string $csrfToken = '';

    /**
     * @var array{expired: int, expiring: int}
     */
    #[Groups(['gradebook_certificate_expirations:read'])]
    public array $summary = ['expired' => 0, 'expiring' => 0];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_certificate_expirations:read'])]
    public array $rows = [];

    public function getId(): string
    {
        return $this->id;
    }
}
