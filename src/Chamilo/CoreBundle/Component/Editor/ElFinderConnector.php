<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor;

/**
 * elFinder's CSRF protection (added in vendor/studio-42/elfinder) only reads
 * the token from the "X-elFinder-CSRF" request header. Some reverse
 * proxies/gateways placed in front of a Chamilo portal (VPN web-rewriting
 * proxies, WAFs, ...) forward request params untouched but strip custom
 * headers, which makes every CSRF-protected command (upload, rm, rename...)
 * fail permanently. This subclass adds a request-param fallback so the
 * token also survives when the header doesn't.
 *
 * Kept outside vendor/ on purpose: vendor/studio-42/elfinder is reinstalled
 * by composer and any direct edit there would be silently lost.
 */
class ElFinderConnector extends \elFinderConnector
{
    protected static $csrfParamName = '_csrf';

    protected function getRequestHeader($name)
    {
        $value = parent::getRequestHeader($name);
        if ($value === '' && $name === self::$csrfHeaderName && isset($_REQUEST[self::$csrfParamName])) {
            $value = trim($_REQUEST[self::$csrfParamName]);
        }

        return $value;
    }
}
