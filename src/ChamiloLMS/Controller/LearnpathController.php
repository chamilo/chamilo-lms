<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LearnpathController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
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

        // Find session.
        $sessionId = api_get_session_id();
        $session = null;
        if (!empty($sessionId)) {
            $session = $app['orm.em']->getRepository('ChamiloLMS\Entity\Session')->find($sessionId);
        }

        // Find course.
        $course = $app['orm.em']->getRepository('ChamiloLMS\Entity\Course')->find($courseId);

        // Getting subscribe users to the course.
        $subscribedUsers = $app['orm.em']->getRepository('ChamiloLMS\Entity\Course')->getSubscribedStudents($course);
        $subscribedUsers = $subscribedUsers->getQuery();
        $subscribedUsers = $subscribedUsers->execute();

        // Getting all users in a nice format.
        $choices = array();
        foreach ($subscribedUsers as $user) {
            $choices[$user->getUserId()] = $user->getCompleteNameWithClasses();
        }

        // Getting subscribed users to a LP.
        $subscribedUsersInLp = $app['orm.em']->getRepository('ChamiloLMS\Entity\CItemProperty')->getUsersSubscribedToItem(
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
        $subscribedGroupsInLp = $app['orm.em']->getRepository('ChamiloLMS\Entity\CItemProperty')->getGroupsSubscribedToItem(
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
                //'query_builder' => function(ChamiloLMS\Entity\Repository\CourseRepository $repo) use ($course) {
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
                //'query_builder' => function(ChamiloLMS\Entity\Repository\CourseRepository $repo) use ($course) {
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
                $app['orm.em']->getRepository('ChamiloLMS\Entity\CItemProperty')->subscribeUsersToItem(
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
                $app['orm.em']->getRepository('ChamiloLMS\Entity\CItemProperty')->subscribeGroupsToItem(
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
}
