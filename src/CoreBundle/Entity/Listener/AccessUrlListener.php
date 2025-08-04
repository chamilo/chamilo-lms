<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Doctrine\ORM\Event\PrePersistEventArgs;

class AccessUrlListener
{
    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
    ) {}

    public function prePersist(AccessUrl $accessUrl, PrePersistEventArgs $args): void
    {
        $firstAccessUrl = $this->accessUrlHelper->getFirstAccessUrl();

        if (!$firstAccessUrl) {
            $accessUrl->setIsLoginOnly(false);

            return;
        }

        if ($loginOnlyAccessUrl = $this->accessUrlHelper->getOnlyLoginAccessUrl()) {
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
}