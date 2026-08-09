<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

use const LIBXML_HTML_NODEFDTD;
use const LIBXML_HTML_NOIMPLIED;
use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;

/**
 * Repairs test (quiz) HTML fields where translatehtml language blocks were
 * split across two different DOM parents by the historical bug in
 * TranslateHtmlLanguageService::upsertWithMarkers(): appending a new language
 * to already-marked content used to string-concatenate the new
 * ".mce-translatehtml" block after the *entire* existing HTML instead of
 * inserting it next to the language block(s) already there. When the first
 * language was wrapped in an editor container (e.g. a "tiny-content" div),
 * every language added afterwards ended up as a sibling of that wrapper
 * instead of inside it — splitting one language group into two. The
 * client-side filter (assets/js/translatehtml.js) groups language blocks by
 * their immediate parent, so it then treats the split as two independent
 * groups and shows one language from each, instead of filtering down to one.
 *
 * This command detects exactly that corruption shape and moves the orphaned
 * blocks back next to their siblings. It intentionally does nothing to a
 * field where the shape does not exactly match (e.g. genuinely independent
 * multi-language groups), leaving it for manual review instead of guessing.
 */
#[AsCommand(
    name: 'chamilo:migration:repair-translatehtml-language-blocks',
    description: 'Repair quiz description/question/answer/feedback fields where translatehtml language blocks were split outside their wrapper element.'
)]
final class RepairTranslateHtmlLanguageBlocksCommand extends Command
{
    private const string MARKER_CLASS = 'mce-translatehtml';

