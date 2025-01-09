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
use Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/account')]
class AccountController extends BaseController
{
    use ControllerTrait;

    public function __construct(
        private readonly UserHelper $userHelper,
        private readonly TranslatorInterface $translator
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
            if ($form->has('illustration')) {
                $illustration = $form['illustration']->getData();
                if ($illustration) {
                    $illustrationRepo->deleteIllustration($user);
                    $illustrationRepo->addIllustration($user, $user, $illustration);
                }
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
            'form' => $form->createView(),
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
                $form->addError(new FormError($this->translator->trans('CSRF token is invalid. Please try again.')));
            } else {
                $currentPassword = $form->get('currentPassword')->getData();
                $newPassword = $form->get('newPassword')->getData();
                $confirmPassword = $form->get('confirmPassword')->getData();

                if (!$userRepository->isPasswordValid($user, $currentPassword)) {
                    $form->get('currentPassword')->addError(new FormError($this->translator->trans('Current password is incorrect.')));
                } elseif ($newPassword !== $confirmPassword) {
                    $form->get('confirmPassword')->addError(new FormError($this->translator->trans('Passwords do not match.')));
                } else {
                    $errors = $this->validatePassword($newPassword);
                    if (\count($errors) > 0) {
                        foreach ($errors as $error) {
                            $form->get('newPassword')->addError(new FormError($error));
                        }
                    } else {
                        $user->setPlainPassword($newPassword);
                        $userRepository->updateUser($user);
                        $this->addFlash('success', $this->translator->trans('Password changed successfully.'));

                        return $this->redirectToRoute('chamilo_core_account_home');
                    }
                }
            }
        }

        return $this->render('@ChamiloCore/Account/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Validate the password against the same requirements as the client-side validation.
     */
    private function validatePassword(string $password): array
    {
        $errors = [];
        $minRequirements = Security::getPasswordRequirements()['min'];

        if (\strlen($password) < $minRequirements['length']) {
            $errors[] = $this->translator->trans('Password must be at least %length% characters long.', ['%length%' => $minRequirements['length']]);
        }
        if ($minRequirements['lowercase'] > 0 && !preg_match('/[a-z]/', $password)) {
            $errors[] = $this->translator->trans('Password must contain at least %count% lowercase characters.', ['%count%' => $minRequirements['lowercase']]);
        }
        if ($minRequirements['uppercase'] > 0 && !preg_match('/[A-Z]/', $password)) {
            $errors[] = $this->translator->trans('Password must contain at least %count% uppercase characters.', ['%count%' => $minRequirements['uppercase']]);
        }
        if ($minRequirements['numeric'] > 0 && !preg_match('/[0-9]/', $password)) {
            $errors[] = $this->translator->trans('Password must contain at least %count% numerical (0-9) characters.', ['%count%' => $minRequirements['numeric']]);
        }
        if ($minRequirements['specials'] > 0 && !preg_match('/[\W]/', $password)) {
            $errors[] = $this->translator->trans('Password must contain at least %count% special characters.', ['%count%' => $minRequirements['specials']]);
        }

        return $errors;
    }
}
