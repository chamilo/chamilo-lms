<?php
/* For licensing terms, see /license.txt */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LtiNamesRoleProvisioningService.
 */
class LtiNamesRoleProvisioningService extends LtiAdvantageService
{
    const NRPS_NONE = 'none';
    const NRPS_CONTEXT_MEMBERSHIP = 'simple';

    const SCOPE_CONTEXT_MEMBERSHIP_READ = 'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';

    const TYPE_MEMBERSHIP_CONTAINER = 'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

    const USER_STATUS_ACTIVE = 'Active';
    const USER_STATUS_INACTIVE = 'Inactive';

    /**
     * {@inheritDoc}
     */
    public function getAllowedScopes()
    {
        return [
            self::SCOPE_CONTEXT_MEMBERSHIP_READ,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getResource(Request $request, JsonResponse $response)
    {
        $parts = explode('/', $request->getPathInfo());
        $parts = array_filter($parts);

        $resource = null;

        if (isset($parts[1], $parts[2]) &&
            (int) $parts[1] > 0 && 'memberships' === $parts[2]
        ) {
            $resource = new LtiContextMembershipResource(
                $request->query->getInt('t'),
                $parts[1],
                $request->query->getInt('s')
            );
        }

        if (!$resource) {
            throw new NotFoundHttpException('Resource  not found for Name and Role Provisioning.');
        }

        return $resource
            ->setRequest($request)
            ->setResponse($response);
    }

    /**
     * @param int   $toolId
     * @param int   $courseId
     * @param int   $sessionId
     * @param array $extraParams
     *
     * @return string
     */
    public static function getUrl($toolId, $courseId, $sessionId = 0, $extraParams = [])
    {
        $base = api_get_path(WEB_PLUGIN_PATH).'ims_lti/nrps2.php';
        $resource = str_replace(
            'context_id',
            $courseId,
            LtiContextMembershipResource::URL_TEMPLATE
        );
        $query = http_build_query(['s' => $sessionId, 't' => $toolId]);

        if ($extraParams) {
            $query .= '&'.http_build_query($extraParams);
        }

        return "$base$resource?$query";
    }
}
