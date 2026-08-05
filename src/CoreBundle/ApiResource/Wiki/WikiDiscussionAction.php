<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiDiscussionActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiDiscussionAction',
    operations: [
        new Post(
            uriTemplate: '/wiki/page/{pageId}/discussion/visibility',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Show or hide one Wiki page discussion',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: self::OPERATION_VISIBILITY,
            processor: WikiDiscussionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/discussion/commenting',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Allow or block comments in one Wiki page discussion',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: self::OPERATION_COMMENTING,
            processor: WikiDiscussionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/discussion/rating',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Allow or block ratings in one Wiki page discussion',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: self::OPERATION_RATING,
            processor: WikiDiscussionActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/discussion/subscription',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Subscribe or unsubscribe from one Wiki page discussion',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            output: false,
            read: false,
            name: self::OPERATION_SUBSCRIPTION,
            processor: WikiDiscussionActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_discussion_action:read']],
    denormalizationContext: ['groups' => ['wiki_discussion_action:write']],
)]
final class WikiDiscussionAction
{
    public const string OPERATION_VISIBILITY = 'post_wiki_discussion_visibility';
    public const string OPERATION_COMMENTING = 'post_wiki_discussion_commenting';
    public const string OPERATION_RATING = 'post_wiki_discussion_rating';
    public const string OPERATION_SUBSCRIPTION = 'post_wiki_discussion_subscription';

    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_discussion_action:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_discussion_action:write'])]
    public string $csrfToken = '';

    #[Groups(['wiki_discussion_action:write'])]
    public bool $enabled = false;

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
