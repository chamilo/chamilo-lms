<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\Forum;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\Forum\ForumThreadGradingProcessor;
use Chamilo\CoreBundle\State\Forum\ForumThreadGradingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/forum_threads/{threadId}/grading',
            name: 'get_forum_thread_grading',
            openapi: new Operation(
                parameters: [
                    new Parameter(
                        name: 'threadId',
                        in: 'path',
                        description: 'Thread id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'cid',
                        in: 'query',
                        description: 'Course id',
                        required: true,
                        schema: ['type' => 'integer'],
                    ),
                    new Parameter(
                        name: 'sid',
                        in: 'query',
                        description: 'Session id',
                        required: false,
                        schema: ['type' => 'integer'],
                    ),
                ],
            ),
            provider: ForumThreadGradingProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
        new Put(
            uriTemplate: '/forum_threads/{threadId}/grading',
            name: 'update_forum_thread_grading',
            input: ForumThreadGradingInput::class,
            processor: ForumThreadGradingProcessor::class,
            read: false,
            openapi: new Operation(
                summary: 'Update forum thread grading settings',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'enabled' => ['type' => 'boolean'],
                                    'categoryId' => ['type' => 'integer'],
                                    'maxScore' => ['type' => 'number'],
                                    'weight' => ['type' => 'number'],
                                    'title' => ['type' => 'string'],
                                    'peerQualify' => ['type' => 'boolean'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['enabled', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_TEACHER') or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER') or is_granted('ROLE_ADMIN')",
        ),
        new Put(
            uriTemplate: '/forum_threads/{threadId}/grading/score',
            name: 'save_forum_thread_score',
            input: ForumThreadScoreInput::class,
            processor: ForumThreadGradingProcessor::class,
            read: false,
            openapi: new Operation(
                summary: 'Save a forum thread score for a user',
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'userId' => ['type' => 'integer'],
                                    'score' => ['type' => 'number'],
                                    'csrfToken' => ['type' => 'string'],
                                ],
                                'required' => ['userId', 'score', 'csrfToken'],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
        ),
    ],
    normalizationContext: [
        'groups' => ['forum_thread_grading:read'],
    ],
)]
final class ForumThreadGrading
{
    #[ApiProperty(identifier: true)]
    #[Groups(['forum_thread_grading:read'])]
    public int $threadId = 0;

    #[Groups(['forum_thread_grading:read'])]
    public bool $enabled = false;

    #[Groups(['forum_thread_grading:read'])]
    public ?int $categoryId = null;

    #[Groups(['forum_thread_grading:read'])]
    public string $title = '';

    #[Groups(['forum_thread_grading:read'])]
    public float $maxScore = 0.0;

    #[Groups(['forum_thread_grading:read'])]
    public float $weight = 0.0;

    #[Groups(['forum_thread_grading:read'])]
    public bool $peerQualify = false;

    #[Groups(['forum_thread_grading:read'])]
    public bool $canManage = false;

    #[Groups(['forum_thread_grading:read'])]
    public bool $canPeerGrade = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['forum_thread_grading:read'])]
    public array $categories = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    #[Groups(['forum_thread_grading:read'])]
    public array $students = [];

    public function getId(): int
    {
        return $this->threadId;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(int $threadId, array $data): self
    {
        $grading = new self();
        $grading->threadId = $threadId;
        $grading->enabled = (bool) ($data['enabled'] ?? false);
        $grading->categoryId = isset($data['categoryId']) ? (int) $data['categoryId'] : null;
        $grading->title = (string) ($data['title'] ?? '');
        $grading->maxScore = (float) ($data['maxScore'] ?? 0);
        $grading->weight = (float) ($data['weight'] ?? 0);
        $grading->peerQualify = (bool) ($data['peerQualify'] ?? false);
        $grading->canManage = (bool) ($data['canManage'] ?? false);
        $grading->canPeerGrade = (bool) ($data['canPeerGrade'] ?? false);

        $categories = $data['categories'] ?? [];
        $grading->categories = \is_array($categories) ? $categories : [];

        $students = $data['students'] ?? [];
        $grading->students = \is_array($students) ? $students : [];

        return $grading;
    }
}
