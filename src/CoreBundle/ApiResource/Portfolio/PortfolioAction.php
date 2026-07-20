<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Portfolio\PortfolioActionProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioAction',
    operations: [
        new Post(
            uriTemplate: '/portfolio/{id}/action',
            requirements: ['id' => '\d+'],
            openapi: new Operation(summary: 'Perform an authorized portfolio item action'),
            read: false,
            security: "is_granted('ROLE_USER')",
            name: 'post_portfolio_item_action',
            processor: PortfolioActionProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_action:read']],
    denormalizationContext: ['groups' => ['portfolio_action:write']],
)]
final class PortfolioAction
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_action:read'])]
    public int $id = 0;

    #[Groups(['portfolio_action:write'])]
    public string $action = '';

    #[Groups(['portfolio_action:write'])]
    public string $csrfToken = '';

    #[Groups(['portfolio_action:write'])]
    public ?int $visibility = null;

    /**
     * @var array<int, int|string>
     */
    #[Groups(['portfolio_action:write'])]
    public array $recipientIds = [];

    #[Groups(['portfolio_action:write'])]
    public ?float $score = null;

    #[Groups(['portfolio_action:write'])]
    public ?int $attachmentId = null;

    #[Groups(['portfolio_action:write'])]
    public string $title = '';

    #[Groups(['portfolio_action:write'])]
    public string $content = '';

    /**
     * @var array<int, int|string>
     */
    #[Groups(['portfolio_action:write'])]
    public array $studentIds = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['portfolio_action:read'])]
    public array $affectedIds = [];

    #[Groups(['portfolio_action:read'])]
    public bool $success = true;

    public function getId(): int
    {
        return $this->id;
    }
}
