<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use PDO;
use StudentFollowUpPlugin;

/**
 * Pins StudentFollowUpPlugin::normalizeLegacyTags(): the legacy "empty" values of sfu_post.tags become the
 * serialized empty array, and any other value is left alone.
 */
final class StudentFollowUpPluginTier3CharacterizationTest extends PluginCharacterizationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::ensurePluginTables(
            ['sfu_post'],
            'StudentFollowUp',
            'StudentFollowUp/StudentFollowUpPlugin.php',
            'StudentFollowUpPlugin'
        );
    }

    /**
     * sfu_post has foreign keys to user: left in the schema, it makes the "every foreign key to user is covered"
     * audits (UserMergeHelperHardeningTest, UserRepositoryHardDeleteTest) fail, so the table goes when the class ends.
     */
    public static function tearDownAfterClass(): void
    {
        $pdo = new PDO(
            \sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DATABASE_HOST') ?: '127.0.0.1', getenv('DATABASE_PORT') ?: '3306', getenv('DATABASE_NAME') ?: 'chamilotest'),
            getenv('DATABASE_USER') ?: 'chamilotest',
            getenv('DATABASE_PASSWORD') ?: 'chamilotest'
        );
        $pdo->exec('DROP TABLE IF EXISTS sfu_post');
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        require_once api_get_path(SYS_PLUGIN_PATH).'StudentFollowUp/StudentFollowUpPlugin.php';
    }

    private function post(?string $tags): int
    {
        // tags is NOT NULL in the table, so the "IS NULL" branch of the query cannot be exercised.
        $this->conn->executeStatement(
            "INSERT INTO sfu_post (insert_user_id, user_id, title, private, external_source, tags, attachment)
             VALUES (1, 1, 'sfu_t', 0, 0, ?, '')",
            [$tags]
        );

        return (int) $this->conn->lastInsertId();
    }

    private function tags(int $id): string
    {
        return (string) $this->conn->fetchOne('SELECT tags FROM sfu_post WHERE id = ?', [$id]);
    }

    public function testLegacyEmptyTagValuesAreNormalizedAndOthersKept(): void
    {
        $empty = $this->post('');
        $zero = $this->post('0');
        $false = $this->post('b:0;');
        $list = $this->post('a:1:{i:0;s:1:"x";}');
        $already = $this->post('a:0:{}');
        $other = $this->post('x');

        StudentFollowUpPlugin::normalizeLegacyTags();

        $this->assertSame('a:0:{}', $this->tags($empty));
        $this->assertSame('a:0:{}', $this->tags($zero));
        $this->assertSame('a:0:{}', $this->tags($false));
        $this->assertSame('a:1:{i:0;s:1:"x";}', $this->tags($list));
        $this->assertSame('a:0:{}', $this->tags($already));
        $this->assertSame('x', $this->tags($other));
    }
}
