<?php

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LearnpathController
 * @package ChamiloLMS\Controller
 */
class LearnpathController
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
        $courseCode = api_get_course_id();
        $lp         = new \learnpath($courseCode, $lpId, api_get_user_id());

        $url = $app['url_generator']->generate('subscribe_users', array('lpId' => $lpId));

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

        $sessionId = api_get_session_id();

        $session = null;
        if (!empty($sessionId)) {
            $session = $app['orm.em']->getRepository('Entity\EntitySession')->find($sessionId);
        }
        $courseId = api_get_course_int_id();

        if (empty($courseId)) {
            $app->abort(403, 'course_not_available');
        }

        $course = $app['orm.em']->getRepository('Entity\EntityCourse')->find($courseId);

        $subscribedUsers = $app['orm.em']->getRepository('Entity\EntityCourse')->getSubscribedStudents($course);
        $subscribedUsers = $subscribedUsers->getQuery();
        $subscribedUsers = $subscribedUsers->execute();

        //Getting all users
        $choices = array();
        foreach ($subscribedUsers as $user) {
            $choices[$user->getUserId()] = $user->getCompleteNameWithClasses();
        }

        $subscribedUsersInLp = $app['orm.em']->getRepository('Entity\EntityCItemProperty')->getUsersSubscribedToItem(
            'learnpath',
            $lpId,
            $course,
            $session
        );

        //Getting users subscribed to the LP
        $selectedChoices = array();
        foreach ($subscribedUsersInLp as $itemProperty) {
            $selectedChoices[] = $itemProperty->getToUserId();
        }

        $form = new \FormValidator('lp_edit', 'post', $url);
        $form->addElement('header', get_lang('SubscribeUsersToLp'));

        $userMultiSelect = $form->addElement('advmultiselect', 'users', get_lang('Users'), $choices);
        $userMultiSelect->setButtonAttributes('add');
        $userMultiSelect->setButtonAttributes('remove');

        //Group list
        $groupList = \CourseManager::get_group_list_of_course(api_get_course_id(), api_get_session_id(), 1);
        $groupChoices = array();
        if (!empty($groupList)) {
            foreach ($groupList as $group) {
                $groupChoices[$group['id']] = $group['name'];
            }
        }

        //Subscribed groups to a LP
        $subscribedGroupsInLp = $app['orm.em']->getRepository('Entity\EntityCItemProperty')->getGroupsSubscribedToItem(
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
                //'class' => 'Entity\EntityCourse',
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
                //'class' => 'Entity\EntityCourse',
                //'property' => 'complete_name',
                //'query_builder' => function(\Entity\Repository\CourseRepository $repo) use ($course) {
                  //  return $repo->getSubscribedStudents($course);
                //},
                'choices' => $selectedChoices
            ))
            ->getForm();
        */
        $defaults = array();

        if (!empty($selectedChoices)) {
            $defaults['users'] = $selectedChoices;
        }
        if (!empty($selectedGroupChoices)) {
            $defaults['groups'] = $selectedGroupChoices;
        }
        $form->setDefaults($defaults);

        if ($request->getMethod() == 'POST') {
            $users = $request->get('users');
            $app['orm.em']->getRepository('Entity\EntityCItemProperty')->SubscribedUsersToItem(
                'learnpath',
                $course,
                $session,
                $lpId,
                $users
            );

            $groups = $request->get('groups');
            $app['orm.em']->getRepository('Entity\EntityCItemProperty')->SubscribedGroupsToItem(
                'learnpath',
                $course,
                $session,
                $lpId,
                $groups
            );

            return $app->redirect($url);
        } else {
            $app['template']->assign('form', $form->toHtml());
        }

        $response = $app['template']->render_template('learnpath/subscribe_users.tpl');

        return new Response($response, 200, array());
    }
}