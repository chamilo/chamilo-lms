<?php
namespace Packback\Lti1p3;

use Packback\Lti1p3\Interfaces\LtiServiceConnectorInterface;

class LtiNamesRolesProvisioningService
{

    private $service_connector;
    private $service_data;

    public function __construct(LtiServiceConnectorInterface $service_connector, array $service_data)
    {
        $this->service_connector = $service_connector;
        $this->service_data = $service_data;
    }

    public function getMembers()
    {
        $members = [];

        $next_page = $this->service_data['context_memberships_url'];

        while ($next_page) {
            $page = $this->service_connector->makeServiceRequest(
                [LtiConstants::NRPS_SCOPE_MEMBERSHIP_READONLY],
                LtiServiceConnector::METHOD_GET,
                $next_page,
                null,
                null,
                'application/vnd.ims.lti-nrps.v2.membershipcontainer+json'
            );

            $members = array_merge($members, $page['body']['members']);

            $next_page = false;
            foreach($page['headers'] as $header) {
                if (preg_match(LtiServiceConnector::NEXT_PAGE_REGEX, $header, $matches)) {
                    $next_page = $matches[1];
                    break;
                }
            }
        }

        return $members;
    }
}
