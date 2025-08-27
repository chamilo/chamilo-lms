<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;

readonly class AccessUrlListener
{
    public function __construct(
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepo,
    ) {}

    public function prePersist(AccessUrl $accessUrl, PrePersistEventArgs $args): void
    {
        $firstAccessUrl = $this->accessUrlHelper->getFirstAccessUrl();

        if (!$firstAccessUrl) {
            $accessUrl->setIsLoginOnly(false);

            return;
        }

        if ($loginOnlyAccessUrl = $this->accessUrlRepo->getOnlyLoginAccessUrl()) {
            $accessUrl
                ->setIsLoginOnly(false)
                ->setSuperior($loginOnlyAccessUrl)
                ->setParentResourceNode($loginOnlyAccessUrl->resourceNode->getId())
            ;

            return;
        }

        if ($accessUrl->isLoginOnly()) {
            $accessUrl->setActive(1);
        }

        $accessUrl
            ->setSuperior($firstAccessUrl)
            ->setParentResourceNode($firstAccessUrl->resourceNode->getId())
        ;
    }

    public function postPersist(AccessUrl $currentAccessUrl, PostPersistEventArgs $args): void
    {
        if (!$currentAccessUrl->isLoginOnly()) {
            return;
        }

        /** @var array<int, AccessUrl> $all */
        $all = $this->accessUrlRepo->findAll();

        $firstAccessUrl = $this->accessUrlHelper->getFirstAccessUrl();

        foreach ($all as $accessUrl) {
            if (\in_array($accessUrl->getId(), [$firstAccessUrl->getId(), $currentAccessUrl->getId()])) {
                continue;
            }

            $accessUrl
                ->setSuperior($currentAccessUrl)
                ->resourceNode->setParent($currentAccessUrl->resourceNode)
            ;
        }

        $args->getObjectManager()->flush();
    }
}
