<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificateCredentialActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificateCredentialAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/certificates/credential-action',
            openapi: new Operation(summary: 'Manually issue, revoke or confirm a Sepolia testnet certificate credential'),
            security: "is_granted('ROLE_ADMIN')",
            name: 'post_gradebook_certificate_credential_action',
            processor: GradebookCertificateCredentialActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['certificate_credential:read']],
    denormalizationContext: ['groups' => ['certificate_credential:write']],
)]
final class GradebookCertificateCredentialAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['certificate_credential:read'])]
    public string $id = 'certificate_credential_action';

    #[Groups(['certificate_credential:write'])]
    public int $certificateId = 0;

    #[Groups(['certificate_credential:write'])]
    public string $action = '';

    #[Groups(['certificate_credential:write'])]
    public string $txHash = '';

    #[Groups(['certificate_credential:read'])]
    public bool $success = false;

    #[Groups(['certificate_credential:read'])]
    public string $token = '';

    #[Groups(['certificate_credential:read'])]
    public string $verificationUrl = '';

    #[Groups(['certificate_credential:read'])]
    public string $anchorData = '';

    #[Groups(['certificate_credential:read'])]
    public string $status = '';

    public function getId(): string
    {
        return $this->id;
    }
}
