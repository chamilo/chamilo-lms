<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Page;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Page\PageBulkDeleteProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PageBulkDeleteAction',
    operations: [
        new Post(
            uriTemplate: '/pages/actions/bulk-delete',
            status: 200,
            openapi: new Operation(summary: 'Delete explicitly selected CMS pages in the current portal'),
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_page_bulk_delete',
            processor: PageBulkDeleteProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['page_bulk_delete:read']],
    denormalizationContext: ['groups' => ['page_bulk_delete:write']],
)]
final class PageBulkDeleteAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['page_bulk_delete:read'])]
    public string $id = 'page_bulk_delete';

    /**
     * @var int[]
     */
    #[Groups(['page_bulk_delete:write'])]
    public array $pageIds = [];

    #[Groups(['page_bulk_delete:read'])]
    public int $deletedCount = 0;

    public function getId(): string
    {
        return $this->id;
    }
}
