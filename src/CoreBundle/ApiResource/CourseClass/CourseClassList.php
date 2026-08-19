<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseClass;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseClass\CourseClassListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseClassList',
    operations: [
        new Get(
            uriTemplate: '/course-classes/list',
            openapi: new Operation(
                summary: 'Classes linked or available in the current course context',
                parameters: [
                    new Parameter(name: 'view', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'groupFilter', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sort', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'order', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_class_list',
            provider: CourseClassListProvider::class,
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
    normalizationContext: ['groups' => ['course_class_list:read']],
)]
final class CourseClassList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_class_list:read'])]
    public string $id = 'course_class_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_class_list:read'])]
    public array $items = [];

    #[Groups(['course_class_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_class_list:read'])]
    public int $courseId = 0;

    #[Groups(['course_class_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_class_list:read'])]
    public string $view = 'registered';

    #[Groups(['course_class_list:read'])]
    public int $groupFilter = 0;

    #[Groups(['course_class_list:read'])]
    public bool $canManage = false;

    #[Groups(['course_class_list:read'])]
    public string $groupsUrl = '';

    #[Groups(['course_class_list:read'])]
    public string $information = '';

    public function getId(): string
    {
        return $this->id;
    }
}
