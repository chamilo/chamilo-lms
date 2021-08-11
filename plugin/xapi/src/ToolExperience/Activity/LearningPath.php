<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CourseBundle\Entity\CLp;
use Xabbuh\XApi\Model\Activity;
use Xabbuh\XApi\Model\Definition;
use Xabbuh\XApi\Model\IRI;
use Xabbuh\XApi\Model\LanguageMap;

/**
 * Class LearningPath.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Activity
 */
class LearningPath extends BaseActivity
{
    /**
     * @var \Chamilo\CourseBundle\Entity\CLp
     */
    private $lp;

    public function __construct(CLp $lp)
    {
        $this->lp = $lp;
    }

    public function generate(): Activity
    {
        $lanIso = api_get_language_isocode();

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'lp/lp_controller.php',
            [
                'action' => 'view',
                'lp_id' => $this->lp->getId(),
                'isStudentView' => 'true',
            ]
        );

        return new Activity(
            IRI::fromString($iri),
            new Definition(
                LanguageMap::create([$lanIso => $this->lp->getName()]),
                null,
                IRI::fromString('http://adlnet.gov/expapi/activities/lesson')
            )
        );
    }
}
