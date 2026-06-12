<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Exercise\ExerciseListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseList',
    operations: [
        new Get(
            uriTemplate: '/exercise/list',
            openapi: new Operation(
                summary: 'Exercise list for a course',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'categoryId', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_exercise_list',
            provider: ExerciseListProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['exercise_list:read']],
)]
final class ExerciseList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_list:read'])]
    public string $id = 'exercise_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_list:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['exercise_list:read'])]
    public array $categories = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['exercise_list:read'])]
    public array $settings = [];

    #[Groups(['exercise_list:read'])]
    public int $totalItems = 0;

    #[Groups(['exercise_list:read'])]
    public bool $canManage = false;

    #[Groups(['exercise_list:read'])]
    public bool $canCreate = false;

    #[Groups(['exercise_list:read'])]
    public bool $usesLegacyActions = true;

    public function getId(): string
    {
        return $this->id;
    }
}
