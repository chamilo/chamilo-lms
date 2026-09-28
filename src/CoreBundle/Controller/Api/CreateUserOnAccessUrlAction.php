<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\CreateUserOnAccessUrlInput;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

use const ENT_QUOTES;

#[AsController]
readonly class CreateUserOnAccessUrlAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private ExtraFieldValuesRepository $extraFieldValuesRepo,
        private ExtraFieldRepository $extraFieldRepo,
        private MessageHelper $messageHelper,
        private TranslatorInterface $translator,
        private UserHelper $userHelper,
        private RequestStack $requestStack,
        private SettingsManager $settingsManager,
        private UserRepository $userRepository,
        private ResetPasswordHelperInterface $resetPasswordHelper,
    ) {}

    public function __invoke(AccessUrl $url, CreateUserOnAccessUrlInput $data): User
    {
        $this->validator->validate($data);

        if (null !== $this->userRepository->findByUsernameCaseInsensitive($data->getUsername())) {
            throw new ConflictHttpException('username_already_exists');
        }

        if (null !== $this->userRepository->findByEmailCaseInsensitive($data->getEmail())) {
            throw new ConflictHttpException('email_already_exists');
        }

        $locale = $data->getLocale();
        if (empty($locale)) {
            $locale = (string) ($this->settingsManager->getSetting('language.platform_language', true) ?: 'en');
        }

        $timezone = $data->getTimezone();
        if (empty($timezone)) {
            $timezone = (string) ($this->settingsManager->getSetting('platform.timezone', true) ?: 'Europe/Paris');
        }

        $status = $data->getStatus() ?? 5;

        $user = new User();
        $user
            ->setUsername($data->getUsername())
            ->setFirstname($data->getFirstname())
            ->setLastname($data->getLastname())
            ->setEmail($data->getEmail())
            ->setLocale($locale)
            ->setTimezone($timezone)
            ->setStatus($status)
            ->setActive(User::ACTIVE)
            ->setPlainPassword($data->getPassword())
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
            ->setRoleFromStatus($status)
        ;

        $this->userRepository->updateUser($user);

        if (!empty($data->extraFields)) {
            foreach ($data->extraFields as $variable => $value) {
                $extraField = $this->extraFieldRepo->findOneBy([
                    'variable' => $variable,
                    'itemType' => 1,
                ]);

                if (!$extraField) {
                    throw new RuntimeException("ExtraField '{$variable}' not found for users.");
                }

                $this->extraFieldValuesRepo->updateItemData(
                    $extraField,
                    $user,
                    $value
                );
            }
        }

        $url->addUser($user);

        $this->em->flush();

        if ($data->getSendEmail()) {
            $request = $this->requestStack->getCurrentRequest();
            $baseUrl = $request->getSchemeAndHttpHost().$request->getBasePath();
            $platformName = $this->settingsManager->getSetting('platform.site_name', true);
            $password = $data->getPassword();
            // $body below is sent both as a stored Chamilo message (rendered as HTML
            // in the recipient's/staff's Messages UI) and as an HTML email — firstname/
            // lastname/username ultimately come from the API caller (for the WordPress
            // storefront integration, straight from a buyer's own WooCommerce billing
            // fields, so anyone placing an order controls them) and must be escaped
            // before interpolation, unlike the server-built links/platform name below.
            $safeFullName = htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8');
            $safeUsername = htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8');
            $safePassword = null !== $password ? htmlspecialchars($password, ENT_QUOTES, 'UTF-8') : $password;

            $subject = \sprintf(
                $this->translator->trans('You are registered on %s'),
                $platformName
            );

            if (null === $password || '' === $password) {
                // No password was supplied by the caller — the account has none set
                // yet, so a message showing a blank "Password:" field would leave the
                // user unable to log in with no way to recover. Generate a real
                // password-reset token (the same mechanism "Forgot your password?"
                // uses, see ResetPasswordController) and send a working
                // set-your-password link instead of an empty credential.
                $resetLink = rtrim($baseUrl, '/').'/login';

                try {
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);
                    $resetLink = rtrim($baseUrl, '/').'/reset-password/reset/'.$resetToken->getToken();
                } catch (ResetPasswordExceptionInterface) {
                    // Fall back to a bare login link rather than let this break
                    // account creation, which already succeeded and was flushed
                    // above — the user can still request a reset manually from there.
                }

                $body = $this->translator->trans(
                    'Hello %s,<br><br>'.
                    'You are registered on %s.<br>'.
                    'Username: <strong>%s</strong><br><br>'.
                    'Please click the link below to set your password and access your account:<br>'.
                    '<a href="%s">Set your password</a><br><br>'.
                    'Best regards,<br>'.
                    '%s'
                );

                $body = \sprintf(
                    $body,
                    $safeFullName,
                    $platformName,
                    $safeUsername,
                    $resetLink,
                    $platformName
                );
            } else {
                $sessionUrl = rtrim($baseUrl, '/').'/sessions';

                $body = $this->translator->trans(
                    'Hello %s,<br><br>'.
                    'You are registered on %s.<br>'.
                    'You can access your account from <a href="%s">here</a>.<br><br>'.
                    'Your login credentials are:<br>'.
                    'Username: <strong>%s</strong><br>'.
                    'Password: <strong>%s</strong><br><br>'.
                    'Best regards,<br>'.
                    '%s'
                );

                $body = \sprintf(
                    $body,
                    $safeFullName,
                    $platformName,
                    $sessionUrl,
                    $safeUsername,
                    $safePassword,
                    $platformName
                );
            }

            $currentUser = $this->userHelper->getCurrent();
            $senderId = $currentUser?->getId() ?? 1;

            $this->messageHelper->sendMessage(
                $user->getId(),
                $subject,
                $body,
                [],
                [],
                0,
                0,
                0,
                $senderId,
                0,
                false,
                true
            );
        }

        return $user;
    }
}
