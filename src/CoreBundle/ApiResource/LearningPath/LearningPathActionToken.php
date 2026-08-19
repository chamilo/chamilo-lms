<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\QueryParameter;
use Chamilo\CoreBundle\State\LearningPath\LearningPathActionTokenProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/action-token',
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
            name: 'get_learning_path_action_token',
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier; without it the auto-launch setting is not read',
                ),
            ],
            provider: LearningPathActionTokenProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_action_token:read']],
)]
final class LearningPathActionToken
{
    #[Groups(['learning_path_action_token:read'])]
    public bool $allowChamiloExport = false;

    #[Groups(['learning_path_action_token:read'])]
    public bool $canAutoLaunch = false;
}
