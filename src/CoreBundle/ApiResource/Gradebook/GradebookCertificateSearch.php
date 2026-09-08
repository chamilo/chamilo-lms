<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificateSearchProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificateSearch',
    operations: [
        new Get(
            uriTemplate: '/gradebook/certificate-search',
            openapi: new Operation(
                summary: 'Search public Gradebook certificates',
            ),
            name: 'get_gradebook_certificate_search',
            provider: GradebookCertificateSearchProvider::class,
            // Declared here rather than in the openapi operation: a QueryParameter
            // documents AND validates, while an openapi Parameter only documents,
            // and a twin declaration there would hide this one.
            //
            // No cid or sid: this search reads no course context. It answers 403
            // unless the platform enables the public certificates search.
            parameters: [
                'firstname' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'First name to search for, trimmed to 255 characters',
                ),
                'lastname' => new QueryParameter(
                    schema: ['type' => 'string'],
                    description: 'Last name to search for, trimmed to 255 characters',
                ),
                'userId' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Restricts the search to the certificates of one user',
                ),
            ],
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
