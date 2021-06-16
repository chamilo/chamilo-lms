<?php
namespace IMSGlobal\LTI;

interface Database {
    public function find_registration_by_issuer($iss);
    public function find_deployment($iss, $deployment_id);
}

?>