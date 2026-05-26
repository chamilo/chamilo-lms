<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CourseBundle\Entity\CLpItem;

/**
 * Class LearningPathItem.
 */
class LearningPathItem extends BaseActivity
{
    private CLpItem $lpItem;

    public function __construct(CLpItem $lpItem)
    {
        $this->lpItem = $lpItem;
    }

    public function generate(): array
    {
        $lpId = method_exists($this->lpItem, 'getLpId')
            ? $this->lpItem->getLpId()
            : (method_exists($this->lpItem, 'getLp') && $this->lpItem->getLp()
                ? $this->lpItem->getLp()->getIid()
                : null);

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'lp/lp_controller.php',
            [
                'action' => 'view',
                'lp_id' => $lpId,
                'isStudentView' => 'true',
                'lp_item' => $this->lpItem->getIid(),
            ]
        );

        return $this->buildActivity(
            $iri,
            (string) $this->lpItem->getTitle(),
            null,
            'http://id.tincanapi.com/activitytype/resource'
        );
    }
}
