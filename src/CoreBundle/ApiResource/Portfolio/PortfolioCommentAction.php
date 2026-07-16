<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Portfolio\PortfolioCommentActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioCommentAction',
    operations: [
        new Post(
            uriTemplate: '/portfolio/comments/{id}/action',
            requirements: ['id' => '\d+'],
            openapi: new Operation(summary: 'Perform an authorized portfolio comment action'),
            read: false,
            security: "is_granted('ROLE_USER')",
            name: 'post_portfolio_comment_action',
            processor: PortfolioCommentActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_comment_action:read']],
    denormalizationContext: ['groups' => ['portfolio_comment_action:write']],
)]
final class PortfolioCommentAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_comment_action:read'])]
    public int $id = 0;

    #[Groups(['portfolio_comment_action:write'])]
    public string $action = '';

    #[Groups(['portfolio_comment_action:write'])]
    public string $csrfToken = '';

    #[Groups(['portfolio_comment_action:write'])]
    public ?int $visibility = null;

    /**
     * @var array<int, int|string>
     */
    #[Groups(['portfolio_comment_action:write'])]
    public array $recipientIds = [];

    #[Groups(['portfolio_comment_action:write'])]
    public ?float $score = null;

    #[Groups(['portfolio_comment_action:write'])]
    public ?int $attachmentId = null;

    #[Groups(['portfolio_comment_action:write'])]
    public string $title = '';

    #[Groups(['portfolio_comment_action:write'])]
    public string $content = '';

    /**
     * @var array<int, int|string>
     */
    #[Groups(['portfolio_comment_action:write'])]
    public array $studentIds = [];

    #[Groups(['portfolio_comment_action:read'])]
    public int $itemId = 0;

    /**
     * @var array<int, int>
     */
    #[Groups(['portfolio_comment_action:read'])]
    public array $affectedIds = [];

    #[Groups(['portfolio_comment_action:read'])]
    public bool $success = true;

    public function getId(): int
    {
        return $this->id;
    }
}
