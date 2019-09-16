<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController.
 *
 * @Route("/user")
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class UserController extends BaseController
{
    /**
     * @Route("/{username}", methods={"GET"})
     *
     * @param string $username
     */
    public function profileAction($username): array
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByUsername($username);

        return $this->render('@ChamiloCore/User/profile.html.twig', ['user' => $user]);
    }
}
