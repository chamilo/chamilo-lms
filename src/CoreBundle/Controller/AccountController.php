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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
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

            if ($form->has('password')) {
                $password = $form['password']->getData();
                if ($password) {
                    $user->setPlainPassword($password);
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
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Ensure user is authenticated and has proper interface
        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        // Build the form and inject user-related options
        $form = $this->createForm(ChangePasswordType::class, [
            'enable2FA' => $user->getMfaEnabled(),
        ], [
            'user' => $user,
            'portal_name' => $settingsManager->getSetting('platform.institution'),
            'password_hasher' => $passwordHasher,
        ]);

        $form->handleRequest($request);
        $session = $request->getSession();
        $qrCodeBase64 = null;
        $showQRCode = false;

        // Generate TOTP secret and QR code for 2FA activation
        if ($form->get('enable2FA')->getData() && !$user->getMfaSecret()) {
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

            // Build QR code image
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

        // Handle form submission
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('newPassword')->getData();
            $enable2FA = $form->get('enable2FA')->getData();

            // Enable 2FA and store encrypted secret
            if ($enable2FA && !$user->getMfaSecret() && $session->has('temporary_mfa_secret')) {
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

            // Disable 2FA if it was previously enabled
            if (!$enable2FA && $user->getMfaEnabled()) {
                $user->setMfaEnabled(false);
                $user->setMfaSecret(null);

                $userRepository->updateUser($user);
                $this->addFlash('success', '2FA disabled successfully.');
                return $this->redirectToRoute('chamilo_core_account_home');
            }

            // Update password if provided
            if (!empty($newPassword)) {
                $user->setPlainPassword($newPassword);
                $userRepository->updateUser($user);
                $this->addFlash('success', 'Password updated successfully.');
                return $this->redirectToRoute('chamilo_core_account_home');
            }
        }

        // Render form with optional QR code for 2FA
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

        return base64_encode($iv.'::'.$encryptedSecret);
    }

    /**
     * Validates the provided TOTP code for the given user.
     */
    private function isTOTPValid(User $user, string $totpCode): bool
    {
        $decryptedSecret = $this->decryptTOTPSecret($user->getMfaSecret(), $_ENV['APP_SECRET']);
        $totp = TOTP::create($decryptedSecret);

        return $totp->verify($totpCode);
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
