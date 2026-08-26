<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ApiResource\Exercise;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\RequestBody;
use ArrayObject;
use Chamilo\CoreBundle\State\Exercise\ExerciseRuntimeUploadAnswerProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'ExerciseRuntimeUploadAnswer',
    operations: [
        new Post(
            uriTemplate: '/exercise/runtime/{exerciseId}/attempt/{attemptId}/upload-answer',
            requirements: [
                'exerciseId' => '\d+',
                'attemptId' => '\d+',
            ],
            openapi: new Operation(
                summary: 'Upload a file, oral recording or completed Office document as a Vue exercise runtime answer',
                parameters: [
                    new Parameter(name: 'exerciseId', in: 'path', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'attemptId', in: 'path', required: true, schema: ['type' => 'integer']),
                ],
                requestBody: new RequestBody(
                    content: new ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'questionId' => ['type' => 'integer'],
                                    'secondsSpent' => ['type' => 'integer'],
                                    'reviewLater' => ['type' => 'boolean'],
                                    'navigationAction' => ['type' => 'string'],
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'Upload answer file, oral expression audio file or completed Office document',
                                    ],
                                ],
                            ],
                        ],
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['questionId'],
                                'properties' => [
                                    'questionId' => ['type' => 'integer'],
                                    'secondsSpent' => ['type' => 'integer'],
                                    'reviewLater' => ['type' => 'boolean'],
                                    'navigationAction' => ['type' => 'string'],
                                    'fileName' => ['type' => 'string'],
                                    'mimeType' => ['type' => 'string'],
                                    'base64Content' => [
                                        'type' => 'string',
                                        'description' => 'Base64-encoded file content for native/mobile clients',
                                    ],
                                ],
                            ],
                        ],
                    ]),
                ),
            ),
            security: "is_granted('ROLE_CURRENT_COURSE_STUDENT') or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')",
            deserialize: false,
            name: 'post_exercise_runtime_upload_answer',
            processor: ExerciseRuntimeUploadAnswerProcessor::class,
            parameters: [
                'cid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Course identifier',
                    required: true,
                ),
                'sid' => new QueryParameter(
                    schema: ['type' => 'integer'],
                    description: 'Session identifier',
                ),
            ],
        ),
    ],
    normalizationContext: ['groups' => ['exercise_runtime_upload_answer:read']],
)]
final class ExerciseRuntimeUploadAnswer
{
    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public ?int $exerciseId = null;

    #[ApiProperty(identifier: true)]
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public ?int $attemptId = null;

    #[Groups(['exercise_runtime_upload_answer:read'])]
    public ?int $questionId = null;

    #[Groups(['exercise_runtime_upload_answer:read'])]
    public bool $success = false;

    #[Groups(['exercise_runtime_upload_answer:read'])]
    public string $message = '';

    /**
     * @var array<int, array{id: int, name: string, size: int, mimeType: string, url: string}>
     */
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public array $files = [];

    /**
     * @var array<int, array{answer: string, position: int|null}>
     */
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public array $savedAnswer = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public array $answeredQuestionIds = [];

    /**
     * @var array<int, int>
     */
    #[Groups(['exercise_runtime_upload_answer:read'])]
    public array $reviewQuestionIds = [];

    #[Groups(['exercise_runtime_upload_answer:read'])]
    public int $answeredCount = 0;

    #[Groups(['exercise_runtime_upload_answer:read'])]
    public bool $canFinish = false;
    public function getExerciseId(): ?int
    {
        return $this->exerciseId;
    }

    public function getAttemptId(): ?int
    {
        return $this->attemptId;
    }
}
