<?php
namespace Packback\Lti1p3;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

use Packback\Lti1p3\MessageValidators\DeepLinkMessageValidator;
use Packback\Lti1p3\MessageValidators\ResourceMessageValidator;
use Packback\Lti1p3\MessageValidators\SubmissionReviewMessageValidator;
use Packback\Lti1p3\Interfaces\Cache;
use Packback\Lti1p3\Interfaces\Cookie;
use Packback\Lti1p3\Interfaces\Database;

class LtiMessageLaunch
{

    private $db;
    private $cache;
    private $request;
    private $cookie;
    private $jwt;
    private $registration;
    private $launch_id;

    /**
     * Constructor
     *
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches.
     * @param Cookie    $cookie     Instance of the Cookie interface used to set and read cookies.
     */
    function __construct(Database $database, Cache $cache = null, Cookie $cookie = null) {
        $this->db = $database;

        $this->launch_id = uniqid("lti1p3_launch_", true);

        $this->cache = $cache;
        $this->cookie = $cookie;
    }

    /**
     * Static function to allow for method chaining without having to assign to a variable first.
     */
    public static function new(Database $database, Cache $cache = null, Cookie $cookie = null) {
        return new LtiMessageLaunch($database, $cache, $cookie);
    }

    /**
     * Load an LtiMessageLaunch from a Cache using a launch id.
     *
     * @param string    $launch_id  The launch id of the LtiMessageLaunch object that is being pulled from the cache.
     * @param Database  $database   Instance of the database interface used for looking up registrations and deployments.
     * @param Cache     $cache      Instance of the Cache interface used to loading and storing launches. If non is provided launch data will be store in $_SESSION.
     *
     * @throws LtiException        Will throw an LtiException if validation fails or launch cannot be found.
     * @return LtiMessageLaunch   A populated and validated LtiMessageLaunch.
     */
    public static function fromCache($launch_id, Database $database, Cache $cache = null) {
        $new = new LtiMessageLaunch($database, $cache, null);
        $new->launch_id = $launch_id;
        $new->jwt = [ 'body' => $new->cache->getLaunchData($launch_id) ];
        return $new->validateRegistration();
    }

    /**
     * Validates all aspects of an incoming LTI message launch and caches the launch if successful.
     *
     * @param array|string  $request    An array of post request parameters. If not set will default to $_POST.
     *
     * @throws LtiException        Will throw an LtiException if validation fails.
     * @return LtiMessageLaunch   Will return $this if validation is successful.
     */
    public function validate(array $request = null)
    {

        if ($request === null) {
            $request = $_POST;
        }
        $this->request = $request;

        return $this->validateState()
            ->validateJwtFormat()
            ->validateNonce()
            ->validateRegistration()
            ->validateJwtSignature()
            ->validateDeployment()
            ->validateMessage()
            ->cacheLaunchData();
    }

    /**
     * Returns whether or not the current launch can use the names and roles service.
     *
     * @return boolean  Returns a boolean indicating the availability of names and roles.
     */
    public function hasNrps()
    {
        return !empty($this->jwt['body'][LtiConstants::NRPS_CLAIM_SERVICE]['context_memberships_url']);
    }

    /**
     * Fetches an instance of the names and roles service for the current launch.
     *
     * @return LtiNamesRolesProvisioningService An instance of the names and roles service that can be used to make calls within the scope of the current launch.
     */
    public function getNrps()
    {
        return new LtiNamesRolesProvisioningService(
            new LtiServiceConnector($this->registration),
            $this->jwt['body'][LtiConstants::NRPS_CLAIM_SERVICE]);
    }

    /**
     * Returns whether or not the current launch can use the groups service.
     *
     * @return boolean  Returns a boolean indicating the availability of groups.
     */
    public function hasGs()
    {
        return !empty($this->jwt['body'][LtiConstants::GS_CLAIM_SERVICE]['context_groups_url']);
    }

