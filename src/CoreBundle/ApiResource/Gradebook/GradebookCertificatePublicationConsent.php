<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificatePublicationConsentProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificatePublicationConsent',
    operations: [
        new Post(
            uriTemplate: '/gradebook/certificates/publication-consent',
            openapi: new Operation(summary: 'Record or withdraw the certificate owner publication approval; no external publication'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'post_gradebook_certificate_publication_consent',
            processor: GradebookCertificatePublicationConsentProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['certificate_publication_consent:read']],
    denormalizationContext: ['groups' => ['certificate_publication_consent:write']],
)]
final class GradebookCertificatePublicationConsent
{
    #[ApiProperty(identifier: true)]
    #[Groups(['certificate_publication_consent:read'])]
    public string $id = 'certificate_publication_consent';

    #[Groups(['certificate_publication_consent:write'])]
    public int $certificateId = 0;

    #[Groups(['certificate_publication_consent:write'])]
    public bool $approve = false;

    #[Groups(['certificate_publication_consent:read'])]
    public bool $success = false;

    #[Groups(['certificate_publication_consent:read'])]
    public ?string $approvedAt = null;

    #[Groups(['certificate_publication_consent:read'])]
    public ?string $revokedAt = null;

    public function getId(): string
    {
        return $this->id;
    }
}
