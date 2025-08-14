<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Pdp\Rules;
use Pdp\TopLevelDomains;
use Symfony\Component\HttpFoundation\RequestStack;

use const PHP_SAPI;

readonly class AccessUrlHelper
{
    public function __construct(
        private AccessUrlRepository $accessUrlRepository,
        private RequestStack $requestStack,
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

        if ('cli' === PHP_SAPI) {
            return $accessUrl;
        }

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
