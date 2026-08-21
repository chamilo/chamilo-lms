<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseProgress;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseProgress\CourseProgressListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-progress/list',
            openapi: new Operation(
            ),
            name: 'get_course_progress_list',
            provider: CourseProgressListProvider::class,
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
        'groups' => ['course_progress_list:read'],
    ],
)]
final class CourseProgressList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_progress_list:read'])]
    public string $id = 'course_progress_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_progress_list:read'])]
    public array $items = [];

    #[Groups(['course_progress_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_progress_list:read'])]
    public int $courseId = 0;

    #[Groups(['course_progress_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_progress_list:read'])]
    public bool $canManage = false;

    #[Groups(['course_progress_list:read'])]
    public bool $studentView = false;

    #[Groups(['course_progress_list:read'])]
    public float $totalAverage = 0.0;

    #[Groups(['course_progress_list:read'])]
    public ?int $lastDoneAdvanceId = null;

    public function getId(): string
    {
        return $this->id;
    }
}
