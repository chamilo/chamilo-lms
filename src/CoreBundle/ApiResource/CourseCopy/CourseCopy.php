<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\ApiResource\CourseCopy;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use Chamilo\CoreBundle\State\CourseCopy\CourseCopyProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Administrative webservice that copies one course into another, or into a newly created course.
 * The copy is synchronous and can take a long time on a large source course; send one pair at a time.
 */
#[ApiResource(
    shortName: 'CourseCopy',
    operations: [
        new Post(
            uriTemplate: '/courses/copy',
            status: 200,
            openapi: new Operation(
                summary: 'Copy an existing course into another course, or into a newly created course',
                description: 'Platform administrators can copy one source course into one target course. The copy is synchronous and wraps the same CourseBuilder/CourseRestorer pipeline as the in-app course copy tool. Only one source and one target are accepted per request.',
            ),
            security: "is_granted('ROLE_ADMIN')",
            read: false,
            name: 'post_course_copy',
            processor: CourseCopyProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['course_copy:read']],
    denormalizationContext: ['groups' => ['course_copy:write']],
)]
final class CourseCopy
{
    #[ApiProperty(identifier: true)]
    #[Groups(['course_copy:read'])]
    public string $id = 'course_copy';

    #[Groups(['course_copy:write'])]
    public ?int $sourceId = null;

    #[Groups(['course_copy:write'])]
    public ?string $sourceCode = null;

    #[Groups(['course_copy:write', 'course_copy:read'])]
    public ?int $targetId = null;

    #[Groups(['course_copy:write', 'course_copy:read'])]
    public ?string $targetCode = null;

    #[Groups(['course_copy:write'])]
    public ?string $targetTitle = null;

    #[Groups(['course_copy:write'])]
    public ?int $targetCategoryId = null;

    #[Groups(['course_copy:write'])]
    public ?string $targetLanguage = null;

    /**
     * Optional allow-listed CourseBuilder tool keys. When omitted, every tool from a full in-app copy is included.
     *
     * @var list<string>|null
     */
    #[Groups(['course_copy:write'])]
    public ?array $tools = null;

    #[Groups(['course_copy:read'])]
    public string $status = 'copied';

    #[Groups(['course_copy:read'])]
    public bool $created = false;

    public function getId(): string
    {
        return $this->id;
    }
}
