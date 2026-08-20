<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseGroup;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseGroup\CourseGroupOverviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseGroupOverview',
    operations: [
        new Get(
            uriTemplate: '/course-groups/overview',
            openapi: new Operation(summary: 'Overview of course groups, tutors and members'),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_group_overview',
            provider: CourseGroupOverviewProvider::class,
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
    normalizationContext: ['groups' => ['course_group_overview:read']],
)]
final class CourseGroupOverview
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_group_overview:read'])]
    public string $id = 'course_group_overview';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_group_overview:read'])]
    public array $groups = [];

    #[Groups(['course_group_overview:read'])]
    public string $csvExportUrl = '';

    #[Groups(['course_group_overview:read'])]
    public string $xlsxExportUrl = '';

    #[Groups(['course_group_overview:read'])]
    public string $pdfExportUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
