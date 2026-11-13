<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseAutomation;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\CourseAutomation\CourseAutomationProcessor;
use Chamilo\CoreBundle\State\CourseAutomation\CourseAutomationProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/course-automation/configuration',
            name: 'get_course_automation_configuration',
            provider: CourseAutomationProvider::class,
            parameters: [
                'cid' => new QueryParameter(schema: ['type' => 'integer'], required: true),
                'sid' => new QueryParameter(schema: ['type' => 'integer']),
            ],
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/course-automation/actions',
            name: 'post_course_automation_action',
            read: false,
            processor: CourseAutomationProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Post(
            uriTemplate: '/course-automation/execute',
            name: 'post_course_automation_execute',
            read: false,
            processor: CourseAutomationProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Delete(
            uriTemplate: '/course-automation/actions/{actionId}',
            name: 'delete_course_automation_action',
            requirements: ['actionId' => '\d+'],
            read: false,
            processor: CourseAutomationProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: ['groups' => ['course_automation:read']],
    denormalizationContext: ['groups' => ['course_automation:write']],
)]
final class CourseAutomationConfiguration
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_automation:read'])]
    public string $id = 'course_automation_configuration';

    #[Groups(['course_automation:read'])]
    public bool $canManage = false;

    #[Groups(['course_automation:read'])]
    public array $actions = [];

    #[Groups(['course_automation:read'])]
    public array $resources = [];

    #[Groups(['course_automation:read'])]
    public array $surveys = [];

    #[Groups(['course_automation:read'])]
    public array $gradebooks = [];

    #[Groups(['course_automation:read'])]
    public bool $canChangeCourseVisibility = false;

    #[Groups(['course_automation:read'])]
    public bool $canUseHiddenVisibility = false;

    #[Groups(['course_automation:read', 'course_automation:write'])]
    public string $eventType = '';

    #[Groups(['course_automation:read', 'course_automation:write'])]
    public string $actionType = '';

    /**
     * @var list<int>
     */
    #[Groups(['course_automation:write'])]
    public array $targetIds = [];

    #[Groups(['course_automation:write'])]
    public ?int $targetId = null;

    #[Groups(['course_automation:write'])]
    public ?int $settingValue = null;

    #[Groups(['course_automation:read'])]
    public string $message = '';

    #[Groups(['course_automation:read'])]
    public int $executed = 0;

    #[Groups(['course_automation:read'])]
    public int $failed = 0;

    #[Groups(['course_automation:read'])]
    public int $skipped = 0;
}
