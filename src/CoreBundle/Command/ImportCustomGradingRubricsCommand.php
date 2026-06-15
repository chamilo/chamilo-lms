<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Imports the client's 44 hardcoded 1.11.x grading rubrics into native 2.x
 * gradebook categories running in POINTS_SUM mode.
 *
 * Each course is resolved by its course CODE (the JSON key), which is stable
 * across the 1.11.x -> 2.0 migration. Resource references (exerciseId, parentId,
 * evaluationId, thread ids) are taken from the seed as 2.x identifiers and
 * validated for existence; anything not found is logged and skipped instead of
 * creating a dangling link. Always run --dry-run first and confirm the report.
 */
#[AsCommand(
    name: 'chamilo:import-custom-grading-rubrics',
    description: 'Import the custom grading rubrics seed as native POINTS_SUM gradebook categories.',
)]
class ImportCustomGradingRubricsCommand extends Command
{
    private const LINK_EXERCISE = 1;
    private const LINK_STUDENTPUBLICATION = 3;
    private const LINK_FORUM_PARTICIPATION = 11;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to the seed JSON file.')
            ->addOption('owner-id', null, InputOption::VALUE_REQUIRED, 'User id used as the category owner.', '1')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist anything, only report.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = (string) $input->getOption('file');
        if ('' === $file || !is_file($file)) {
            $io->error(\sprintf('Seed file not found: %s', $file));

            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $ownerId = (int) $input->getOption('owner-id');

        $owner = $this->em->getRepository(User::class)->find($ownerId);
        if (null === $owner) {
            $io->error(\sprintf('Owner user #%d not found.', $ownerId));

            return Command::FAILURE;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!\is_array($data)) {
            $io->error('Could not decode the seed JSON.');

            return Command::FAILURE;
        }

        $courseRepo = $this->em->getRepository(Course::class);
        $createdCategories = 0;
        $createdItems = 0;
        $skipped = [];

        foreach ($data as $courseCode => $course) {
            $courseCode = (string) $courseCode;

            /** @var Course|null $courseEntity */
            $courseEntity = $courseRepo->findOneBy(['code' => $courseCode]);
            if (null === $courseEntity) {
                $skipped[] = \sprintf('Course "%s" not found by code; whole rubric skipped.', $courseCode);

                continue;
            }

            $category = new GradebookCategory();
            $category->setTitle(\sprintf('%s rubric', $courseCode));
            $category->setUser($owner);
            $category->setCourse($courseEntity);
            $category->setWeight(100.0);
            $category->setVisible(true);
            $category->setCalculationMode(GradebookCalculationMode::POINTS_SUM);

            if (!$dryRun) {
                $this->em->persist($category);
            }
            $createdCategories++;

            foreach (($course['components'] ?? []) as $component) {
                $items = $this->buildItems($component, $courseEntity, $category, $courseCode, $skipped, $dryRun);
                $createdItems += $items;
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        foreach ($skipped as $message) {
            $io->warning($message);
        }

        $io->success(\sprintf(
            '%s: %d categories, %d items (%d issues).',
            $dryRun ? 'Dry-run' : 'Imported',
            $createdCategories,
            $createdItems,
            \count($skipped)
        ));

        return Command::SUCCESS;
    }

    /**
     * Builds the native gradebook items for a single seed component.
     *
     * @param array<string, mixed> $component
     * @param string[]             $skipped   Collects human-readable skip reasons (by reference)
     *
     * @return int Number of items created
     */
    private function buildItems(
        array $component,
        Course $course,
        GradebookCategory $category,
        string $courseCode,
        array &$skipped,
        bool $dryRun
    ): int {
        $type = (string) ($component['type'] ?? '');

        return match ($type) {
            'exercise' => $this->createLink(
                self::LINK_EXERCISE,
                (int) ($component['exerciseId'] ?? 0),
                $this->weightToPoints($component['weight'] ?? 0),
                CQuiz::class,
                $course,
                $category,
                $courseCode,
                $skipped,
                $dryRun
            ),
            'assignment' => $this->createLink(
                self::LINK_STUDENTPUBLICATION,
                (int) ($component['parentId'] ?? 0),
                $this->weightToPoints($component['weight'] ?? 0),
                CStudentPublication::class,
                $course,
                $category,
                $courseCode,
                $skipped,
                $dryRun
            ),
            'gradebook_result' => $this->createEvaluation(
                (int) ($component['evaluationId'] ?? 0),
                $this->weightToPoints($component['weight'] ?? 0),
                $course,
                $category,
                $dryRun
            ),
            'forum' => $this->createForumItems($component, $course, $category, $courseCode, $skipped, $dryRun),
            default => $this->skip($skipped, \sprintf('Course "%s": unknown component type "%s".', $courseCode, $type)),
        };
    }

    /**
     * Migration rule: points weight = multiplier × 100 (e.g. 0.20 -> 20).
     */
    private function weightToPoints(mixed $multiplier): float
    {
        return round((float) $multiplier * 100, 4);
    }

    /**
     * Creates a native gradebook link of the given type after validating the referenced resource exists.
     *
     * @param class-string $resourceClass
     * @param string[]     $skipped
     */
    private function createLink(
        int $type,
        int $refId,
        float $weight,
        string $resourceClass,
        Course $course,
        GradebookCategory $category,
        string $courseCode,
        array &$skipped,
        bool $dryRun
    ): int {
        if ($refId <= 0 || null === $this->em->getRepository($resourceClass)->find($refId)) {
            return $this->skip(
                $skipped,
                \sprintf('Course "%s": %s #%d not found, link skipped.', $courseCode, $resourceClass, $refId)
            );
        }

        $link = new GradebookLink();
        $link->setType($type);
        $link->setRefId($refId);
        $link->setCourse($course);
        $link->setCategory($category);
        $link->setWeight($weight);
        $link->setVisible(1);
        $link->setLocked(0);

        if (!$dryRun) {
            $this->em->persist($link);
        }

        return 1;
    }

    /**
     * Creates one ForumParticipationLink per thread in the component, all sharing the same one/many points.
     *
     * @param array<string, mixed> $component
     * @param string[]             $skipped
     */
    private function createForumItems(
        array $component,
        Course $course,
        GradebookCategory $category,
        string $courseCode,
        array &$skipped,
        bool $dryRun
    ): int {
        $pointsOne = (string) round((float) ($component['one'] ?? 0), 4);
        $pointsMany = (string) round((float) ($component['many'] ?? 0), 4);
        $created = 0;

        foreach (($component['threads'] ?? []) as $threadId) {
            $threadId = (int) $threadId;
            if ($threadId <= 0 || null === $this->em->getRepository(CForumThread::class)->find($threadId)) {
                $this->skip($skipped, \sprintf('Course "%s": forum thread #%d not found, skipped.', $courseCode, $threadId));

                continue;
            }

            $link = new GradebookLink();
            $link->setType(self::LINK_FORUM_PARTICIPATION);
            $link->setRefId($threadId);
            $link->setCourse($course);
            $link->setCategory($category);
            // The item's max points (pointsMany) is also its weight, so in POINTS_SUM the
            // contribution equals the earned points (score/max × weight = score).
            $link->setWeight((float) $pointsMany);
            $link->setVisible(1);
            $link->setLocked(0);
            $link->setPointsOne($pointsOne);
            $link->setPointsMany($pointsMany);

            if (!$dryRun) {
                $this->em->persist($link);
            }
            $created++;
        }

        return $created;
    }

    /**
     * Creates a native gradebook evaluation. The score scale assumed for the points weight is 0-100
     * (max = 100), so score/max × weight reproduces the client's "score × multiplier" arithmetic.
     */
    private function createEvaluation(
        int $evaluationId,
        float $weight,
        Course $course,
        GradebookCategory $category,
        bool $dryRun
    ): int {
        $evaluation = new GradebookEvaluation();
        $evaluation->setTitle(\sprintf('Evaluation %d', $evaluationId));
        $evaluation->setCourse($course);
        $evaluation->setCategory($category);
        $evaluation->setWeight($weight);
        $evaluation->setMax(100.0);
        $evaluation->setVisible(1);
        $evaluation->setType('evaluation');
        $evaluation->setLocked(0);
        $evaluation->setCreatedAt(new DateTime());

        if (!$dryRun) {
            $this->em->persist($evaluation);
        }

        return 1;
    }

    /**
     * Records a skip reason and returns 0 (no item created).
     *
     * @param string[] $skipped
     */
    private function skip(array &$skipped, string $message): int
    {
        $skipped[] = $message;

        return 0;
    }
}
