<?php

/* This file contains all the configuration variable for the cas module
 * In the future, these will be in the database
*/

if (api_is_cas_activated()) {
    require_once __DIR__.'/../../../vendor/apereo/phpcas/source/CAS.php';

    // Get the $cas array from app/config/auth.conf.php
    global $cas;

    if (is_array($cas) && array_key_exists('debug', $cas) && !empty($cas['debug'])) {
        phpCAS::setDebug($cas['debug']);
    }

    if (is_array($cas) && array_key_exists('verbose', $cas) && $cas['verbose']) {
        phpCAS::setVerbose(true);
    }

    if (!phpCAS::isInitialized()) {
        switch (api_get_setting('cas_protocol')) {
            case 'CAS1':
                $version = CAS_VERSION_1_0;
                break;
            case 'CAS3':
                $version = CAS_VERSION_3_0;
                break;
            case 'SAML':
                $version = SAML_VERSION_1_1;
                break;
            case 'CAS2':
            default:
                $version = CAS_VERSION_2_0;
        }
        $port = api_get_setting('cas_port');
        if (is_null($port)) {
            $port = 443;
        } else {
            $port = intval($port) ?: 443;
        }
        $uri = api_get_setting('cas_server_uri') ?: '';
        $hostname = api_get_setting('cas_server') ?: 'localhost';

        phpCAS::client($version, $hostname, $port, $uri);

        if (is_array($cas) && array_key_exists('noCasServerValidation', $cas) && $cas['noCasServerValidation']) {
            phpCAS::setNoCasServerValidation();
        }

        $proxySettings = api_get_configuration_value('proxy_settings');
        if (false !== $proxySettings) {
            if (is_array($proxySettings) && array_key_exists('https', $proxySettings)) {
                $https = $proxySettings['https'];
                if (is_array($https) && array_key_exists('proxy', $https)) {
                    $proxy = $https['proxy'];
                    if (is_string($proxy) && !empty($proxy)) {
                        phpCAS::setExtraCurlOption(CURLOPT_PROXY, $proxy);
                    }
                }
            }
        }

        if (is_array($cas) && array_key_exists('fixedServiceURL', $cas)) {
            $fixedServiceURL = $cas['fixedServiceURL'];
            if (is_string($fixedServiceURL)) {
                phpCAS::setFixedServiceURL($fixedServiceURL);
            } elseif (is_bool($fixedServiceURL) && $fixedServiceURL) {
                phpCAS::setFixedServiceURL(api_get_configuration_value('root_web'));
            }
        }
    }
}
