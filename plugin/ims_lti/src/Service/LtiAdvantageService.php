<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

/**
 * Class LtiAdvantageService.
 */
abstract class LtiAdvantageService
{
    const AGS_SIMPLE = 'simple';
    const AGS_FULL = 'full';

    /**
     * @var ImsLtiTool
     */
    protected $tool;

    /**
     * @param ImsLtiTool $tool
     *
     * @return LtiAdvantageService
     */
    public function setTool(ImsLtiTool $tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return array
     */
    abstract public function getAllowedScopes();
}
