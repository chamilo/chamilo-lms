<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Survey;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Survey\SurveyListProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/survey/list',
            name: 'get_survey_list',
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
                        name: 'search',
                        in: 'query',
                        description: 'Search surveys by title, code or description',
                        required: false,
                        schema: ['type' => 'string'],
                    ),
                ],
            ),
            provider: SurveyListProvider::class,
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
        ),
    ],
    normalizationContext: [
        'groups' => ['survey_list:read'],
    ],
)]
final class SurveyList
{
    #[ApiProperty(identifier: true)]
    #[Groups(['survey_list:read'])]
    public string $id = 'survey_list';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['survey_list:read'])]
    public array $items = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['survey_list:read'])]
    public array $settings = [];

    #[Groups(['survey_list:read'])]
    public int $totalItems = 0;

    #[Groups(['survey_list:read'])]
    public bool $canManage = false;

    #[Groups(['survey_list:read'])]
    public bool $canCreate = false;

    public function getId(): string
    {
        return $this->id;
    }
}
