<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Pdp\Rules;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

use const PHP_SAPI;
use const PHP_URL_HOST;

readonly class AccessUrlHelper
{
    public function __construct(
        private AccessUrlRepository $accessUrlRepository,
        private RequestStack $requestStack,
    ) {}

    public function isMultiple(): bool
    {
        try {
            return $this->accessUrlRepository->count([]) > 1;
        } catch (Throwable $e) {
            // During a fresh install, DB/tables may not exist yet.
            // Treat as single URL and do not crash.
            error_log('AccessUrlHelper::isMultiple(): DB not ready, treating as single URL. '.$e->getMessage());

            return false;
        }
    }

    public function getFirstAccessUrl(): ?AccessUrl
    {
        try {
            $urlId = $this->accessUrlRepository->getFirstId();
            if (empty($urlId)) {
                return null;
            }

            return $this->accessUrlRepository->find($urlId) ?: null;
        } catch (Throwable $e) {
            // During installation, the access_url table may not exist yet.
            error_log('AccessUrlHelper::getFirstAccessUrl(): DB not ready, returning null. '.$e->getMessage());

            return null;
        }
    }

    public function getCurrent(): ?AccessUrl
    {
        // Safe fallback (may be null during install)
        $accessUrl = $this->getFirstAccessUrl();

        if ('cli' === PHP_SAPI) {
            return $accessUrl;
        }

        // If multi-URL is enabled, try to match the current host.
        // All DB errors are caught to avoid breaking the install bootstrap.
        if ($this->isMultiple()) {
            $request = $this->requestStack->getMainRequest();
            if (null === $request) {
                return $accessUrl;
            }

            try {
                $url = $request->getSchemeAndHttpHost().'/';
                $matched = $this->accessUrlRepository->findOneBy(['url' => $url]);

                if ($matched instanceof AccessUrl) {
                    return $matched;
                }
            } catch (Throwable $e) {
                error_log('AccessUrlHelper::getCurrent(): failed to resolve current URL, using fallback. '.$e->getMessage());
            }
        }

        return $accessUrl;
    }

    public function isSameBaseDomain(array $urls): bool
    {
        if (empty($urls)) {
            return false;
        }

        $projectDir = Container::$container->getParameter('kernel.project_dir');

        $rules = Rules::fromPath($projectDir.'/config/public_suffix_list.dat');

        $firstHost = parse_url($urls[0], PHP_URL_HOST);

        if (!$firstHost) {
            return false;
        }

        $firstDomain = $rules->resolve($firstHost)->registrableDomain()->toString();

        if (!$firstDomain) {
            return false;
        }

        // Comparar con el resto
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if (!$host) {
                return false;
            }

            $domain = $rules->resolve($host)->registrableDomain();

            if ($domain->toString() !== $firstDomain) {
                return false;
            }
        }

        return true;
    }
}
