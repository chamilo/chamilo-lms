<?php

namespace ChamiloLMS\Controller;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

class LearnpathController {

    /**
     * Index
     *
     * @param \Silex\Application $app
     * @param int $lpId
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Application $app, $lpId)
    {
        $request = $app['request'];
        $courseCode = api_get_course_id();
        $lp = new \learnpath($courseCode, $lpId, api_get_user_id());

        $url = $app['url_generator']->generate('subscribe_users', array('lpId' => $lpId));

        $breadcrumb = array(
            array('url' => api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?action=list', 'name' => get_lang('LearningPaths')),
            array('url' => api_get_path(WEB_CODE_PATH)."newscorm/lp_controller.php?action=build&lp_id=".$lp->get_id(), 'name' => $lp->get_name()),
            array('url' => '#', 'name' => get_lang('SubscribeUsers'))
        );

        $app['breadcrumb'] = $breadcrumb;

        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();

        if (empty($courseId)) {
            $app->abort(403, 'course_not_available');
        }

        $course = $app['orm.em']->getRepository('Entity\EntityCourse')->find($courseId);

        $subscribedUsers = $app['orm.em']->getRepository('Entity\EntityCourse')->getSubscribedStudents($course);

        $subscribedUsers = $subscribedUsers->getQuery();
        $subscribedUsers =  $subscribedUsers->execute();

        $choices = array();
        foreach ($subscribedUsers as $user) {
            $choices[$user->getUserId()] = $user->getCompleteName();
        }

        $subscribedUsersInLp = $app['orm.em']->getRepository('Entity\EntityCItemProperty')->getUsersSubscribedToItem('learnpath', $lpId, $course);

        $selectedChoices = array();
        foreach ($subscribedUsersInLp as $itemProperty) {
            $userId = $itemProperty->getToUserId();
            $user = $app['orm.em']->getRepository('Entity\EntityUser')->find($userId);
            //$selectedChoices[$user->getUserId()] = $user->getCompleteName();
            $selectedChoices[] = $user->getUserId();
            if (isset($choices[$user->getUserId()])) {
                //unset($choices[$user->getUserId()]);
            }
        }

        $form = new \FormValidator('lp_edit', 'post', $url);
        $form->addElement('header', get_lang('SubscribeUsersToLp'));
        $group_tutors_element = $form->addElement('advmultiselect', 'users', get_lang('Users'), $choices);

        $group_tutors_element->setButtonAttributes('add');
        $group_tutors_element->setButtonAttributes('remove');

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

        if (!empty($selectedChoices)) {
            $defaults['users'] = $selectedChoices;
            $form->setDefaults($defaults);
        }

        if ($request->getMethod() == 'POST') {
            //$form->bind($request);
            //$data = $form->getData();
            //var_dump($request->request);exit;
            $users = $request->get('users');
            //$destination = isset($data['destination']) ? $data['destination'] : array();
            $app['orm.em']->getRepository('Entity\EntityCItemProperty')->SubscribedUsersToItem('learnpath', $course, $sessionId, $lpId, $users);
            return $app->redirect($url);
        } else {
            $app['template']->assign('form', $form->toHtml());
        }

        $response = $app['template']->render_template('learnpath/subscribe_users.tpl');
        return new Response($response, 200, array());
    }
}