    /**
     * Fetches an instance of the groups service for the current launch.
     *
     * @return LtiCourseGroupsService An instance of the groups service that can be used to make calls within the scope of the current launch.
     */
    public function getGs()
    {
        return new LtiCourseGroupsService(
            new LtiServiceConnector($this->registration),
            $this->jwt['body'][LtiConstants::GS_CLAIM_SERVICE]);
    }

    /**
     * Returns whether or not the current launch can use the assignments and grades service.
     *
     * @return boolean  Returns a boolean indicating the availability of assignments and grades.
     */
    public function hasAgs()
    {
        return !empty($this->jwt['body'][LtiConstants::AGS_CLAIM_ENDPOINT]);
    }

    /**
     * Fetches an instance of the assignments and grades service for the current launch.
     *
     * @return LtiAssignmentsGradesService An instance of the assignments an grades service that can be used to make calls within the scope of the current launch.
     */
    public function getAgs()
    {
        return new LtiAssignmentsGradesService(
            new LtiServiceConnector($this->registration),
            $this->jwt['body'][LtiConstants::AGS_CLAIM_ENDPOINT]);
    }

    /**
     * Fetches a deep link that can be used to construct a deep linking response.
     *
     * @return LtiDeepLink An instance of a deep link to construct a deep linking response for the current launch.
     */
    public function getDeepLink()
    {
        return new LtiDeepLink(
            $this->registration,
            $this->jwt['body'][LtiConstants::DEPLOYMENT_ID],
            $this->jwt['body'][LtiConstants::DL_DEEP_LINK_SETTINGS]);
    }

    /**
     * Returns whether or not the current launch is a deep linking launch.
     *
     * @return boolean  Returns true if the current launch is a deep linking launch.
     */
    public function isDeepLinkLaunch()
    {
        return $this->jwt['body'][LtiConstants::MESSAGE_TYPE] === 'LtiDeepLinkingRequest';
    }

    /**
     * Returns whether or not the current launch is a submission review launch.
     *
     * @return boolean  Returns true if the current launch is a submission review launch.
     */
    public function isSubmissionReviewLaunch()
    {
        return $this->jwt['body'][LtiConstants::MESSAGE_TYPE] === 'LtiSubmissionReviewRequest';
    }

    /**
     * Returns whether or not the current launch is a resource launch.
     *
     * @return boolean  Returns true if the current launch is a resource launch.
     */
    public function isResourceLaunch()
    {
        return $this->jwt['body'][LtiConstants::MESSAGE_TYPE] === 'LtiResourceLinkRequest';
    }

    /**
     * Fetches the decoded body of the JWT used in the current launch.
     *
     * @return array|object Returns the decoded json body of the launch as an array.
     */
    public function getLaunchData()
    {
        return $this->jwt['body'];
    }

    /**
     * Get the unique launch id for the current launch.
     *
     * @return string   A unique identifier used to re-reference the current launch in subsequent requests.
     */
    public function getLaunchId()
    {
        return $this->launch_id;
    }

    private function getPublicKey() {
        $key_set_url = $this->registration->getKeySetUrl();

        // Download key set
        $public_key_set = json_decode(file_get_contents($key_set_url), true);

        if (empty($public_key_set)) {
            // Failed to fetch public keyset from URL.
            throw new LtiException('Failed to fetch public key', 1);
        }

        // Find key used to sign the JWT (matches the KID in the header)
        foreach ($public_key_set['keys'] as $key) {
            if ($key['kid'] == $this->jwt['header']['kid']) {
                try {
                    return openssl_pkey_get_details(
                        JWK::parseKeySet([
                            'keys' => [$key]
                        ])[$key['kid']]
                    );
                } catch(\Exception $e) {
                    return false;
                }
            }
        }

        // Could not find public key with a matching kid and alg.
        throw new LtiException('Unable to find public key', 1);
    }

    private function cacheLaunchData() {
        $this->cache->cacheLaunchData($this->launch_id, $this->jwt['body']);
        return $this;
    }

