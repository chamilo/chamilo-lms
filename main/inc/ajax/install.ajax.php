<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls for install
 */

//require_once '../global.inc.php';

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
                            $a_required_fields = array($person_name, $person_role, $company_name, $company_activity, $company_country);
                            $required_field_error = false;
                            foreach($a_required_fields as $required_file) {
                                if (trim($required_file) === '') {
                                    $required_field_error = true;
                                    break;
                                }
                            }

                            if ($required_field_error) {
                                echo 'required_field_error';
                            } else {

                                // save contact information with web service                                
                                require_once '../lib/nusoap/nusoap.php';

                                // create a client
                                $client = new nusoap_client('http://version.chamilo.org/contact.php?wsdl', true);

                                // call method ws_add_contact_information
                                $contact_params = array(
                                                        'person_name' => $person_name,
                                                        'person_email' => $person_email,
                                                        'person_role' => $person_role,
                                                        'financial_decision' => $financial_decision,
                                                        'contact_language' => $contact_language,
                                                        'company_name' => $company_name,
                                                        'company_activity' => $company_activity,
                                                        'company_country' => $company_country,
                                                        'company_city' => $company_city
                                                    );

                                $result = $client->call('ws_add_contact_information', array('contact_params' => $contact_params));
								
								echo $result;
							}

				}
			break;	
	default:
		echo '';
}
exit;