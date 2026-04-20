<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Command;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\ScheduledAnnouncementHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Database;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

#[AsCommand(
    name: 'app:send-scheduled-announcements',
    description: 'Send scheduled announcements to all users.',
)]
class SendScheduledAnnouncementsCommand extends Command
{
    private const COURSE_ANNOUNCEMENT_ITEM_TYPE = 21;
    private const USER_SOFT_DELETED = -2;

    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly ScheduledAnnouncementHelper $scheduledAnnouncementHelper,
        private readonly EntityManager $em,
        private readonly MailerInterface $mailer,
        private readonly MessageHelper $messageHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_NONE, 'If set, debug messages will be shown.')
            ->addOption(
                'also-internal-message',
                null,
                InputOption::VALUE_NONE,
                'If set, an internal message will also be created for each recipient.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Database::setManager($this->em);

        $container = $this->getApplication()->getKernel()->getContainer();
        Container::setContainer($container);

        $io = new SymfonyStyle($input, $output);
        $debug = (bool) $input->getOption('debug');
        $alsoInternalMessage = (bool) $input->getOption('also-internal-message');

        $urlList = $this->accessUrlRepository->findAll();

        if (empty($urlList)) {
            $io->warning('No access URLs found.');

            return Command::SUCCESS;
        }

        foreach ($urlList as $url) {
            $urlId = $url->getId();
            $io->writeln('Portal: #'.$urlId.' - '.$url->getUrl());

            try {
                $messagesSent = $this->scheduledAnnouncementHelper->sendPendingMessages($urlId, $debug);
                $io->writeln('Messages sent: '.$messagesSent);

                if ($debug) {
                    $io->writeln('Debug: Processed portal with ID '.$urlId);
                }
            } catch (Throwable $e) {
                $io->error('Error processing portal with ID '.$urlId.': '.$e->getMessage());

                return Command::FAILURE;
            }

            try {
                $courseAnnouncementMessagesSent = $this->sendPendingCourseAnnouncementMessages(
                    $urlId,
                    $debug,
                    $alsoInternalMessage,
                    $io
                );
                $io->writeln('Course announcement messages sent: '.$courseAnnouncementMessagesSent);
            } catch (Throwable $e) {
                $io->error('Error processing scheduled course announcements: '.$e->getMessage());

                return Command::FAILURE;
            }
        }

        $io->success('All scheduled announcements have been sent.');

        return Command::SUCCESS;
    }

    private function sendPendingCourseAnnouncementMessages(
        int $accessUrlId,
        bool $debug,
        bool $alsoInternalMessage,
        SymfonyStyle $io
    ): int {
        $connection = $this->em->getConnection();

        if (!$this->isDateBasedCourseAnnouncementEnabled($connection, $accessUrlId)) {
            if ($debug) {
                $io->writeln('Debug: Date-based course announcements disabled for access URL #'.$accessUrlId);
            }

            return 0;
        }

        $today = date('Y-m-d');
        $messagesSent = 0;
        $announcements = $this->getPendingCourseAnnouncements($connection, $accessUrlId);

        foreach ($announcements as $announcement) {
            $announcementId = (int) ($announcement['iid'] ?? 0);
            $resourceNodeId = (int) ($announcement['resource_node_id'] ?? 0);

            if ($announcementId <= 0 || $resourceNodeId <= 0) {
                continue;
            }

            $extraValues = $this->getCourseAnnouncementExtraValues($connection, $announcementId);

            if (1 !== (int) ($extraValues['send_notification_at_a_specific_date'] ?? 0)) {
                continue;
            }

            $dateToSend = (string) ($extraValues['date_to_send_notification'] ?? '');
            if ('' === $dateToSend || $today < substr($dateToSend, 0, 10)) {
                continue;
            }

            $contextRows = $this->getAnnouncementContextRows($connection, $resourceNodeId, $accessUrlId);
            if (empty($contextRows)) {
                if ($debug) {
                    $io->writeln('Debug: No resource link context found for scheduled course announcement #'.$announcementId);
                }

                continue;
            }

            $sender = $this->getSenderAddress($connection, $resourceNodeId);
            if (null === $sender) {
                if ($debug) {
                    $io->writeln('Debug: Missing valid sender e-mail for scheduled course announcement #'.$announcementId);
                }

                continue;
            }

            $senderUserId = $this->getAnnouncementCreatorId($connection, $resourceNodeId);
            $sendToUsersInSession = 1 === (int) ($extraValues['send_to_users_in_session'] ?? 0);

            $recipients = $this->resolveRecipients(
                $connection,
                $contextRows,
                $sendToUsersInSession,
                $accessUrlId
            );

            if (empty($recipients)) {
                if ($debug) {
                    $io->writeln('Debug: No recipients found for scheduled course announcement #'.$announcementId);
                }

                continue;
            }

            $deliveryResult = $this->sendAnnouncementDeliveries(
                $announcement,
                $sender,
                $senderUserId,
                $recipients,
                $alsoInternalMessage,
                $debug,
                $io
            );

            if (($deliveryResult['email_sent_count'] ?? 0) <= 0) {
                if ($debug && ($deliveryResult['internal_message_count'] ?? 0) > 0) {
                    $io->writeln(
                        'Debug: Internal messages created for scheduled course announcement #'.$announcementId.
                        ' but no e-mail was sent successfully.'
                    );
                }

                continue;
            }

            $connection->update(
                'c_announcement',
                ['email_sent' => 1],
                ['iid' => $announcementId]
            );

            ++$messagesSent;

            if ($debug) {
                $io->writeln(
                    'Debug: Scheduled course announcement sent: #'.$announcementId.
                    ' to '.($deliveryResult['email_sent_count'] ?? 0).' recipient(s)'.
                    (($deliveryResult['internal_message_count'] ?? 0) > 0
                        ? ' and '.($deliveryResult['internal_message_count'] ?? 0).' internal message(s) created'
                        : '')
                );
            }
        }

        return $messagesSent;
    }

    private function isDateBasedCourseAnnouncementEnabled(Connection $connection, int $accessUrlId): bool
    {
        $value = $connection->fetchOne(
            '
            SELECT selected_value
            FROM settings
            WHERE variable = :variable
              AND category = :category
              AND access_url = :accessUrlId
            LIMIT 1
            ',
            [
                'variable' => 'course_announcement_scheduled_by_date',
                'category' => 'announcement',
                'accessUrlId' => $accessUrlId,
            ]
        );

        if (false === $value) {
            $value = $connection->fetchOne(
                '
                SELECT selected_value
                FROM settings
                WHERE variable = :variable
                  AND category = :category
                  AND access_url = 1
                LIMIT 1
                ',
                [
                    'variable' => 'course_announcement_scheduled_by_date',
                    'category' => 'announcement',
                ]
            );
        }

        return 'true' === $value;
    }

    private function getPendingCourseAnnouncements(Connection $connection, int $accessUrlId): array
    {
        return $connection->fetchAllAssociative(
            '
            SELECT DISTINCT a.iid, a.resource_node_id, a.title, a.content
            FROM c_announcement a
            INNER JOIN resource_link rl
                ON rl.resource_node_id = a.resource_node_id
               AND rl.deleted_at IS NULL
               AND rl.c_id IS NOT NULL
            INNER JOIN access_url_rel_course aurc
                ON aurc.c_id = rl.c_id
               AND aurc.access_url_id = :accessUrlId
            WHERE a.email_sent IS NULL OR a.email_sent = 0
            ORDER BY a.iid DESC
            ',
            [
                'accessUrlId' => $accessUrlId,
            ]
        );
    }

    private function getCourseAnnouncementExtraValues(Connection $connection, int $announcementId): array
    {
        $rows = $connection->fetchAllAssociative(
            '
            SELECT ef.variable, efv.field_value
            FROM extra_field_values efv
            INNER JOIN extra_field ef
                ON ef.id = efv.field_id
            WHERE ef.item_type = :itemType
              AND efv.item_id = :itemId
              AND ef.variable IN (
                :sendNotificationVariable,
                :dateVariable,
                :sendToUsersVariable
              )
            ',
            [
                'itemType' => self::COURSE_ANNOUNCEMENT_ITEM_TYPE,
                'itemId' => $announcementId,
                'sendNotificationVariable' => 'send_notification_at_a_specific_date',
                'dateVariable' => 'date_to_send_notification',
                'sendToUsersVariable' => 'send_to_users_in_session',
            ]
        );

        $result = [];
        foreach ($rows as $row) {
            $variable = (string) ($row['variable'] ?? '');
            if ('' === $variable) {
                continue;
            }

            $result[$variable] = $row['field_value'] ?? null;
        }

        return $result;
    }

    private function getAnnouncementContextRows(Connection $connection, int $resourceNodeId, int $accessUrlId): array
    {
        return $connection->fetchAllAssociative(
            '
            SELECT DISTINCT
                rl.c_id,
                rl.session_id,
                rl.usergroup_id,
                rl.group_id,
                rl.user_id
            FROM resource_link rl
            INNER JOIN access_url_rel_course aurc
                ON aurc.c_id = rl.c_id
               AND aurc.access_url_id = :accessUrlId
            WHERE rl.resource_node_id = :resourceNodeId
              AND rl.deleted_at IS NULL
              AND rl.c_id IS NOT NULL
            ORDER BY rl.id ASC
            ',
            [
                'accessUrlId' => $accessUrlId,
                'resourceNodeId' => $resourceNodeId,
            ]
        );
    }

    private function getSenderAddress(Connection $connection, int $resourceNodeId): ?Address
    {
        $row = $connection->fetchAssociative(
            '
            SELECT u.email, u.firstname, u.lastname, u.username
            FROM resource_node rn
            INNER JOIN user u
                ON u.id = rn.creator_id
            WHERE rn.id = :resourceNodeId
            LIMIT 1
            ',
            [
                'resourceNodeId' => $resourceNodeId,
            ]
        );

        if (empty($row)) {
            return null;
        }

        $email = trim((string) ($row['email'] ?? ''));
        if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = trim(
            ((string) ($row['firstname'] ?? '')).' '.((string) ($row['lastname'] ?? ''))
        );

        if ('' === $name) {
            $name = (string) ($row['username'] ?? '');
        }

        return '' !== $name ? new Address($email, $name) : new Address($email);
    }

    private function getAnnouncementCreatorId(Connection $connection, int $resourceNodeId): int
    {
        $creatorId = $connection->fetchOne(
            '
            SELECT creator_id
            FROM resource_node
            WHERE id = :resourceNodeId
            LIMIT 1
            ',
            [
                'resourceNodeId' => $resourceNodeId,
            ]
        );

        return (int) ($creatorId ?: 0);
    }

    private function resolveRecipients(
        Connection $connection,
        array $contextRows,
        bool $sendToUsersInSession,
        int $accessUrlId
    ): array {
        $recipients = [];
        $allCourseSessionRecipientsLoaded = [];

        foreach ($contextRows as $contextRow) {
            $courseId = (int) ($contextRow['c_id'] ?? 0);
            $sessionId = isset($contextRow['session_id']) ? (int) ($contextRow['session_id'] ?? 0) : 0;
            $userId = isset($contextRow['user_id']) ? (int) ($contextRow['user_id'] ?? 0) : 0;
            $groupId = isset($contextRow['group_id']) ? (int) ($contextRow['group_id'] ?? 0) : 0;
            $usergroupId = isset($contextRow['usergroup_id']) ? (int) ($contextRow['usergroup_id'] ?? 0) : 0;

            if ($courseId <= 0) {
                continue;
            }

            if ($userId > 0) {
                $this->appendRecipientsByUserIds($connection, [$userId], $recipients);
                continue;
            }

            if ($groupId > 0) {
                $userIds = $connection->fetchFirstColumn(
                    '
                    SELECT DISTINCT gru.user_id
                    FROM c_group_rel_user gru
                    WHERE gru.c_id = :courseId
                      AND gru.group_id = :groupId
                    ',
                    [
                        'courseId' => $courseId,
                        'groupId' => $groupId,
                    ]
                );

                $this->appendRecipientsByUserIds($connection, $userIds, $recipients);
                continue;
            }

            if ($usergroupId > 0) {
                $userIds = $connection->fetchFirstColumn(
                    '
                    SELECT DISTINCT ugu.user_id
                    FROM usergroup_rel_user ugu
                    WHERE ugu.usergroup_id = :usergroupId
                    ',
                    [
                        'usergroupId' => $usergroupId,
                    ]
                );

                $this->appendRecipientsByUserIds($connection, $userIds, $recipients);
                continue;
            }

            if ($sendToUsersInSession) {
                $key = $courseId.'-'.$accessUrlId;

                if (!isset($allCourseSessionRecipientsLoaded[$key])) {
                    $userIds = $connection->fetchFirstColumn(
                        '
                        SELECT DISTINCT srcu.user_id
                        FROM session_rel_course_rel_user srcu
                        INNER JOIN access_url_rel_session aurs
                            ON aurs.session_id = srcu.session_id
                           AND aurs.access_url_id = :accessUrlId
                        WHERE srcu.c_id = :courseId
                        ',
                        [
                            'accessUrlId' => $accessUrlId,
                            'courseId' => $courseId,
                        ]
                    );

                    $this->appendRecipientsByUserIds($connection, $userIds, $recipients);
                    $allCourseSessionRecipientsLoaded[$key] = true;
                }

                continue;
            }

            if ($sessionId > 0) {
                $userIds = $connection->fetchFirstColumn(
                    '
                    SELECT DISTINCT srcu.user_id
                    FROM session_rel_course_rel_user srcu
                    WHERE srcu.c_id = :courseId
                      AND srcu.session_id = :sessionId
                    ',
                    [
                        'courseId' => $courseId,
                        'sessionId' => $sessionId,
                    ]
                );

                $this->appendRecipientsByUserIds($connection, $userIds, $recipients);
                continue;
            }

            $userIds = $connection->fetchFirstColumn(
                '
                SELECT DISTINCT cru.user_id
                FROM course_rel_user cru
                WHERE cru.c_id = :courseId
                ',
                [
                    'courseId' => $courseId,
                ]
            );

            $this->appendRecipientsByUserIds($connection, $userIds, $recipients);
        }

        return array_values($recipients);
    }

    private function appendRecipientsByUserIds(Connection $connection, array $userIds, array &$recipients): void
    {
        $userIds = array_values(
            array_unique(
                array_filter(
                    array_map('intval', $userIds),
                    static fn (int $userId): bool => $userId > 0
                )
            )
        );

        if (empty($userIds)) {
            return;
        }

        $rows = $connection->fetchAllAssociative(
            '
            SELECT u.id, u.email, u.firstname, u.lastname, u.username
            FROM user u
            WHERE u.id IN (:userIds)
              AND u.status != :softDeletedStatus
              AND u.email IS NOT NULL
              AND u.email != \'\'
            ',
            [
                'userIds' => $userIds,
                'softDeletedStatus' => self::USER_SOFT_DELETED,
            ],
            [
                'userIds' => ArrayParameterType::INTEGER,
            ]
        );

        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if (false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $userId = (int) ($row['id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $name = trim(
                ((string) ($row['firstname'] ?? '')).' '.((string) ($row['lastname'] ?? ''))
            );

            if ('' === $name) {
                $name = (string) ($row['username'] ?? '');
            }

            $recipients[$userId] = [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
            ];
        }
    }

    private function sendAnnouncementDeliveries(
        array $announcement,
        Address $sender,
        int $senderUserId,
        array $recipients,
        bool $alsoInternalMessage,
        bool $debug,
        SymfonyStyle $io
    ): array {
        $emailSentCount = 0;
        $internalMessageCount = 0;

        $subject = trim((string) ($announcement['title'] ?? ''));
        if ('' === $subject) {
            $subject = 'Course announcement';
        }

        $htmlBody = (string) ($announcement['content'] ?? '');
        if ('' === $htmlBody) {
            $htmlBody = '<p></p>';
        }

        $textBody = trim(html_entity_decode(strip_tags($htmlBody)));
        if ('' === $textBody) {
            $textBody = ' ';
        }

        $announcementId = (int) ($announcement['iid'] ?? 0);

        foreach ($recipients as $recipient) {
            $recipientUserId = (int) ($recipient['id'] ?? 0);
            $recipientEmail = trim((string) ($recipient['email'] ?? ''));
            $recipientName = trim((string) ($recipient['name'] ?? ''));

            if ($alsoInternalMessage && $senderUserId > 0 && $recipientUserId > 0 && $recipientUserId !== $senderUserId) {
                try {
                    $messageId = $this->messageHelper->sendMessageSimple(
                        $recipientUserId,
                        $subject,
                        $htmlBody,
                        $senderUserId,
                        false,
                        false,
                        []
                    );

                    if (null !== $messageId) {
                        ++$internalMessageCount;

                        if ($debug) {
                            $io->writeln(
                                'Debug: Internal message created for scheduled course announcement #'.$announcementId.
                                ' to user #'.$recipientUserId
                            );
                        }
                    }
                } catch (Throwable $e) {
                    if ($debug) {
                        $io->writeln(
                            'Debug: Failed to create internal message for scheduled course announcement #'.$announcementId.
                            ' to user #'.$recipientUserId.' - '.$e->getMessage()
                        );
                    }
                }
            }

            if (false === filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            try {
                $email = (new Email())
                    ->from($sender)
                    ->to('' !== $recipientName ? new Address($recipientEmail, $recipientName) : new Address($recipientEmail))
                    ->subject($subject)
                    ->html($htmlBody)
                    ->text($textBody);

                $this->mailer->send($email);
                ++$emailSentCount;
            } catch (Throwable $e) {
                if ($debug) {
                    $io->writeln(
                        'Debug: Failed to send scheduled course announcement #'.$announcementId.
                        ' to '.$recipientEmail.' - '.$e->getMessage()
                    );
                }
            }
        }

        return [
            'email_sent_count' => $emailSentCount,
            'internal_message_count' => $internalMessageCount,
        ];
    }
}
