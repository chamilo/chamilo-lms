<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;

/**
 * Course settings extensions are Symfony services, not legacy FormValidator plugins.
 * Implementations must be registered as services (see config/plugin.yaml for external plugins).
 */
interface CourseSettingsPluginInterface
{
    public function getIntegrationKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function getIntegration(Course $course, ?Session $session): array;

    /** @param array<string, mixed> $values
     * @return null|array<string, mixed>
     */
    public function getSection(Course $course, ?Session $session, array $values): ?array;

    /**
     * @return array<string, mixed>
     */
    public function getValues(Course $course, ?Session $session): array;

    /**
     * @param array<string, mixed> $values
     */
    public function saveValues(Course $course, ?Session $session, array $values): void;

    /**
     * @param array<string, mixed> $values
     */
    public function afterSave(Course $course, ?Session $session, array $values): void;

    /**
     * Whether a legacy c_course_setting field is writable while this plugin is enabled.
     */
    public function managesCourseSetting(string $variable): bool;
}
