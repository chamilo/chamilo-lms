<?php

/* For licensing terms, see /license.txt */

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Responses to AJAX calls for installation.
 */
require_once __DIR__.'/../../../../vendor/autoload.php';

$request = Request::createFromGlobals();

$action = $request->query->getString('a');

switch ($action) {
    case 'send_contact_information':
        if ($request->isXmlHttpRequest()) {
            // get params from contact form
            $person_name = $request->request->get('person_name');
            $person_email = $request->request->get('person_email');
            $person_role = $request->request->get('person_role');
            $financial_decision = $request->request->get('financial_decision');
            $contact_language = $request->request->get('language');
            $company_name = $request->request->get('company_name');
            $company_activity = $request->request->get('company_activity');
            $company_country = $request->request->get('company_country');
            $company_city = $request->request->get('company_city');

            // validating required fields
            $a_required_fields = [$person_name, $person_role, $company_name, $company_activity, $company_country];
            $required_field_error = false;
            foreach ($a_required_fields as $required_file) {
                if ('' === trim($required_file)) {
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
                    $client = new Client();
                    $res = $client->request('GET', $url, $options);
                    if ('200' == $res->getStatusCode() || '301' == $res->getStatusCode()) {
                        $urlValidated = true;
                    }
                } catch (Exception|GuzzleException $e) {
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

                $client = new Client();
                $options['query'] = $data;
                $res = $client->request('GET', $url, $options);
                if ('200' == $res->getStatusCode()) {
                    echo '1';
                }
            }
        }
        break;

    case 'test_mailer':
        if ($request->isXmlHttpRequest()) {
            $mailerDsn = $request->request->get('mailerDsn');
            $mailerTestFrom = $request->request->get('mailerTestFrom');
            $mailerFromEmail = $request->request->get('mailerFromEmail');
            $mailerFromName = $request->request->get('mailerFromName');

            try {
                $transport = Transport::fromDsn($mailerDsn);

                $mailer = new Mailer($transport);

                $email = (new Email())
                    ->from(new Address($mailerTestFrom))
                    ->to($mailerFromEmail ?: 'test@example.com')
                    ->subject('Chamilo Mail Test')
                    ->text('This is a test e-mail sent from Chamilo installation wizard.');

                $mailer->send($email);

                echo json_encode(['success' => true]);
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        break;
    default:
        echo '';
}
exit;
