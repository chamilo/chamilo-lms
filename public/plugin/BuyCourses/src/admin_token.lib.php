<?php

declare(strict_types=1);

/* For license terms, see /license.txt */

/**
 * Anti-CSRF token of the BuyCourses administration pages.
 *
 * The pages under public/plugin are legacy pages: CsrfProtectionListener never sees them, so every
 * state-changing request (completing or cancelling a sale, deleting an account, saving a setting...)
 * must carry this token. It has its own namespace and is never rotated by a request, so several tabs
 * and a page that redirects after saving keep working.
 */
const BUYCOURSES_ADMIN_TOKEN_PREFIX = 'buycourses_admin';

if (!function_exists('buycoursesAdminTokenName')) {
    /**
     * Name of the request field carrying the token (same rule as Security::generateSecTokenVariable()).
     */
    function buycoursesAdminTokenName(): string
    {
        return BUYCOURSES_ADMIN_TOKEN_PREFIX.'_sec_token';
    }

    function buycoursesAdminToken(): string
    {
        return (string) Security::get_existing_token(BUYCOURSES_ADMIN_TOKEN_PREFIX);
    }

    function buycoursesAdminTokenIsValid(): bool
    {
        return Security::check_token('request', null, BUYCOURSES_ADMIN_TOKEN_PREFIX);
    }

    /**
     * "name=token" query fragment to append to the links of state-changing actions.
     */
    function buycoursesAdminTokenQuery(): string
    {
        return http_build_query([buycoursesAdminTokenName() => buycoursesAdminToken()]);
    }

    /**
     * Adds the token as a hidden field of a form.
     */
    function buycoursesAdminTokenField(FormValidator $form): void
    {
        $form->addHidden(buycoursesAdminTokenName(), buycoursesAdminToken());
    }

    /**
     * The current page with its (re-encoded) query string: where a refused request goes back to.
     */
    function buycoursesSelfUrl(): string
    {
        $query = http_build_query($_GET);

        return api_get_self().('' !== $query ? '?'.$query : '');
    }

    /**
     * Stops a state-changing request that does not carry a valid token: flash message, redirect, exit.
     */
    function buycoursesRequireAdminToken(?string $redirectUrl = null): void
    {
        if (buycoursesAdminTokenIsValid()) {
            return;
        }

        Display::addFlash(Display::return_message(get_lang('Not validated'), 'warning'));
        header('Location: '.($redirectUrl ?? buycoursesSelfUrl()));

        exit;
    }

    /**
     * Same as buycoursesRequireAdminToken() but only for POST requests (pages that also render on GET).
     */
    function buycoursesRequireAdminTokenOnPost(?string $redirectUrl = null): void
    {
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
            buycoursesRequireAdminToken($redirectUrl);
        }
    }
}
