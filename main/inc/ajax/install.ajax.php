<?php
/* For licensing terms, see /license.txt */

use GuzzleHttp\Client;

/**
 * Responses to AJAX calls for install.
 */
require_once __DIR__.'/../../../vendor/autoload.php';

$action = $_GET['a'];

switch ($action) {
    case 'send_contact_information':
        if (!empty($_POST)) {
            // get params from contact form
            $person_name = $_POST['person_name'];
            $person_email = $_POST['person_email'];
            $person_role = $_POST['person_role'];
            $financial_decision = $_POST['financial_decision'];
            $contact_language = $_POST['language'];
            $company_name = $_POST['company_name'];
            $company_activity = $_POST['company_activity'];
            $company_country = $_POST['company_country'];
            $company_city = $_POST['company_city'];

            // validating required fields
            $a_required_fields = [$person_name, $person_role, $company_name, $company_activity, $company_country];
            $required_field_error = false;
            foreach ($a_required_fields as $required_file) {
                if (trim($required_file) === '') {
                    $required_field_error = true;
                    break;
                }
            }

            // Return error if any of the required fields is empty
            if ($required_field_error) {
                echo 'required_field_error';
                break;
            } else {
                // save contact information with web service
                // create a client

                $url = 'https://version.chamilo.org/contactv2.php';
                $options = [
                    'verify' => false,
                ];

                $urlValidated = false;
                try {
                    $client = new GuzzleHttp\Client();
                    $res = $client->request('GET', $url, $options);
                    if ($res->getStatusCode() == '200' || $res->getStatusCode() == '301') {
                        $urlValidated = true;
                    }
                } catch (Exception $e) {
                    error_log("Could not check $url from ".__FILE__);
                    break;
                }

                $data = [
                    'person_name' => $person_name,
                    'person_email' => $person_email,
                    'person_role' => $person_role,
                    'financial_decision' => $financial_decision,
                    'contact_language' => $contact_language,
                    'company_name' => $company_name,
                    'company_activity' => $company_activity,
                    'company_country' => $company_country,
                    'company_city' => $company_city,
                ];

                $client = new GuzzleHttp\Client();
                $options['query'] = $data;
                $res = $client->request('GET', $url, $options);
                if ($res->getStatusCode() == '200') {
                    echo '1';
                }
            }
        }
        break;
    default:
        echo '';
}
exit;
