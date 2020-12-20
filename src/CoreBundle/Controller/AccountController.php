<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Form\ProfileType;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AccountController.
 *
 * @Route("/account")
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AccountController extends BaseController
{
    use ControllerTrait;

    /**
     * @Route("/home", methods={"GET"}, name="chamilo_core_account_home")
     */
    public function homeAction()
    {
        $user = $this->getUser();

        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        return $this->render('@ChamiloCore/Account/home.html.twig', ['user' => $user]);
    }

    /**
     * @Route("/edit", methods={"GET", "POST"}, name="chamilo_core_account_edit")
     */
    public function editAction(Request $request, UserRepository $userRepository, IllustrationRepository $illustrationRepo)
    {
        $user = $this->getUser();

        if (!is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        $form = $this->createForm(ProfileType::class, $user);
        $form->setData($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $illustration = $form['illustration']->getData();
            if ($illustration) {
                $illustrationRepo->addIllustrationToUser($this->getUser(), $illustration);
            }
            $userRepository->updateUser($user);
            $this->addFlash('success', $this->trans('Updated'));
            $url = $this->generateUrl('chamilo_core_account_home', ['username' => $user->getUsername()]);

            return new RedirectResponse($url);
        }

        return $this->render('@ChamiloCore/Account/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }
}
