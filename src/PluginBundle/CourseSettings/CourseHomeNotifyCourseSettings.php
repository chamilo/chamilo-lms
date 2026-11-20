<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsPluginStore;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class CourseHomeNotifyCourseSettings extends AbstractCourseSettingsPlugin
{
    public function __construct(
        PluginHelper $pluginHelper,
        CourseSettingsPluginStore $store,
        private readonly Connection $connection,
    ) {
        parent::__construct($pluginHelper, $store);
    }

    protected function pluginName(): string
    {
        return 'CourseHomeNotify';
    }

    public function getIntegrationKey(): string
    {
        return 'courseHomeNotify';
    }

    public function getIntegration(Course $course, ?Session $session): array
    {
        return ['enabled' => $this->pluginHelper->isPluginEnabled($this->pluginName())
            && $this->tableExists('course_home_notify_notification')];
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        return $this->section('course_home_notify', 'Notify in course home', 'bell', [
            $this->field('course_home_notify_enabled', 'Enable course home notification', 'checkbox'),
            $this->field('course_home_notify_content', 'Content', 'editor'),
            $this->field('course_home_notify_expiration_link', 'Expiration link', 'url'),
        ], collapsed: true);
    }

    public function getValues(Course $course, ?Session $session): array
    {
        $notification = $this->getCourseHomeNotification($course);

        return [
            'course_home_notify_enabled' => $notification['exists'],
            'course_home_notify_content' => $notification['content'],
            'course_home_notify_expiration_link' => $notification['expirationLink'],
        ];
    }

    public function saveValues(Course $course, ?Session $session, array $values): void
    {
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName())
            || !$this->tableExists('course_home_notify_notification')) {
            return;
        }

        $courseId = (int) $course->getId();
        $notification = $this->connection->fetchAssociative(
            'SELECT id FROM course_home_notify_notification WHERE c_id = :courseId LIMIT 1',
            ['courseId' => $courseId],
            ['courseId' => Types::INTEGER],
        );
        $enabled = (bool) ($values['course_home_notify_enabled'] ?? false);
        if (!$enabled) {
            if (false !== $notification) {
                $this->connection->delete(
                    'course_home_notify_notification',
                    ['id' => (int) $notification['id']],
                    ['id' => Types::INTEGER],
                );
            }

            return;
        }

        $payload = [
            'content' => (string) ($values['course_home_notify_content'] ?? ''),
            'expiration_link' => trim((string) ($values['course_home_notify_expiration_link'] ?? '')),
        ];
        $types = [
            'content' => Types::TEXT,
            'expiration_link' => Types::STRING,
        ];

        if (false === $notification) {
            $payload['hash'] = bin2hex(random_bytes(16));
            $payload['c_id'] = $courseId;
            $types['hash'] = Types::STRING;
            $types['c_id'] = Types::INTEGER;
            $this->connection->insert('course_home_notify_notification', $payload, $types);

            return;
        }

        $this->connection->update(
            'course_home_notify_notification',
            $payload,
            ['id' => (int) $notification['id']],
            $types,
            ['id' => Types::INTEGER],
        );
    }

    private function getCourseHomeNotification(Course $course): array
    {
        $empty = ['content' => '', 'expirationLink' => '', 'exists' => false];
        if (!$this->pluginHelper->isPluginEnabled($this->pluginName())
            || !$this->tableExists('course_home_notify_notification')) {
            return $empty;
        }

        $notification = $this->connection->fetchAssociative(
            'SELECT content, expiration_link FROM course_home_notify_notification WHERE c_id = :courseId LIMIT 1',
            ['courseId' => (int) $course->getId()],
            ['courseId' => Types::INTEGER],
        );
        if (false === $notification) {
            return $empty;
        }

        return [
            'content' => (string) ($notification['content'] ?? ''),
            'expirationLink' => (string) ($notification['expiration_link'] ?? ''),
            'exists' => true,
        ];
    }

    private function tableExists(string $name): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$name]);
    }
}
