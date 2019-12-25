<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

class LtiAssignmentGradesService extends LtiAdvantageService
{
    const SCOPE_LINE_ITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    const SCOPE_LINE_ITEM_READ = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    const SCOPE_RESULT_READ = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
    const SCOPE_SCORE = 'https://purl.imsglobal.org/spec/lti-ags/scope/score';

    public function getAllowedScopes()
    {
        $scopes = [
            self::SCOPE_LINE_ITEM_READ,
            self::SCOPE_RESULT_READ,
            self::SCOPE_SCORE,
        ];

        $toolServices = $this->tool->getAdvantageServices();

        if (self::AGS_FULL === $toolServices['ags']) {
            $scopes[] = self::SCOPE_LINE_ITEM;
        }

        return $scopes;
    }
}
