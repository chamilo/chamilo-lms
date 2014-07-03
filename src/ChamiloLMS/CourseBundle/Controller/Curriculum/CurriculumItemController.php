<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CourseBundle\Controller\Curriculum;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\CoreBundle\Entity\CurriculumItem;
use ChamiloLMS\CoreBundle\Entity\CurriculumCategory;
use ChamiloLMS\CoreBundle\Form\CurriculumItemType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumItemController
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 * @Route("/curriculum_item")
 */
class CurriculumItemController
{
    public function getControllerAlias()
    {
        return 'curriculum_item.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'tool/curriculum/item/';
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\CurriculumItem';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\CurriculumItemType';
    }

    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('List')
            )
        );
        $this->setBreadcrumb($breadcrumbs);
        return parent::listingAction();
    }

      /**
    * @Route("/add-from-category/{id}")
    * @Method({"GET, POST"})
    */
    public function addFromCategoryAction($id)
    {
         $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('AddItems')
            )
        );
        $this->setBreadcrumb($breadcrumbs);
        $request = $this->getRequest();
        $formType = $this->getFormType();

        $entity = new CurriculumItem();

        $category = $this->get('orm.em')->getRepository('ChamiloLMS\CoreBundle\Entity\CurriculumCategory')->find($id);
        $entity->setCategory($category);

        $form = $this->get('form.factory')->create($formType, $entity);

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                /** @var CurriculumCategory $item */
                $item = $form->getData();
                $em = $this->getManager();
                $em->persist($item);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', "Added");
                $url = $this->createUrl('list_link');
                return $this->redirect($url);
            }
        }

        $template = $this->get('template');
        $template->assign('links', $this->generateLinks());
        $template->assign('form', $form->createView());
        $template->assign('parent_id', $id);
        $response = $template->render_template($this->getTemplatePath().'add_from_category.tpl');

        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
         $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    'route' => 'curriculum_user.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('Categories'),
                'url' => array(
                    'route' => 'curriculum_category.controller:indexAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )

            ),
            array(
                'name' => get_lang('Edit')
            )
        );
        $this->setBreadcrumb($breadcrumbs);
        return parent::editAction($id);
    }




    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['add_from_category'] = 'curriculum_item.controller:addFromCategoryAction';
        return $routes ;
    }
}
