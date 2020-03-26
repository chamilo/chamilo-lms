<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Chamilo\CoreBundle\Traits\CourseControllerTrait;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class ToolBaseController extends BaseController implements CourseControllerInterface
{
    use CourseControllerTrait;
    use ControllerTrait;
}
