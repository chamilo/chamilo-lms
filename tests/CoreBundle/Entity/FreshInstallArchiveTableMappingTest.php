<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CoreBundle\Entity\ExerciseAttemptRecycleBinEntry;
use Chamilo\CoreBundle\Entity\GradebookCertificateLegacyReference;
use Chamilo\CoreBundle\Entity\LearningPathTrackingRecycleBinEntry;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The fresh installer creates the schema from ORM metadata, then baselines migrations.
 * DBAL-managed archive tables must therefore remain mapped as Doctrine entities.
 */
final class FreshInstallArchiveTableMappingTest extends TestCase
{
    public function testArchiveTablesHaveCompleteSchemaMappings(): void
    {
        $schemas = [
            GradebookCertificateLegacyReference::class => [
                'table' => 'gradebook_certificate_legacy_reference',
                'columns' => [
                    'certificate_id' => ['bigint', false],
                    'legacy_user_id' => ['integer', false],
                    'cat_id' => ['integer', true],
                    'score_certificate' => ['float', false],
                    'created_at' => ['datetime', false],
                    'path_certificate' => ['text', true],
                    'html_content' => ['text', true],
                    'content_sha256' => ['string', true],
                    'imported_at' => ['datetime', false],
                ],
                'indexes' => [
                    'idx_gclr_legacy_user_id' => ['legacy_user_id'],
                    'idx_gclr_cat_id' => ['cat_id'],
                ],
            ],
            ExerciseAttemptRecycleBinEntry::class => [
                'table' => 'exercise_attempt_recycle_bin',
                'columns' => [
                    'id' => ['integer', false],
                    'attempt_id' => ['integer', false],
                    'exercise_id' => ['integer', false],
                    'user_id' => ['integer', false],
                    'course_id' => ['integer', false],
                    'session_id' => ['integer', true],
                    'deleted_by' => ['integer', false],
                    'deleted_at' => ['datetime', false],
                    'snapshot_sha256' => ['string', false],
                    'snapshot' => ['text', false],
                    'restored_by' => ['integer', true],
                    'restored_at' => ['datetime', true],
                ],
                'indexes' => [
                    'idx_exercise_recycle_context' => ['course_id', 'session_id', 'exercise_id', 'restored_at'],
                    'idx_exercise_recycle_deleted' => ['deleted_at'],
                ],
            ],
            LearningPathTrackingRecycleBinEntry::class => [
                'table' => 'learning_path_tracking_recycle_bin',
                'columns' => [
                    'id' => ['integer', false],
                    'lp_id' => ['integer', false],
                    'course_id' => ['integer', false],
                    'session_id' => ['integer', true],
                    'user_id' => ['integer', false],
                    'deleted_by' => ['integer', false],
                    'deleted_at' => ['datetime', false],
                    'snapshot_sha256' => ['string', false],
                    'snapshot' => ['text', false],
                    'restored_by' => ['integer', true],
                    'restored_at' => ['datetime', true],
                ],
                'indexes' => [
                    'idx_lp_tracking_recycle_context' => ['lp_id', 'course_id', 'session_id', 'restored_at'],
                    'idx_lp_tracking_recycle_deleted' => ['deleted_at'],
                ],
            ],
        ];

        foreach ($schemas as $class => $expected) {
            $reflection = new ReflectionClass($class);
            self::assertCount(1, $reflection->getAttributes(ORM\Entity::class), $class);

            $table = $reflection->getAttributes(ORM\Table::class)[0]->newInstance();
            self::assertSame($expected['table'], $table->name);

            $columns = [];
            foreach ($reflection->getProperties() as $property) {
                $attributes = $property->getAttributes(ORM\Column::class);
                if ([] === $attributes) {
                    continue;
                }
                $column = $attributes[0]->newInstance();
                $columns[$column->name] = [$column->type, $column->nullable];

                if (\in_array($column->name, ['snapshot_sha256', 'content_sha256'], true)) {
                    self::assertSame(64, $column->length);
                    self::assertTrue($column->options['fixed'] ?? false);
                }
            }
            self::assertSame($expected['columns'], $columns, $class);

            $indexes = [];
            foreach ($reflection->getAttributes(ORM\Index::class) as $attribute) {
                $index = $attribute->newInstance();
                $indexes[$index->name] = $index->columns;
            }
            self::assertSame($expected['indexes'], $indexes, $class);
        }
    }
}
