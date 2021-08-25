<?php
namespace Packback\Lti1p3;

use Packback\Lti1p3\Interfaces\Cache;
use Packback\Lti1p3\Interfaces\Cookie;
use Packback\Lti1p3\Interfaces\Database;

class LtiOidcLogin
{
    public const COOKIE_PREFIX = 'lti1p3_';

    public const ERROR_MSG_LAUNCH_URL = 'No launch URL configured';
    public const ERROR_MSG_ISSUER = 'Could not find issuer';
    public const ERROR_MSG_LOGIN_HINT = 'Could not find login hint';
    public const ERROR_MSG_REGISTRATION = 'Could not find registration details';

    private $db;
    private $cache;
    private $cookie;

    /**
     * Constructor
     *
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     * @param Cookie    $cookie     Instance of the Cookie interface used to set and read cookies. Will default to using $_COOKIE and setcookie.
     */
    function __construct(Database $database, Cache $cache = null, Cookie $cookie = null)
    {
        $this->db = $database;
        $this->cache = $cache;
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database, Cache $cache = null, Cookie $cookie = null)
    {
        return new LtiOidcLogin($database, $cache, $cookie);
    }

    /**
     * Calculate the redirect location to return to based on an OIDC third party initiated login request.
     *
     * @param string        $launch_url URL to redirect back to after the OIDC login. This URL must match exactly a URL white listed in the platform.
     * @param array|string  $request    An array of request parameters. If not set will default to $_REQUEST.
     *
     * @return Redirect Returns a redirect object containing the fully formed OIDC login URL.
     */
    public function doOidcLoginRedirect($launch_url, array $request = null)
    {

        if ($request === null) {
            $request = $_REQUEST;
        }

        if (empty($launch_url)) {
            throw new OidcException(static::ERROR_MSG_LAUNCH_URL, 1);
        }

        // Validate Request Data.
        $registration = $this->validateOidcLogin($request);

        /*
         * Build OIDC Auth Response.
         */

        // Generate State.
        // Set cookie (short lived)
        $state = str_replace('.', '_', uniqid('state-', true));
        $this->cookie->setCookie(static::COOKIE_PREFIX.$state, $state, 60);

        // Generate Nonce.
        $nonce = uniqid('nonce-', true);
        $this->cache->cacheNonce($nonce);

        // Build Response.
        $auth_params = [
            'scope'         => 'openid', // OIDC Scope.
            'response_type' => 'id_token', // OIDC response is always an id token.
            'response_mode' => 'form_post', // OIDC response is always a form post.
            'prompt'        => 'none', // Don't prompt user on redirect.
            'client_id'     => $registration->getClientId(), // Registered client id.
            'redirect_uri'  => $launch_url, // URL to return to after login.
            'state'         => $state, // State to identify browser session.
            'nonce'         => $nonce, // Prevent replay attacks.
            'login_hint'    => $request['login_hint'] // Login hint to identify platform session.
        ];

        // Pass back LTI message hint if we have it.
        if (isset($request['lti_message_hint'])) {
            // LTI message hint to identify LTI context within the platform.
            $auth_params['lti_message_hint'] = $request['lti_message_hint'];
        }

        $auth_login_return_url = $registration->getAuthLoginUrl() . '?' . http_build_query($auth_params);

        // Return auth redirect.
        return new Redirect($auth_login_return_url, http_build_query($request));

    }

    public function validateOidcLogin($request)
    {

        // Validate Issuer.
        if (empty($request['iss'])) {
            throw new OidcException(static::ERROR_MSG_ISSUER, 1);
        }

        // Validate Login Hint.
        if (empty($request['login_hint'])) {
            throw new OidcException(static::ERROR_MSG_LOGIN_HINT, 1);
        }

        // Fetch Registration Details.
        $registration = $this->db->findRegistrationByIssuer($request['iss'], $request['client_id'] ?? null);

        // Check we got something.
        if (empty($registration)) {
            throw new OidcException(static::ERROR_MSG_REGISTRATION, 1);
        }

        // Return Registration.
        return $registration;
    }
}
