<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Portfolio\PortfolioItemProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioItem',
    operations: [
        new Get(
            uriTemplate: '/portfolio/{id}',
            requirements: ['id' => '\d+'],
            openapi: new Operation(
                summary: 'Read a portfolio item and its visible comments',
                parameters: [
                    new Parameter(name: 'id', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'cid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'user', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'get_portfolio_item',
            provider: PortfolioItemProvider::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_item:read']],
)]
final class PortfolioItem
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_item:read'])]
    public int $id = 0;

    #[Groups(['portfolio_item:read'])]
    public string $mode = 'personal';

    #[Groups(['portfolio_item:read'])]
    public ?int $courseId = null;

    #[Groups(['portfolio_item:read'])]
    public ?int $sessionId = null;

    /**
     * @var array<string, mixed>
     */
    #[Groups(['portfolio_item:read'])]
    public array $item = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_item:read'])]
    public array $comments = [];

    #[Groups(['portfolio_item:read'])]
    public string $csrfToken = '';

    #[Groups(['portfolio_item:read'])]
    public float $maxScore = 0.0;

    #[Groups(['portfolio_item:read'])]
    public bool $canQualifyItems = false;

    #[Groups(['portfolio_item:read'])]
    public bool $canQualifyComments = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_item:read'])]
    public array $commentTemplates = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_item:read'])]
    public array $recipientOptions = [];

    #[Groups(['portfolio_item:read'])]
    public bool $canComment = false;

    #[Groups(['portfolio_item:read'])]
    public bool $advancedSharingEnabled = false;

    public function getId(): int
    {
        return $this->id;
    }
}
