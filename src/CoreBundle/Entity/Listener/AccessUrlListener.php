<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
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

            $this->inheritColorTheme($accessUrl, $loginOnlyAccessUrl);

            return;
        }

        if ($accessUrl->isLoginOnly()) {
            $accessUrl->setActive(1);
        }

        // A caller (e.g. the URL management page's parent selector) may already have chosen
        // an explicit parent before persisting; only fall back to the first URL when none was
        // set. This does not apply above: a login-only URL always wins, since every other URL
        // must funnel through it for the central-login feature to work.
        if (null !== $accessUrl->getSuperior()) {
            $this->inheritColorTheme($accessUrl, $accessUrl->getSuperior());

            return;
        }

        $accessUrl
            ->setSuperior($firstAccessUrl)
            ->setParentResourceNode($firstAccessUrl->resourceNode->getId())
        ;

        $this->inheritColorTheme($accessUrl, $firstAccessUrl);
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

    /**
     * Copies the parent's active color theme so a new URL is not left themeless.
     * The relation cascades on persist, so no explicit persist/flush is needed here.
     */
    private function inheritColorTheme(AccessUrl $accessUrl, AccessUrl $parentAccessUrl): void
    {
        if (null !== $accessUrl->getActiveColorTheme()) {
            return;
        }

        $parentColorTheme = $parentAccessUrl->getActiveColorTheme()?->getColorTheme();

        if (null === $parentColorTheme) {
            return;
        }

        $accessUrl->addColorTheme(
            (new AccessUrlRelColorTheme())
                ->setColorTheme($parentColorTheme)
                ->setActive(true)
        );
    }
}
