<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Form\ChangePasswordType;
use Chamilo\CoreBundle\Form\ProfileType;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\Traits\ControllerTrait;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;
use Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
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
    public function edit(
        Request $request,
        UserRepository $userRepository,
        IllustrationRepository $illustrationRepo,
        SettingsManager $settingsManager
    ): Response {
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

            if ($form->has('password')) {
                $password = $form['password']->getData();
                if ($password) {
                    $user->setPlainPassword($password);
                    $user->setPasswordUpdateAt(new \DateTimeImmutable());
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
    public function changePassword(
        Request $request,
        UserRepository $userRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        SettingsManager $settingsManager,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage,
    ): Response {
        /** @var ?User $user */
        $user = $this->getUser();

        if (!$user || !$user instanceof UserInterface) {
            $userId = $request->query->get('userId');
            error_log("User not logged in. Received userId from query: " . $userId);

            if (!$userId || !ctype_digit($userId)) {
                error_log("Access denied: Missing or invalid userId.");
                throw $this->createAccessDeniedException('This user does not have access to this section.');
            }

            $user = $userRepository->find((int)$userId);

            if (!$user || !$user instanceof UserInterface) {
                error_log("Access denied: User not found with ID $userId");
                throw $this->createAccessDeniedException('User not found or invalid.');
            }

            error_log("Loaded user by ID: " . $user->getId());
        }

        $isRotation = $request->query->getBoolean('rotate', false);

        $form = $this->createForm(ChangePasswordType::class, [
            'enable2FA' => $user->getMfaEnabled(),
        ], [
            'user' => $user,
            'portal_name' => $settingsManager->getSetting('platform.institution'),
            'password_hasher' => $passwordHasher,
            'enable_2fa_field' => !$isRotation,
        ]);
        $form->handleRequest($request);

        $session = $request->getSession();
        $qrCodeBase64 = null;
        $showQRCode = false;

        // Build QR code preview if user opts to enable 2FA but hasn't saved yet
        if (
            $form->isSubmitted()
            && $form->has('enable2FA')
            && $form->get('enable2FA')->getData()
            && !$user->getMfaSecret()
        ) {
            if (!$session->has('temporary_mfa_secret')) {
                $totp = TOTP::create();
                $secret = $totp->getSecret();
                $session->set('temporary_mfa_secret', $secret);
            } else {
                $secret = $session->get('temporary_mfa_secret');
            }

            $totp = TOTP::create($secret);
            $portalName = $settingsManager->getSetting('platform.institution');
            $totp->setLabel($portalName . ' - ' . $user->getEmail());

            $qrCodeResult = Builder::create()
                ->writer(new PngWriter())
                ->data($totp->getProvisioningUri())
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(300)
                ->margin(10)
                ->build();

            $qrCodeBase64 = base64_encode($qrCodeResult->getString());
            $showQRCode = true;
        }

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $submittedToken = $request->request->get('_token');
                if (!$csrfTokenManager->isTokenValid(new CsrfToken('change_password', $submittedToken))) {
                    $form->addError(new FormError($this->translator->trans('CSRF token is invalid. Please try again.')));
                } else {
                    $currentPassword = $form->get('currentPassword')->getData();
                    $newPassword = $form->get('newPassword')->getData();
                    $confirmPassword = $form->get('confirmPassword')->getData();
                    $enable2FA = !$isRotation && $form->has('enable2FA')
                        ? $form->get('enable2FA')->getData()
                        : false;

                    if ($enable2FA && !$user->getMfaSecret()) {
                        $secret = $session->get('temporary_mfa_secret');
                        $encryptedSecret = $this->encryptTOTPSecret($secret, $_ENV['APP_SECRET']);
                        $user->setMfaSecret($encryptedSecret);
                        $user->setMfaEnabled(true);
                        $user->setMfaService('TOTP');
                        $userRepository->updateUser($user);

                        $session->remove('temporary_mfa_secret');

                        $this->addFlash('success', '2FA activated successfully.');

                        return $this->redirectToRoute('chamilo_core_account_home');
                    }

                    if (!$isRotation && !$enable2FA && $user->getMfaEnabled()) {
                        $user->setMfaEnabled(false);
                        $user->setMfaSecret(null);
                        $userRepository->updateUser($user);
                        $this->addFlash('success', '2FA disabled successfully.');
                    }

                    if ($newPassword || $confirmPassword || $currentPassword) {
                        if (!$userRepository->isPasswordValid($user, $currentPassword)) {
                            $form->get('currentPassword')->addError(new FormError(
                                $this->translator->trans('The current password is incorrect.')
                            ));
                        } elseif ($newPassword !== $confirmPassword) {
                            $form->get('confirmPassword')->addError(new FormError(
                                $this->translator->trans('Passwords do not match.')
                            ));
                        } else {
                            $user->setPlainPassword($newPassword);
                            $user->setPasswordUpdateAt(new \DateTimeImmutable());
                            $userRepository->updateUser($user);
                            $this->addFlash('success', 'Password updated successfully.');

                            // Re-login if the user was not logged
                            if (!$this->getUser()) {
                                $token = new UsernamePasswordToken(
                                    $user,
                                    'main',
                                    $user->getRoles()
                                );
                                $tokenStorage->setToken($token);
                                $request->getSession()->set('_security_main', serialize($token));
                            }

                            return $this->redirectToRoute('chamilo_core_account_home');
                        }
                    }
                }
            } else {
                error_log("Form is NOT valid.");
            }
        } else {
            error_log("Form NOT submitted yet.");
        }

        return $this->render('@ChamiloCore/Account/change_password.html.twig', [
            'form' => $form->createView(),
            'qrCode' => $qrCodeBase64,
            'user' => $user,
            'showQRCode' => $showQRCode,
        ]);
    }

    /**
     * Encrypts the TOTP secret using AES-256-CBC encryption.
     */
    private function encryptTOTPSecret(string $secret, string $encryptionKey): string
    {
        $cipherMethod = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipherMethod));
        $encryptedSecret = openssl_encrypt($secret, $cipherMethod, $encryptionKey, 0, $iv);

        return base64_encode($iv . '::' . $encryptedSecret);
    }

    /**
     * Decrypts the TOTP secret using AES-256-CBC decryption.
     */
    private function decryptTOTPSecret(string $encryptedSecret, string $encryptionKey): string
    {
        $cipherMethod = 'aes-256-cbc';
        list($iv, $encryptedData) = explode('::', base64_decode($encryptedSecret), 2);

        return openssl_decrypt($encryptedData, $cipherMethod, $encryptionKey, 0, $iv);
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
