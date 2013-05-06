<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\DoctrineCollectionAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class AdminController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AdminController
{
    /**
     * Index
     *
     * @param   \Silex\Application $app
     * @param   int $lpId
     *
     * @todo move calls in repositories
     *
     * @return Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app, $lpId)
    {
        $request    = $app['request'];

        $courseId = api_get_course_int_id();

        //@todo use the before filter to aborts this course calls
        if (empty($courseId)) {
            $app->abort(403, 'Course not available');
        }

        $courseCode = api_get_course_id();

        $lp = new \learnpath($courseCode, $lpId, api_get_user_id());

        $url = $app['url_generator']->generate('subscribe_users', array('lpId' => $lpId));

        //Setting breadcrumb @todo move this in the template lib
        $breadcrumb = array(
            array(
                'url'  => api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?action=list',
                'name' => get_lang('LearningPaths')
            ),
            array(
                'url'  => api_get_path(WEB_CODE_PATH)."newscorm/lp_controller.php?action=build&lp_id=".$lp->get_id(),
                'name' => $lp->get_name()
            ),
            array('url' => '#', 'name' => get_lang('SubscribeUsers'))
        );

        $app['breadcrumb'] = $breadcrumb;

        //Find session
        $sessionId = api_get_session_id();
        $session = null;
        if (!empty($sessionId)) {
            $session = $app['orm.em']->getRepository('Entity\Session')->find($sessionId);
        }

        //Find course
        $course = $app['orm.em']->getRepository('Entity\Course')->find($courseId);

        //Getting subscribe users to the course
        $subscribedUsers = $app['orm.em']->getRepository('Entity\Course')->getSubscribedStudents($course);
        $subscribedUsers = $subscribedUsers->getQuery();
        $subscribedUsers = $subscribedUsers->execute();

        //Getting all users in a nice format
        $choices = array();
        foreach ($subscribedUsers as $user) {
            $choices[$user->getUserId()] = $user->getCompleteNameWithClasses();
        }

        //Getting subscribed users to a LP
        $subscribedUsersInLp = $app['orm.em']->getRepository('Entity\CItemProperty')->getUsersSubscribedToItem(
            'learnpath',
            $lpId,
            $course,
            $session
        );
        $selectedChoices = array();
        foreach ($subscribedUsersInLp as $itemProperty) {
            $selectedChoices[] = $itemProperty->getToUserId();
        }

        //Building the form for Users
        $formUsers = new \FormValidator('lp_edit', 'post', $url);
        $formUsers->addElement('hidden', 'user_form', 1);
        $formUsers->addElement('header', get_lang('SubscribeUsersToLp'));

        $userMultiSelect = $formUsers->addElement('advmultiselect', 'users', get_lang('Users'), $choices);
        $userMultiSelect->setButtonAttributes('add');
        $userMultiSelect->setButtonAttributes('remove');

        $formUsers->addElement('style_submit_button', 'submit', get_lang('Save'), 'class="save"');

        $defaults = array();

        if (!empty($selectedChoices)) {
            $defaults['users'] = $selectedChoices;
        }

        $formUsers->setDefaults($defaults);

        //Building the form for Groups

        $form = new \FormValidator('lp_edit', 'post', $url);
        $form->addElement('header', get_lang('SubscribeGroupsToLp'));
        $form->addElement('hidden', 'group_form', 1);

        //Group list
        $groupList = \CourseManager::get_group_list_of_course(api_get_course_id(), api_get_session_id(), 1);
        $groupChoices = array();
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $groupChoices[$group['id']] = $group['name'];
            }
        }

        //Subscribed groups to a LP
        $subscribedGroupsInLp = $app['orm.em']->getRepository('Entity\CItemProperty')->getGroupsSubscribedToItem(
            'learnpath',
            $lpId,
            $course,
            $session
        );

        $selectedGroupChoices = array();
        foreach ($subscribedGroupsInLp as $itemProperty) {
            $selectedGroupChoices[] = $itemProperty->getToGroupId();
        }

        $groupMultiSelect = $form->addElement('advmultiselect', 'groups', get_lang('Groups'), $groupChoices);
        $groupMultiSelect->setButtonAttributes('add');
        $groupMultiSelect->setButtonAttributes('remove');

        // submit button
        $form->addElement('style_submit_button', 'submit', get_lang('Save'), 'class="save"');

        /*$form = $app['form.factory']->createBuilder('form')
             ->add('origin', 'choice', array(
                'label' => get_lang('Origin'),
                'multiple' => true,
                'required' => false,
                'expanded' => false,
                //'class' => 'Entity\Course',
                //'property' => 'complete_name',
                //'query_builder' => function(\Entity\Repository\CourseRepository $repo) use ($course) {
                    $repo =  $repo->getSubscribedStudents($course);
                    return $repo;
                },
                'choices' => $choices
            ))
            ->add('destination', 'choice', array(
                'label' => get_lang('Destination'),
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                //'class' => 'Entity\Course',
                //'property' => 'complete_name',
                //'query_builder' => function(\Entity\Repository\CourseRepository $repo) use ($course) {
                  //  return $repo->getSubscribedStudents($course);
                //},
                'choices' => $selectedChoices
            ))
            ->getForm();
        */
        $defaults = array();
        if (!empty($selectedGroupChoices)) {
            $defaults['groups'] = $selectedGroupChoices;
        }
        $form->setDefaults($defaults);

        if ($request->getMethod() == 'POST') {

            //Subscribing users
            $users = $request->get('users');
            $userForm = $request->get('user_form');
            if (!empty($userForm)) {
                $app['orm.em']->getRepository('Entity\CItemProperty')->subscribeUsersToItem(
                    'learnpath',
                    $course,
                    $session,
                    $lpId,
                    $users
                );
            }

            //Subscribing groups
            $groups = $request->get('groups');
            $groupForm = $request->get('group_form');

            if (!empty($groupForm)) {
                $app['orm.em']->getRepository('Entity\CItemProperty')->subscribeGroupsToItem(
                    'learnpath',
                    $course,
                    $session,
                    $lpId,
                    $groups
                );
            }

            return $app->redirect($url);
        } else {
            $app['template']->assign('formUsers', $formUsers->toHtml());
            $app['template']->assign('formGroups', $form->toHtml());
        }
        $response = $app['template']->render_template('learnpath/subscribe_users.tpl');

        return new Response($response, 200, array());
    }

    public function getCategoriesAction(Application $app, $id)
    {
        // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul class="nav nav-list">',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($app) {
                $url = $app['url_generator']->generate('admin_get_categories', array('id' => $row['iid']));
                return \Display::url($row['title'], $url);
            }
            //'representationField' => 'slug',
            //'html' => true
        );
        $cats = $repo->findOneByIid($id);
        $htmlTree = $repo->childrenHierarchy(
            $cats, /* starting from root nodes */
            true, /* false: load all children, true: only direct */
            $options
        );
        return $htmlTree;
    }

    /**
     * Gets the question list per category
     * @param Application $app
     * @param $categoryId
     * @return int
     */
    public function getQuestionsByCategoryAction(Application $app, $categoryId)
    {
         // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        /** @var \Entity\CQuizCategory $category*/
        $category = $repo->find($categoryId);
        $questions = $category->getQuestions();

        //$adapter = new DoctrineCollectionAdapter($questions);

        //$adapter    = new FixedAdapter($nbResults, array());
        /*$pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(10); // 10 by default
        $pagerfanta->setCurrentPage(1); // 1 by default
        */
        //$this->app['pagerfanta.view.router.name']   = 'userportal';
        /*$this->app['pagerfanta.view.router.params'] = array(
            'filter' => $filter,
            'type'   => 'courses',
            'page'   => $page
        );*/
        //$app['template']->assign('pagination', $pagerfanta);

        foreach ($questions as $question) {

        }
        $response = $app['template']->render_template('admin/questions.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @param Application $app
     */
    public function questionsAction(Application $app)
    {
        // Getting CQuizCategory repo.
        $repo = $app['orm.em']->getRepository('Entity\CQuizCategory');

        $options = array(
            'decorate' => true,
            'rootOpen' => '<ul class="nav nav-list">',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($row) use ($app) {
                $url = $app['url_generator']->generate('admin_get_categories', array('id' => $row['iid']));
                return \Display::url($row['title'], $url);
            }
            //'representationField' => 'slug',
            //'html' => true
        );

        // Getting all categories only first level lvl=1
        $query = $app['orm.em']
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId <> 0 AND node.lvl = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $tree = $repo->buildTree($query->getArrayResult(), $options);
        $app['template']->assign('category_tree', $tree);

        // Getting globals
        $query = $app['orm.em']
            ->createQueryBuilder()
            ->select('node')
            ->from('Entity\CQuizCategory', 'node')
            ->where('node.cId = 0')
            ->orderBy('node.root, node.lft', 'ASC')
            ->getQuery();

        $tree = $repo->buildTree($query->getArrayResult(), $options);
        $app['template']->assign('global_category_tree', $tree);

        $response = $app['template']->render_template('admin/question_categories.tpl');
        return new Response($response, 200, array());

    }
}