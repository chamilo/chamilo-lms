<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Types;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Uid\Uuid;

use const PHP_URL_HOST;

/**
 * MultiURL Converter (Single → Multi)
 * Usage:
 *   php bin/console app:multi-url:convert "https://admin.example.com/" [--admin-username=admin] [--preserve-admin-id] [--dry-run] [--force].
 *
 * What it does:
 *   • Default: adds a NEW ADMIN URL; keeps current URL as SECONDARY; links the chosen admin user.
 *     Also copies user_auth_source for the admin user to the new ADMIN URL (when table exists),
 *     so the admin can login on the admin URL even with external auth sources.
 *
 *   • --preserve-admin-id: keeps CURRENT row as ADMIN, inserts SECONDARY (id+1), and moves URL-bound FKs
 *     (incl. access_url_rel_usergroup for classes, AND user_auth_source for login continuity).
 *     Then copies user_auth_source for the admin user back to the ADMIN URL.
 *
 * Safeguards:
 *   • Expects exactly 1 row in access_url (unless --force). Runs in a transaction; --dry-run prints the plan only.
 *
 * After running:
 *   • Enable multi-URL in configuration and clear caches if needed. Non-existent tables/columns are skipped.
 */
#[AsCommand(
    name: 'app:multi-url:convert',
    description: 'Convert a single-URL Chamilo portal to MultiURL. Optionally preserve current access_url ID as admin and move foreign keys.'
)]
class ConvertToMultiUrlCommand extends Command
{
    public function __construct(
        private readonly Connection $conn,
        private readonly ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('admin-url', InputArgument::REQUIRED, 'New admin URL (with scheme and trailing slash)')
            ->addOption('admin-username', null, InputOption::VALUE_REQUIRED, 'Global admin username (default: user id 1)')
            ->addOption('preserve-admin-id', null, InputOption::VALUE_NONE, 'Legacy migration: keep current access_url id as ADMIN and insert secondary with id+1, moving foreign keys')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not write; only show planned changes')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip single-URL safety check (use with caution)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $newAdminUrl = (string) $input->getArgument('admin-url');
        $username = (string) ($input->getOption('admin-username') ?? '');
        $preserveId = (bool) $input->getOption('preserve-admin-id');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');

        $prefix = (string) ($this->params->has('database_prefix') ? $this->params->get('database_prefix') : '');
        $T = static fn (string $name) => $prefix.$name;

        /** @var AbstractSchemaManager $sm */
        $sm = $this->conn->createSchemaManager();

        $exists = fn (string $table) => $sm->tablesExist([$table]);
        $tableColumns = function (string $table) use ($sm, $exists): array {
            if (!$exists($table)) {
                return [];
            }
            $cols = [];
            foreach ($sm->listTableColumns($table) as $col) {
                $cols[$col->getName()] = $col;
            }

            return $cols;
        };

        $firstExistingColumn = static function (array $available, array $candidates): ?string {
            foreach ($candidates as $c) {
                if (isset($available[$c])) {
                    return $c;
                }
            }

            return null;
        };

        $nowUtc = static fn (): DateTime => new DateTime('now', new DateTimeZone('UTC'));

        $guessHost = static function (string $url): string {
            $host = parse_url($url, PHP_URL_HOST);

            return (\is_string($host) && '' !== $host) ? $host : $url;
        };

        $slugify = static function (string $value): string {
            $value = mb_strtolower(trim($value));
            $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
            $value = trim($value, '-');

            return '' !== $value ? $value : 'node';
        };

        // Canonical tables (based on your entities)
        $accessUrl = $T('access_url');
        $relCourse = $T('access_url_rel_course');
        $relCourseCategory = $T('access_url_rel_course_category');
        $relSession = $T('access_url_rel_session');
        $relUser = $T('access_url_rel_user');
        $relUsergroup = $T('access_url_rel_usergroup');
        $userRelCourseVote = $T('user_rel_course_vote');   // url_id
        $trackOnline = $T('track_e_online');         // access_url_id
        $sysAnnouncement = $T('sys_announcement');       // access_url_id
        $skill = $T('skill');                  // access_url_id
        $branchSync = $T('branch_sync');            // access_url_id
        $sessionCategory = $T('session_category');       // access_url_id
        $userAuthSource = $T('user_auth_source');       // url_id, user_id, authentication

        // Resource tables
        $resourceNode = $T('resource_node');
        $resourceType = $T('resource_type');

        // Optional/legacy extras (existence-checked)
        $systemCalendar = $T('system_calendar');         // access_url_id (if present)
        $trackCourseRanking = $T('track_course_ranking');    // url_id (if present)

        $userTable = $T('user');

        // Safety: expect single URL unless --force
        $countUrl = (int) $this->conn->fetchOne("SELECT COUNT(*) FROM {$accessUrl}");
        if (!$force && 1 !== $countUrl) {
            $io->error("Aborting: expected exactly 1 row in access_url; found {$countUrl}. Use --force if you know what you are doing.");

            return Command::FAILURE;
        }

        // Fetch current access_url row (lowest id)
        $row = $this->conn->fetchAssociative("SELECT * FROM {$accessUrl} ORDER BY id ASC LIMIT 1");
        if (!$row) {
            $io->error('No access_url row found. Nothing to convert.');

            return Command::FAILURE;
        }

        $currentId = (int) $row['id'];
        $currentUrl = (string) $row['url'];

        // Resolve admin user id from username (fallback to 1)
        $adminUserId = 1;
        if ('' !== $username) {
            $adminUserId = (int) ($this->conn->fetchOne(
                "SELECT id FROM {$userTable} WHERE username = :u",
                ['u' => $username],
                ['u' => Types::STRING]
            ) ?: 1);

            if (1 === $adminUserId) {
                $io->warning("Username '{$username}' not found. Falling back to user id 1.");
            }
        } else {
            $io->note('No admin username provided; using admin id 1.');
        }

        // Inspect access_url columns
        $accessUrlCols = $tableColumns($accessUrl);
        $hasLft = isset($accessUrlCols['lft']);
        $hasRgt = isset($accessUrlCols['rgt']);
        $hasLvl = isset($accessUrlCols['lvl']);
        $rootCol = isset($accessUrlCols['tree_root']) ? 'tree_root' : (isset($accessUrlCols['root']) ? 'root' : null);
        $hasTree = ($hasLft && $hasRgt && $hasLvl);

        // AccessUrl has resource_node_id in recent Chamilo 2 schema
        $hasResourceNodeId = isset($accessUrlCols['resource_node_id']);

        /**
         * Copy admin authentication mapping from one URL to another (user_auth_source).
         * This is needed when a user relies on external auth sources and must login on multiple URLs.
         *
         * Table schema expected (as confirmed):
         *   user_auth_source(id, url_id, user_id, authentication)
         */
        $copyUserAuthSourceForUser = function (int $fromUrlId, int $toUrlId, int $userId) use (
            $io,
            $exists,
            $tableColumns,
            $userAuthSource
        ): void {
            if (!$exists($userAuthSource)) {
                return;
            }

            $cols = $tableColumns($userAuthSource);
            foreach (['url_id', 'user_id', 'authentication'] as $required) {
                if (!isset($cols[$required])) {
                    $available = implode(', ', array_keys($cols));

                    throw new RuntimeException("Table {$userAuthSource} is missing required column '{$required}'. Available columns: {$available}");
                }
            }

            // Copy only one row (lowest id) to avoid accidental duplicates when data is inconsistent.
            $io->text("∙ Copy user_auth_source for user {$userId} from URL {$fromUrlId} to URL {$toUrlId}");
            $this->conn->executeStatement(
                "INSERT INTO {$userAuthSource} (url_id, user_id, authentication)
                 SELECT :toUrlId, src.user_id, src.authentication
                 FROM {$userAuthSource} src
                 WHERE src.url_id = :fromUrlId
                   AND src.user_id = :userId
                   AND NOT EXISTS (
                     SELECT 1
                     FROM {$userAuthSource} dst
                     WHERE dst.url_id = :toUrlId AND dst.user_id = :userId
                   )
                 ORDER BY src.id ASC
                 LIMIT 1",
                [
                    'fromUrlId' => $fromUrlId,
                    'toUrlId' => $toUrlId,
                    'userId' => $userId,
                ],
                [
                    'fromUrlId' => Types::INTEGER,
                    'toUrlId' => Types::INTEGER,
                    'userId' => Types::INTEGER,
                ]
            );
        };

        /**
         * Ensure access_url.resource_node_id is not NULL for the given access_url row.
         * This implementation is STRICT (no guessing): it only fills columns we can guarantee from the entity/schema.
         * If resource_node has a NOT NULL column with no default that we don't handle, it fails with a clear message.
         */
        $ensureResourceNodeId = function (int $urlId, string $urlValue) use (
            $io,
            $exists,
            $tableColumns,
            $firstExistingColumn,
            $accessUrl,
            $resourceNode,
            $resourceType,
            $hasResourceNodeId,
            $nowUtc,
            $guessHost,
            $slugify,
            $adminUserId
        ): void {
            if (!$hasResourceNodeId) {
                return;
            }
            if (!$exists($resourceNode) || !$exists($resourceType)) {
                throw new RuntimeException('resource_node/resource_type tables are missing. Cannot create resource_node for access_url.');
            }

            $currentNodeId = $this->conn->fetchOne(
                "SELECT resource_node_id FROM {$accessUrl} WHERE id = :id",
                ['id' => $urlId],
                ['id' => Types::INTEGER]
            );

            if (null !== $currentNodeId && (int) $currentNodeId > 0) {
                return;
            }

            // Resolve ResourceType id using ResourceType::title (your DB shows title='urls')
            $rtCols = $tableColumns($resourceType);
            if (!isset($rtCols['title'])) {
                $available = implode(', ', array_keys($rtCols));

                throw new RuntimeException("Cannot resolve resource_type by title: column 'title' not found. Available columns: {$available}");
            }

            $rtId = (int) ($this->conn->fetchOne(
                "SELECT id FROM {$resourceType} WHERE title = :t ORDER BY id ASC LIMIT 1",
                ['t' => 'urls'],
                ['t' => Types::STRING]
            ) ?: 0);

            if ($rtId <= 0) {
                throw new RuntimeException("resource_type with title 'urls' was not found. Cannot create resource_node for access_url.");
            }

            $rnCols = $tableColumns($resourceNode);

            $colTitle = $firstExistingColumn($rnCols, ['title']);
            $colSlug = $firstExistingColumn($rnCols, ['slug']);
            $colTypeId = $firstExistingColumn($rnCols, ['resource_type_id']);
            $colCreator = $firstExistingColumn($rnCols, ['creator_id']);
            $colPublic = $firstExistingColumn($rnCols, ['public']);
            $colUuid = $firstExistingColumn($rnCols, ['uuid']);

            $colCreated = $firstExistingColumn($rnCols, ['created_at', 'createdAt']);
            $colUpdated = $firstExistingColumn($rnCols, ['updated_at', 'updatedAt']);

            if (!$colTitle || !$colSlug || !$colTypeId) {
                $available = implode(', ', array_keys($rnCols));

                throw new RuntimeException("resource_node missing expected columns (title/slug/resource_type_id). Available columns: {$available}");
            }

            $title = $guessHost($urlValue);
            $slug = $slugify($title);

            $insertCols = [];
            $params = [];
            $types = [];

            // Mandatory core fields
            $insertCols[] = $colTitle;
            $params[$colTitle] = $title;
            $types[$colTitle] = Types::STRING;

            $insertCols[] = $colSlug;
            $params[$colSlug] = $slug;
            $types[$colSlug] = Types::STRING;

            $insertCols[] = $colTypeId;
            $params[$colTypeId] = $rtId;
            $types[$colTypeId] = Types::INTEGER;

            // public (bool) if exists
            if ($colPublic) {
                $insertCols[] = $colPublic;
                $params[$colPublic] = false;
                $types[$colPublic] = Types::BOOLEAN;
            }

            // creator_id if required or present
            if ($colCreator) {
                $isNotNull = $rnCols[$colCreator]->getNotnull();
                if ($isNotNull) {
                    $insertCols[] = $colCreator;
                    $params[$colCreator] = $adminUserId;
                    $types[$colCreator] = Types::INTEGER;
                }
            }

            // uuid (unique) if exists
            if ($colUuid) {
                $uuid = Uuid::v4();
                $uuidTypeName = $rnCols[$colUuid]->getType()->getName();
                $uuidLen = $rnCols[$colUuid]->getLength();

                $useBinary = ('binary' === $uuidTypeName) || (16 === $uuidLen);
                $insertCols[] = $colUuid;
                $params[$colUuid] = $useBinary ? $uuid->toBinary() : $uuid->toRfc4122();
                $types[$colUuid] = $useBinary ? Types::BINARY : Types::STRING;
            }

            // timestamps if exist
            if ($colCreated) {
                $insertCols[] = $colCreated;
                $params[$colCreated] = $nowUtc();
                $types[$colCreated] = Types::DATETIME_MUTABLE;
            }
            if ($colUpdated) {
                $insertCols[] = $colUpdated;
                $params[$colUpdated] = $nowUtc();
                $types[$colUpdated] = Types::DATETIME_MUTABLE;
            }

            // STRICT CHECK: if there are NOT NULL columns without defaults we didn't fill -> fail clearly
            foreach ($rnCols as $name => $colObj) {
                \assert($colObj instanceof Column);

                if ('id' === $name) {
                    continue;
                }

                if (\in_array($name, $insertCols, true)) {
                    continue;
                }

                if ($colObj->getNotnull() && null === $colObj->getDefault()) {
                    $available = implode(', ', array_keys($rnCols));

                    throw new RuntimeException("resource_node.{$name} is NOT NULL and has no default, but the command does not set it. Please handle it explicitly. Available columns: {$available}");
                }
            }

            $placeholders = array_map(static fn (string $c) => ':'.$c, $insertCols);

            $io->text("∙ Create resource_node for access_url {$urlId}");
            $this->conn->executeStatement(
                "INSERT INTO {$resourceNode} (".implode(',', $insertCols).') VALUES ('.implode(',', $placeholders).')',
                $params,
                $types
            );

            $newNodeId = (int) $this->conn->lastInsertId();

            $io->text("∙ Link access_url {$urlId} to resource_node {$newNodeId}");
            $this->conn->executeStatement(
                "UPDATE {$accessUrl} SET resource_node_id = :nid WHERE id = :id",
                ['nid' => $newNodeId, 'id' => $urlId],
                ['nid' => Types::INTEGER, 'id' => Types::INTEGER]
            );
        };

        // Plan
        $io->section('Current / Planned URLs');
        if ($preserveId) {
            $adminUrlId = $currentId;
            $oldUrlId = $currentId + 1;
            $io->listing([
                "Keep current row as ADMIN: id={$adminUrlId} url will become {$newAdminUrl}",
                "Insert SECONDARY URL: id={$oldUrlId} url will be {$currentUrl}",
                "Move foreign keys: {$adminUrlId} -> {$oldUrlId} (includes user groups/classes + user_auth_source).",
                'Copy admin user_auth_source back to ADMIN URL to keep admin login working on the admin URL.',
                'Create missing resource_node entries for any inserted access_url rows.',
            ]);
        } else {
            $io->listing([
                "Keep current URL as SECONDARY: id={$currentId} url={$currentUrl}",
                "Insert new ADMIN URL with auto id: url={$newAdminUrl}",
                'No FK moves; only link admin user to new admin URL.',
                'Copy admin user_auth_source to the new ADMIN URL to keep admin login working on the admin URL (when applicable).',
                'Create missing resource_node entries for the inserted ADMIN access_url.',
            ]);
        }

        if ($dryRun) {
            $io->success('Dry-run complete. No changes were committed.');
            $io->note('If you proceed for real, remember to enable multiple_access_urls afterwards.');

            return Command::SUCCESS;
        }

        $this->conn->beginTransaction();

        try {
            if ($preserveId) {
                // 1) Make current row ADMIN
                $io->text('∙ Update current access_url -> ADMIN');
                $this->conn->executeStatement(
                    "UPDATE {$accessUrl} SET url = :adminUrl, description = :descr WHERE id = :id",
                    [
                        'adminUrl' => $newAdminUrl,
                        'descr' => 'The main admin URL',
                        'id' => $currentId,
                    ],
                    [
                        'adminUrl' => Types::STRING,
                        'descr' => Types::STRING,
                        'id' => Types::INTEGER,
                    ]
                );

                // 2) Insert SECONDARY with explicit id = currentId+1
                $adminUrlId = $currentId;
                $oldUrlId = $currentId + 1;

                $collision = (int) $this->conn->fetchOne(
                    "SELECT COUNT(*) FROM {$accessUrl} WHERE id = :id",
                    ['id' => $oldUrlId],
                    ['id' => Types::INTEGER]
                );
                if ($collision > 0) {
                    throw new RuntimeException("Cannot insert SECONDARY access_url with id={$oldUrlId}: id already exists.");
                }

                $insertCols = ['id', 'url', 'description', 'active', 'created_by', 'tms', 'url_type'];
                $params = [
                    'id' => $oldUrlId,
                    'url' => $currentUrl,
                    'description' => '',
                    'active' => 1,
                    'created_by' => 1,
                    'tms' => $nowUtc(),
                    'url_type' => null,
                ];
                $types = [
                    'id' => Types::INTEGER,
                    'url' => Types::STRING,
                    'description' => Types::STRING,
                    'active' => Types::BOOLEAN,
                    'created_by' => Types::INTEGER,
                    'tms' => Types::DATETIME_MUTABLE,
                    'url_type' => Types::BOOLEAN,
                ];

                if ($hasTree) {
                    $insertCols[] = 'lft';
                    $insertCols[] = 'rgt';
                    $insertCols[] = 'lvl';
                    $params['lft'] = 1;
                    $params['rgt'] = 2;
                    $params['lvl'] = 0;
                    $types['lft'] = Types::INTEGER;
                    $types['rgt'] = Types::INTEGER;
                    $types['lvl'] = Types::INTEGER;
                }

                if ($rootCol) {
                    $insertCols[] = $rootCol;
                    $params[$rootCol] = null;
                    $types[$rootCol] = Types::INTEGER;
                }

                $io->text("∙ Insert SECONDARY access_url (explicit id = {$oldUrlId})");
                $placeholders = array_map(static fn (string $c) => ':'.$c, $insertCols);

                $this->conn->executeStatement(
                    "INSERT INTO {$accessUrl} (".implode(',', $insertCols).') VALUES ('.implode(',', $placeholders).')',
                    $params,
                    $types
                );

                if ($rootCol) {
                    $io->text('∙ Initialize tree root column for SECONDARY');
                    $this->conn->executeStatement(
                        "UPDATE {$accessUrl} SET {$rootCol} = :self WHERE id = :self",
                        ['self' => $oldUrlId],
                        ['self' => Types::INTEGER]
                    );
                }

                // Ensure resource nodes exist for BOTH URLs
                $ensureResourceNodeId($oldUrlId, $currentUrl);
                $ensureResourceNodeId($adminUrlId, $newAdminUrl);

                // 3) Move FKs from ADMIN to SECONDARY
                $move = function (string $table, string $col) use ($io, $adminUrlId, $oldUrlId, $exists, $tableColumns): void {
                    if (!$exists($table)) {
                        return;
                    }
                    $cols = $tableColumns($table);
                    if (!isset($cols[$col])) {
                        return;
                    }
                    $io->text("∙ Update {$table}.{$col} {$adminUrlId} -> {$oldUrlId}");
                    $this->conn->executeStatement(
                        "UPDATE {$table} SET {$col} = :to WHERE {$col} = :from",
                        ['to' => $oldUrlId, 'from' => $adminUrlId],
                        ['to' => Types::INTEGER, 'from' => Types::INTEGER]
                    );
                };

                $move($userRelCourseVote, 'url_id');
                $move($trackOnline, 'access_url_id');
                $move($sysAnnouncement, 'access_url_id');
                $move($skill, 'access_url_id');
                $move($relCourse, 'access_url_id');
                $move($relCourseCategory, 'access_url_id');
                $move($relSession, 'access_url_id');
                $move($relUser, 'access_url_id');
                $move($relUsergroup, 'access_url_id');
                $move($branchSync, 'access_url_id');
                $move($sessionCategory, 'access_url_id');

                if ($exists($systemCalendar)) {
                    $move($systemCalendar, 'access_url_id');
                }
                if ($exists($trackCourseRanking)) {
                    $move($trackCourseRanking, 'url_id');
                }

                // user_auth_source (otherwise users may not login on the SECONDARY URL)
                if ($exists($userAuthSource)) {
                    $move($userAuthSource, 'url_id');
                }

                // Copy admin user_auth_source back to ADMIN URL (so admin can login on ADMIN URL)
                $copyUserAuthSourceForUser($oldUrlId, $adminUrlId, $adminUserId);

                // 4) Ensure admin user is linked to ADMIN url
                $io->text('∙ Ensure admin user has relation to ADMIN url');
                $this->conn->executeStatement(
                    "INSERT INTO {$relUser} (access_url_id, user_id)
                     SELECT :adminId, :userId
                     WHERE NOT EXISTS (
                       SELECT 1 FROM {$relUser} WHERE access_url_id = :adminId AND user_id = :userId
                     )",
                    ['adminId' => $adminUrlId, 'userId' => $adminUserId],
                    ['adminId' => Types::INTEGER, 'userId' => Types::INTEGER]
                );
            } else {
                // SAFER: Insert ADMIN with auto id; keep current as SECONDARY (no FK moves)
                $io->text('∙ Insert ADMIN access_url (auto id)');
                $insertCols = ['url', 'description', 'active', 'created_by', 'tms', 'url_type'];
                $params = [
                    'url' => $newAdminUrl,
                    'description' => 'The main admin URL',
                    'active' => 1,
                    'created_by' => 1,
                    'tms' => $nowUtc(),
                    'url_type' => null,
                ];
                $types = [
                    'url' => Types::STRING,
                    'description' => Types::STRING,
                    'active' => Types::BOOLEAN,
                    'created_by' => Types::INTEGER,
                    'tms' => Types::DATETIME_MUTABLE,
                    'url_type' => Types::BOOLEAN,
                ];

                if ($hasTree) {
                    $insertCols[] = 'lft';
                    $insertCols[] = 'rgt';
                    $insertCols[] = 'lvl';
                    $params['lft'] = 1;
                    $params['rgt'] = 2;
                    $params['lvl'] = 0;
                    $types['lft'] = Types::INTEGER;
                    $types['rgt'] = Types::INTEGER;
                    $types['lvl'] = Types::INTEGER;
                }

                if ($rootCol) {
                    $insertCols[] = $rootCol;
                    $params[$rootCol] = null;
                    $types[$rootCol] = Types::INTEGER;
                }

                $placeholders = array_map(static fn (string $c) => ':'.$c, $insertCols);

                $this->conn->executeStatement(
                    "INSERT INTO {$accessUrl} (".implode(',', $insertCols).') VALUES ('.implode(',', $placeholders).')',
                    $params,
                    $types
                );

                // Get new id deterministically
                $newId = (int) $this->conn->fetchOne(
                    "SELECT id FROM {$accessUrl} WHERE url = :u ORDER BY id DESC LIMIT 1",
                    ['u' => $newAdminUrl],
                    ['u' => Types::STRING]
                );

                if ($rootCol) {
                    $io->text('∙ Initialize tree root column for ADMIN access_url');
                    $this->conn->executeStatement(
                        "UPDATE {$accessUrl} SET {$rootCol} = :self WHERE id = :self",
                        ['self' => $newId],
                        ['self' => Types::INTEGER]
                    );
                }

                // Ensure ADMIN access_url has a resource node
                $ensureResourceNodeId($newId, $newAdminUrl);

                // Copy user_auth_source for admin user to the new ADMIN URL (so admin can login there)
                $copyUserAuthSourceForUser($currentId, $newId, $adminUserId);

                // Ensure admin user is linked to new ADMIN url
                $io->text('∙ Ensure admin user has relation to ADMIN url');
                $this->conn->executeStatement(
                    "INSERT INTO {$relUser} (access_url_id, user_id)
                     SELECT :adminId, :userId
                     WHERE NOT EXISTS (
                       SELECT 1 FROM {$relUser} WHERE access_url_id = :adminId AND user_id = :userId
                     )",
                    ['adminId' => $newId, 'userId' => $adminUserId],
                    ['adminId' => Types::INTEGER, 'userId' => Types::INTEGER]
                );
            }

            $this->conn->commit();
            $io->success('Portal converted to MultiURL successfully.');
            $io->note('Remember to enable multi-URL mode in configuration (e.g., configuration.php or platform setting).');

            return Command::SUCCESS;
        } catch (DBALException|RuntimeException $e) {
            $this->conn->rollBack();
            $io->error('Conversion failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
