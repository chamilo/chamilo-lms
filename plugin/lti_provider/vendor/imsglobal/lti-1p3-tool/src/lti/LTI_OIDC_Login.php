<?php
namespace IMSGlobal\LTI;

class LTI_OIDC_Login {

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
    function __construct(Database $database, Cache $cache = null, Cookie $cookie = null) {
        $this->db = $database;
        if ($cache === null) {
            $cache = new Cache();
        }
        $this->cache = $cache;

        if ($cookie === null) {
            $cookie = new Cookie();
        }
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database, Cache $cache = null, Cookie $cookie = null) {
        return new LTI_OIDC_Login($database, $cache, $cookie);
    }

    /**
     * Calculate the redirect location to return to based on an OIDC third party initiated login request.
     *
     * @param string        $launch_url URL to redirect back to after the OIDC login. This URL must match exactly a URL white listed in the platform.
     * @param array|string  $request    An array of request parameters. If not set will default to $_REQUEST.
     *
     * @return Redirect Returns a redirect object containing the fully formed OIDC login URL.
     */
    public function do_oidc_login_redirect($launch_url, array $request = null) {

        if ($request === null) {
            $request = $_REQUEST;
        }

        if (empty($launch_url)) {
            throw new OIDC_Exception("No launch URL configured", 1);
        }

        // Validate Request Data.
        $registration = $this->validate_oidc_login($request);

        /*
         * Build OIDC Auth Response.
         */

        // Generate State.
        // Set cookie (short lived)
        $state = str_replace('.', '_', uniqid('state-', true));
        $this->cookie->set_cookie("lti_$state", $state, 60);

        // Generate Nonce.
        $nonce = uniqid('nonce-', true);
        $this->cache->cache_nonce($nonce);

        // Build Response.
        $auth_params = [
            'scope'         => 'openid', // OIDC Scope.
            'response_type' => 'id_token', // OIDC response is always an id token.
            'response_mode' => 'form_post', // OIDC response is always a form post.
            'prompt'        => 'none', // Don't prompt user on redirect.
            'client_id'     => $registration->get_client_id(), // Registered client id.
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

        $auth_login_return_url = $registration->get_auth_login_url() . "?" . http_build_query($auth_params);
        // Return auth redirect.
        return new Redirect($auth_login_return_url, http_build_query($request));

    }

    protected function validate_oidc_login($request) {

        error_log("LTI_DEBUG :: provider :: validate_oidc_loginT : ".print_r($request, true));

        // Validate Issuer.
        if (empty($request['iss'])) {
            throw new OIDC_Exception("Could not find issuer", 1);
        }

        // Validate Login Hint.
        if (empty($request['login_hint'])) {
            throw new OIDC_Exception("Could not find login hint", 1);
        }

        // Fetch Registration Details.
        $registration = $this->db->find_registration_by_issuer($request['iss']);

        // Check we got something.
        if (empty($registration)) {
            throw new OIDC_Exception("Could not find registration details", 1);
        }

        // Return Registration.
        return $registration;
    }
}
