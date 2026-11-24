<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\Lrs;

use Symfony\Component\HttpFoundation\Request;
use XApiPlugin;

/**
 * Base controller for xAPI legacy LRS endpoints.
 */
abstract class BaseController
{
    // Request attribute holding the launch that per-launch credentials are limited to.
    public const LAUNCH_SCOPE = '_xapi_launch_scope';

    protected Request $httpRequest;

    public function __construct(Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * The launch the caller is limited to, or null for a full LRS client (configured credentials).
     *
     * @return array{agent: array<string, mixed>, activity: string, registration: string}|null
     */
    protected function getLaunchScope(): ?array
    {
        $scope = $this->httpRequest->attributes->get(self::LAUNCH_SCOPE);

        return \is_array($scope) ? $scope : null;
    }

    /**
     * Content launched for one learner may only act as that learner.
     *
     * @param mixed $agent the agent of the request or of a statement, as decoded from JSON
     */
    protected function isAgentOutsideLaunch(mixed $agent): bool
    {
        $scope = $this->getLaunchScope();
        if (null === $scope) {
            return false;
        }

        return !\is_array($agent)
            || XApiPlugin::getAgentIdentifier($agent) !== XApiPlugin::getAgentIdentifier($scope['agent']);
    }
}
