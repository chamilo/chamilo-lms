<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CourseBundle\Entity\CLp;

/**
 * Class LearningPath.
 */
class LearningPath extends BaseActivity
{
    private CLp $lp;

    public function __construct(CLp $lp)
    {
        $this->lp = $lp;
    }

    public function generate(): array
    {
        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'lp/lp_controller.php',
            [
                'action' => 'view',
                'lp_id' => $this->lp->getIid(),
                'isStudentView' => 'true',
            ]
        );

        return $this->buildActivity(
            $iri,
            (string) $this->lp->getTitle(),
            null,
            'http://adlnet.gov/expapi/activities/lesson'
        );
    }
}
