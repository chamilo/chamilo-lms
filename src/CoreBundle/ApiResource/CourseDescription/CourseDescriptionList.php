<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseDescription;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseDescription\CourseDescriptionListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-description/list',
            openapi: new Operation(
            ),
            name: 'get_course_description_list',
            provider: CourseDescriptionListProvider::class,
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
