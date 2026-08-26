<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiSettingsProcessor;
use Chamilo\CoreBundle\State\Wiki\WikiSettingsProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiSettings',
    operations: [
        new Get(
            uriTemplate: '/wiki/settings',
            openapi: new Operation(
                summary: 'Read Wiki settings for the current course',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_settings',
            provider: WikiSettingsProvider::class,
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
        ),
        new Post(
            uriTemplate: '/wiki/settings',
            status: Response::HTTP_NO_CONTENT,
            openapi: new Operation(
                summary: 'Update Wiki settings for the current course',
                parameters: [
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_wiki_settings',
            processor: WikiSettingsProcessor::class,
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
                'gid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Group identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['wiki_settings:read']],
    denormalizationContext: ['groups' => ['wiki_settings:write']],
)]
final class WikiSettings
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_settings:read'])]
    public string $id = 'wiki_settings';

    #[Groups(['wiki_settings:read'])]
    public int $courseId = 0;

    #[Groups(['wiki_settings:read', 'wiki_settings:write'])]
    public bool $enabled = true;

    #[Groups(['wiki_settings:read', 'wiki_settings:write'])]
    public bool $categoriesEnabled = false;

    #[Groups(['wiki_settings:read', 'wiki_settings:write'])]
    public bool $htmlStrictFiltering = false;

    public function getId(): string
    {
        return $this->id;
    }
}
