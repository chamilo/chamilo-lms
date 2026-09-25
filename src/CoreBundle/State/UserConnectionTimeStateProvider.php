<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\UserConnectionTime;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TrackELoginRepository;
use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<UserConnectionTime>
 */
final readonly class UserConnectionTimeStateProvider implements ProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private TrackELoginRepository $trackELoginRepository,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserConnectionTime
    {
        $user = $this->userRepository->find((int) $uriVariables['id']);
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        // As the legacy function did, only users of the current portal are counted.
        if ($this->accessUrlHelper->isMultiple()) {
            $accessUrl = $this->accessUrlHelper->getCurrent();
            if (null === $accessUrl || !$accessUrl->hasUser($user)) {
                throw new NotFoundHttpException('User not found.');
            }
        }

        $request = $context['request'] ?? null;
        $startDate = $this->getDateParameter($request, 'startDate');
        $endDate = $this->getDateParameter($request, 'endDate');

        if (null !== $startDate && null !== $endDate && $endDate < $startDate) {
            throw new BadRequestHttpException('endDate must not be before startDate.');
        }

        $seconds = $this->trackELoginRepository->getTotalConnectionTime((int) $user->getId(), $startDate, $endDate);

        return new UserConnectionTime(
            (int) $user->getId(),
            $user->getUsername(),
            $seconds,
            \sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60),
        );
    }

    private function getDateParameter(?Request $request, string $name): ?DateTime
    {
        $value = $request?->query->get($name);
        if (null === $value || '' === $value) {
            return null;
        }

        try {
            $date = new DateTime((string) $value);
        } catch (Exception) {
            throw new BadRequestHttpException(\sprintf('Invalid %s, expected an ISO 8601 date-time.', $name));
        }

        // track_e_login stores UTC.
        return $date->setTimezone(new DateTimeZone('UTC'));
    }
}
