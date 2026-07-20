<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiPageActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiPageAction',
    operations: [
        new Post(
            uriTemplate: '/wiki/page/{pageId}/visibility',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Change Wiki page visibility',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_VISIBILITY,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/protection',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Change Wiki page edit protection',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_PROTECTION,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/subscription',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Subscribe or unsubscribe from Wiki page notifications',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_SUBSCRIPTION,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/delete',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Delete a Wiki page and all its versions',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_DELETE_PAGE,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/context/add-lock',
            openapi: new Operation(
                summary: 'Allow or block creation of new Wiki pages in the current context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_ADD_LOCK,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/context/subscription',
            openapi: new Operation(
                summary: 'Subscribe or unsubscribe from all Wiki changes in the current context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_CONTEXT_SUBSCRIPTION,
            processor: WikiPageActionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/context/delete',
            openapi: new Operation(
                summary: 'Delete every Wiki page in the current context',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            read: false,
            output: false,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: self::OPERATION_DELETE_CONTEXT,
            processor: WikiPageActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_page_action:read']],
    denormalizationContext: ['groups' => ['wiki_page_action:write']],
)]
final class WikiPageAction
{
    public const CSRF_TOKEN_ID = 'wiki_page_management';

    public const OPERATION_VISIBILITY = 'post_wiki_page_visibility';
    public const OPERATION_PROTECTION = 'post_wiki_page_protection';
    public const OPERATION_SUBSCRIPTION = 'post_wiki_page_subscription';
    public const OPERATION_DELETE_PAGE = 'post_wiki_page_delete';
    public const OPERATION_ADD_LOCK = 'post_wiki_context_add_lock';
    public const OPERATION_CONTEXT_SUBSCRIPTION = 'post_wiki_context_subscription';
    public const OPERATION_DELETE_CONTEXT = 'post_wiki_context_delete';

    #[Groups(['wiki_page_action:write'])]
    public string $csrfToken = '';

    #[Groups(['wiki_page_action:write'])]
    public bool $enabled = false;

    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_page_action:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_page_action:read'])]
    public string $reflink = '';

    #[Groups(['wiki_page_action:read'])]
    public bool $visible = true;

    #[Groups(['wiki_page_action:read'])]
    public bool $editLocked = false;

    #[Groups(['wiki_page_action:read'])]
    public bool $addLocked = false;

    #[Groups(['wiki_page_action:read'])]
    public bool $subscribed = false;

    #[Groups(['wiki_page_action:read'])]
    public bool $deleted = false;

    #[Groups(['wiki_page_action:read'])]
    public bool $contextDeleted = false;

    #[Groups(['wiki_page_action:read'])]
    public int $deletedVersions = 0;

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
