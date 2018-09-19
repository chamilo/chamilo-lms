<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
     *
     * @Template("ChamiloCoreBundle:User:profile.html.twig")
     *
     * @return array
     */
    public function profileAction($username): array
    {
        $user = $this->container->get('fos_user.user_manager')->findUserByUsername($username);

        return ['user' => $user];
    }
}
