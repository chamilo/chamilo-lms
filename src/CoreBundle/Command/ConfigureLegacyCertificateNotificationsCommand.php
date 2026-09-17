<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ExtraField;
use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'chamilo:migration:configure-legacy-certificate-notifications',
    description: 'Persist legacy certificate notification templates as course extra fields.'
)]
final class ConfigureLegacyCertificateNotificationsCommand extends Command
{
    private const string SUBJECT_VARIABLE =
        'plugin_gradingelectronic_certificate_notification_subject';

    private const string MESSAGE_VARIABLE =
        'plugin_gradingelectronic_certificate_notification_message';

    private const string GRADING_ELECTRONIC_COURSE_VARIABLE =
        'plugin_gradingelectronic_course_id';

    private const string COMPLETION_RULE_VARIABLE =
        'course_completion_rule';

    private const string SUBJECT = 'Course Completion Certificate';

    private const array TENNESSEE_COURSE_CODES = [
        'AERIALDRIVEROPERATOR',
        'FIREINSPECTOR',
        'PUMPERDRIVEROPERATOR',
        'TN1810',
        'TNFDISO',
        'TNFI1',
        'TNFI2',
        'TNFO1',
        'TNFO1FO2',
        'TNFO2',
        'TNFO3',
    ];

    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Report the changes without modifying the database.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Configure legacy certificate notifications');

        $dryRun = (bool) $input->getOption('dry-run');

