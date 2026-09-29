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
use Chamilo\CoreBundle\State\Gradebook\GradebookLearnerSkillsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLearnerSkills',
    operations: [
        new Get(
            uriTemplate: '/gradebook/learner-skills',
            openapi: new Operation(
                summary: 'Gradebook skill-item validation status for one learner in the current course context',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'userId', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'get_gradebook_learner_skills',
            provider: GradebookLearnerSkillsProvider::class,
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
    normalizationContext: ['groups' => ['gradebook_learner_skills:read']],
)]
final class GradebookLearnerSkills
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_learner_skills:read'])]
    public string $id = 'gradebook_learner_skills';

    /**
     * @var array<string, int>
     */
    #[Groups(['gradebook_learner_skills:read'])]
    public array $context = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['gradebook_learner_skills:read'])]
    public array $learner = [];

    /**
     * @var list<array<string, mixed>>
     */
    #[Groups(['gradebook_learner_skills:read'])]
    public array $skills = [];

    #[Groups(['gradebook_learner_skills:read'])]
    public string $csrfToken = '';

    public function getId(): string
    {
        return $this->id;
    }
}
