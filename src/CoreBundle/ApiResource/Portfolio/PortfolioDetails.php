<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\Portfolio\PortfolioDetailsProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioDetails',
    operations: [
        new Get(
            uriTemplate: '/portfolio/details',
            openapi: new Operation(summary: 'Read Portfolio progress and scoring details'),
            security: "is_granted('ROLE_USER')",
            name: 'get_portfolio_details',
            provider: PortfolioDetailsProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_details:read']],
)]
final class PortfolioDetails
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_details:read'])]
    public string $id = 'portfolio_details';

    #[Groups(['portfolio_details:read'])]
    public string $mode = 'personal';

    /**
     * @var array<string, mixed>
     */
    #[Groups(['portfolio_details:read'])]
    public array $owner = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_details:read'])]
    public array $owners = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_details:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_details:read'])]
    public array $comments = [];

    #[Groups(['portfolio_details:read'])]
    public int $totalItems = 0;

    #[Groups(['portfolio_details:read'])]
    public int $requiredItems = 0;

    #[Groups(['portfolio_details:read'])]
    public int $totalComments = 0;

    #[Groups(['portfolio_details:read'])]
    public int $requiredComments = 0;

    #[Groups(['portfolio_details:read'])]
    public float $itemScoreTotal = 0.0;

    #[Groups(['portfolio_details:read'])]
    public float $commentScoreTotal = 0.0;

    #[Groups(['portfolio_details:read'])]
    public bool $canSelectOwner = false;

    #[Groups(['portfolio_details:read'])]
    public bool $canExport = true;

    public function getId(): string
    {
        return $this->id;
    }
}
