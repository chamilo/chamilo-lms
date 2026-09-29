<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CourseBundle\Entity\CQuiz;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use const DATE_ATOM;

final readonly class UpdateCourseTestSettingsTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{updated: true, changed_fields: list<string>, test: array<string, mixed>}
     */
    #[McpTool(
        name: 'update_course_test_settings',
        description: 'Edit the configuration of an existing test in a course managed by the authenticated teacher. Locate it by testId or exact testTitle. Supports title, description, random answer order, maximum attempts, duration, availability dates, pass percentage and draft/published visibility. Send an empty startAt or endAt value to clear that date.',
    )]
    public function updateCourseTestSettings(
        int $courseId,
        ?int $testId = null,
        ?string $testTitle = null,
        ?string $title = null,
        ?string $description = null,
        ?bool $randomAnswers = null,
        ?int $maxAttempts = null,
        ?int $durationMinutes = null,
        ?string $startAt = null,
        ?string $endAt = null,
        ?int $passPercentage = null,
        ?bool $publish = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $quiz = $this->resolveQuiz($courseId, $testId, $testTitle);
            $resourceLink = $this->resolveBaseCourseLink($course, $quiz);
            $changedFields = [];

            if (null !== $title) {
                $title = trim(strip_tags($title));
                if ('' === $title || mb_strlen($title) > 255) {
                    throw new InvalidArgumentException('The test title is required and cannot be longer than 255 characters.');
                }
                if ($title !== $quiz->getTitle()) {
                    $quiz->setTitle($title);
                    $quiz->getResourceNode()?->setTitle($title);
                    $changedFields[] = 'title';
                }
            }

            if (null !== $description) {
                if (mb_strlen($description) > 2_000_000) {
                    throw new InvalidArgumentException('The test description is too large.');
                }
                $description = (string) Security::remove_XSS($description);
                if ($description !== (string) $quiz->getDescription()) {
                    $quiz->setDescription($description);
                    $changedFields[] = 'description';
                }
            }

            if (null !== $randomAnswers && $randomAnswers !== $quiz->getRandomAnswers()) {
                $quiz->setRandomAnswers($randomAnswers);
                $changedFields[] = 'random_answers';
            }

            if (null !== $maxAttempts) {
                if ($maxAttempts < 0 || $maxAttempts > 1000) {
                    throw new InvalidArgumentException('The maximum attempts value must be between 0 and 1000. Use 0 for unlimited attempts.');
                }
                if ($maxAttempts !== $quiz->getMaxAttempt()) {
                    $quiz->setMaxAttempt($maxAttempts);
                    $changedFields[] = 'max_attempts';
                }
            }

            if (null !== $durationMinutes) {
                if ($durationMinutes < 0 || $durationMinutes > 10080) {
                    throw new InvalidArgumentException('The duration must be between 0 and 10080 minutes. Use 0 for no time limit.');
                }
                $duration = 0 === $durationMinutes ? null : $durationMinutes * 60;
                if ($duration !== $quiz->getDuration()) {
                    $quiz->setDuration($duration);
                    $changedFields[] = 'duration_minutes';
                }
            }

            if (null !== $startAt) {
                $startTime = $this->parseDate($startAt, 'startAt');
                if ($startTime?->getTimestamp() !== $quiz->getStartTime()?->getTimestamp()) {
                    $quiz->setStartTime($startTime);
                    $changedFields[] = 'start_at';
                }
            }

            if (null !== $endAt) {
                $endTime = $this->parseDate($endAt, 'endAt');
                if ($endTime?->getTimestamp() !== $quiz->getEndTime()?->getTimestamp()) {
                    $quiz->setEndTime($endTime);
                    $changedFields[] = 'end_at';
                }
            }

            if (null !== $passPercentage) {
                if ($passPercentage < 0 || $passPercentage > 100) {
                    throw new InvalidArgumentException('The pass percentage must be between 0 and 100.');
                }
                if ($passPercentage !== (int) $quiz->getPassPercentage()) {
                    $quiz->setPassPercentage($passPercentage);
                    $changedFields[] = 'pass_percentage';
                }
            }

            if (null !== $publish) {
                $visibility = $publish
                    ? ResourceLink::VISIBILITY_PUBLISHED
                    : ResourceLink::VISIBILITY_DRAFT;
                if ($resourceLink->getVisibility() !== $visibility) {
                    $resourceLink->setVisibility($visibility);
                    $this->entityManager->persist($resourceLink);
                    $changedFields[] = 'visibility';
                }
            }

            if (null !== $quiz->getStartTime()
                && null !== $quiz->getEndTime()
                && $quiz->getEndTime() <= $quiz->getStartTime()
            ) {
                throw new InvalidArgumentException('The test end date must be later than the start date.');
            }

            if ([] === $changedFields) {
                throw new InvalidArgumentException('No test setting change was provided.');
            }

            $this->entityManager->persist($quiz);
            if (null !== $quiz->getResourceNode()) {
                $this->entityManager->persist($quiz->getResourceNode());
            }
            $this->entityManager->flush();

            return [
                'updated' => true,
                'changed_fields' => array_values(array_unique($changedFields)),
                'test' => [
                    'quiz_id' => (int) $quiz->getIid(),
                    'title' => $quiz->getTitle(),
                    'description' => $quiz->getDescription(),
                    'random_answers' => $quiz->getRandomAnswers(),
                    'max_attempts' => $quiz->getMaxAttempt(),
                    'duration_minutes' => null !== $quiz->getDuration() ? (int) round($quiz->getDuration() / 60) : 0,
                    'start_at' => $quiz->getStartTime()?->format(DATE_ATOM),
                    'end_at' => $quiz->getEndTime()?->format(DATE_ATOM),
                    'pass_percentage' => $quiz->getPassPercentage(),
                    'visibility' => $resourceLink->getVisibility(),
                    'published' => ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility(),
                    'content_url' => '/resources/exercise/'.$quiz->getResourceNode()?->getId().'/'.$quiz->getIid().'/edit?cid='.$courseId,
                ],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test settings could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    private function resolveQuiz(int $courseId, ?int $testId, ?string $testTitle): CQuiz
    {
        $testId = null !== $testId && $testId > 0 ? $testId : null;
        $testTitle = null !== $testTitle ? trim($testTitle) : '';
        if (null === $testId && '' === $testTitle) {
            throw new InvalidArgumentException('Provide either testId or testTitle.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter('courseId', $courseId, Types::INTEGER)
        ;

        if (null !== $testId) {
            $queryBuilder
                ->andWhere('quiz.iid = :testId')
                ->setParameter('testId', $testId, Types::INTEGER)
            ;
        } else {
            $queryBuilder
                ->andWhere('quiz.title = :testTitle')
                ->setParameter('testTitle', $testTitle, Types::STRING)
            ;
        }

        /** @var list<CQuiz> $matches */
        $matches = $queryBuilder->getQuery()->getResult();
        if ([] === $matches) {
            throw new InvalidArgumentException('The test was not found in this course.');
        }
        if (\count($matches) > 1) {
            throw new InvalidArgumentException('More than one test has this title. Provide testId to disambiguate.');
        }

        return $matches[0];
    }

    private function resolveBaseCourseLink(Course $course, CQuiz $quiz): ResourceLink
    {
        $resourceLink = $quiz->getResourceNode()?->getResourceLinkByContext($course, null, null);
        if (!$resourceLink instanceof ResourceLink) {
            throw new RuntimeException('The test is not linked to the selected base course.');
        }

        return $resourceLink;
    }

    private function parseDate(string $value, string $field): ?DateTime
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        try {
            return new DateTime($value);
        } catch (Throwable) {
            throw new InvalidArgumentException(\sprintf('The %s value must be a valid ISO 8601 date and time.', $field));
        }
    }
}
