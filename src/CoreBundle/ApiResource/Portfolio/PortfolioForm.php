<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Portfolio;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\Controller\Api\PortfolioFormAction;
use Chamilo\CoreBundle\State\Portfolio\PortfolioFormProcessor;
use Chamilo\CoreBundle\State\Portfolio\PortfolioFormProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'PortfolioForm',
    operations: [
        new Get(
            uriTemplate: '/portfolio/form',
            openapi: new Operation(
                summary: 'Portfolio item create or edit form data',
                parameters: [
                    new Parameter(name: 'cid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'sid', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_USER')",
            name: 'get_portfolio_form',
            provider: PortfolioFormProvider::class,
        ),
        new Post(
            uriTemplate: '/portfolio',
            controller: PortfolioFormAction::class,
            openapi: new Operation(summary: 'Create a portfolio item'),
            security: "is_granted('ROLE_USER')",
            read: false,
            deserialize: false,
            name: 'post_portfolio_item',
            processor: PortfolioFormProcessor::class,
        ),
        new Post(
            uriTemplate: '/portfolio/{id}/edit',
            requirements: ['id' => '\d+'],
            controller: PortfolioFormAction::class,
            openapi: new Operation(summary: 'Update a portfolio item'),
            security: "is_granted('ROLE_USER')",
            read: false,
            deserialize: false,
            name: 'post_portfolio_item_edit',
            processor: PortfolioFormProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['portfolio_form:read']],
)]
final class PortfolioForm
{
    #[ApiProperty(identifier: true)]
    #[Groups(['portfolio_form:read'])]
    public ?int $id = null;

    #[Groups(['portfolio_form:read'])]
    public string $mode = 'personal';

    #[Groups(['portfolio_form:read'])]
    public ?int $courseId = null;

    #[Groups(['portfolio_form:read'])]
    public ?int $sessionId = null;

    #[Groups(['portfolio_form:read'])]
    public string $title = '';

    #[Groups(['portfolio_form:read'])]
    public string $content = '';

    #[Groups(['portfolio_form:read'])]
    public ?int $categoryId = null;

    #[Groups(['portfolio_form:read'])]
    public int $visibility = 1;

    /**
     * @var array<int, int>
     */
    #[Groups(['portfolio_form:read'])]
    public array $recipientIds = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['portfolio_form:read'])]
    public array $tagIds = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['portfolio_form:read'])]
    public array $extraValues = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $categories = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $templates = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $tags = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $extraFields = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $attachments = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['portfolio_form:read'])]
    public array $recipientOptions = [];

    #[Groups(['portfolio_form:read'])]
    public bool $isNew = true;

    #[Groups(['portfolio_form:read'])]
    public bool $canEdit = false;

    #[Groups(['portfolio_form:read'])]
    public bool $advancedSharingEnabled = false;

    #[Groups(['portfolio_form:read'])]
    public bool $titleAsHtml = false;

    #[Groups(['portfolio_form:read'])]
    public string $csrfToken = '';

    public function getId(): ?int
    {
        return $this->id;
    }
}
