<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseUserIdentifierProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * The profile field a test requires before it starts (see FinalExamAccessRule), filled in by the
 * learner from the start page of the test.
 */
#[ApiResource(
    shortName: 'ExerciseUserIdentifier',
    operations: [
        new Post(
            uriTemplate: '/exercise/overview/{exerciseId}/user-identifier',
            requirements: ['exerciseId' => '\d+'],
            openapi: new Operation(
                summary: 'Save the user identifier a test requires before it starts',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            name: 'post_exercise_user_identifier',
            processor: ExerciseUserIdentifierProcessor::class,
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
    normalizationContext: ['groups' => ['exercise_user_identifier:read']],
    denormalizationContext: ['groups' => ['exercise_user_identifier:write']],
)]
final class ExerciseUserIdentifier
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_user_identifier:read'])]
    public ?int $exerciseId = null;

    /**
     * Digits only, or NONE when the test lets the learner decline to give one.
     */
    #[Groups(['exercise_user_identifier:write'])]
    public string $value = '';

    #[Groups(['exercise_user_identifier:read'])]
    public bool $success = false;
}
