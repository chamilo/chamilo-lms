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
use Chamilo\CoreBundle\State\Wiki\WikiDiscussionProcessor;
use Chamilo\CoreBundle\State\Wiki\WikiDiscussionProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiDiscussion',
    operations: [
        new Get(
            uriTemplate: '/wiki/page/{pageId}/discussion',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Read the discussion for one logical Wiki page',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_discussion',
            provider: WikiDiscussionProvider::class,
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
            uriTemplate: '/wiki/page/{pageId}/discussion',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Add a comment to one logical Wiki page discussion',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: 'post_wiki_discussion_comment',
            processor: WikiDiscussionProcessor::class,
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
    normalizationContext: ['groups' => ['wiki_discussion:read']],
    denormalizationContext: ['groups' => ['wiki_discussion:write']],
)]
final class WikiDiscussion
{
    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_discussion:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_discussion:read'])]
    public int $courseId = 0;

    #[Groups(['wiki_discussion:read'])]
    public ?int $sessionId = null;

    #[Groups(['wiki_discussion:read'])]
    public ?int $groupId = null;

    #[Groups(['wiki_discussion:read'])]
    public int $nodeId = 0;

    #[Groups(['wiki_discussion:read'])]
    public string $reflink = '';

    #[Groups(['wiki_discussion:read'])]
    public string $title = '';

    #[Groups(['wiki_discussion:read'])]
    public string $latestAuthorName = '';

    #[Groups(['wiki_discussion:read'])]
    public ?string $latestUpdatedAt = null;

    #[Groups(['wiki_discussion:read'])]
    public bool $visible = true;

    #[Groups(['wiki_discussion:read'])]
    public bool $commentsOpen = true;

    #[Groups(['wiki_discussion:read'])]
    public bool $ratingsOpen = true;

    #[Groups(['wiki_discussion:read'])]
    public bool $subscribed = false;

    #[Groups(['wiki_discussion:read'])]
    public bool $canManage = false;

    #[Groups(['wiki_discussion:read'])]
    public bool $canComment = false;

    #[Groups(['wiki_discussion:read'])]
    public bool $canRate = false;

    #[Groups(['wiki_discussion:read'])]
    public bool $canSubscribe = false;

    #[Groups(['wiki_discussion:read'])]
    public int $commentCount = 0;

    #[Groups(['wiki_discussion:read'])]
    public int $scoredCommentCount = 0;

    #[Groups(['wiki_discussion:read'])]
    public float $averageRating = 0.0;

    /**
     * @var array<int, array<string, int|string|null>>
     */
    #[Groups(['wiki_discussion:read'])]
    public array $comments = [];

    #[Groups(['wiki_discussion:write'])]
    public string $comment = '';

    #[Groups(['wiki_discussion:write'])]
    public ?int $rating = null;

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
