<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Repository\CAnnouncementRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

final readonly class ScheduledCourseAnnouncementProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CAnnouncementRepository $announcementRepository,
        private AnnouncementScheduleManager $scheduleManager,
        private AnnouncementRecipientResolver $recipientResolver,
        private AnnouncementEmailRecipientResolver $emailRecipientResolver,
        private AnnouncementEmailSender $emailSender,
    ) {}

    public function sendPendingMessages(
        int $accessUrlId,
        bool $debug,
        SymfonyStyle $io,
        bool $alsoInternalMessage = false,
    ): int {
        $connection = $this->entityManager->getConnection();
        if (!$this->isEnabled($connection, $accessUrlId)) {
            if ($debug) {
                $io->writeln('Debug: Date-based course announcements disabled for access URL #'.$accessUrlId);
            }

            return 0;
        }

        $messagesSent = 0;
        foreach ($this->getPendingAnnouncementIds($connection, $accessUrlId) as $announcementId) {
            $messagesSent += $this->processAnnouncement(
                $connection,
                $announcementId,
                $accessUrlId,
                $debug,
                $io,
                $alsoInternalMessage,
            );
        }

        return $messagesSent;
    }

    private function processAnnouncement(
        Connection $connection,
        int $announcementId,
        int $accessUrlId,
        bool $debug,
        SymfonyStyle $io,
        bool $alsoInternalMessage,
    ): int {
        $connection->beginTransaction();

        try {
            $state = $connection->fetchAssociative(
                <<<'SQL'
                    SELECT a.email_sent, rl.c_id, rl.session_id
                    FROM c_announcement a
                    INNER JOIN resource_link rl
                        ON rl.resource_node_id = a.resource_node_id
                       AND rl.deleted_at IS NULL
                       AND rl.c_id IS NOT NULL
                    INNER JOIN access_url_rel_course aurc
                        ON aurc.c_id = rl.c_id
                       AND aurc.access_url_id = :accessUrlId
                    WHERE a.iid = :announcementId
                    ORDER BY rl.id ASC
                    LIMIT 1
                    FOR UPDATE
                    SQL,
                [
                    'accessUrlId' => $accessUrlId,
                    'announcementId' => $announcementId,
                ],
            );

            if (false === $state || 1 === (int) ($state['email_sent'] ?? 0)) {
                $connection->commit();

                return 0;
            }

            $announcement = $this->announcementRepository->find($announcementId);
            if (!$announcement instanceof CAnnouncement) {
                $connection->commit();

                return 0;
            }

            $schedule = $this->scheduleManager->getValues($announcement);
            if (!$schedule['scheduleByDate'] || !$this->isDue($schedule['scheduleDate'])) {
                $connection->commit();

                return 0;
            }

            $course = $this->entityManager->getRepository(Course::class)->find((int) ($state['c_id'] ?? 0));
            if (!$course instanceof Course) {
                $connection->commit();

                return 0;
            }

            $session = null;
            $sessionId = (int) ($state['session_id'] ?? 0);
            if ($sessionId > 0) {
                $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
                if (!$session instanceof Session) {
                    $connection->commit();

                    return 0;
                }
            }

            $sender = $announcement->getCreator();
            if (!$sender instanceof User || null === $sender->getId()) {
                if ($debug) {
                    $io->writeln('Debug: Missing creator for scheduled course announcement #'.$announcementId);
                }
                $connection->commit();

                return 0;
            }

            $selection = $this->recipientResolver->getSelectedRecipients(
                $announcement,
                $course,
                $session,
                null,
            );
            $recipients = $this->emailRecipientResolver->resolvePrimaryRecipients(
                $selection,
                $course,
                $session,
                null,
                $schedule['sendToUsersInSessions'],
            );

            if ([] === $recipients) {
                if ($debug) {
                    $io->writeln('Debug: No recipients found for scheduled course announcement #'.$announcementId);
                }
                $connection->commit();

                return 0;
            }

            $delivery = $this->emailSender->send(
                $announcement,
                $course,
                $session,
                null,
                $sender,
                $recipients,
                [],
                true,
                false,
                $alsoInternalMessage,
            );

            if ($delivery['primarySentCount'] <= 0) {
                if ($debug) {
                    if ($alsoInternalMessage) {
                        $io->writeln(
                            \sprintf(
                                'Debug: Scheduled course announcement #%d has %d internal message(s) available '.
                                '(%d created in this run), but no external email was delivered.',
                                $announcementId,
                                $delivery['internalMessageCount'],
                                $delivery['internalMessageCreatedCount'],
                            ),
                        );
                    } else {
                        $io->writeln(
                            'Debug: Scheduled course announcement #'.$announcementId.
                            ' could not be delivered by external email.',
                        );
                    }
                }
                $connection->commit();

                return 0;
            }

            $announcement->setEmailSent(true);
            $this->entityManager->persist($announcement);
            $this->entityManager->flush();
            $connection->commit();

            if ($debug) {
                $io->writeln(
                    'Debug: Scheduled course announcement #'.$announcementId.
                    ' delivered to '.$delivery['primarySentCount'].' recipient(s).',
                );
            }

            return 1;
        } catch (Throwable $throwable) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $this->entityManager->clear();

            throw $throwable;
        }
    }

    private function isDue(string $scheduleDate): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($scheduleDate));
        $errors = DateTimeImmutable::getLastErrors();

        if (false === $date || (
            false !== $errors
            && (0 !== $errors['warning_count'] || 0 !== $errors['error_count'])
        )) {
            return false;
        }

        return $date <= new DateTimeImmutable('today');
    }

    private function isEnabled(Connection $connection, int $accessUrlId): bool
    {
        $value = $connection->fetchOne(
            <<<'SQL'
                SELECT selected_value
                FROM settings
                WHERE variable = :variable
                  AND category = :category
                  AND access_url = :accessUrlId
                LIMIT 1
                SQL,
            [
                'variable' => 'course_announcement_scheduled_by_date',
                'category' => 'announcement',
                'accessUrlId' => $accessUrlId,
            ],
        );

        if (false === $value && 1 !== $accessUrlId) {
            $value = $connection->fetchOne(
                <<<'SQL'
                    SELECT selected_value
                    FROM settings
                    WHERE variable = :variable
                      AND category = :category
                      AND access_url = 1
                    LIMIT 1
                    SQL,
                [
                    'variable' => 'course_announcement_scheduled_by_date',
                    'category' => 'announcement',
                ],
            );
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<int, int>
     */
    private function getPendingAnnouncementIds(Connection $connection, int $accessUrlId): array
    {
        $ids = $connection->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT a.iid
                FROM c_announcement a
                INNER JOIN resource_link rl
                    ON rl.resource_node_id = a.resource_node_id
                   AND rl.deleted_at IS NULL
                   AND rl.c_id IS NOT NULL
                INNER JOIN access_url_rel_course aurc
                    ON aurc.c_id = rl.c_id
                   AND aurc.access_url_id = :accessUrlId
                WHERE a.email_sent IS NULL OR a.email_sent = 0
                ORDER BY a.iid ASC
                SQL,
            ['accessUrlId' => $accessUrlId],
        );

        return array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
    }
}
