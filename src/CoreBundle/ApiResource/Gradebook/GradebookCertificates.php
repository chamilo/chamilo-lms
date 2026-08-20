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
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificatesProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificates',
    operations: [
        new Get(
            uriTemplate: '/gradebook/certificates',
            openapi: new Operation(
                summary: 'Gradebook certificates for the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'officialCode', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_certificates',
            provider: GradebookCertificatesProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_certificates:read']],
)]
final class GradebookCertificates
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_certificates:read'])]
    public string $id = 'gradebook_certificates';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_certificates:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_certificates:read'])]
    public array $category = [];

    #[Groups(['gradebook_certificates:read'])]
    public bool $canManage = false;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_certificates:read'])]
    public array $settings = [];

    /**
     * @var list<array{label: string, value: string}>
     */
    #[Groups(['gradebook_certificates:read'])]
    public array $officialCodeOptions = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_certificates:read'])]
    public array $learners = [];

    #[Groups(['gradebook_certificates:read'])]
    public string $csrfToken = '';

    #[Groups(['gradebook_certificates:read'])]
    public string $customCertificateFallbackUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
