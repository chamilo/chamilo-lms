<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Controller;

use Chamilo\CourseBundle\Manager\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sylius\Bundle\SettingsBundle\Form\Factory\SettingsFormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Settings controller.
 *
 * @author Paweł Jędrzejewski <pawel@sylius.org>
 */
class SettingsController extends AbstractController
{
    /**
     * Edit configuration with given namespace.
     *
     * @param Request $request
     * @param string  $namespace
     * @ParamConverter("course", class="ChamiloCoreBundle:Course", options={"repository_method" = "findOneByCode"})
     *
     * @return Response
     */
    public function updateAction(Request $request, $namespace, $course)
    {
        $manager = $this->getSettingsManager();

        $schemaAlias = $manager->convertNameSpaceToService($namespace);
        $settings = $manager->load($namespace);

        $form = $this
            ->getSettingsFormFactory()
            ->create($schemaAlias);

        $form->setData($settings);

        if ($form->handleRequest($request)->isValid()) {
            $messageType = 'success';
            try {
                $manager->setCourse($course);
                $manager->saveSettings($namespace, $form->getData());
                $message = $this->getTranslator()->trans(
                    'sylius.settings.update',
                    [],
                    'flashes'
                );
            } catch (ValidatorException $exception) {
                $message = $this->getTranslator()->trans(
                    $exception->getMessage(),
                    [],
                    'validators'
                );
                $messageType = 'error';
            }
            $request->getSession()->getBag('flashes')->add(
                $messageType,
                $message
            );

            if ($request->headers->has('referer')) {
                return $this->redirect($request->headers->get('referer'));
            }
        }
        $schemas = $manager->getSchemas();

        return $this->render(
            $request->attributes->get(
                'template',
                'ChamiloCourseBundle:Settings:update.html.twig'
            ),
            [
                'course' => $course,
                'schemas' => $schemas,
                'settings' => $settings,
                'form' => $form->createView(),
                'keyword' => '',
                'search_form' => '',
            ]
        );
    }

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

    /**
     * Get translator.
     *
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
