<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Admin;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use Chamilo\CoreBundle\State\Admin\AdminQuestionBankProcessor;
use Chamilo\CoreBundle\State\Admin\AdminQuestionBankProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AdminQuestionBank',
    operations: [
        new Get(
            uriTemplate: '/admin/questions',
            openapi: new Operation(
                summary: 'Global question bank for administrators and question managers',
                parameters: [
                    new Parameter(name: 'form_sent', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'page', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'itemsPerPage', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'id', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'title', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'description', in: 'query', required: false, schema: ['type' => 'string']),
                    new Parameter(name: 'selected_course', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'question_level', in: 'query', required: false, schema: ['type' => 'integer']),
                    new Parameter(name: 'answer_type', in: 'query', required: false, schema: ['type' => 'integer']),
                ],
            ),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_QUESTION_MANAGER')",
            name: 'get_admin_question_bank',
            provider: AdminQuestionBankProvider::class,
        ),
        new Post(
            uriTemplate: '/admin/questions/action',
            openapi: new Operation(summary: 'Run a global question bank administrative action'),
            security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_QUESTION_MANAGER')",
            name: 'post_admin_question_bank_action',
            processor: AdminQuestionBankProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['admin_question_bank:read']],
    denormalizationContext: ['groups' => ['admin_question_bank:write']],
)]
final class AdminQuestionBank
{
    #[ApiProperty(identifier: true)]
    #[Groups(['admin_question_bank:read'])]
    public string $id = 'admin_question_bank';

    #[Groups(['admin_question_bank:write'])]
    public string $action = '';

    #[Groups(['admin_question_bank:write'])]
    public ?int $questionId = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $items = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $courseOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $difficultyOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $questionTypeOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $extraFields = [];

    /**
     * @var array<string, mixed>
     */
    #[Groups(['admin_question_bank:read'])]
    public array $filters = [];

    #[Groups(['admin_question_bank:read'])]
    public int $page = 1;

    #[Groups(['admin_question_bank:read'])]
    public int $itemsPerPage = 20;

    #[Groups(['admin_question_bank:read'])]
    public int $totalItems = 0;

    #[Groups(['admin_question_bank:read'])]
    public bool $searched = false;

    #[Groups(['admin_question_bank:read'])]
    public bool $success = false;

    #[Groups(['admin_question_bank:read'])]
    public string $message = '';
}
