<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\UserBundle\Repository\UserRepository;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController.
 *
 * @Route("/account")
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AccountController extends BaseController
{
    /**
     * @Route("/edit", methods={"GET"}, name="chamilo_core_account_edit")
     *
     * @param string $username
     */
    public function editAction(UserRepository $userRepository)
    {
        $user = $this->getUser();

        return $this->render('@ChamiloCore/Account/edit.html.twig', ['user' => $user]);
    }
}
