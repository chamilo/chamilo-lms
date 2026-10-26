<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Page;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Page\PageLanguageDeleteProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PageLanguageDeleteAction',
    operations: [
        new Post(
            uriTemplate: '/pages/{pageId}/delete-languages',
            requirements: ['pageId' => '\d+'],
            status: 200,
            openapi: new Operation(summary: 'Delete explicitly selected language versions of a CMS page'),
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_page_delete_languages',
            processor: PageLanguageDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['page_language_delete:read']],
    denormalizationContext: ['groups' => ['page_language_delete:write']],
)]
final class PageLanguageDeleteAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['page_language_delete:read'])]
    public ?int $pageId = null;

    /**
     * @var int[]
     */
    #[Groups(['page_language_delete:write'])]
    public array $pageIds = [];

    #[Groups(['page_language_delete:read'])]
    public int $deletedCount = 0;
}
