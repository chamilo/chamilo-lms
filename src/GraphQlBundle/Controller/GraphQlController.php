<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Controller;

use Chamilo\UserBundle\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class GraphQlController.
 *
 * @package Chamilo\GraphQlBundle\Controller
 */
class GraphQlController extends AbstractController
{
    /**
     * @Route("/learnpath/view", name="chamilo_graphql_learnpath")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     *
     * @todo Use symfony router to generate url
     */
    public function viewLearnpathAction(Request $request): RedirectResponse
    {
        $token = $request->query->get('token');
        $secret = $this->getParameter('secret');

        try {
            $jwt = JWT::decode($token, $secret, ['HS384']);
        } catch (\Exception $exception) {
            throw $this->createAccessDeniedException($exception->getMessage());
        }

        /** @var User $user */
        $user = $this->get('fos_user.user_manager')->find($jwt->data->user);

        if (!$user) {
            throw $this->createAccessDeniedException('Access denied.');
        }

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        $this->container->get('session')->set('_security_main', serialize($token));

        $webCodePath = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?';
        $urlParams = http_build_query([
            'cidReq' => $jwt->data->course->code,
            'id_session' => (int) $jwt->data->session,
            'gidReq' => 0,
            'gradebook' => 0,
            'origin' => '',
            'action' => 'view',
            'lp_id' => (int) $jwt->data->lp,
            'isStudentView' => 'true',
        ]);

        return new RedirectResponse($webCodePath.$urlParams);
    }
}
