<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Manager\SettingsManager;
use Chamilo\SettingsBundle\Manager\SettingsManager as ChamiloSettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Settings controller.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class SettingsController extends BaseController
{

    /**
     * Get settings manager.
     *
     * @return SettingsManager
     */
    protected function getSettingsManager()
    {
        return $this->get('chamilo_course.settings.manager');
    }

    /**
     * Get settings form factory.
     *
     * @return SettingsFormFactoryInterface
     */
    protected function getSettingsFormFactory()
    {
        return $this->get('chamilo_course.settings.form_factory');
    }
}
