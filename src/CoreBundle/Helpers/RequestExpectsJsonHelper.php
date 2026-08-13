<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Symfony\Component\HttpFoundation\Request;

/**
 * Tells whether a request expects a machine-readable JSON answer rather than an
 * HTML page.
 *
 * Denials must be answered differently depending on the caller: a browser
 * navigation is best served a redirect to the login page or an error page,
 * while the Vue SPA and any other XHR consumer need a status code they can
 * branch on. Answering a redirect to the latter produces a 200 carrying the
 * login page, which is indistinguishable from a successful response.
 *
 * Both JSON flavours must be listed explicitly: "application/ld+json" (the
 * Accept the SPA sends to API Platform routes) does not contain the
 * "application/json" substring.
 */
class RequestExpectsJsonHelper
{
    public static function expectsJson(Request $request): bool
    {
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        $accept = (string) $request->headers->get('Accept');

        return str_contains($accept, 'application/json') || str_contains($accept, 'application/ld+json');
    }
}
