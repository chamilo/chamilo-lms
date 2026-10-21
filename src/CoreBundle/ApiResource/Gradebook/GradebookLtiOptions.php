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
use Chamilo\CoreBundle\State\Gradebook\GradebookLtiOptionsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLtiOptions',
    operations: [
        new Get(
            uriTemplate: '/gradebook/lti-options',
            openapi: new Operation(
                summary: 'LTI tools available for Gradebook evaluation linking in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_lti_options',
            provider: GradebookLtiOptionsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_lti_options:read']],
)]
final class GradebookLtiOptions
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_lti_options:read'])]
    public string $id = 'gradebook_lti_options';

    /**
     * @var list<array{value: int, label: string}>
     */
    #[Groups(['gradebook_lti_options:read'])]
    public array $tools = [];

    public function getId(): string
    {
        return $this->id;
    }
}
