<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class AccessUrlHelper
{
    public function __construct(
        private readonly AccessUrlRepository $accessUrlRepository,
        private readonly RequestStack $requestStack,
    ) {}

    public function isMultiple(): bool
    {
        static $accessUrlEnabled;

        if (!isset($accessUrlEnabled)) {
            $accessUrlEnabled = $this->accessUrlRepository->count([]) > 1;
        }

        return $accessUrlEnabled;
    }

    public function getFirstAccessUrl(): ?AccessUrl
    {
        $urlId = $this->accessUrlRepository->getFirstId();

        return $this->accessUrlRepository->find($urlId) ?: null;
    }

    public function getCurrent(): ?AccessUrl
    {
        static $accessUrl;

        if (!empty($accessUrl)) {
            return $accessUrl;
        }

        $accessUrl = $this->getFirstAccessUrl();

        if ($this->isMultiple()) {
            $request = $this->requestStack->getMainRequest();

            if (null === $request) {
                return $accessUrl;
            }

            $url = $request->getSchemeAndHttpHost().'/';

            /** @var AccessUrl $accessUrl */
            $accessUrl = $this->accessUrlRepository->findOneBy(['url' => $url]);
        }

        return $accessUrl;
    }
}
