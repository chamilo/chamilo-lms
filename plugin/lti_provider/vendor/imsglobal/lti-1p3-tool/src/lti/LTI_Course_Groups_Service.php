<?php
namespace IMSGlobal\LTI;

class LTI_Course_Groups_Service {

    private $service_connector;
    private $service_data;

    public function __construct(LTI_Service_Connector $service_connector, $service_data) {
        $this->service_connector = $service_connector;
        $this->service_data = $service_data;
    }

    public function get_groups() {

        $groups = [];

        $next_page = $this->service_data['context_groups_url'];

        while ($next_page) {
            $page = $this->service_connector->make_service_request(
                $this->service_data['scope'],
                'GET',
                $next_page,
                null,
                null,
                'application/vnd.ims.lti-gs.v1.contextgroupcontainer+json'
            );

            $groups = array_merge($groups, $page['body']['groups']);

            $next_page = false;
            foreach($page['headers'] as $header) {
                if (preg_match(LTI_Service_Connector::NEXT_PAGE_REGEX, $header, $matches)) {
                    $next_page = $matches[1];
                    break;
                }
            }
        }
        return $groups;

    }

    public function get_sets() {

        $sets = [];

        // Sets are optional.
        if (!isset($this->service_data['context_group_sets_url'])) {
            return [];
        }

        $next_page = $this->service_data['context_group_sets_url'];

        while ($next_page) {
            $page = $this->service_connector->make_service_request(
                $this->service_data['scope'],
                'GET',
                $next_page,
                null,
                null,
                'application/vnd.ims.lti-gs.v1.contextgroupcontainer+json'
            );

            $sets = array_merge($sets, $page['body']['sets']);

            $next_page = false;
            foreach($page['headers'] as $header) {
                if (preg_match(LTI_Service_Connector::NEXT_PAGE_REGEX, $header, $matches)) {
                    $next_page = $matches[1];
                    break;
                }
            }
        }
        return $sets;

    }

    public function get_groups_by_set() {
        $groups = $this->get_groups();
        $sets = $this->get_sets();

        $groups_by_set = [];
        $unsetted = [];

        foreach ($sets as $key => $set) {
            $groups_by_set[$set['id']] = $set;
            $groups_by_set[$set['id']]['groups'] = [];
        }

        foreach ($groups as $key => $group) {
            if (isset($group['set_id']) && isset($groups_by_set[$group['set_id']])) {
                $groups_by_set[$group['set_id']]['groups'][$group['id']] = $group;
            } else {
                $unsetted[$group['id']] = $group;
            }
        }

        if (!empty($unsetted)) {
            $groups_by_set['none'] = [
                "name" => "None",
                "id" => "none",
                "groups" => $unsetted,
            ];
        }

        return $groups_by_set;
    }
}
?>