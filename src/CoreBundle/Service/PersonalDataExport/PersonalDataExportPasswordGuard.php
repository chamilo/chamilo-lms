<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Re-checks the current user's password before a personal-data export.
 * Failed attempts are rate-limited. The password itself is never logged.
 */
final class PersonalDataExportPasswordGuard
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RateLimiterFactory $personalDataExportPasswordLimiter,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {}

    public function assertCurrentPassword(User $user, string $plainPassword): void
    {
        $limiter = $this->personalDataExportPasswordLimiter->create('user-'.(string) $user->getId());
        if ($limiter->consume(0)->getRemainingTokens() <= 0) {
            $this->logger->warning('Personal data export blocked by rate limit.', [
                'user_id' => $user->getId(),
            ]);

            throw new TooManyRequestsHttpException(null, $this->translator->trans('Too many attempts. Please try again later.'));
        }

        if ('' === $plainPassword || !$this->userRepository->isPasswordValid($user, $plainPassword)) {
            $limiter->consume(1);
            $this->logger->warning('Personal data export rejected because the password was incorrect.', [
                'user_id' => $user->getId(),
            ]);

            throw new AccessDeniedHttpException($this->translator->trans('The current password is incorrect'));
        }

        $limiter->reset();
    }
}