    private function validateState() {
        // Check State for OIDC.
        if ($this->cookie->getCookie(LtiOidcLogin::COOKIE_PREFIX . $this->request['state']) !== $this->request['state']) {
            // Error if state doesn't match
            throw new LtiException('State not found', 1);
        }
        return $this;
    }

    private function validateJwtFormat() {
        $jwt = $this->request['id_token'];

        if (empty($jwt)) {
            throw new LtiException('Missing id_token', 1);
        }

        // Get parts of JWT.
        $jwt_parts = explode('.', $jwt);

        if (count($jwt_parts) !== 3) {
            // Invalid number of parts in JWT.
            throw new LtiException('Invalid id_token, JWT must contain 3 parts', 1);
        }

        // Decode JWT headers.
        $this->jwt['header'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[0]), true);
        // Decode JWT Body.
        $this->jwt['body'] = json_decode(JWT::urlsafeB64Decode($jwt_parts[1]), true);

        return $this;
    }

    private function validateNonce() {
        if (!isset($this->jwt['body']['nonce'])) {
            throw new LtiException('Missing Nonce');
        }
        if (!$this->cache->checkNonce($this->jwt['body']['nonce'])) {
            throw new LtiException('Invalid Nonce');
        }
        return $this;
    }

    private function validateRegistration() {
        // Find registration.
        $client_id = is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
        $this->registration = $this->db->findRegistrationByIssuer($this->jwt['body']['iss'], $client_id);

        if (empty($this->registration)) {
            throw new LtiException('Registration not found.', 1);
        }

        // Check client id.
        if ( $client_id !== $this->registration->getClientId()) {
            // Client not registered.
            throw new LtiException('Client id not registered for this issuer', 1);
        }

        return $this;
    }

    private function validateJwtSignature() {
        if (!isset($this->jwt['header']['kid'])) {
            throw new LtiException('No KID specified in the JWT Header');
        }

        // Fetch public key.
        $public_key = $this->getPublicKey();

        // Validate JWT signature
        try {
            JWT::decode($this->request['id_token'], $public_key['key'], array('RS256'));
        } catch(ExpiredException $e) {
            // Error validating signature.
            throw new LtiException('Invalid signature on id_token', 1);
        }

        return $this;
    }

    private function validateDeployment() {
        if (!isset($this->jwt['body'][LtiConstants::DEPLOYMENT_ID])) {
            throw new LtiException('No deployment ID was specified', 1);
        }

        // Find deployment.
        $client_id = is_array($this->jwt['body']['aud']) ? $this->jwt['body']['aud'][0] : $this->jwt['body']['aud'];
        $deployment = $this->db->findDeployment($this->jwt['body']['iss'], $this->jwt['body'][LtiConstants::DEPLOYMENT_ID], $client_id);

        if (empty($deployment)) {
            // deployment not recognized.
            throw new LtiException('Unable to find deployment', 1);
        }

        return $this;
    }

    private function validateMessage() {
        if (empty($this->jwt['body'][LtiConstants::MESSAGE_TYPE])) {
            // Unable to identify message type.
            throw new LtiException('Invalid message type', 1);
        }

        /**
         * @todo Fix this nonsense
         */

        // Create instances of all validators
        $validators = [
            new DeepLinkMessageValidator,
            new ResourceMessageValidator,
            new SubmissionReviewMessageValidator,
        ];

        $message_validator = false;
        foreach ($validators as $validator) {
            if ($validator->canValidate($this->jwt['body'])) {
                if ($message_validator !== false) {
                    // Can't have more than one validator apply at a time.
                    throw new LtiException('Validator conflict', 1);
                }
                $message_validator = $validator;
            }
        }

        if ($message_validator === false) {
            throw new LtiException('Unrecognized message type.', 1);
        }

        if (!$message_validator->validate($this->jwt['body'])) {
            throw new LtiException('Message validation failed.', 1);
        }

        return $this;

    }
}

