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
use Chamilo\CoreBundle\State\Gradebook\GradebookLearnerSkillActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'GradebookLearnerSkillAction',
    operations: [
        new Post(
            uriTemplate: '/gradebook/learner-skills/action',
            openapi: new Operation(
                summary: 'Toggle a learner skill conclusion from Gradebook skill-item validation',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN')
                or is_granted('ROLE_CURRENT_COURSE_TEACHER')
                or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
                or is_granted('ROLE_SESSION_MANAGER')",
            name: 'post_gradebook_learner_skill_action',
            processor: GradebookLearnerSkillActionProcessor::class,
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
    normalizationContext: ['groups' => ['gradebook_learner_skill_action:read']],
    denormalizationContext: ['groups' => ['gradebook_learner_skill_action:write']],
)]
final class GradebookLearnerSkillAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['gradebook_learner_skill_action:read'])]
    public string $id = 'gradebook_learner_skill_action';

    #[Groups(['gradebook_learner_skill_action:write'])]
    public int $userId = 0;

    #[Groups(['gradebook_learner_skill_action:write'])]
    public int $skillId = 0;

    #[Groups(['gradebook_learner_skill_action:write'])]
    public string $submittedCsrfToken = '';

    #[Groups(['gradebook_learner_skill_action:read'])]
    public bool $acquired = false;

    #[Groups(['gradebook_learner_skill_action:read'])]
    public bool $success = false;

    public function getId(): string
    {
        return $this->id;
    }
}
