<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'chamilo:disable-user-conditions',
    description: 'Disable learner accounts matching inactivity conditions and notify them.'
)]
final class DisableUserConditionsCommand extends Command
{
    private const EXTRA_FIELD_VARIABLE_TERM_ACTIVATED = 'termactivated';

    private const USER_STATUS_STUDENT = 5;
    private const USER_ACTIVE_ENABLED = 1;
    private const USER_ACTIVE_DISABLED = 0;

    private const MESSAGE_RECEIVER_TYPE_TO = 1;

    /** @var array<string, string[]> */
    private array $tableColumnsCache = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly SettingsManager $settingsManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'apply',
                null,
                InputOption::VALUE_NONE,
                'Apply changes. Without this option, the command only reports what would happen.'
            )
            ->addOption(
                'user-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit execution to one learner user ID.'
            )
            ->addOption(
                'case',
                null,
                InputOption::VALUE_REQUIRED,
                'Limit execution to one case: 1, 2 or 3.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apply = (bool) $input->getOption('apply');
        $userIdFilter = $input->getOption('user-id');
        $caseFilter = $input->getOption('case');

        if (null !== $userIdFilter && (!ctype_digit((string) $userIdFilter) || (int) $userIdFilter <= 0)) {
            $output->writeln('<error>The --user-id option must be a positive integer.</error>');

            return Command::FAILURE;
        }

        if (null !== $caseFilter && !in_array((string) $caseFilter, ['1', '2', '3'], true)) {
            $output->writeln('<error>The --case option must be 1, 2 or 3.</error>');

            return Command::FAILURE;
        }

        $senderId = $this->getSenderId();

        if ($senderId <= 0) {
            $output->writeln('<error>Please set workflows.disable_user_conditions_sender_id with a valid numeric user ID.</error>');

            return Command::FAILURE;
        }

        $senderInfo = $this->getUserInfo($senderId);

        if (empty($senderInfo)) {
            $output->writeln('<error>Please set workflows.disable_user_conditions_sender_id with an existing user ID.</error>');

            return Command::FAILURE;
        }

        if (self::USER_ACTIVE_ENABLED !== (int) $senderInfo['active']) {
            $output->writeln('<error>The sender user configured in workflows.disable_user_conditions_sender_id is not active.</error>');

            return Command::FAILURE;
        }

        $termActivatedFieldId = $this->getUserExtraFieldId(self::EXTRA_FIELD_VARIABLE_TERM_ACTIVATED);

        if ($termActivatedFieldId <= 0) {
            $output->writeln('<error>User extra field "termactivated" was not found.</error>');

            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $date3Months = $now->sub(new \DateInterval('P3M'));
        $date6Months = $now->sub(new \DateInterval('P6M'));

        $output->writeln($apply ? '<info>Apply mode enabled.</info>' : '<comment>Dry-run mode. No changes will be made.</comment>');
        $output->writeln(sprintf(
            'Sender user: #%d %s (%s)',
            $senderId,
            $this->getCompleteName($senderInfo),
            $senderInfo['email'] ?? ''
        ));
        $output->writeln('Now: '.$now->format('Y-m-d H:i:s'));
        $output->writeln('3 months old: '.$date3Months->format('Y-m-d H:i:s'));
        $output->writeln('6 months old: '.$date6Months->format('Y-m-d H:i:s'));
        $output->writeln('');

        $processedUsers = [];
        $filteredUserId = null !== $userIdFilter ? (int) $userIdFilter : null;

        if (null === $caseFilter || '1' === (string) $caseFilter) {
            $this->processCaseWithoutContractAndInactive(
                $output,
                $termActivatedFieldId,
                $date3Months,
                $senderId,
                $apply,
                $filteredUserId,
                $processedUsers
            );
        }

        if (null === $caseFilter || '3' === (string) $caseFilter) {
            $this->processCaseCertificateAndInactive(
                $output,
                $date6Months,
                $senderId,
                $apply,
                $filteredUserId,
                $processedUsers
            );
        }

        if (null === $caseFilter || '2' === (string) $caseFilter) {
            $this->processCaseValidatedContractAndInactive(
                $output,
                $termActivatedFieldId,
                $date6Months,
                $senderId,
                $apply,
                $filteredUserId,
                $processedUsers
            );
        }

        $output->writeln('');
        $output->writeln(sprintf('Processed users: %d', count($processedUsers)));

        return Command::SUCCESS;
    }

    private function getSenderId(): int
    {
        $rawValue = trim((string) $this->settingsManager->getSetting('workflows.disable_user_conditions_sender_id'));

        if ('' === $rawValue || !ctype_digit($rawValue)) {
            return 0;
        }

        return (int) $rawValue;
    }

    private function getUserExtraFieldId(string $variable): int
    {
        if (!$this->tableExists('extra_field')) {
            return 0;
        }

        $columns = $this->getTableColumns('extra_field');

        $conditions = ['variable = :variable'];
        $parameters = ['variable' => $variable];

        if (in_array('item_type', $columns, true)) {
            $conditions[] = 'item_type = :itemType';
            $parameters['itemType'] = $this->getUserExtraFieldItemType();
        }

        $fieldId = $this->connection->fetchOne(
            'SELECT id FROM extra_field WHERE '.implode(' AND ', $conditions).' ORDER BY id ASC',
            $parameters
        );

        return false !== $fieldId ? (int) $fieldId : 0;
    }

    private function getUserExtraFieldItemType(): int
    {
        if (\defined(EntityExtraField::class.'::USER_FIELD_TYPE')) {
            return EntityExtraField::USER_FIELD_TYPE;
        }

        return 1;
    }

    private function getExtraFieldValueColumn(): string
    {
        if (!$this->tableExists('extra_field_values')) {
            return '';
        }

        $columns = $this->getTableColumns('extra_field_values');

        if (in_array('field_value', $columns, true)) {
            return 'field_value';
        }

        if (in_array('value', $columns, true)) {
            return 'value';
        }

        return '';
    }

    private function getExtraFieldValueItemTypeCondition(string $alias): string
    {
        $columns = $this->getTableColumns('extra_field_values');

        if (!in_array('item_type', $columns, true)) {
            return '';
        }

        return sprintf(' AND %s.item_type = %d ', $alias, $this->getUserExtraFieldItemType());
    }

    /**
     * Case 1:
     * Learners without validated contract and without connection for 3 months.
     *
     * @param array<int, true> $processedUsers
     */
    private function processCaseWithoutContractAndInactive(
        OutputInterface $output,
        int $fieldId,
        \DateTimeImmutable $date3Months,
        int $senderId,
        bool $apply,
        ?int $userIdFilter,
        array &$processedUsers
    ): void {
        $valueColumn = $this->getExtraFieldValueColumn();

        if ('' === $valueColumn) {
            $output->writeln('<error>extra_field_values value column was not found.</error>');

            return;
        }

        $userFilterSql = null !== $userIdFilter ? ' AND u.id = :userId ' : '';
        $itemTypeCondition = $this->getExtraFieldValueItemTypeCondition('ev');

        $sql = "
            SELECT u.id
            FROM user u
            LEFT JOIN extra_field_values ev
                ON u.id = ev.item_id
                AND ev.field_id = :fieldId
                $itemTypeCondition
            WHERE
                LOWER(COALESCE(ev.$valueColumn, '')) NOT IN ('1', 'true', 'yes')
                AND u.active = :active
                AND u.status = :studentStatus
                $userFilterSql
        ";

        $parameters = [
            'fieldId' => $fieldId,
            'active' => self::USER_ACTIVE_ENABLED,
            'studentStatus' => self::USER_STATUS_STUDENT,
        ];

        if (null !== $userIdFilter) {
            $parameters['userId'] = $userIdFilter;
        }

        $students = $this->connection->fetchAllAssociative($sql, $parameters);

        foreach ($students as $student) {
            $studentId = (int) $student['id'];

            if (isset($processedUsers[$studentId])) {
                continue;
            }

            $lastConnectionDate = $this->getLastConnectionDate($studentId);

            if (null === $lastConnectionDate || $lastConnectionDate >= $date3Months) {
                continue;
            }

            $disabledUser = $this->getUserInfo($studentId);

            if (empty($disabledUser) || self::USER_ACTIVE_ENABLED !== (int) $disabledUser['active']) {
                continue;
            }

            $subject = $this->buildDisabledSubject($disabledUser);
            $content = $this->buildDisabledMessage(1, $disabledUser);

            $this->reportUserAction($output, $studentId, $disabledUser, 1, $lastConnectionDate, $date3Months, $subject, $content);

            if ($apply) {
                $this->applyDisabledUserWorkflow($studentId, $subject, $content, $senderId);
            }

            $processedUsers[$studentId] = true;
        }
    }

    /**
     * Case 3:
     * Learners with a completed certificate and without connection for 6 months.
     *
     * @param array<int, true> $processedUsers
     */
    private function processCaseCertificateAndInactive(
        OutputInterface $output,
        \DateTimeImmutable $date6Months,
        int $senderId,
        bool $apply,
        ?int $userIdFilter,
        array &$processedUsers
    ): void {
        if (!$this->tableExists('gradebook_certificate') || !$this->tableExists('track_e_login')) {
            $output->writeln('<comment>Skipping case 3 because gradebook_certificate or track_e_login table was not found.</comment>');

            return;
        }

        $userFilterSql = null !== $userIdFilter ? ' AND c.user_id = :userId ' : '';

        $sql = "
            SELECT c.user_id, MAX(l.login_date) AS latest_login_date
            FROM gradebook_certificate c
            INNER JOIN track_e_login l ON l.login_user_id = c.user_id
            INNER JOIN user u ON l.login_user_id = u.id
            WHERE
                u.status = :studentStatus
                AND u.active = :active
                $userFilterSql
            GROUP BY c.user_id
        ";

        $parameters = [
            'studentStatus' => self::USER_STATUS_STUDENT,
            'active' => self::USER_ACTIVE_ENABLED,
        ];

        if (null !== $userIdFilter) {
            $parameters['userId'] = $userIdFilter;
        }

        $students = $this->connection->fetchAllAssociative($sql, $parameters);

        foreach ($students as $student) {
            $studentId = (int) $student['user_id'];

            if (isset($processedUsers[$studentId])) {
                continue;
            }

            $lastConnectionDate = $this->createDateFromDatabaseValue((string) $student['latest_login_date']);

            if (null === $lastConnectionDate || $lastConnectionDate >= $date6Months) {
                continue;
            }

            $disabledUser = $this->getUserInfo($studentId);

            if (empty($disabledUser) || self::USER_ACTIVE_ENABLED !== (int) $disabledUser['active']) {
                continue;
            }

            $subject = $this->buildDisabledSubject($disabledUser);
            $content = $this->buildDisabledMessage(3, $disabledUser);

            $this->reportUserAction($output, $studentId, $disabledUser, 3, $lastConnectionDate, $date6Months, $subject, $content);

            if ($apply) {
                $this->applyDisabledUserWorkflow($studentId, $subject, $content, $senderId);
            }

            $processedUsers[$studentId] = true;
        }
    }

    /**
     * Case 2:
     * Learners with validated contract and without connection for 6 months.
     *
     * @param array<int, true> $processedUsers
     */
    private function processCaseValidatedContractAndInactive(
        OutputInterface $output,
        int $fieldId,
        \DateTimeImmutable $date6Months,
        int $senderId,
        bool $apply,
        ?int $userIdFilter,
        array &$processedUsers
    ): void {
        $valueColumn = $this->getExtraFieldValueColumn();

        if ('' === $valueColumn) {
            $output->writeln('<error>extra_field_values value column was not found.</error>');

            return;
        }

        $userFilterSql = null !== $userIdFilter ? ' AND u.id = :userId ' : '';
        $itemTypeCondition = $this->getExtraFieldValueItemTypeCondition('ev');

        $sql = "
            SELECT u.id
            FROM user u
            INNER JOIN extra_field_values ev
                ON u.id = ev.item_id
                AND ev.field_id = :fieldId
                $itemTypeCondition
            WHERE
                LOWER(COALESCE(ev.$valueColumn, '')) IN ('1', 'true', 'yes')
                AND u.active = :active
                AND u.status = :studentStatus
                $userFilterSql
        ";

        $parameters = [
            'fieldId' => $fieldId,
            'active' => self::USER_ACTIVE_ENABLED,
            'studentStatus' => self::USER_STATUS_STUDENT,
        ];

        if (null !== $userIdFilter) {
            $parameters['userId'] = $userIdFilter;
        }

        $students = $this->connection->fetchAllAssociative($sql, $parameters);

        foreach ($students as $student) {
            $studentId = (int) $student['id'];

            if (isset($processedUsers[$studentId])) {
                continue;
            }

            $lastConnectionDate = $this->getLastConnectionDate($studentId);

            if (null === $lastConnectionDate || $lastConnectionDate >= $date6Months) {
                continue;
            }

            $disabledUser = $this->getUserInfo($studentId);

            if (empty($disabledUser) || self::USER_ACTIVE_ENABLED !== (int) $disabledUser['active']) {
                continue;
            }

            $subject = $this->buildDisabledSubject($disabledUser);
            $content = $this->buildDisabledMessage(2, $disabledUser);

            $this->reportUserAction($output, $studentId, $disabledUser, 2, $lastConnectionDate, $date6Months, $subject, $content);

            $studentBossId = $this->getFirstStudentBoss($studentId);
            $bossInfo = [];
            $bossSubject = '';
            $bossContent = '';

            if ($studentBossId > 0) {
                $bossInfo = $this->getUserInfo($studentBossId);

                if (!empty($bossInfo)) {
                    $bossSubject = $this->buildDisabledSubject($disabledUser);
                    $bossContent = $this->buildBossDisabledMessage($disabledUser);

                    $output->writeln(sprintf(
                        'Boss notification: #%d %s | Subject: %s',
                        $studentBossId,
                        $this->getCompleteName($bossInfo),
                        $bossSubject
                    ));
                }
            }

            if ($apply) {
                $this->connection->beginTransaction();

                try {
                    $this->sendMessage($studentId, $subject, $content, $senderId);
                    $this->disableUser($studentId);

                    if (!empty($bossInfo) && '' !== $bossSubject) {
                        $this->sendMessage($studentBossId, $bossSubject, $bossContent, $senderId);
                    }

                    $this->removeAllBossFromStudent($studentId);

                    $this->connection->commit();
                } catch (\Throwable $exception) {
                    $this->connection->rollBack();

                    throw $exception;
                }
            }

            $processedUsers[$studentId] = true;
        }
    }

    private function applyDisabledUserWorkflow(
        int $studentId,
        string $subject,
        string $content,
        int $senderId
    ): void {
        $this->connection->beginTransaction();

        try {
            $this->sendMessage($studentId, $subject, $content, $senderId);
            $this->disableUser($studentId);

            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    private function getLastConnectionDate(int $userId): ?\DateTimeImmutable
    {
        if (!$this->tableExists('track_e_login')) {
            return null;
        }

        $value = $this->connection->fetchOne(
            'SELECT MAX(login_date) FROM track_e_login WHERE login_user_id = :userId',
            ['userId' => $userId]
        );

        if (false === $value || null === $value) {
            return null;
        }

        return $this->createDateFromDatabaseValue((string) $value);
    }

    private function createDateFromDatabaseValue(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);

        if ('' === $value || '0000-00-00' === $value || '0000-00-00 00:00:00' === $value) {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getUserInfo(int $userId): array
    {
        $user = $this->connection->fetchAssociative(
            'SELECT id, username, firstname, lastname, email, locale, active, status
             FROM user
             WHERE id = :userId',
            ['userId' => $userId]
        );

        return false !== $user ? $user : [];
    }

    /**
     * @param array<string, mixed> $user
     */
    private function getCompleteName(array $user): string
    {
        $firstname = trim((string) ($user['firstname'] ?? ''));
        $lastname = trim((string) ($user['lastname'] ?? ''));

        $completeName = trim($firstname.' '.$lastname);

        if ('' !== $completeName) {
            return $completeName;
        }

        return (string) ($user['username'] ?? ('#'.($user['id'] ?? '')));
    }

    /**
     * @param array<string, mixed> $user
     */
    private function buildDisabledSubject(array $user): string
    {
        return sprintf('Account disabled: %s', $this->getCompleteName($user));
    }

    /**
     * @param array<string, mixed> $user
     */
    private function buildDisabledMessage(int $caseNumber, array $user): string
    {
        return match ($caseNumber) {
            1 => 'Your account has been disabled because no validated contract was found and there has been no recent activity.',
            2 => 'Your account has been disabled because your contract was validated but there has been no recent activity.',
            3 => 'Your account has been disabled because your certificate was completed and there has been no recent activity.',
            default => 'Your account has been disabled because it matched an automatic disabling condition.',
        };
    }

    /**
     * @param array<string, mixed> $disabledUser
     */
    private function buildBossDisabledMessage(array $disabledUser): string
    {
        return sprintf(
            'The learner account "%s" has been disabled because it matched an automatic disabling condition.',
            $this->getCompleteName($disabledUser)
        );
    }

    private function disableUser(int $userId): void
    {
        $this->connection->update(
            'user',
            ['active' => self::USER_ACTIVE_DISABLED],
            [
                'id' => $userId,
                'active' => self::USER_ACTIVE_ENABLED,
            ]
        );
    }

    private function getFirstStudentBoss(int $studentId): int
    {
        if (!$this->tableExists('user_rel_user')) {
            return 0;
        }

        $bossId = $this->connection->fetchOne(
            'SELECT friend_user_id
             FROM user_rel_user
             WHERE user_id = :studentId
               AND relation_type = :relationType
             ORDER BY id ASC
             LIMIT 1',
            [
                'studentId' => $studentId,
                'relationType' => UserRelUser::USER_RELATION_TYPE_BOSS,
            ]
        );

        return false !== $bossId ? (int) $bossId : 0;
    }

    private function removeAllBossFromStudent(int $studentId): void
    {
        if (!$this->tableExists('user_rel_user')) {
            return;
        }

        $this->connection->delete(
            'user_rel_user',
            [
                'user_id' => $studentId,
                'relation_type' => UserRelUser::USER_RELATION_TYPE_BOSS,
            ]
        );
    }

    private function sendMessage(int $recipientId, string $subject, string $content, int $senderId): void
    {
        if (!$this->tableExists('message') || !$this->tableExists('message_rel_user')) {
            throw new \RuntimeException('Message tables were not found.');
        }

        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $messageColumns = $this->getTableColumns('message');

        $messageData = [
            'user_sender_id' => $senderId,
            'title' => $subject,
            'content' => $content,
            'send_date' => $now,
        ];

        if (in_array('msg_type', $messageColumns, true)) {
            $messageData['msg_type'] = Message::MESSAGE_TYPE_INBOX;
        }

        if (in_array('status', $messageColumns, true)) {
            $messageData['status'] = 0;
        }

        if (in_array('parent_id', $messageColumns, true)) {
            $messageData['parent_id'] = 0;
        }

        if (in_array('group_id', $messageColumns, true)) {
            $messageData['group_id'] = 0;
        }

        if (in_array('update_date', $messageColumns, true)) {
            $messageData['update_date'] = $now;
        }

        $this->connection->insert('message', $messageData);

        $messageId = (int) $this->connection->lastInsertId();

        if ($messageId <= 0) {
            throw new \RuntimeException('The internal message could not be created.');
        }

        $this->connection->insert(
            'message_rel_user',
            [
                'message_id' => $messageId,
                'user_id' => $recipientId,
                'msg_read' => 0,
                'receiver_type' => self::MESSAGE_RECEIVER_TYPE_TO,
                'starred' => 0,
                'deleted_at' => null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $disabledUser
     */
    private function reportUserAction(
        OutputInterface $output,
        int $studentId,
        array $disabledUser,
        int $caseNumber,
        \DateTimeImmutable $lastConnectionDate,
        \DateTimeImmutable $thresholdDate,
        string $subject,
        string $content
    ): void {
        $output->writeln(sprintf(
            'User #%d (%s) would be disabled. Case %d. Last connection: %s - threshold: %s',
            $studentId,
            $disabledUser['username'] ?? '',
            $caseNumber,
            $lastConnectionDate->format('Y-m-d H:i:s'),
            $thresholdDate->format('Y-m-d H:i:s')
        ));
        $output->writeln(sprintf('Subject: %s', $subject));
        $output->writeln(sprintf('Content: %s', $content));
        $output->writeln('');
    }

    private function tableExists(string $tableName): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->tablesExist([$tableName]);
    }

    /**
     * @return string[]
     */
    private function getTableColumns(string $tableName): array
    {
        if (isset($this->tableColumnsCache[$tableName])) {
            return $this->tableColumnsCache[$tableName];
        }

        if (!$this->tableExists($tableName)) {
            $this->tableColumnsCache[$tableName] = [];

            return [];
        }

        $schemaManager = $this->connection->createSchemaManager();

        $this->tableColumnsCache[$tableName] = array_map(
            static fn ($column): string => $column->getName(),
            $schemaManager->listTableColumns($tableName)
        );

        return $this->tableColumnsCache[$tableName];
    }
}
