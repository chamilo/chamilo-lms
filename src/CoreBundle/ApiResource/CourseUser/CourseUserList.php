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
use Chamilo\CoreBundle\State\CourseUser\CourseUserListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'CourseUserList',
    operations: [
        new Get(
            uriTemplate: '/course-users/list',
            openapi: new Operation(
                summary: 'Course users list in the current course and session context',
                parameters: [
                    new Parameter(name: 'type', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'search', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'active', in: 'query', required: false, schema: ['type' => 'boolean']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sort', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'order', in: 'query', required: false, schema: ['type' => 'string']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_course_user_list',
            provider: CourseUserListProvider::class,
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
    normalizationContext: ['groups' => ['course_user_list:read']],
)]
final class CourseUserList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_user_list:read'])]
    public string $id = 'course_user_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_user_list:read'])]
    public array $items = [];

    #[Groups(['course_user_list:read'])]
    public int $totalItems = 0;

    #[Groups(['course_user_list:read'])]
    public int $courseId = 0;

    #[Groups(['course_user_list:read'])]
    public ?int $sessionId = null;

    #[Groups(['course_user_list:read'])]
    public int $type = 5;

    #[Groups(['course_user_list:read'])]
    public bool $canManage = false;

    #[Groups(['course_user_list:read'])]
    public bool $canSubscribe = false;

    #[Groups(['course_user_list:read'])]
    public bool $canUnsubscribe = false;

    #[Groups(['course_user_list:read'])]
    public bool $canImport = false;

    #[Groups(['course_user_list:read'])]
    public bool $canSetTutor = false;

    #[Groups(['course_user_list:read'])]
    public bool $canSelfUnsubscribe = false;

    #[Groups(['course_user_list:read'])]
    public int $currentUserId = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['course_user_list:read'])]
    public array $extraFields = [];

    /**
     * @var string[]
     */
    #[Groups(['course_user_list:read'])]
    public array $hiddenFields = [];

    #[Groups(['course_user_list:read'])]
    public bool $showEmail = false;

    #[Groups(['course_user_list:read'])]
    public bool $westernNameOrder = true;

    #[Groups(['course_user_list:read'])]
    public bool $showLegalAgreement = false;

    #[Groups(['course_user_list:read'])]
    public string $warning = '';

    #[Groups(['course_user_list:read'])]
    public bool $showUpgradeCta = false;

    #[Groups(['course_user_list:read'])]
    public string $sessionManagementUrl = '';

    #[Groups(['course_user_list:read'])]
    public bool $showSessionManagement = false;

    #[Groups(['course_user_list:read'])]
    public bool $showClasses = false;

    #[Groups(['course_user_list:read'])]
    public bool $showSubscriptionTabs = false;

    #[Groups(['course_user_list:read'])]
    public bool $canInviteByEmail = false;

    #[Groups(['course_user_list:read'])]
    public string $groupsUrl = '';

    public function getId(): string
    {
        return $this->id;
    }
}
