<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MailHelper;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiMailcue;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class WikiNotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailHelper $mailHelper,
        private LoggerInterface $logger,
    ) {}

    public function notifyPageSaved(
        CWiki $wiki,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $actor,
        bool $isNewPage,
    ): void {
        if (1 !== $wiki->getVisibility()) {
            return;
        }

        if ($isNewPage && 2 === $wiki->getAssignment()) {
            return;
        }

        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $watchers = array_values(array_unique(array_merge(
            $this->getWatcherUserIds($courseId, $sessionId, $groupId, 'watch:'.$wiki->getReflink()),
            $this->getWatcherUserIds($courseId, $sessionId, $groupId, 'wiki'),
        )));

        $eventText = $isNewPage
            ? $this->translate('Page was added')
            : $this->translate('It has modified the page');
        $body = $eventText.' <strong>'.htmlspecialchars($wiki->getTitle(), ENT_QUOTES, 'UTF-8').'</strong> '
            .$this->translate('in').' '.$this->translate('Wiki');

        $this->sendNotifications($course, $session, $group, $actor, $watchers, $body);
    }

    /**
     * @param array<int, int> $watcherUserIds
     */
    public function notifyPageDeleted(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $actor,
        string $title,
        array $watcherUserIds,
    ): void {
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $watchers = array_values(array_unique(array_merge(
            $watcherUserIds,
            $this->getWatcherUserIds($courseId, $sessionId, $groupId, 'wiki'),
        )));
        $body = $this->translate('One page has been deleted in the Wiki').' <strong>'
            .htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</strong>';

        $this->sendNotifications($course, $session, $group, $actor, $watchers, $body);
    }

    /**
     * @return array<int, int>
     */
    private function getWatcherUserIds(int $courseId, int $sessionId, int $groupId, string $type): array
    {
        $mailCues = $this->entityManager->getRepository(CWikiMailcue::class)->createQueryBuilder('m')
            ->andWhere('m.cId = :courseId')
            ->andWhere('COALESCE(m.groupId, 0) = :groupId')
            ->andWhere('COALESCE(m.sessionId, 0) = :sessionId')
            ->andWhere('m.type = :type')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('groupId', $groupId, Types::INTEGER)
            ->setParameter('sessionId', $sessionId, Types::INTEGER)
            ->setParameter('type', $type, Types::STRING)
            ->getQuery()
            ->getResult()
        ;
        $userIds = [];

        foreach ($mailCues as $mailCue) {
            if (!$mailCue instanceof CWikiMailcue || $mailCue->getUserId() <= 0) {
                continue;
            }

            $userIds[] = $mailCue->getUserId();
        }

        return array_values(array_unique($userIds));
    }

    /**
     * @param array<int, int> $watcherUserIds
     */
    private function sendNotifications(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $actor,
        array $watcherUserIds,
        string $eventBody,
    ): void {
        if ([] === $watcherUserIds) {
            return;
        }

        $courseTitle = $course->getTitle();
        $contextTitle = $courseTitle;
        if ($session instanceof Session) {
            $contextTitle .= ' ('.$session->getTitle().')';
        }
        if ($group instanceof CGroup) {
            $contextTitle .= ' - '.$group->getTitle();
        }

        $subject = $this->translate('Notify Wiki changes').' - '.$courseTitle;
        $actorName = htmlspecialchars($actor->getFullName(), ENT_QUOTES, 'UTF-8');
        $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        foreach ($watcherUserIds as $watcherUserId) {
            if ($watcherUserId === (int) $actor->getId()) {
                continue;
            }

            $watcher = $this->entityManager->getRepository(User::class)->find($watcherUserId);
            if (!$watcher instanceof User || '' === trim($watcher->getEmail())) {
                continue;
            }

            $recipientName = $watcher->getFullName();
            $body = $this->translate('Dear user').' '.htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8').',<br /><br />'
                .$eventBody.' <strong>'.htmlspecialchars($contextTitle, ENT_QUOTES, 'UTF-8').'</strong><br /><br />'
                .$this->translate('edited by').': '.$actorName.' ('.$date.')<br /><br />'
                .'<span style="font-size:70%;">'
                .$this->translate('This notification has been made in accordance with their desire to monitor changes in the Wiki. This option means you have activated the button')
                .': <strong>'.$this->translate('Notify me of changes').'</strong></span>';

            try {
                $this->mailHelper->send(
                    $recipientName,
                    $watcher->getEmail(),
                    $subject,
                    $body,
                );
            } catch (Throwable $throwable) {
                $this->logger->warning('Wiki notification delivery failed.', [
                    'recipient_id' => $watcherUserId,
                    'course_id' => $course->getId(),
                    'session_id' => $session?->getId(),
                    'group_id' => $group?->getIid(),
                    'exception' => $throwable,
                ]);
            }
        }
    }

    private function translate(string $message): string
    {
        if (!\function_exists('get_lang')) {
            return $message;
        }

        return (string) \get_lang($message);
    }
}
