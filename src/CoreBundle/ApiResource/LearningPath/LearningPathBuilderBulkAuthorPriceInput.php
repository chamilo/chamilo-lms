<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\LearningPath;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\LearningPath\LearningPathBuilderMutationProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/learning_paths/{lpId}/builder/author-price',
            requirements: ['lpId' => '\\d+'],
            read: false,
            output: false,
            status: Response::HTTP_NO_CONTENT,
            name: 'update_learning_path_builder_author_price',
            processor: LearningPathBuilderMutationProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['learning_path_builder_author_price:read']],
    denormalizationContext: ['groups' => ['learning_path_builder_author_price:write']],
)]
final class LearningPathBuilderBulkAuthorPriceInput
{
    #[ApiProperty(identifier: true)]
    #[Groups(['learning_path_builder_author_price:read'])]
    public ?int $lpId = null;

    /** @var int[] */
    #[Groups(['learning_path_builder_author_price:write'])]
    public array $itemIds = [];

    /** @var int[] */
    #[Groups(['learning_path_builder_author_price:write'])]
    public array $authorIds = [];

    #[Groups(['learning_path_builder_author_price:write'])]
    public bool $removeAuthors = false;

    #[Groups(['learning_path_builder_author_price:write'])]
    public ?float $price = null;

    #[Groups(['learning_path_builder_author_price:write'])]
    public string $csrfToken = '';

    public function getLpId(): ?int
    {
        return $this->lpId;
    }
}
