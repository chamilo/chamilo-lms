<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Course as CourseActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Site as SiteActivity;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\Statement;
use Xabbuh\XApi\Model\StatementId;
use Xabbuh\XApi\Model\Uuid;
use XApiPlugin;

/**
 * Class BaseStatement.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Statement
 */
abstract class BaseStatement
{
    abstract public function generate(): Statement;

    protected function generateStatementId(string $type): StatementId
    {
        $uuid = Uuid::uuid5(
            XApiPlugin::create()->get(XApiPlugin::SETTING_UUID_NAMESPACE),
            uniqid($type)
        );

        return StatementId::fromUuid($uuid);
    }

    protected function generateContext(): Context
    {
        $platform = api_get_setting('Institution').' - '.api_get_setting('siteName');

        $groupingActivities = [];
        $groupingActivities[] = (new SiteActivity())->generate();

        if (api_get_course_id()) {
            $groupingActivities[] = (new CourseActivity())->generate();
        }

        return (new Context())
            ->withPlatform($platform)
            ->withLanguage(api_get_language_isocode())
            ->withContextActivities(
                new ContextActivities(null, $groupingActivities)
            );
    }
}
