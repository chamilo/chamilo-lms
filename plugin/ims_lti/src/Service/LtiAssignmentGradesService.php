<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

class LtiAssignmentGradesService extends LtiAdvantageService
{
    const AGS_NONE = 'none';
    const AGS_SIMPLE = 'simple';
    const AGS_FULL = 'full';

    const SCOPE_LINE_ITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    const SCOPE_LINE_ITEM_READ = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';

    /**
     * @return array
     */
    public function getAllowedScopes()
    {
        $scopes = [
            self::SCOPE_LINE_ITEM_READ,
        ];

        $toolServices = $this->tool->getAdvantageServices();

        if (self::AGS_FULL === $toolServices['ags']) {
            $scopes[] = self::SCOPE_LINE_ITEM;
        }

        return $scopes;
    }
}
