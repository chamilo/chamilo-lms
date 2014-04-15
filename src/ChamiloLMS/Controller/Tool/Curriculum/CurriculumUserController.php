<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Tool\Curriculum;

use ChamiloLMS\Controller\CommonController;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use ChamiloLMS\Entity\CurriculumItemRelUser;
use ChamiloLMS\Entity\CurriculumItem;
use ChamiloLMS\Entity\Repository\CurriculumItemRelUserRepository;
use ChamiloLMS\Form\CurriculumItemRelUserCollectionType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
    public function indexAction(Application $app)
    {
        // @todo Use filters like "after/before|finish" to manage user access
        api_block_anonymous_users();

        // Abort request because the user is not allowed here - @todo use filters
        if ($app['allowed'] == false) {
            return $app->abort(403, 'Not allowed');
        }

        $breadcrumbs = array(
            array(
                'name' => get_lang('Curriculum'),
                'url' => array(
                    /*'route' => 'exercise_question_pool_global',
                    'routeParameters' => array(
                        'cidReq' => api_get_course_id(),
                        'id_session' => api_get_session_id(),
                    )*/
                )
            )
        );

        $this->setBreadcrumb($breadcrumbs);

        $userId = $this->getUser()->getUserId();

        $qb = $this->getManager()
            ->createQueryBuilder()
            ->select('node, i, u')
            ->from('ChamiloLMS\Entity\CurriculumCategory', 'node')
            ->innerJoin('node.course', 'c')
            ->leftJoin('node.items', 'i')
            ->leftJoin('i.userItems', 'u', 'WITH', 'u.userId = :userId OR u.userId IS NULL')
            //->where('u.userId = :userId or u.userId IS NULL')
            //->orWhere('u.userId IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('node.root, node.lft, node.title', 'ASC');
        $this->setCourseParameters($qb, 'node');
        $query = $qb->getQuery();

        $categories = $query->getResult();

        $formList = array();
        /** @var \ChamiloLMS\Entity\CurriculumCategory $category */
        foreach ($categories as $category) {

            /** @var \ChamiloLMS\Entity\CurriculumItem $item */
            foreach ($category->getItems() as $item) {

                $formType = new CurriculumItemRelUserCollectionType($item->getId());

                $count = count($item->getUserItems());

                // If there are no items for the user, then create a new one!
                if ($count == 0) {
                    $userItem = new CurriculumItemRelUser();
                    $userItem->setItemId($item->getId());
                    $userItemList = array(
                        $userItem
                    );
                    $item->setUserItems($userItemList);
                }
                $form = $this->get('form.factory')->create($formType, $item);
                $formList[$item->getId()] = $form->createView();
            }
        }

        if (api_is_allowed_to_edit()) {
            $this->get('template')->assign('teacher_links', $categories);
        }

        $this->get('template')->assign('categories', $categories);
        $this->get('template')->assign('links', $this->generateLinks());
        $this->get('template')->assign('form_list', $formList);
        $this->get('template')->assign('isAllowed', api_is_allowed_to_edit(true, true, true));

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
        $form = $this->get('form.factory')->create($this->getFormType(), $this->getDefaultEntity());
        $token = $this->get('security')->getToken();
        $user = $token->getUser();

        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            // @todo move this in a repo!
            if ($form->isValid()) {
                /** @var CurriculumItem $item */
                $postedItem = $form->getData();

                /** @var CurriculumItemRelUser $curriculumItemRelUser  */
                $postedItemId = null;
                foreach ($postedItem->getUserItems() as $curriculumItemRelUser) {
                    $postedItemId = $curriculumItemRelUser->getItemId();
                    break;
                }

                if (empty($postedItemId)) {
                    return 0;
                }

                 // Get user items

                $query = $this->getManager()
                    ->createQueryBuilder()
                    ->select('node, i, u')
                    ->from('ChamiloLMS\Entity\CurriculumCategory', 'node')
                    ->innerJoin('node.items', 'i')
                    ->innerJoin('i.userItems', 'u')
                    ->orderBy('node.root, node.lft', 'ASC')
                    ->where('u.userId = :user_id AND i.id = :item_id')
                    ->setParameter('user_id', $user->getUserId())
                    ->setParameter('item_id', $postedItemId)
                    ->getQuery();

                $categories = $query->getResult();

                /** @var \ChamiloLMS\Entity\CurriculumCategory $category */
                $alreadyAdded = array();

                foreach ($categories as $category) {
                    foreach ($category->getItems() as $item) {
                        if ($item->getId() == $postedItemId) {
                            // Now we can do stuff
                            /** @var CurriculumItemRelUser $userItem */
                            foreach ($item->getUserItems() as $userItem) {
                                $alreadyAdded[md5($userItem->getDescription())] = $userItem;
                            }
                        }
                    }
                }

                // @todo check this
                $user = $this->get('orm.em')->getRepository('ChamiloLMS\Entity\User')->find($user->getUserId());

                $counter = 1;
                $parsed = array();

                /** @var CurriculumItem $newItem */
                $newItem = $this->getCurriculumItemRepository()->find($postedItemId);

                /** @var CurriculumItemRelUser $curriculumItemRelUser */
                foreach ($postedItem->getUserItems() as $curriculumItemRelUser) {
                    $curriculumItemRelUser->setUser($user);

                    // $newItem = $this->getCurriculumItemRepository()->find($curriculumItemRelUser->getItemId());
                    $curriculumItemRelUser->setItem($newItem);
                    $curriculumItemRelUser->setOrderId(strval($counter));
                    $description = $curriculumItemRelUser->getDescription();

                    // Need description
                    if (empty($description)) {
                        // error_log('skip');
                        continue;
                    }

                    // @todo improve this
                    if (!empty($alreadyAdded)) {
                        $hash = md5($curriculumItemRelUser->getDescription());
                        if (isset($alreadyAdded[$hash])) {
                            $parsed[] = $hash;
                            // $this->get('monolog')->addInfo($curriculumItemRelUser->getDescription());
                            // error_log("aaa ->".$curriculumItemRelUser->getDescription());
                            continue;
                        } else {
                            // No need to check because it's an update.
                            // Update
                            $this->createAction($curriculumItemRelUser);
                        }
                    } else {
                        // Insert
                        $this->checkAndCreateAction($curriculumItemRelUser);
                    }

                    $counter++;
                }

                if (!empty($alreadyAdded)) {
                    foreach ($alreadyAdded as $hash => $item) {
                        if (!in_array($hash, $parsed)) {
                            $this->removeEntity($item->getId());
                        }
                    }
                }

            }
        }
        $response = null;
        return new Response($response, 200, array());
    }

    /**
    *
    * @Route("{userId}/get-user-items")
    * @Method({"GET"})
    */
    public function getUserItemsAction($userId)
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
                'name' => get_lang('Results'),
                'url' => array(
                    'route' => 'curriculum_category.controller:resultsAction',
                    'routeParameters' => array(
                        'course' => $this->getCourse()->getCode()
                    )
                )
            ),
            array(
                'name' => get_lang('UserResults'),
            )
        );
        $this->setBreadcrumb($breadcrumbs);

        if (!api_is_allowed_to_edit()) {
            return $this->abort(403);
        }

        $qb = $this->getManager()
            ->createQueryBuilder()
            ->select('node, i, u')
            ->from('ChamiloLMS\Entity\CurriculumCategory', 'node')
            ->innerJoin('node.course', 'c')
            ->leftJoin('node.items', 'i')
            ->leftJoin('i.userItems', 'u', 'WITH', 'u.userId = :userId OR u.userId IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('node.root, node.lft, node.title', 'ASC');
        $this->setCourseParameters($qb, 'node');
        $query = $qb->getQuery();
        $categories = $query->getResult();
        foreach ($categories as $category) {
            /** @var \ChamiloLMS\Entity\CurriculumItem $item */
            $score = 0;
            foreach ($category->getItems() as $item) {

                $formType = new CurriculumItemRelUserCollectionType($item->getId());
                $form = $this->get('form.factory')->create($formType, $item, array('disabled' => true));
                $formList[$item->getId()] = $form->createView();

                foreach ($item->getUserItems() as $userItem) {
                    $score += $item->getScore();
                }
                $categoryScore[$item->getCategoryId()] = $score;
            }
        }

        $this->get('template')->assign('categories', $categories);
        $this->get('template')->assign('category_score', $categoryScore);
        $this->get('template')->assign('form_list', $formList);
        $this->get('template')->assign('isAllowed', api_is_allowed_to_edit(true, true, true));

        $response = $this->get('template')->render_template($this->getTemplatePath().'get_user_items.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @param CurriculumItemRelUser $object
     * @return JsonResponse
     * @throws \Exception
     */
    private function checkAndCreateAction($object)
    {
        if (false === $object) {
            throw new \Exception('Unable to create the entity');
        }
        /** @var CurriculumItemRelUserRepository $repo */
        $repo = $this->getRepository();
        if ($repo->isAllowToInsert($object->getItem(), $object->getUser())) {
            $this->createAction($object);
        }
        return false;
    }

    /**
    *
    * @Route("/{id}", requirements={"id" = "\d+"})
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
    * @Route("/{id}/edit", requirements={"id" = "\d+"})
    * @Method({"GET"})
    */
    public function editAction($id)
    {
        return parent::editAction($id);
    }

    /**
    *
    * @Route("/{id}/delete", requirements={"id" = "\d+"})
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
        return $routes;
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
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\CurriculumItemRelUser');
    }

    private function getCurriculumCategoryRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\CurriculumCategory');
    }

    private function getCurriculumItemRepository()
    {
        return $this->get('orm.em')->getRepository('ChamiloLMS\Entity\CurriculumItem');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new CurriculumItemRelUser();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new CurriculumItemRelUserCollectionType();
    }
}