        try {
            $courses = $this->loadEligibleCourses();

            $summary = [
                'courses' => \count($courses),
                'general' => 0,
                'edc' => 0,
                'tennessee' => 0,
                'would_insert' => 0,
                'would_fill_empty' => 0,
                'already_correct' => 0,
                'conflicts' => 0,
            ];

            $changes = [];

            foreach ($courses as $course) {
                $courseId = (int) $course['id'];
                $courseCode = (string) $course['code'];
                $profile = $this->resolveProfile($courseCode);

                ++$summary[$profile];

                $template = [
                    'subject' => self::SUBJECT,
                    'message' => $this->messageForProfile($profile),
                ];

                foreach (
                    [
                        'subject' => self::SUBJECT_VARIABLE,
                        'message' => self::MESSAGE_VARIABLE,
                    ] as $key => $variable
                ) {
                    $status = $this->inspectValue(
                        $courseId,
                        $variable,
                        $template[$key]
                    );

                    ++$summary[$status];

                    $changes[] = [
                        'course_id' => $courseId,
                        'course_code' => $courseCode,
                        'profile' => $profile,
                        'variable' => $variable,
                        'status' => $status,
                        'value' => $template[$key],
                    ];
                }
            }

            $io->definitionList(
                ['Eligible courses' => $summary['courses']],
                ['General profile' => $summary['general']],
                ['EDC profile' => $summary['edc']],
                ['Tennessee profile' => $summary['tennessee']],
                ['Would insert' => $summary['would_insert']],
                ['Would fill empty' => $summary['would_fill_empty']],
                ['Already correct' => $summary['already_correct']],
                ['Conflicts' => $summary['conflicts']]
            );

            if ($summary['conflicts'] > 0) {
                foreach ($changes as $change) {
                    if ('conflicts' !== $change['status']) {
                        continue;
                    }

                    $io->warning(\sprintf(
                        'Course %d (%s): existing value for %s differs from the legacy template.',
                        $change['course_id'],
                        $change['course_code'],
                        $change['variable']
                    ));
                }

                $io->error(
                    'Conflicting existing certificate notification values were found. No changes were applied.'
                );

                return Command::FAILURE;
            }

            if ($dryRun) {
                $io->success('Dry-run completed. No database changes were made.');

                return Command::SUCCESS;
            }

            $this->connection->transactional(function () use ($changes): void {
                $subjectFieldId = $this->ensureExtraField(
                    self::SUBJECT_VARIABLE,
                    ExtraField::FIELD_TYPE_TEXT,
                    'Certificate notification subject'
                );

                $messageFieldId = $this->ensureExtraField(
                    self::MESSAGE_VARIABLE,
                    ExtraField::FIELD_TYPE_TEXTAREA,
                    'Certificate notification message'
                );

                foreach ($changes as $change) {
                    if ('already_correct' === $change['status']) {
                        continue;
                    }

                    $fieldId = self::SUBJECT_VARIABLE === $change['variable']
                        ? $subjectFieldId
                        : $messageFieldId;

                    $this->persistValue(
                        $fieldId,
                        (int) $change['course_id'],
                        (string) $change['value']
                    );
                }
            });

            $io->success('Legacy certificate notification templates were persisted.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * @return list<array{id: int|string, code: string}>
     */
    private function loadEligibleCourses(): array
    {
        return $this->connection->fetchAllAssociative(
            <<<'SQL'
SELECT DISTINCT c.id, c.code
FROM course c
WHERE
    (
        EXISTS (
            SELECT 1
            FROM extra_field ef
            INNER JOIN extra_field_values efv
                ON efv.field_id = ef.id
            WHERE ef.item_type = :courseItemType
              AND ef.variable = :completionVariable
              AND efv.item_id = c.id
              AND TRIM(COALESCE(efv.field_value, '')) <> ''
        )
        OR
        EXISTS (
            SELECT 1
            FROM extra_field ef
            INNER JOIN extra_field_values efv
                ON efv.field_id = ef.id
            WHERE ef.item_type = :courseItemType
              AND ef.variable = :gradingElectronicVariable
              AND efv.item_id = c.id
              AND TRIM(COALESCE(efv.field_value, '')) <> ''
        )
    )
    AND EXISTS (
        SELECT 1
        FROM gradebook_category gc
        WHERE gc.c_id = c.id
          AND (gc.parent_id IS NULL OR gc.parent_id = 0)
          AND gc.generate_certificates = 1
    )
ORDER BY c.id
SQL,
            [
                'courseItemType' => ExtraField::COURSE_FIELD_TYPE,
                'completionVariable' => self::COMPLETION_RULE_VARIABLE,
                'gradingElectronicVariable' => self::GRADING_ELECTRONIC_COURSE_VARIABLE,
            ]
        );
    }

    private function resolveProfile(string $courseCode): string
    {
        $courseCode = strtoupper(trim($courseCode));

        if ('EDC' === $courseCode) {
            return 'edc';
        }

        if (\in_array($courseCode, self::TENNESSEE_COURSE_CODES, true)) {
            return 'tennessee';
        }

        return 'general';
    }

    private function inspectValue(
        int $courseId,
        string $variable,
        string $expectedValue
    ): string {
        $fields = $this->connection->fetchAllAssociative(
            'SELECT id, value_type
             FROM extra_field
             WHERE item_type = :itemType
               AND variable = :variable
             ORDER BY id',
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => $variable,
            ]
        );

        if ([] === $fields) {
            return 'would_insert';
        }

        if (1 !== \count($fields)) {
            throw new RuntimeException(\sprintf(
                'Multiple course extra fields were found for %s.',
                $variable
            ));
        }

        $expectedValueType = self::SUBJECT_VARIABLE === $variable
            ? ExtraField::FIELD_TYPE_TEXT
            : ExtraField::FIELD_TYPE_TEXTAREA;

        if ($expectedValueType !== (int) $fields[0]['value_type']) {
            throw new RuntimeException(\sprintf(
                'Course extra field %s has value_type %d; expected %d.',
                $variable,
                (int) $fields[0]['value_type'],
                $expectedValueType
            ));
        }

        $values = $this->connection->fetchFirstColumn(
            'SELECT field_value
             FROM extra_field_values
             WHERE field_id = :fieldId
               AND item_id = :courseId
             ORDER BY id',
            [
                'fieldId' => (int) $fields[0]['id'],
                'courseId' => $courseId,
            ]
        );

        if ([] === $values) {
            return 'would_insert';
        }

        if (1 !== \count($values)) {
            throw new RuntimeException(\sprintf(
                'Course %d has multiple values for %s.',
                $courseId,
                $variable
            ));
        }

        $existingValue = (string) $values[0];

        if ('' === trim($existingValue)) {
            return 'would_fill_empty';
        }

        if ($existingValue === $expectedValue) {
            return 'already_correct';
        }

        return 'conflicts';
    }

    private function ensureExtraField(
        string $variable,
        int $valueType,
        string $displayText
    ): int {
        $fields = $this->connection->fetchAllAssociative(
            'SELECT id, value_type
             FROM extra_field
             WHERE item_type = :itemType
               AND variable = :variable
             ORDER BY id',
            [
                'itemType' => ExtraField::COURSE_FIELD_TYPE,
                'variable' => $variable,
            ]
        );

        if (1 === \count($fields)) {
            if ($valueType !== (int) $fields[0]['value_type']) {
                throw new RuntimeException(\sprintf(
                    'Course extra field %s has value_type %d; expected %d.',
                    $variable,
                    (int) $fields[0]['value_type'],
                    $valueType
                ));
            }

            return (int) $fields[0]['id'];
        }

        if (\count($fields) > 1) {
            throw new RuntimeException(\sprintf(
                'Multiple course extra fields were found for %s.',
                $variable
            ));
        }

        $this->connection->insert('extra_field', [
            'item_type' => ExtraField::COURSE_FIELD_TYPE,
            'value_type' => $valueType,
            'variable' => $variable,
            'display_text' => $displayText,
            'created_at' => new \DateTimeImmutable(),
        ], [
            'created_at' => 'datetime_immutable',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function persistValue(
        int $fieldId,
        int $courseId,
        string $value
    ): void {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, field_value
             FROM extra_field_values
             WHERE field_id = :fieldId
               AND item_id = :courseId
             ORDER BY id',
            [
                'fieldId' => $fieldId,
                'courseId' => $courseId,
            ]
        );

        if ([] === $rows) {
            $now = new \DateTimeImmutable();

            $this->connection->insert('extra_field_values', [
                'field_id' => $fieldId,
                'field_value' => $value,
                'item_id' => $courseId,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'created_at' => 'datetime_immutable',
                'updated_at' => 'datetime_immutable',
            ]);

            return;
        }

        if (1 !== \count($rows)) {
            throw new RuntimeException(\sprintf(
                'Course %d has multiple values for field %d.',
                $courseId,
                $fieldId
            ));
        }

        $existingValue = (string) ($rows[0]['field_value'] ?? '');

        if ($existingValue === $value) {
            return;
        }

        if ('' !== trim($existingValue)) {
            throw new RuntimeException(\sprintf(
                'Course %d already has a different non-empty value for field %d.',
                $courseId,
                $fieldId
            ));
        }

        $this->connection->update(
            'extra_field_values',
            [
                'field_value' => $value,
                'updated_at' => new \DateTimeImmutable(),
            ],
            ['id' => (int) $rows[0]['id']],
            ['updated_at' => 'datetime_immutable']
        );
    }

    private function messageForProfile(string $profile): string
    {
        return match ($profile) {
            'edc' => <<<'HTML'
<p>((user_first_name)) ((user_last_name)),</p>
<p>Congratulations! You have successfully completed the course ((course_title)).</p>
<p>Use the link below to view or print your certificate of completion:<br>((certificate_link))</p>
<p><strong>To save the certificate as a PDF:</strong></p>
<ol>
    <li>Open the certificate link.</li>
    <li>Use your browser's print option.</li>
    <li>Select “Save as PDF” as the destination.</li>
    <li>Select landscape orientation, then save the file.</li>
</ol>
<p>We hope you enjoyed your learning experience and look forward to seeing you in future classes. Please contact us if you need assistance.</p>
<p>Sincerely,<br>((portal_name))</p>
HTML,
            'tennessee' => <<<'HTML'
<p>((user_first_name)) ((user_last_name)),</p>
<p>Congratulations! You have successfully completed the course ((course_title)). You earned a passing score of ((score))%.</p>
<p>Your certificate is available using the link below:<br>((certificate_link))</p>
<p><strong>To save the certificate as a PDF:</strong></p>
<ol>
    <li>Open the certificate link.</li>
    <li>Use your browser's print option.</li>
    <li>Select “Save as PDF” as the destination.</li>
    <li>Select landscape orientation, then save the file.</li>
</ol>
<p>We look forward to seeing you in future classes. Please contact us if you need assistance.</p>
<p>Sincerely,<br>((portal_name))</p>
HTML,
            default => <<<'HTML'
<p>((user_first_name)) ((user_last_name)),</p>
<p>Congratulations! You have successfully completed the course ((course_title)). You earned a passing score of ((score))%, and your grade has been submitted to the State Fire College. Please allow a few days for it to appear in their system.</p>
<p>Use the link below to view or print your certificate of completion:<br>((certificate_link))</p>
<p><strong>To save the certificate as a PDF:</strong></p>
<ol>
    <li>Open the certificate link.</li>
    <li>Use your browser's print option.</li>
    <li>Select “Save as PDF” as the destination.</li>
    <li>Select landscape orientation, then save the file.</li>
</ol>
<p>We hope you enjoyed your learning experience and look forward to seeing you in future classes. Please contact us if you need assistance.</p>
<p>Sincerely,<br>((portal_name))</p>
HTML,
        };
    }
}
