<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use CustomCertificatePlugin;
use ReflectionMethod;

/**
 * Pins the private getGradebookScoreForUser() of the CustomCertificate plugin, whose category scoping and
 * "latest certificate wins" ordering the main characterization test (one certificate per student) cannot tell
 * apart from their inversions.
 */
final class CustomCertificatePluginTier2CharacterizationTest extends PluginCharacterizationTestCase
{
    private const TABLE = 'plugin_customcertificate';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            [self::TABLE],
            'CustomCertificate',
            'CustomCertificate/src/CustomCertificatePlugin.php',
            'CustomCertificatePlugin',
            'database.php'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'CustomCertificate/src/CustomCertificatePlugin.php';
    }

    private function category(): int
    {
        $this->conn->executeStatement(
            'INSERT INTO gradebook_category (user_id, c_id, session_id, title, weight, visible, locked, generate_certificates, is_requirement, calculation_mode)
             VALUES (1, 1, NULL, ?, 100, 1, 0, 1, 0, ?)',
            ['cu_category', 'weighted_average']
        );

        return (int) $this->conn->lastInsertId();
    }

    private function certificate(int $userId, int $categoryId, float $score): void
    {
        $this->conn->executeStatement(
            "INSERT INTO gradebook_certificate (cat_id, user_id, score_certificate, created_at, path_certificate, publish)
             VALUES (?, ?, ?, '2026-03-01 10:00:00', 'cu_hash.html', 0)",
            [$categoryId, $userId, $score]
        );
    }

    private function score(int $userId, int $categoryId): string
    {
        $method = new ReflectionMethod(CustomCertificatePlugin::class, 'getGradebookScoreForUser');

        return $method->invoke(null, $userId, $categoryId);
    }

    public function testTheScoreIsTheLatestCertificateOfTheStudentInThatCategory(): void
    {
        $student = $this->rawUser('cu_t2_a');
        $categoryA = $this->category();
        $categoryB = $this->category();
        $this->certificate($student, $categoryA, 50);
        $this->certificate($student, $categoryA, 90);
        $this->certificate($student, $categoryB, 10);

        $this->assertSame('90', $this->score($student, $categoryA), 'The latest certificate (highest id) of the category.');
        $this->assertSame('10', $this->score($student, $categoryB), 'Certificates of other categories are not mixed in.');
        $this->assertSame('', $this->score($student, $categoryB + 1000));
        $this->assertSame('', $this->score(0, $categoryA));
    }
}
