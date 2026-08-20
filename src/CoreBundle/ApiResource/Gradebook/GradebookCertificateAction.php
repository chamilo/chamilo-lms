<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Gradebook;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Gradebook\GradebookCertificateActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookCertificateAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/certificates/action',
            openapi: new Operation(
                summary: 'Generate or delete Gradebook certificates in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_certificate_action',
            processor: GradebookCertificateActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_certificate_action:read']],
    denormalizationContext: ['groups' => ['gradebook_certificate_action:write']],
)]
final class GradebookCertificateAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_certificate_action:read'])]
    public string $id = 'gradebook_certificate_action';

    #[Groups(['gradebook_certificate_action:read', 'gradebook_certificate_action:write'])]
    public string $action = '';

    #[Groups(['gradebook_certificate_action:write'])]
    public ?int $categoryId = null;

    #[Groups(['gradebook_certificate_action:write'])]
    public ?int $userId = null;

    #[Groups(['gradebook_certificate_action:write'])]
    public string $officialCode = '';

    #[Groups(['gradebook_certificate_action:write'])]
    public string $notificationMessage = '';

    #[Groups(['gradebook_certificate_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_certificate_action:read'])]
    public bool $success = false;

    #[Groups(['gradebook_certificate_action:read'])]
    public int $affected = 0;

    #[Groups(['gradebook_certificate_action:read'])]
    public string $message = '';

    public function getId(): string
    {
        return $this->id;
    }
}
