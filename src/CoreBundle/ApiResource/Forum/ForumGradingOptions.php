<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Forum\ForumGradingOptionsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forum/grading-options',
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
                ],
            ),
            provider: ForumGradingOptionsProvider::class,
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_grading_options:read'],
    ],
)]
final class ForumGradingOptions
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_grading_options:read'])]
    public string $id = 'forum_grading_options';

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['forum_grading_options:read'])]
    public array $categories = [];

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     */
    public static function fromCategories(array $categories): self
    {
        $options = new self();
        $options->categories = $categories;

        return $options;
    }
}
