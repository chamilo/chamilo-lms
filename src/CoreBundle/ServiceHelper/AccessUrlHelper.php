<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AccessUrlHelper
{
    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly RouterInterface $router,
    ) {}

    public function getFirstAccessUrl(): AccessUrl
    {
        $urlId = $this->accessUrlRepository->getFirstId();

        return $this->accessUrlRepository->find($urlId);
    }

    public function getCurrent(): AccessUrl
    {
        static $accessUrl;

        if (!empty($accessUrl)) {
            return $accessUrl;
        }

        $url = $this->router->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL);

        /** @var AccessUrl $accessUrl */
        $accessUrl = $this->accessUrlRepository->findOneBy(['url' => $url]);

        if (!$accessUrl) {
            $accessUrl = $this->getFirstAccessUrl();
        }

        return $accessUrl;
    }

    public function hasMultipleAccessUrls(): bool
    {
        return $this->accessUrlRepository->hasMultipleAccessUrls();
    }
}
