<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Component\Gradebook\CourseCompletionRuleEvaluator;
use Doctrine\DBAL\Connection;
use JsonException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const DATE_ATOM;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'chamilo:migration:evaluate-course-completion',
    description: 'Evaluate one configured course-completion rule without changing gradebook, tracking or certificates.'
)]
final class EvaluateCourseCompletionRuleCommand extends Command
{
    private readonly CourseCompletionRuleEvaluator $evaluator;

    public function __construct(
        private readonly Connection $connection
    ) {
        // CoreBundle Component classes are excluded from service auto-discovery.
        $this->evaluator = new CourseCompletionRuleEvaluator($connection);

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'course-code',
                null,
                InputOption::VALUE_REQUIRED,
                'Course code to evaluate.'
            )
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_REQUIRED,
                'User ID to evaluate.'
            )
            ->addOption(
                'category-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Root gradebook category ID. Required only when the course has multiple roots.'
            )
            ->addOption(
                'session-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional session ID override.'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Optional path where the JSON evaluation report will be written.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Evaluate course completion rule');

        $courseCode = trim((string) $input->getOption('course-code'));
        $userId = (int) $input->getOption('user-id');
        $requestedCategoryId = (int) $input->getOption('category-id');
        $requestedSessionId = $input->getOption('session-id');

        if ('' === $courseCode || $userId <= 0) {
            $io->error('Both --course-code and a positive --user-id are required.');

            return Command::INVALID;
        }

        try {
            $course = $this->connection->fetchAssociative(
                'SELECT id, code, title FROM course WHERE code = :code LIMIT 1',
                ['code' => $courseCode]
            );
            if (false === $course) {
                throw new RuntimeException(\sprintf('Course %s was not found.', $courseCode));
            }

            $courseId = (int) $course['id'];
            if (!$this->evaluator->supports($courseId)) {
                throw new RuntimeException(\sprintf('No course completion rule is configured for course %s.', $courseCode));
            }

            $categories = $this->connection->fetchAllAssociative(
                <<<'SQL'
SELECT id, certif_min_score, session_id, title
FROM gradebook_category
WHERE c_id = :courseId
  AND parent_id IS NULL
ORDER BY id
SQL,
                ['courseId' => $courseId]
            );
            if ([] === $categories) {
                throw new RuntimeException(\sprintf('No root gradebook category was found for course %s.', $courseCode));
            }

            $category = $this->selectCategory($categories, $requestedCategoryId);
            $sessionId = null === $requestedSessionId
                ? (int) ($category['session_id'] ?? 0)
                : max(0, (int) $requestedSessionId);
            $minimumScore = (float) ($category['certif_min_score'] ?? 0);

            $result = $this->evaluator->evaluate(
                $userId,
                $courseId,
                (string) $course['code'],
                $minimumScore,
                $sessionId
            );

            $io->definitionList(
                ['Course' => \sprintf('%s — %s', $course['code'], $course['title'])],
                ['Course ID' => $courseId],
                ['Category ID' => (int) $category['id']],
                ['Category title' => (string) $category['title']],
                ['User ID' => $userId],
                ['Session ID' => $sessionId],
                ['Minimum score' => $minimumScore],
                ['Rule complete' => $result['complete'] ? 'yes' : 'no'],
                ['Calculated score' => null === $result['score'] ? 'not available' : $this->formatNumber($result['score'])],
                ['Partial score' => $this->formatNumber($result['partial_score'])],
                ['Finished' => $result['finished'] ? 'yes' : 'no']
            );

            $rows = [];
            foreach ($result['components'] as $component) {
                $rows[] = [
                    (string) $component['type'],
                    (string) $component['resource_id'],
                    null === $component['mapped_resource_id']
                        ? '-'
                        : (string) $component['mapped_resource_id'],
                    null === $component['attempts'] ? '-' : (string) $component['attempts'],
                    null === $component['raw_score']
                        ? '-'
                        : $this->formatNumber((float) $component['raw_score']),
                    $this->formatNumber((float) $component['weight']),
                    null === $component['score']
                        ? '-'
                        : $this->formatNumber((float) $component['score']),
                    (string) $component['status'],
                ];
            }

            if ([] !== $rows) {
                $io->table(
                    ['Type', 'Source ID', 'Resource ID', 'Rows', 'Raw %/score', 'Weight/max', 'Contribution', 'Status'],
                    $rows
                );
            }

            foreach ($result['warnings'] as $warning) {
                $io->warning($warning);
            }
            foreach ($result['errors'] as $error) {
                $io->error($error);
            }

            $outputPath = trim((string) $input->getOption('output'));
            if ('' !== $outputPath) {
                $this->writeJsonReport($outputPath, [
                    'generated_at' => gmdate(DATE_ATOM),
                    'category_id' => (int) $category['id'],
                    'evaluation' => $result,
                ]);
                $io->note(\sprintf('JSON report written to %s', $outputPath));
            }

            if (!$result['complete']) {
                $io->error('Evaluation stopped safely because the rule is incomplete or inconsistent.');

                return Command::FAILURE;
            }

            $io->success('Read-only course completion evaluation completed.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @param list<array<string, mixed>> $categories
     *
     * @return array<string, mixed>
     */
    private function selectCategory(array $categories, int $requestedCategoryId): array
    {
        if ($requestedCategoryId > 0) {
            foreach ($categories as $category) {
                if ($requestedCategoryId === (int) $category['id']) {
                    return $category;
                }
            }

            throw new RuntimeException(\sprintf('Category %d is not a root gradebook category of the selected course.', $requestedCategoryId));
        }

        if (1 === \count($categories)) {
            return $categories[0];
        }

        $categoryIds = array_map(
            static fn (array $category): string => (string) $category['id'],
            $categories
        );

        throw new RuntimeException(\sprintf('The course has multiple root gradebook categories (%s). Re-run with --category-id.', implode(', ', $categoryIds)));
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    /**
     * @param array<string, mixed> $report
     */
    private function writeJsonReport(string $outputPath, array $report): void
    {
        $directory = \dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException(\sprintf('Could not create output directory %s.', $directory));
        }

        try {
            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Could not encode the JSON report.', 0, $exception);
        }

        if (false === file_put_contents($outputPath, $json."\n")) {
            throw new RuntimeException(\sprintf('Could not write JSON report to %s.', $outputPath));
        }
    }
}
