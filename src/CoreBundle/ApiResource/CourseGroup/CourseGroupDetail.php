<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupDetailProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupDetail',
    operations: [
        new Get(
            uriTemplate: '/course-groups/{groupId}/detail',
            uriVariables: [
                'groupId' => new Link(schema: ['type' => 'integer'], property: 'groupId'),
            ],
            openapi: new Operation(summary: 'Course group area data'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_detail',
            provider: CourseGroupDetailProvider::class,
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
    normalizationContext: ['groups' => ['course_group_detail:read']],
)]
final class CourseGroupDetail
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_detail:read'])]
    public string $id = 'course_group_detail';

    #[Groups(['course_group_detail:read'])]
    public int $groupId = 0;

    #[Groups(['course_group_detail:read'])]
    public string $title = '';

    #[Groups(['course_group_detail:read'])]
    public string $description = '';

    #[Groups(['course_group_detail:read'])]
    public bool $canManage = false;

    #[Groups(['course_group_detail:read'])]
    public bool $canSelfRegister = false;

    #[Groups(['course_group_detail:read'])]
    public bool $canSelfUnregister = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_group_detail:read'])]
    public array $tools = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_group_detail:read'])]
    public array $tutors = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_group_detail:read'])]
    public array $members = [];

    public function getId(): string
    {
        return $this->id;
    }
}
