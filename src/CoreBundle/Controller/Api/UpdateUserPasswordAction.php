<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Security\PasswordPolicyValidator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTime;
use Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Dedicated admin/self password-reset endpoint (PATCH /api/users/{id}/password), replacing
 * the "update_user_password" webservice action requested in issue #6695. The generic
 * PATCH /api/users/{id} (with a "plainPassword" field) already lets a caller set a
 * password as a side effect, but it enforces neither the platform's password-strength
 * policy nor the "force change at next login" flag below.
 *
 * Access-URL scoping of *who* may reach this action for a given target user is enforced
 * upstream by UserVoter::EDIT (this operation's security expression), so it is not
 * duplicated here.
 */
#[AsController]
final readonly class UpdateUserPasswordAction
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordPolicyValidator $passwordPolicyValidator,
        private SettingsManager $settingsManager,
    ) {}

    public function __invoke(User $user, Request $request): User
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON body.');
        }

        $password = $payload['password'] ?? null;
        if (!\is_string($password) || '' === $password) {
            throw new BadRequestHttpException('Missing "password".');
        }

        $forceChange = (bool) ($payload['forcePasswordChangeAtNextLogin'] ?? false);

        if ('true' === (string) $this->settingsManager->getSetting('security.check_password', true)) {
            $errors = $this->passwordPolicyValidator->validate($password);
            if ([] !== $errors) {
                throw new UnprocessableEntityHttpException(implode(' ', $errors));
            }
        }

        $user->setPlainPassword($password);
        $user->setPasswordUpdatedAt(new DateTime());
        $user->setPasswordRequestedAt($forceChange ? new DateTime() : null);

        $this->userRepository->updateUser($user);

        Event::addEvent(LOG_USER_PASSWORD_UPDATE, LOG_USER_ID, $user->getId());

        return $user;
    }
}
