<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Component\Essence;

use Chamilo\CoreBundle\Helpers\SafeHttpClientHelper;
use Essence\Http\Client;
use Essence\Http\Exception as EssenceHttpException;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * SSRF-safe HTTP client for the Essence library (advisory 22.4).
 *
 * Essence fetches OEmbed/OpenGraph URLs server-side (MetaTags::_extract ->
 * Client::get); its bundled cURL/native clients have no private-IP filtering and
 * follow redirects, so a teacher-set course/session video URL could reach
 * internal hosts or the cloud metadata endpoint. Injecting this client via
 * `new Essence(['Http' => new SafeEssenceHttpClient()])` routes every Essence
 * fetch through SafeHttpClientHelper, which refuses loopback/private/reserved
 * targets, validates each redirect hop and speaks only http(s).
 */
final class SafeEssenceHttpClient implements Client
{
    private string $userAgent = '';

    /**
     * @param string $agent
     */
    public function setUserAgent($agent): void
    {
        $this->userAgent = (string) $agent;
    }

    /**
     * @param string $url
     */
    public function get($url): string
    {
        $options = [];
        if ('' !== $this->userAgent) {
            $options['headers'] = ['User-Agent' => $this->userAgent];
        }

        try {
            return SafeHttpClientHelper::create()
                ->request('GET', (string) $url, $options)
                ->getContent()
            ;
        } catch (ExceptionInterface $e) {
            // Blocked, unreachable or HTTP error: surface as Essence's own
            // exception so the provider skips the embed instead of leaking.
            throw new EssenceHttpException((string) $url, 0, $e instanceof Exception ? $e : null);
        }
    }
}
