<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Statement;

use Chamilo\CoreBundle\Entity\PortfolioAttachment;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Course as CourseActivity;
use Chamilo\PluginBundle\XApi\ToolExperience\Activity\Site as SiteActivity;
use Chamilo\UserBundle\Entity\User;
use Xabbuh\XApi\Model\Attachment;
use Xabbuh\XApi\Model\Context;
use Xabbuh\XApi\Model\ContextActivities;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\IRL;
use Xabbuh\XApi\Model\LanguageMap;
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

    protected function generateStatementId(string $type, string $value): StatementId
    {
        $uuid = Uuid::uuid5(
            XApiPlugin::create()->get(XApiPlugin::SETTING_UUID_NAMESPACE),
            "$type/$value"
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

    /**
     * @param array|PortfolioAttachment[] $portfolioAttachments
     *
     * @return array|Attachment[]
     */
    protected function generateAttachments(array $portfolioAttachments, User $user): array
    {
        if (empty($portfolioAttachments)) {
            return [];
        }

        $attachments = [];

        $userDirectory = \UserManager::getUserPathById($user->getId(), 'system');
        $attachmentsDirectory = $userDirectory.'portfolio_attachments/';

        $langIso = api_get_language_isocode();

        $cidreq = api_get_cidreq();
        $baseUrl = api_get_path(WEB_CODE_PATH).'portfolio/index.php?'.($cidreq ? $cidreq.'&' : '');

        foreach ($portfolioAttachments as $portfolioAttachment) {
            $attachmentFilename = $attachmentsDirectory.$portfolioAttachment->getPath();

            $display = LanguageMap::create(
                ['und' => $portfolioAttachment->getFilename()]
            );
            $description = null;

            if ($portfolioAttachment->getComment()) {
                $description = LanguageMap::create(
                    [$langIso => $portfolioAttachment->getComment()]
                );
            }

            $attachments[] = new Attachment(
                IRI::fromString('http://id.tincanapi.com/attachment/supporting_media'),
                mime_content_type($attachmentFilename),
                $portfolioAttachment->getSize(),
                hash_file('sha256', $attachmentFilename),
                $display,
                $description,
                IRL::fromString(
                    $baseUrl.http_build_query(['action' => 'download', 'file' => $portfolioAttachment->getPath()])
                )
            );
        }

        return $attachments;
    }
}
