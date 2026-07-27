<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\Controller\Api\PortfolioCommentFormAction;
use Chamilo\CoreBundle\State\Portfolio\PortfolioCommentFormProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioCommentForm',
    operations: [
        new Post(
            uriTemplate: '/portfolio/comments',
            controller: PortfolioCommentFormAction::class,
            openapi: new Operation(summary: 'Create a portfolio comment or reply'),
            security: "is_granted('ROLE_USER')",
            output: false,
            read: false,
            deserialize: false,
            name: 'post_portfolio_comment',
            processor: PortfolioCommentFormProcessor::class,
        ),
        new Post(
            uriTemplate: '/portfolio/comments/edit',
            controller: PortfolioCommentFormAction::class,
            openapi: new Operation(summary: 'Update a portfolio comment'),
            security: "is_granted('ROLE_USER')",
            output: false,
            read: false,
            deserialize: false,
            name: 'post_portfolio_comment_edit',
            processor: PortfolioCommentFormProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_comment_form:read']],
)]
final class PortfolioCommentForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_comment_form:read'])]
    public ?int $id = null;

    #[Groups(['portfolio_comment_form:read'])]
    public int $itemId = 0;

    #[Groups(['portfolio_comment_form:read'])]
    public bool $success = true;

    public function getId(): ?int
    {
        return $this->id;
    }
}
