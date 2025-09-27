<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLink;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_PROXY;
use const CURLOPT_PROXYPORT;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;

class CheckCLinkAction extends AbstractController
{
    public function __invoke(CLink $link, Request $request, SettingsManager $settingsManager): JsonResponse
    {
        $url = $request->query->get('url');
        $result = $this->checkUrl($url, $settingsManager);

        return new JsonResponse(['isValid' => $result]);
    }

    private function checkUrl(string $url, SettingsManager $settingsManager): bool
    {
        // Check if curl is available.
        if (!\in_array('curl', get_loaded_extensions())) {
            return false;
        }

        // set URL and other appropriate options
        $defaults = [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4,
        ];

        // Check for proxy settings in your application configuration
        $proxySettings = $settingsManager->getSetting('security.proxy_settings', true);
        if ($proxySettings && isset($proxySettings['curl_setopt_array'])) {
            $defaults[CURLOPT_PROXY] = $proxySettings['curl_setopt_array']['CURLOPT_PROXY'];
            $defaults[CURLOPT_PROXYPORT] = $proxySettings['curl_setopt_array']['CURLOPT_PROXYPORT'];
        }

        // Create a new cURL resource
        $ch = curl_init();
        curl_setopt_array($ch, $defaults);

        // grab URL and pass it to the browser
        ob_start();
        $result = curl_exec($ch);
        ob_get_clean();

        // close cURL resource, and free up system resources
        curl_close($ch);

        // Check for any errors
        if (false === $result || 200 != curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            return false;
        }

        return true;
    }
}
