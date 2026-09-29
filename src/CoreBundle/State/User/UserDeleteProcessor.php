<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

/**
 * Routes the delete through UserManager::delete_user() instead of the plain Doctrine
 * remove processor, which skipped everything the platform does when a user goes:
 * the USER_DELETED events plugins clean up from, reassigning what the user created
 * to the fallback user, and dropping their files and messages.
 *
 * The deletion is the platform's soft delete -- what the admin user list does -- so
 * the account stays restorable.
 *
 * @implements ProcessorInterface<User, void>
 */
final readonly class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private UserHelper $userHelper,
        private TranslatorInterface $translator,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof User) {
            return;
        }

        $userId = (int) $data->getId();

        // Both messages reuse the wording UserManager::deleteUserWithVerification()
        // already shows for these two cases, so they are translatable as they stand.
        if ($userId === (int) $this->userHelper->getCurrent()?->getId()) {
            throw new AccessDeniedHttpException($this->translator->trans('You cannot delete this user'));
        }

        if (!UserManager::delete_user($userId)) {
            throw new ConflictHttpException($this->translator->trans('This user cannot be deleted because he is still teacher in a course. You can either remove his teacher status from these courses and then delete his account, or disable his account instead of deleting it.'));
        }
    }
}
