<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificateSearchProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificateSearch',
    operations: [
        new Get(
            uriTemplate: '/gradebook/certificate-search',
            openapi: new Operation(
                summary: 'Search public Gradebook certificates',
                parameters: [
                    new Parameter(name: 'firstname', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'lastname', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'userId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            name: 'get_gradebook_certificate_search',
            provider: GradebookCertificateSearchProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_certificate_search:read']],
)]
final class GradebookCertificateSearch
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_certificate_search:read'])]
    public string $id = 'gradebook_certificate_search';

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_certificate_search:read'])]
    public array $users = [];

    /**
     * @var array<string, mixed>|null
     */
    #[Groups(['gradebook_certificate_search:read'])]
    public ?array $selectedUser = null;

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_certificate_search:read'])]
    public array $courseCertificates = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_certificate_search:read'])]
    public array $sessionCertificates = [];

    #[Groups(['gradebook_certificate_search:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
