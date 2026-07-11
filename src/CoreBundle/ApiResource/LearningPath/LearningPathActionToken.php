<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\LearningPath\LearningPathActionTokenProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/learning_paths/action-token',
            name: 'get_learning_path_action_token',
            provider: LearningPathActionTokenProvider::class,
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_action_token:read']],
)]
final class LearningPathActionToken
{
    #[Groups(['learning_path_action_token:read'])]
    public string $token = '';

    #[Groups(['learning_path_action_token:read'])]
    public bool $allowChamiloExport = false;
}
