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
use Chamilo\CoreBundle\State\Gradebook\GradebookBadgesProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookBadges',
    operations: [
        new Get(
            uriTemplate: '/gradebook/badges',
            openapi: new Operation(
                summary: 'OpenBadges export data for a learner in the current Gradebook context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'userId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_STUDENT')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_badges',
            provider: GradebookBadgesProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_badges:read']],
)]
final class GradebookBadges
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_badges:read'])]
    public string $id = 'gradebook_badges';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_badges:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_badges:read'])]
    public array $learner = [];

    #[Groups(['gradebook_badges:read'])]
    public string $backpackScriptUrl = '';

    /**
     * @var list<string>
     */
    #[Groups(['gradebook_badges:read'])]
    public array $assertions = [];

    public function getId(): string
    {
        return $this->id;
    }
}
