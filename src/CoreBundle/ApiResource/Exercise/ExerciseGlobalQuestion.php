<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseGlobalQuestionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseGlobalQuestion',
    operations: [
        new Get(
            uriTemplate: '/exercise/questions/global',
            openapi: new Operation(
                summary: 'Global question type selector',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_global_question_types',
            provider: ExerciseGlobalQuestionProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_global_question:read']],
)]
final class ExerciseGlobalQuestion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_global_question:read'])]
    public string $id = 'global';

    #[Groups(['exercise_global_question:read'])]
    public string $title = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_global_question:read'])]
    public array $questionTypes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_global_question:read'])]
    public array $exercises = [];

    #[Groups(['exercise_global_question:read'])]
    public bool $canManage = false;

    public function getId(): string
    {
        return $this->id;
    }
}
