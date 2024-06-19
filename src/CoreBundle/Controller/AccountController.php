<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\ChangePasswordType;
use Chamilo\CoreBundle\Form\ProfileType;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\ServiceHelper\UserHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/account')]
class AccountController extends BaseController
{
    use ControllerTrait;

    public function __construct(
        private readonly UserHelper $userHelper,
    ) {}

    #[Route('/edit', name: 'chamilo_core_account_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserRepository $userRepository, IllustrationRepository $illustrationRepo, SettingsManager $settingsManager): Response
    {
        $user = $this->userHelper->getCurrent();

        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        /** @var User $user */
        $form = $this->createForm(ProfileType::class, $user);
        $form->setData($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $illustration = $form['illustration']->getData();
            if ($illustration) {
                $illustrationRepo->deleteIllustration($user);
                $illustrationRepo->addIllustration($user, $user, $illustration);
            }

            $showTermsIfProfileCompleted = ('true' === $settingsManager->getSetting('show_terms_if_profile_completed'));
            $user->setProfileCompleted($showTermsIfProfileCompleted);

            $userRepository->updateUser($user);
            $this->addFlash('success', $this->trans('Updated'));
            $url = $this->generateUrl('chamilo_core_account_home');

            $request->getSession()->set('_locale_user', $user->getLocale());

            return new RedirectResponse($url);
        }

        return $this->render('@ChamiloCore/Account/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/change-password', name: 'chamilo_core_account_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserRepository $userRepository, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        $user = $this->getUser();

        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submittedToken = $request->request->get('_token');

            if (!$csrfTokenManager->isTokenValid(new CsrfToken('change_password', $submittedToken))) {
                $form->addError(new FormError('CSRF token is invalid. Please try again.'));
            } else {
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();

                if (!$userRepository->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError('Current password is incorrect.'));
                } elseif ($newPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(new FormError('Passwords do not match.'));
                } else {
                    $user->setPlainPassword($newPassword);
                    $userRepository->updateUser($user);
                    $this->addFlash('success', 'Password changed successfully.');
                    return $this->redirectToRoute('chamilo_core_account_home');
                }
            }
        }

        return $this->render('@ChamiloCore/Account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
