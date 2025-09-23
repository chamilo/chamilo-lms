<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Validator\ValidatorInterface;
use Chamilo\CoreBundle\Dto\CreateUserOnAccessUrlInput;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class CreateUserOnAccessUrlAction
{
    public function __construct(
        private EntityManagerInterface $em,
        private AccessUrlRepository $accessUrlRepo,
        private ValidatorInterface $validator,
        private ExtraFieldValuesRepository $extraFieldValuesRepo,
        private ExtraFieldRepository $extraFieldRepo,
        private MessageHelper $messageHelper,
        private TranslatorInterface $translator,
        private UserHelper $userHelper,
        private readonly RequestStack $requestStack,
        private readonly SettingsManager $settingsManager,
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(CreateUserOnAccessUrlInput $data): User
    {
        $this->validator->validate($data);

        $url = $this->accessUrlRepo->find($data->getAccessUrlId());
        if (!$url) {
            throw new NotFoundHttpException('Access URL not found.');
        }

        $user = new User();
        $user
            ->setUsername($data->getUsername())
            ->setFirstname($data->getFirstname())
            ->setLastname($data->getLastname())
            ->setEmail($data->getEmail())
            ->setLocale($data->getLocale() ?? 'en')
            ->setTimezone($data->getTimezone() ?? 'Europe/Paris')
            ->setStatus($data->getStatus() ?? 5)
            ->setActive(User::ACTIVE)
            ->setPlainPassword($data->getPassword())
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
            ->setRoleFromStatus($data->getStatus() ?? 5)
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

        $hasAccess = $user->getPortals()->exists(
            fn ($k, $rel) => $rel->getUrl()?->getId() === $url->getId()
        );

        if (!$hasAccess) {
            $rel = new AccessUrlRelUser();
            $rel->setUser($user)->setUrl($url);

            $this->em->persist($rel);
            $this->em->flush();
        }

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
}
