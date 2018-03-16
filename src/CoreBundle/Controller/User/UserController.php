<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\User;

use Chamilo\CoreBundle\Controller\BaseController;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController.
 *
 * @package Chamilo\CoreBundle\Controller
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UserController extends BaseController
{
    /**
     * @Route("/me")
     * @Method({"GET"})
     */
    public function indexAction(Request $request)
    {
        $userInfo = api_get_user_info($this->getUser()->getUserId());

        return $this->getTemplate()->render(
            $this->getTemplatePath().'me.tpl',
            ['user', $userInfo]
        );

        $response = $this->getTemplate()->renderTemplate(
            $this->getTemplatePath().'me.tpl'
        );

        return new Response($response, 200, ['user', $userInfo]);
    }

    /**
     * @Route("/{username}")
     * @Method({"GET"})
     * @Template("ChamiloCoreBundle:User:profile.html.twig")
     */
    public function profileAction($username)
    {
        $userId = \UserManager::get_user_id_from_username($username);
        $userInfo = api_get_user_info($userId);

        return [
            'user' => $userInfo,
            'form_send_message' => \MessageManager::generate_message_form(
                'send_message'
            ),
            'form_send_invitation' => \MessageManager::generate_invitation_form(
                'send_invitation'
            ),
        ];
    }

    /**
     * @Route("/me/my_courses", options={"expose"=true})
     * @Method({"GET"})
     */
    public function myCoursesAction()
    {
        $user = $this->getUser();
        $courses = $user->getCourses();

        $output = [];
        /** @var CourseRelUser $courseRelUser */
        foreach ($courses as $courseRelUser) {
            $course = $courseRelUser->getCourse();
            if ($course) {
                $output[] = [
                    'id' => $course->getId(),
                    'title' => $course->getTitle(),
                ];
            }
        }

        return $response = new JsonResponse(['items' => $output]);
    }

    /**
     * @Route("/online")
     * @Method({"GET"})
     */
    public function onlineAction($app)
    {
        $response = $app['template']->renderLayout('layout_1_col.tpl');

        return new Response($response, 200, []);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'user/';
    }
}
