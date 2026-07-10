<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Announcement;

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class AnnouncementScheduleManager
{
    private const SEND_AT_DATE_VARIABLE = 'send_notification_at_a_specific_date';
    private const DATE_TO_SEND_VARIABLE = 'date_to_send_notification';
    private const SEND_TO_SESSIONS_VARIABLE = 'send_to_users_in_session';

    public function __construct(
        private SettingsManager $settingsManager,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function isAvailable(?Session $session): bool
    {
        return null === $session && $this->isSettingEnabled(
            $this->settingsManager->getSetting('announcement.course_announcement_scheduled_by_date', true),
        );
    }

    public function supportsSendToUsersInSessions(): bool
    {
        return $this->findCourseAnnouncementField(self::SEND_TO_SESSIONS_VARIABLE) instanceof ExtraField;
    }

    /**
     * @return array{scheduleByDate: bool, scheduleDate: string, sendToUsersInSessions: bool}
     */
    public function getValues(CAnnouncement $announcement): array
    {
        $announcementId = (int) ($announcement->getIid() ?? 0);
        if ($announcementId <= 0) {
            return [
                'scheduleByDate' => false,
                'scheduleDate' => '',
                'sendToUsersInSessions' => false,
            ];
        }

        return [
            'scheduleByDate' => $this->toBoolean(
                $this->getValue($announcementId, self::SEND_AT_DATE_VARIABLE),
            ),
            'scheduleDate' => trim((string) $this->getValue(
                $announcementId,
                self::DATE_TO_SEND_VARIABLE,
            )),
            'sendToUsersInSessions' => $this->toBoolean(
                $this->getValue($announcementId, self::SEND_TO_SESSIONS_VARIABLE),
            ),
        ];
    }

    public function save(
        CAnnouncement $announcement,
        bool $scheduleByDate,
        ?string $scheduleDate,
        bool $sendToUsersInSessions,
    ): void {
        $announcementId = (int) ($announcement->getIid() ?? 0);
        if ($announcementId <= 0) {
            throw new RuntimeException('The announcement must be persisted before its schedule is saved.');
        }

        $values = [
            self::SEND_AT_DATE_VARIABLE => $scheduleByDate ? '1' : '0',
            self::DATE_TO_SEND_VARIABLE => $scheduleByDate ? trim((string) $scheduleDate) : '',
            self::SEND_TO_SESSIONS_VARIABLE => $sendToUsersInSessions ? '1' : '0',
        ];

        foreach ($values as $variable => $value) {
            $field = $this->findCourseAnnouncementField($variable);
            if (!$field instanceof ExtraField) {
                // Old databases can miss the course-announcement definition for this optional flag.
                if (self::SEND_TO_SESSIONS_VARIABLE === $variable && '0' === $value) {
                    continue;
                }

                throw new RuntimeException('Missing course announcement extra field: '.$variable);
            }

            $storedValue = $this->extraFieldValuesRepository->findOneBy([
                'field' => $field,
                'itemId' => $announcementId,
            ]);

            if (!$storedValue instanceof ExtraFieldValues) {
                $storedValue = (new ExtraFieldValues())
                    ->setField($field)
                    ->setItemId($announcementId)
                ;
            }

            $storedValue->setFieldValue($value);
            $this->entityManager->persist($storedValue);
        }

        $this->entityManager->flush();
    }

    private function getValue(int $announcementId, string $variable): ?string
    {
        $field = $this->findCourseAnnouncementField($variable);
        if (!$field instanceof ExtraField) {
            return null;
        }

        $storedValue = $this->extraFieldValuesRepository->findOneBy([
            'field' => $field,
            'itemId' => $announcementId,
        ]);

        return $storedValue instanceof ExtraFieldValues ? $storedValue->getFieldValue() : null;
    }

    private function findCourseAnnouncementField(string $variable): ?ExtraField
    {
        return $this->extraFieldRepository->findByVariable(ExtraField::COURSE_ANNOUNCEMENT, $variable);
    }

    private function toBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isSettingEnabled(mixed $value): bool
    {
        return $this->toBoolean($value);
    }
}
