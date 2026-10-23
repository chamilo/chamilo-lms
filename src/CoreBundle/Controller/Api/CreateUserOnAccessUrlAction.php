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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private Security $security,
    ) {}

    public function __invoke(AccessUrl $url, CreateUserOnAccessUrlInput $data): User
    {
        $this->validator->validate($data);

        $this->denyUnlessCallerWorksOnUrl($url);

        $locale = $data->getLocale();
        if (empty($locale)) {
            $locale = (string) ($this->settingsManager->getSetting('language.platform_language', true) ?: 'en');
        }

        $timezone = $data->getTimezone();
        if (empty($timezone)) {
            $timezone = (string) ($this->settingsManager->getSetting('platform.timezone', true) ?: 'Europe/Paris');
        }

        $status = $this->resolveStatus($data->getStatus());

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
            $sessionUrl = rtrim($baseUrl, '/').'/sessions';
            $platformName = $this->settingsManager->getSetting('platform.site_name', true);
            $password = $data->getPassword();

            $subject = \sprintf(
                $this->translator->trans('You are registered on %s'),
                $platformName
            );

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
                $user->getFullName(),
                $platformName,
                $sessionUrl,
                $user->getUsername(),
                $password,
                $platformName
            );

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

    /**
     * The access URL comes from the request path, so it has to be one the caller works on: a
     * global admin on any URL, anyone else only on a URL they are registered on.
     */
    private function denyUnlessCallerWorksOnUrl(AccessUrl $url): void
    {
        $caller = $this->userHelper->getCurrent();

        if (null === $caller) {
            throw new AccessDeniedHttpException();
        }

        if (!$this->security->isGranted('ROLE_GLOBAL_ADMIN') && !$url->hasUser($caller)) {
            throw new AccessDeniedHttpException('You cannot create users on this access URL.');
        }
    }

    /**
     * Only a known status is accepted, and only an admin may create anything other than a
     * learner: session managers use this endpoint to register learners, and must not be able
     * to create accounts with rights they could not grant otherwise.
     */
    private function resolveStatus(?int $status): int
    {
        $status ??= STUDENT;

        if (!\in_array($status, [COURSEMANAGER, SESSIONADMIN, DRH, STUDENT, STUDENT_BOSS, INVITEE], true)) {
            throw new BadRequestHttpException('Invalid status.');
        }

        if (STUDENT !== $status && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('You cannot create users with this status.');
        }

        return $status;
    }
}
