<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Wiki;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Wiki\WikiPageFormProcessor;
use Chamilo\CoreBundle\State\Wiki\WikiPageFormProvider;
use Chamilo\CoreBundle\State\Wiki\WikiPageLockProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'WikiPageForm',
    operations: [
        new Get(
            uriTemplate: '/wiki/form',
            openapi: new Operation(
                summary: 'Load Wiki page creation or edition data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'title', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'pageId', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'get_wiki_page_form',
            provider: WikiPageFormProvider::class,
        ),
        new Post(
            uriTemplate: '/wiki/page',
            openapi: new Operation(
                summary: 'Create a Wiki page',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'node', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_wiki_page',
            processor: WikiPageFormProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Create a new version of a Wiki page',
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
            read: false,
            name: 'post_wiki_page_version',
            processor: WikiPageFormProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/lock',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Acquire the Wiki page edition lock',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_wiki_page_lock',
            processor: WikiPageLockProcessor::class,
        ),
        new Post(
            uriTemplate: '/wiki/page/{pageId}/unlock',
            requirements: ['pageId' => '\d+'],
            openapi: new Operation(
                summary: 'Release the Wiki page edition lock',
                parameters: [
                    new Parameter(name: 'pageId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'gid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'isStudentView', in: 'query', required: false, schema: ['type' => 'boolean']),
                ],
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            read: false,
            name: 'post_wiki_page_unlock',
            processor: WikiPageLockProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['wiki_page_form:read']],
    denormalizationContext: ['groups' => ['wiki_page_form:write']],
)]
final class WikiPageForm
{
    #[Groups(['wiki_page_form:read'])]
    public ?int $iid = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['wiki_page_form:read'])]
    public ?int $pageId = null;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $reflink = 'index';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $title = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $content = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $comment = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $progress = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $language = '';

    /**
     * @var array<int, int>
     */
    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public array $categoryIds = [];

    /**
     * @var array<int, array{id:int, title:string, label:string, pathTitle:string, parentId:?int, level:int}>
     */
    #[Groups(['wiki_page_form:read'])]
    public array $categories = [];

    #[Groups(['wiki_page_form:read'])]
    public bool $categoriesEnabled = false;

    #[Groups(['wiki_page_form:read'])]
    public bool $canManageCategories = false;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $csrfToken = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $baseVersion = 0;

    #[Groups(['wiki_page_form:read'])]
    public int $version = 0;

    #[Groups(['wiki_page_form:read'])]
    public int $assignment = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public bool $createAssignment = false;

    #[Groups(['wiki_page_form:read'])]
    public bool $canConfigureAssignment = false;

    #[Groups(['wiki_page_form:read'])]
    public int $assignmentTargetCount = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $task = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $feedback1 = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $feedback2 = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public string $feedback3 = '';

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $feedbackProgress1 = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $feedbackProgress2 = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $feedbackProgress3 = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public ?string $startDate = null;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public ?string $endDate = null;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public bool $delayedSubmit = false;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $maxWords = 0;

    #[Groups(['wiki_page_form:read', 'wiki_page_form:write'])]
    public int $maxVersions = 0;

    #[Groups(['wiki_page_form:read'])]
    public string $assignmentOwnerName = '';

    #[Groups(['wiki_page_form:read'])]
    public bool $isNew = true;

    #[Groups(['wiki_page_form:read'])]
    public bool $isInheritedFromCourse = false;

    #[Groups(['wiki_page_form:read'])]
    public bool $canManage = false;

    #[Groups(['wiki_page_form:read'])]
    public bool $requiresLock = false;

    #[Groups(['wiki_page_form:read'])]
    public bool $lockAcquired = false;

    #[Groups(['wiki_page_form:read'])]
    public int $lockTimeoutMinutes = 20;

    /**
     * @var array<int, array{value:string, label:string}>
     */
    #[Groups(['wiki_page_form:read'])]
    public array $languages = [];

    /**
     * @var array<int, array{value:int, label:string}>
     */
    #[Groups(['wiki_page_form:read'])]
    public array $progressOptions = [];

    /**
     * @var array<string, bool>
     */
    #[Groups(['wiki_page_form:read'])]
    public array $settings = [];

    public function getPageId(): ?int
    {
        return $this->pageId;
    }
}
