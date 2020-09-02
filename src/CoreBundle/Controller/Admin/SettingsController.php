<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/admin")
 */
class SettingsController extends BaseController
{
    use ControllerTrait;

    /**
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/settings", name="admin_settings")
     */
    public function indexAction(): Response
    {
        $manager = $this->getSettingsManager();
        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloCore/Admin/Settings/index.html.twig',
            [
                'schemas' => $schemas,
            ]
        );
    }

    /**
     * Edit configuration with given namespace.
     *
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/settings/search_settings", name="chamilo_platform_settings_search")
     */
    public function searchSettingAction(Request $request): Response
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
            '@ChamiloCore/Admin/Settings/search.html.twig',
            [
                'keyword' => $keyword,
                'schemas' => $schemas,
                'settings' => $settings,
                'form_list' => $formList,
                'search_form' => $searchForm->createView(),
            ]
        );
    }

    /**
     * Edit configuration with given namespace.
     *
     * @IsGranted("ROLE_ADMIN")
     *
     * @Route("/settings/{namespace}", name="chamilo_platform_settings")
     *
     * @param string $namespace
     */
    public function updateSettingAction(Request $request, $namespace): Response
    {
        $manager = $this->getSettingsManager();
        // @todo improve get the current url entity
        $urlId = $request->getSession()->get('access_url_id');
        $url = $this->getDoctrine()->getRepository(AccessUrl::class)->find($urlId);
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
                $message = $this->trans('Settings have been successfully updated');
            } catch (ValidatorException $exception) {
                $message = $this->trans($exception->getMessage(), [], 'validators');
                $messageType = 'error';
            }

            $this->addFlash($messageType, $message);
            if (!empty($keywordFromGet)) {
                return $this->redirect($request->headers->get('referer'));
            }
        }
        $schemas = $manager->getSchemas();

        return $this->render(
            '@ChamiloCore/Admin/Settings/default.html.twig',
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
     */
    public function syncSettings(Request $request)
    {
        $manager = $this->getSettingsManager();
        // @todo improve get the current url entity
        $urlId = $request->getSession()->get('access_url_id');
        $url = $this->getDoctrine()->getRepository(AccessUrl::class)->find($urlId);
        $manager->setUrl($url);
        $manager->installSchemas($url);
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getSearchForm()
    {
        $builder = $this->container->get('form.factory')->createNamedBuilder('search');
        $builder->add('keyword', TextType::class);
        $builder->add('search', SubmitType::class);

        return $builder->getForm();
    }
}
