<?php

/* For license terms, see /license.txt */

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Shared helper for the IMS/LTI cartridge auto-detect.
 *
 * Single home for the cartridge fetch+parse logic that used to be duplicated in
 * both the legacy plugin (ImsLtiPlugin::getLaunchUrlFromCartridge) and the
 * Symfony form (Chamilo\LtiBundle\Form\ExternalToolType). Keeping it here means
 * the SSRF control lives in one place.
 */
class ImsLtiCartridge
{
    /**
     * Fetch an LTI cartridge URL and return its blti:launch_url.
     *
     * SSRF protection: the request goes through Symfony's NoPrivateNetworkHttpClient,
     * which blocks any target resolving to a loopback/private/reserved range (incl.
     * the cloud metadata endpoint 169.254.169.254) and re-validates every redirect
     * hop. HttpClient only speaks http(s), so file://, gopher:// etc. are rejected
     * for free and TLS is verified by default.
     *
     * @param string $cartridgeUrl
     *
     * @return string|null the launch URL, or null when the cartridge is
     *                      unreachable/blocked or carries no launch URL
     */
    public static function resolveLaunchUrl($cartridgeUrl): ?string
    {
        if ('' === trim((string) $cartridgeUrl)) {
            return null;
        }

        $client = new NoPrivateNetworkHttpClient(HttpClient::create());

        try {
            $content = $client->request('GET', (string) $cartridgeUrl, ['timeout' => 10])->getContent();
        } catch (ExceptionInterface $e) {
            return null;
        }

        if ('' === $content) {
            return null;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NONET);

        if (false === $xml) {
            return null;
        }

        $result = $xml->xpath('blti:launch_url');

        if (empty($result)) {
            return null;
        }

        return (string) $result[0];
    }
}