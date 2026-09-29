<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookMyCertificatesProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookMyCertificates',
    operations: [
        new Get(
            uriTemplate: '/gradebook/my-certificates',
            openapi: new Operation(summary: 'Certificates achieved by the authenticated user'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_gradebook_my_certificates',
            provider: GradebookMyCertificatesProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['gradebook_my_certificates:read']],
)]
final class GradebookMyCertificates
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_my_certificates:read'])]
    public string $id = 'gradebook_my_certificates';

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_my_certificates:read'])]
    public array $courseCertificates = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_my_certificates:read'])]
    public array $sessionCertificates = [];

    #[Groups(['gradebook_my_certificates:read'])]
    public bool $allowExport = true;

    #[Groups(['gradebook_my_certificates:read'])]
    public bool $allowSearch = false;

    #[Groups(['gradebook_my_certificates:read'])]
    public string $searchUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
