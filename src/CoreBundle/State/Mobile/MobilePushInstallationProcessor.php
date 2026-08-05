<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Mobile;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Mobile\MobilePushInstallation as MobilePushInstallationResource;
use Chamilo\CoreBundle\Entity\MobilePushInstallation;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\MobilePushInstallationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<MobilePushInstallationResource, MobilePushInstallationResource|null>
 */
final readonly class MobilePushInstallationProcessor implements ProcessorInterface
{
    public function __construct(
        private MobilePushInstallationRepository $repository,
        private EntityManagerInterface $entityManager,
        private UserHelper $userHelper,
        private AccessUrlHelper $accessUrlHelper,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ?MobilePushInstallationResource {
        $user = $this->userHelper->getCurrent();
        $accessUrl = $this->accessUrlHelper->getCurrent();

        if (null === $user || null === $accessUrl) {
            throw new AccessDeniedHttpException('An authenticated user and current access URL are required.');
        }

        if ($operation instanceof Delete) {
            $installationId = (string) ($uriVariables['installationId'] ?? '');
            if ('' !== $installationId) {
                $this->repository->removeOwnedInstallation($user, $accessUrl, $installationId);
            }

            return null;
        }

        if (!$operation instanceof Post || !$data instanceof MobilePushInstallationResource) {
            throw new LogicException('Unsupported mobile push installation operation.');
        }

        $token = trim($data->token);
        $tokenHash = hash('sha256', $token);

        $byInstallation = $this->repository->findOneByAccessUrlAndInstallationId(
            $accessUrl,
            $data->installationId
        );
        $byToken = $this->repository->findOneByAccessUrlAndTokenHash($accessUrl, $tokenHash);

        if ($byInstallation instanceof MobilePushInstallation) {
            $installation = $byInstallation;

            if ($byToken instanceof MobilePushInstallation
                && $byToken->getId() !== $byInstallation->getId()
            ) {
                $this->entityManager->remove($byToken);
                $this->entityManager->flush();
            }
        } elseif ($byToken instanceof MobilePushInstallation) {
            $installation = $byToken;
        } else {
            $installation = new MobilePushInstallation();
        }

        $now = new DateTime();
        $installation
            ->setUser($user)
            ->setAccessUrl($accessUrl)
            ->setInstallationId($data->installationId)
            ->setToken($token)
            ->setTokenHash($tokenHash)
            ->setPlatform($data->platform)
            ->setUpdatedAt($now)
            ->setLastSeenAt($now)
        ;

        $this->entityManager->persist($installation);
        $this->entityManager->flush();

        return MobilePushInstallationResource::fromEntity($installation);
    }
}
