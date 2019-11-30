<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\IllustrationRepository;
use Chamilo\ThemeBundle\Model\UserInterface;
use Chamilo\UserBundle\Form\ProfileType;
use Chamilo\UserBundle\Repository\UserRepository;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController.
 *
 * @Route("/account")
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
class AccountController extends BaseController
{
    private $userRepository;
    private $formFactory;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/edit", methods={"GET", "POST"}, name="chamilo_core_account_edit")
     *
     * @param string $username
     */
    public function editAction(Request $request, UserManagerInterface $userManager, IllustrationRepository $illustrationRepository)
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
                $file = $illustrationRepository->addIllustration($resource, $this->getUser(), $illustration);
                $em = $illustrationRepository->getEntityManager();
                $em->persist($file);
                $em->flush();
            }

            $userManager->updateUser($user);

            $this->addFlash('success', $this->trans('Updated'));
            $url = $this->generateUrl('chamilo_core_user_profile', ['username' => $user->getUsername()]);
            $response = new RedirectResponse($url);

            return $response;
        }

        return $this->render('@ChamiloCore/Account/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }
}
