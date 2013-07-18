<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\Curriculum;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Entity;
use ChamiloLMS\Form\CurriculumItemRelUserCollectionType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class CurriculumUserController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CurriculumUserController extends CommonController
{
    /**
     *
     * @Route("/")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        // @todo put this in a function
        $repo = $this->getCurriculumCategoryRepository();

        $query = $this->getManager()
            ->createQueryBuilder()
            ->select('node, i')
            ->from('Entity\CurriculumCategory', 'node')
            ->innerJoin('node.items', 'i')
            ->leftJoin('i.userItems', 'u')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $categories = $query->getResult();

        $formList = array();
        /** @var \Entity\CurriculumCategory $category */

        foreach ($categories as $category) {
            /** @var \Entity\CurriculumItem $item */
            foreach ($category->getItems() as $item) {
                //$userItems = $item->getUserItems();
                //var_dump(get_class($userItems));
                $formType = new CurriculumItemRelUserCollectionType($item->getId());
                $form = $this->get('form.factory')->create($formType, $item);
                $formList[$item->getId()] = $form->createView();
            }
        }

        $this->get('template')->assign('categories', $query->getResult());
        $this->get('template')->assign('links', $this->generateLinks());
        $this->get('template')->assign('form_list', $formList);

        $response = $this->get('template')->render_template($this->getTemplatePath().'list.tpl');
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/save-user-item")
    * @Method({"POST"})
    */
    public function saveUserItemAction()
    {
        $request = $this->getRequest();
        $form = $this->get('form.factory')->create($this->getFormType());
        $token = $this->get('security')->getToken();

        if ($request->getMethod() == 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var Entity\CurriculumItem $item */
                $item = $form->getData();

                $user = $token->getUser();
                // @todo check this
                $user = $this->get('orm.em')->getRepository('Entity\User')->find($user->getUserId());

                $counter = 1;
                /** @var Entity\CurriculumItemRelUser $curriculumItemRelUser  */
                foreach ($item->getUserItems() as $curriculumItemRelUser) {
                    $curriculumItemRelUser->setUser($user);
                    //$item = new Entity\CurriculumItem();
                    $item = $this->getCurriculumItemRepository()->find($curriculumItemRelUser->getItemId());
                    $curriculumItemRelUser->setItem($item);
                    $curriculumItemRelUser->setOrderId(strval($counter));
                    $this->createAction($curriculumItemRelUser);
                    $counter++;
                }
            }
        }
        $response = null;
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
    * @Method({"GET"})
    */
    public function readAction($id)
    {
        return parent::readAction($id);
    }

    /**
    * @Route("/add")
    * @Method({"GET"})
    */
    public function addAction()
    {
        return parent::addAction();
    }

    /**
    *
    * @Route("/{id}/edit", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
        return parent::editAction($id);
    }

    /**
    *
    * @Route("/{id}/delete", requirements={"id" = "\d+"}, defaults={"foo" = "bar"})
    * @Method({"GET"})
    */
    public function deleteAction($id)
    {
        return parent::deleteAction($id);
    }

    protected function getControllerAlias()
    {
        return 'curriculum_user.controller';
    }

    protected function generateDefaultCrudRoutes()
    {
        $routes = parent::generateDefaultCrudRoutes();
        $routes['add_from_category'] = 'curriculum_item.controller:addFromCategoryAction';
        //$routes['add_items'] = 'curriculum_item.controller:addItemsAction';

        return $routes ;
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'tool/curriculum/user/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CurriculumItemRelUser');
    }

    private function getCurriculumCategoryRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CurriculumCategory');
    }

    private function getCurriculumItemRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\CurriculumItem');
    }


    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\CurriculumItemRelUser();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new CurriculumItemRelUserCollectionType();
    }
}
