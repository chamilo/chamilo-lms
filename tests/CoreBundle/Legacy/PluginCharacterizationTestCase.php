<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Legacy;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession;
use Doctrine\DBAL\Connection;
use PDO;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use const PHP_BINARY;

/**
 * Shared harness of the characterization tests of the legacy plugins under public/plugin that keep their own
 * tables (LearningCalendar, CustomCertificate, CourseLegal).
 *
 * Those tables are created by the plugin installer, not by doctrine:schema:create, so they are missing from the
 * test database. DDL inside a test would break the DAMA savepoint: ensurePluginTables() runs the plugin's own
 * install() ONCE in a child process (PluginInstallSchema.php), before DAMA opens its transaction, and only when a
 * table is missing. Same approach as BuyCoursesCharacterizationTestCase.
 *
 * Fixtures use the fixed users (admin id 1, anon id 2) and courses 1 and 2 and are rolled back by DAMA.
 */
abstract class PluginCharacterizationTestCase extends KernelTestCase
{
    protected Connection $conn;

    /**
     * @param string[] $tables
     */
    protected static function ensurePluginTables(
        array $tables,
        string $pluginDir,
        string $classFile,
        string $className,
        ?string $schemaScript = null
    ): void {
        // A plain PDO connection: booting the kernel here would make DAMA open its transaction too early.
        // Same database as Doctrine, which adds the dbname_suffix of the test environment (doctrine.yaml).
        $dsn = \sprintf(
            'mysql:host=%s;port=%s;dbname=%s',
            $_SERVER['DATABASE_HOST'] ?? '127.0.0.1',
            $_SERVER['DATABASE_PORT'] ?? '3306',
            ($_SERVER['DATABASE_NAME'] ?? 'chamilo').'_test'.($_SERVER['TEST_TOKEN'] ?? '')
        );
        $pdo = new PDO($dsn, $_SERVER['DATABASE_USER'] ?? 'root', $_SERVER['DATABASE_PASSWORD'] ?? '');
        $missing = false;
        foreach ($tables as $table) {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$table]);
            if (0 === (int) $stmt->fetchColumn()) {
                $missing = true;
            }
        }
        if (!$missing) {
            return;
        }

        $script = __DIR__.'/PluginInstallSchema.php';
        exec(
            'APP_ENV=test '.escapeshellarg(PHP_BINARY).' -d memory_limit=2G '.escapeshellarg($script).' '
            .escapeshellarg($pluginDir).' '.escapeshellarg($classFile).' '.escapeshellarg($className)
            .(null !== $schemaScript ? ' '.escapeshellarg($schemaScript) : '').' 2>&1',
            $out,
            $status
        );
        if (0 !== $status) {
            throw new RuntimeException($pluginDir.' schema install failed: '.implode("\n", $out));
        }
    }

    protected static function prepareLegacyContext(string $uri = '/'): void
    {
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        $session = new Session(new MockArraySessionStorage());
        Container::setSession($session);
        $request = Request::create($uri);
        $request->setSession($session);
        $request->setLocale('en_US');
        Container::setRequest($request);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        self::prepareLegacyContext();
        $this->conn = self::getContainer()->get('doctrine')->getConnection();
        $this->loginAs(0);
    }

    protected function tearDown(): void
    {
        $this->loginAs(0);
        parent::tearDown();
    }

    /**
     * Logs a user in for the legacy code (security token + legacy session): 0 = nobody.
     */
    protected function loginAs(int $userId): void
    {
        $tokenStorage = self::getContainer()->get('security.token_storage');
        if ($userId <= 0) {
            ChamiloSession::erase('_user');
            $tokenStorage->setToken(null);

            return;
        }

        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->find($userId);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
        ChamiloSession::write('_user', ['id' => $userId, 'user_id' => $userId]);
    }

    /**
     * Inserts a user with a raw INSERT (no resource_node row, see the deadlock note in the brief).
     *
     * @param string[] $roles
     */
    protected function rawUser(string $tag, array $roles = [], int $status = 5): int
    {
        static $seq = 0;
        $name = $tag.'_'.getmypid().'_'.(++$seq);
        $this->conn->executeStatement(
            'INSERT INTO user (username, username_canonical, email, email_canonical, firstname, lastname, locale,
                password, timezone, locked, expired, credentials_expired, salt, roles, status, active, uuid,
                mfa_enabled, created_at, updated_at, official_code)
             VALUES (?, ?, ?, ?, ?, ?, \'en_US\', \'x\', \'UTC\', 0, 0, 0, \'x\', ?, ?, 1,
                UNHEX(REPLACE(UUID(), \'-\', \'\')), 0, NOW(), NOW(), \'\')',
            [$name, $name, $name.'@example.com', $name.'@example.com', 'First'.$tag, 'Last'.$tag, json_encode($roles), $status]
        );
        $id = (int) $this->conn->lastInsertId();
        $this->conn->executeStatement('INSERT INTO access_url_rel_user (access_url_id, user_id) VALUES (1, ?)', [$id]);

        return $id;
    }
}
