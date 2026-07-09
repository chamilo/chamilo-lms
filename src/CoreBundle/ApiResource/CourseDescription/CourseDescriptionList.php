<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseDescription;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-description/list',
            name: 'get_course_description_list',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'gid',
                        in: 'query',
                        description: 'Group id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'isStudentView',
                        in: 'query',
                        description: 'Force the read-only student view',
                        required: false,
                        schema: ['type' => 'boolean'],
                    ),
                ],
            ),
            provider: CourseDescriptionListProvider::class,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
        ),
    ],
    normalizationContext: [
        'groups' => ['course_description_list:read'],
    ],
)]
final class CourseDescriptionList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_description_list:read'])]
    public string $id = 'course_description_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_description_list:read'])]
    public array $items = [];

    #[Groups(['course_description_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_description_list:read'])]
    public int $courseId = 0;

    #[Groups(['course_description_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_description_list:read'])]
    public bool $canManage = false;

    #[Groups(['course_description_list:read'])]
    public bool $studentView = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_description_list:read'])]
    public array $types = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['course_description_list:read'])]
    public array $settings = [];

    public function getId(): string
    {
        return $this->id;
    }
}
