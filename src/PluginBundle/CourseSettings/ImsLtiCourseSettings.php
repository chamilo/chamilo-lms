<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\PluginBundle\CourseSettings;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\State\CourseSettings\AbstractCourseSettingsPlugin;

final class ImsLtiCourseSettings extends AbstractCourseSettingsPlugin
{
    protected function pluginName(): string
    {
        return 'ImsLti';
    }

    public function getIntegrationKey(): string
    {
        return 'lti';
    }

    public function getIntegration(Course $course, ?Session $session): array
    {
        $enabled = $this->pluginHelper->isPluginEnabled($this->pluginName());
        $parameters = ['cid' => (int) $course->getId()];
        if (null !== $session) {
            $parameters['sid'] = (int) $session->getId();
        }

        return [
            'enabled' => $enabled,
            'url' => $enabled ? '/plugin/ImsLti/configure.php?'.http_build_query($parameters) : '',
        ];
    }

    public function getSection(Course $course, ?Session $session, array $values): ?array
    {
        return $this->section('external_tools_lti', 'External tools (LTI)', 'link', [
            $this->field('lti_configuration_url', 'Configure external tools', 'external-link')
                + ['value' => $this->getIntegration($course, $session)['url']],
        ], collapsed: true);
    }
}
