<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\SettingsBundle\Manager\SettingsManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sylius\Bundle\SettingsBundle\Controller\SettingsController as SyliusSettingsController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * Class SettingsController.
 *
 * @package Chamilo\SettingsBundle\Controller
 */
class SettingsController extends SyliusSettingsController
{
    /**
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/settings", name="admin_settings")
     *
     * @return Response
     */
    public function indexAction()
    {
        $manager = $this->getSettingsManager();
        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloTheme/Admin/Settings/index.html.twig',
            [
                'schemas' => $schemas,
            ]
        );
    }

    /**
     * Edit configuration with given namespace.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/settings/search_settings", name="chamilo_platform_settings_search")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchSettingAction(Request $request)
    {
        $manager = $this->getSettingsManager();
        $formList = [];
        $keyword = $request->get('keyword');

        $searchForm = $this->getSearchForm();
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $values = $searchForm->getData();
            $keyword = $values['keyword'];
        }

        if (empty($keyword)) {
            throw $this->createNotFoundException();
        }

        $settingsFromKeyword = $manager->getParametersFromKeywordOrderedByCategory($keyword);

        $settings = [];
        if (!empty($settingsFromKeyword)) {
            foreach ($settingsFromKeyword as $category => $parameterList) {
                $list = [];
                foreach ($parameterList as $parameter) {
                    $list[] = $parameter->getVariable();
                }
                $settings = $manager->load($category, null);
                $schemaAlias = $manager->convertNameSpaceToService($category);
                $form = $this->getSettingsFormFactory()->create($schemaAlias);

                foreach ($settings->getParameters() as $name => $value) {
                    if (!in_array($name, $list)) {
                        $form->remove($name);
                        $settings->remove($name);
                    }
                }
                $form->setData($settings);
                $formList[$category] = $form->createView();
            }
        }

        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloTheme/Admin/Settings/search.html.twig',
            [
                'keyword' => $keyword,
                'schemas' => $schemas,
                'settings' => $settings,
                'form_list' => $formList,
                'keyword' => $keyword,
                'search_form' => $searchForm->createView(),
            ]
        );
    }

    /**
     * Edit configuration with given namespace.
     *
     * @Security("has_role('ROLE_ADMIN')")
     *
     * @Route("/settings/{namespace}", name="chamilo_platform_settings")
     *
     * @param Request $request
     * @param string  $namespace
     *
     * @return Response
     */
    public function updateSettingAction(Request $request, $namespace)
    {
        $manager = $this->getSettingsManager();
        // @todo improve get the current url entity
        $urlId = $request->getSession()->get('access_url_id');
        $url = $this->getDoctrine()->getRepository('ChamiloCoreBundle:AccessUrl')->find($urlId);
        $manager->setUrl($url);
        $schemaAlias = $manager->convertNameSpaceToService($namespace);
        $searchForm = $this->getSearchForm();

        $keyword = '';
        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $values = $searchForm->getData();
            $keyword = $values['keyword'];
            $settingsFromKeyword = $manager->getParametersFromKeyword(
                $schemaAlias,
                $keyword
            );
        }

        $keywordFromGet = $request->query->get('keyword');
        if ($keywordFromGet) {
            $keyword = $keywordFromGet;
            $searchForm->setData(['keyword' => $keyword]);
            $settingsFromKeyword = $manager->getParametersFromKeyword(
                $schemaAlias,
                $keywordFromGet
            );
        }

        $settings = $manager->load($namespace);
        $form = $this->getSettingsFormFactory()->create($schemaAlias);

        if (!empty($keyword)) {
            $params = $settings->getParameters();
            foreach ($params as $name => $value) {
                if (!in_array($name, array_keys($settingsFromKeyword))) {
                    $form->remove($name);
                }
            }
        }

        $form->setData($settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageType = 'success';
            try {
                $manager->save($form->getData());
                $message = $this->getTranslator()->trans('sylius.settings.update', [], 'flashes');
            } catch (ValidatorException $exception) {
                $message = $this->getTranslator()->trans($exception->getMessage(), [], 'validators');
                $messageType = 'error';
            }

            $this->addFlash($messageType, $message);
            if (!empty($keywordFromGet)) {
                return $this->redirect($request->headers->get('referer'));
            }

            /*if ($request->headers->has('referer')) {
                return $this->redirect($request->headers->get('referer'));
            }*/
        }
        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloTheme/Admin/Settings/default.html.twig',
            [
                'schemas' => $schemas,
                'settings' => $settings,
                'form' => $form->createView(),
                'keyword' => $keyword,
                'search_form' => $searchForm->createView(),
            ]
        );
    }

    /**
     * Sync settings from classes with the database.
     *
     * @param Request $request
     */
    public function syncSettings(Request $request)
    {
        $manager = $this->getSettingsManager();
        // @todo improve get the current url entity
        $urlId = $request->getSession()->get('access_url_id');
        $url = $this->getDoctrine()->getRepository('ChamiloCoreBundle:AccessUrl')->find($urlId);
        $manager->setUrl($url);
        $manager->installSchemas($url);
    }

    /**
     * @return SettingsManager
     */
    protected function getSettingsManager()
    {
        return $this->get('chamilo.settings.manager');
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getSearchForm()
    {
        $builder = $this->container->get('form.factory')->createNamedBuilder('search');
        $builder->add('keyword', TextType::class);
        $builder->add('search', SubmitType::class);
        $searchForm = $builder->getForm();

        return $searchForm;
    }
}
