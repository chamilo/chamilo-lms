<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Chamilo\CoreBundle\State\Forum\ForumActionTokenProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forum/action-token',
            provider: ForumActionTokenProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_action_token:read'],
    ],
)]
final class ForumActionToken
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_action_token:read'])]
    public string $id = 'forum_action_token';

    #[Groups(['forum_action_token:read'])]
    public string $token = '';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['forum_action_token:read'])]
    public array $settings = [];

    public function getId(): string
    {
        return $this->id;
    }
}