    public function __construct(
        private readonly Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would change without writing anything.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Only process the first N rows per column (for testing).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Repair translatehtml language blocks');

        $dryRun = (bool) $input->getOption('dry-run');
        $limit = null !== $input->getOption('limit') ? (int) $input->getOption('limit') : null;

        if ($dryRun) {
            $io->note('Running in dry-run mode: no rows will be updated.');
        }

        /** @var list<array{table: string, idColumn: string, columns: list<string>}> $targets */
        $targets = [
            ['table' => 'c_quiz', 'idColumn' => 'iid', 'columns' => ['description']],
            ['table' => 'c_quiz_question', 'idColumn' => 'iid', 'columns' => ['description']],
            ['table' => 'c_quiz_answer', 'idColumn' => 'iid', 'columns' => ['answer', 'comment']],
        ];

        $summary = [];

        $this->connection->beginTransaction();

        try {
            foreach ($targets as $target) {
                foreach ($target['columns'] as $column) {
                    $summary[$target['table'].'.'.$column] = $this->repairColumn(
                        $io,
                        $target['table'],
                        $target['idColumn'],
                        $column,
                        $limit,
                        $dryRun,
                    );
                }
            }

            if ($dryRun) {
                $this->connection->rollBack();
            } else {
                $this->connection->commit();
            }
        } catch (Throwable $exception) {
            $this->connection->rollBack();
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $rows = [];
        $totals = ['scanned' => 0, 'repaired' => 0, 'ambiguous' => 0];
        foreach ($summary as $field => $counts) {
            $rows[] = [$field, $counts['scanned'], $counts['repaired'], $counts['ambiguous']];
            $totals['scanned'] += $counts['scanned'];
            $totals['repaired'] += $counts['repaired'];
            $totals['ambiguous'] += $counts['ambiguous'];
        }
        $rows[] = ['TOTAL', $totals['scanned'], $totals['repaired'], $totals['ambiguous']];

        $io->table(['Field', 'Scanned (had markers)', 'Repaired', 'Ambiguous (skipped)'], $rows);

        if ($totals['ambiguous'] > 0) {
            $io->warning(\sprintf(
                '%d field(s) had more than one split language group and did not match the known corruption shape — left untouched for manual review.',
                $totals['ambiguous'],
            ));
        }

        $io->success($dryRun ? 'Dry run complete.' : \sprintf('Repaired %d field(s).', $totals['repaired']));

        return Command::SUCCESS;
    }

    /**
     * @return array{scanned: int, repaired: int, ambiguous: int}
     */
    private function repairColumn(
        SymfonyStyle $io,
        string $table,
        string $idColumn,
        string $column,
        ?int $limit,
        bool $dryRun,
    ): array {
        $counts = ['scanned' => 0, 'repaired' => 0, 'ambiguous' => 0];

        $qb = $this->connection->createQueryBuilder()
            ->select($idColumn, $column)
            ->from($table)
        ;
        $qb->where($qb->expr()->like($column, ':marker'))
            ->setParameter('marker', '%'.self::MARKER_CLASS.'%')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $rows = $qb->executeQuery()->iterateAssociative();

        foreach ($rows as $row) {
            $id = (int) $row[$idColumn];
            $html = (string) ($row[$column] ?? '');
            ++$counts['scanned'];

            $result = $this->repairHtml($html);

            if ('unchanged' === $result['status']) {
                continue;
            }

            if ('ambiguous' === $result['status']) {
                ++$counts['ambiguous'];
                $io->text(\sprintf('  [ambiguous] %s.%s #%d — skipped', $table, $column, $id));

                continue;
            }

            ++$counts['repaired'];
            $io->text(\sprintf('  [repaired]  %s.%s #%d', $table, $column, $id));

            if (!$dryRun) {
                $this->connection->update($table, [$column => $result['html']], [$idColumn => $id]);
            }
        }

        return $counts;
    }

    /**
     * @return array{status: 'unchanged'|'repaired'|'ambiguous', html: ?string}
     */
    private function repairHtml(string $html): array
    {
        if ('' === trim($html) || !str_contains($html, self::MARKER_CLASS)) {
            return ['status' => 'unchanged', 'html' => null];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="__chamilo_repair_root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('__chamilo_repair_root');
        if (!$root instanceof DOMElement) {
            return ['status' => 'unchanged', 'html' => null];
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            './/*[contains(concat(" ", normalize-space(@class), " "), " '.self::MARKER_CLASS.' ")]',
            $root,
        );

        if (false === $nodes || $nodes->length < 2) {
            return ['status' => 'unchanged', 'html' => null];
        }

        // Group the marked elements by their immediate parent, identified by
        // object identity (spl_object_id), mirroring groupByParent() in
        // assets/js/translatehtml.js.
        $groups = [];
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $parent = $node->parentNode;
            if (!$parent instanceof DOMElement) {
                continue;
            }
            $key = spl_object_id($parent);
            $groups[$key]['parent'] ??= $parent;
            $groups[$key]['nodes'][] = $node;
        }

        if (\count($groups) < 2) {
            return ['status' => 'unchanged', 'html' => null];
        }

        // Only repair the exact known corruption shape: one "flat" group whose
        // parent is the field's own root, and exactly one "wrapper" group whose
        // parent is a distinct element directly under the root (e.g.
        // <div class="tiny-content">). Anything else (more than one wrapper,
        // a wrapper nested deeper than one level, or several independent
        // groups) is left alone rather than guessed at.
        $rootKey = spl_object_id($root);
        $wrapperKey = null;

        foreach ($groups as $key => $group) {
            if ($key === $rootKey) {
                continue;
            }

            if (null !== $wrapperKey || $group['parent']->parentNode !== $root) {
                return ['status' => 'ambiguous', 'html' => null];
            }

            $wrapperKey = $key;
        }

        if (null === $wrapperKey || !isset($groups[$rootKey])) {
            return ['status' => 'ambiguous', 'html' => null];
        }

        $wrapperParent = $groups[$wrapperKey]['parent'];

        // Nodes matched by the XPath query above are already in document
        // order, so the root-level (orphaned) nodes can simply be appended
        // to the wrapper in the order they were found.
        foreach ($groups[$rootKey]['nodes'] as $node) {
            $wrapperParent->appendChild($node);
        }

        $repairedHtml = '';
        foreach ($root->childNodes as $child) {
            $repairedHtml .= $document->saveHTML($child) ?: '';
        }

        return ['status' => 'repaired', 'html' => $repairedHtml];
    }
}
