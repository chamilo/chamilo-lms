<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\CourseUser;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\CourseUser\CourseUserAvailableProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseUserAvailable',
    operations: [
        new Get(
            uriTemplate: '/course-users/available',
            openapi: new Operation(
                summary: 'Users available for subscription in the current course context',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'extraFieldId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'extraFieldValue', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sort', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'order', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_user_available',
            provider: CourseUserAvailableProvider::class,
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
    normalizationContext: ['groups' => ['course_user_available:read']],
)]
final class CourseUserAvailable
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_user_available:read'])]
    public string $id = 'course_user_available';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_user_available:read'])]
    public array $items = [];

    #[Groups(['course_user_available:read'])]
    public int $totalItems = 0;

    #[Groups(['course_user_available:read'])]
    public int $courseId = 0;

    #[Groups(['course_user_available:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_user_available:read'])]
    public int $type = 5;

    #[Groups(['course_user_available:read'])]
    public bool $canManage = false;

    #[Groups(['course_user_available:read'])]
    public bool $canSubscribe = false;

    #[Groups(['course_user_available:read'])]
    public bool $showEmail = false;

    #[Groups(['course_user_available:read'])]
    public bool $westernNameOrder = true;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_user_available:read'])]
    public array $extraFields = [];

    #[Groups(['course_user_available:read'])]
    public bool $showSubscriptionTabs = false;

    #[Groups(['course_user_available:read'])]
    public bool $showClasses = false;

    #[Groups(['course_user_available:read'])]
    public bool $canInviteByEmail = false;

    #[Groups(['course_user_available:read'])]
    public string $groupsUrl = '';

    #[Groups(['course_user_available:read'])]
    public string $warning = '';

    #[Groups(['course_user_available:read'])]
    public bool $showUpgradeCta = false;

    public function getId(): string
    {
        return $this->id;
    }
}
