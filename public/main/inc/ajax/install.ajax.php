<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

use Chamilo\CoreBundle\Framework\Container;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Responses to AJAX calls for installation.
 */
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Send a JSON response and stop execution.
 *
 * @param array<string,mixed> $payload
 */
function json_response(array $payload, int $statusCode = 200): void
{
    // Always return JSON for AJAX consumers.
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    http_response_code($statusCode);

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Safe access to request data (works even if the Container request is not available).
 */
function get_query_param(string $name, string $default = ''): string
{
    if (isset($_GET[$name])) {
        return (string) $_GET[$name];
    }

    return $default;
}

function get_post_param(string $name, string $default = ''): string
{
    if (isset($_POST[$name])) {
        return (string) $_POST[$name];
    }

    return $default;
}

try {
    $request = null;

    try {
        $request = Container::getRequest();
    } catch (\Throwable $e) {
        // Container request might not be available in some install contexts.
        $request = null;
    }

    $action = '';
    if ($request) {
        $action = (string) $request->query->get('a', '');
    } else {
        $action = get_query_param('a', '');
    }

    switch ($action) {
        case 'send_contact_information':
            // get params from contact form
            $person_name = $request ? (string) $request->request->get('person_name') : get_post_param('person_name');
            $person_email = $request ? (string) $request->request->get('person_email') : get_post_param('person_email');
            $person_role = $request ? (string) $request->request->get('person_role') : get_post_param('person_role');
            $financial_decision = $request ? (string) $request->request->get('financial_decision') : get_post_param('financial_decision');
            $contact_language = $request ? (string) $request->request->get('language') : get_post_param('language');
            $company_name = $request ? (string) $request->request->get('company_name') : get_post_param('company_name');
            $company_activity = $request ? (string) $request->request->get('company_activity') : get_post_param('company_activity');
            $company_country = $request ? (string) $request->request->get('company_country') : get_post_param('company_country');
            $company_city = $request ? (string) $request->request->get('company_city') : get_post_param('company_city');

            // validating required fields
            $a_required_fields = [$person_name, $person_role, $company_name, $company_activity, $company_country];
            $required_field_error = false;
            foreach ($a_required_fields as $required_field) {
                if ('' === trim((string) $required_field)) {
                    $required_field_error = true;
                    break;
                }
            }

            // Return error if any of the required fields is empty
            if ($required_field_error) {
                echo 'required_field_error';
                break;
            }

            // save contact information with web service
            $url = 'https://version.chamilo.org/contactv2.php';
            $options = [
                'verify' => false,
            ];

            $urlValidated = false;
            try {
                $client = new Client(['timeout' => 8.0]);
                $res = $client->request('GET', $url, $options);
                if ('200' == $res->getStatusCode() || '301' == $res->getStatusCode()) {
                    $urlValidated = true;
                }
            } catch (\Throwable|GuzzleException $e) {
                error_log('Could not check remote contact endpoint: ' . $url);
                break;
            }

            if (!$urlValidated) {
                // Keep legacy behavior: just stop silently.
                error_log('Remote contact endpoint not validated: ' . $url);
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

            try {
                $client = new Client(['timeout' => 8.0]);
                $options['query'] = $data;
                $res = $client->request('GET', $url, $options);
                if ('200' == $res->getStatusCode()) {
                    echo '1';
                }
            } catch (\Throwable|GuzzleException $e) {
                error_log('Could not send contact information: ' . $e->getMessage());
            }
            break;

        case 'test_mailer':
            // Always return JSON for this action.
            $mailerDsn = $request ? (string) $request->request->get('mailerDsn') : get_post_param('mailerDsn');
            $mailerTestDestination = $request ? (string) $request->request->get('mailerTestDestination') : get_post_param('mailerTestDestination');
            $mailerFromEmail = $request ? (string) $request->request->get('mailerFromEmail') : get_post_param('mailerFromEmail');
            $mailerFromName = $request ? (string) $request->request->get('mailerFromName') : get_post_param('mailerFromName');

            $mailerDsn = trim($mailerDsn);
            $mailerTestDestination = trim($mailerTestDestination);
            $mailerFromEmail = trim($mailerFromEmail);
            $mailerFromName = trim($mailerFromName);

            if ($mailerDsn === '') {
                json_response([
                    'success' => false,
                    'message' => 'Missing mailer DSN.',
                ], 400);
            }

            if ($mailerFromEmail === '' || !filter_var($mailerFromEmail, FILTER_VALIDATE_EMAIL)) {
                json_response([
                    'success' => false,
                    'message' => 'Invalid "From" email address. Please provide a valid email (e.g. you@gmail.com).',
                ], 400);
            }

            if ($mailerFromName === '') {
                $mailerFromName = 'Chamilo';
            }

            if ($mailerTestDestination === '') {
                $mailerTestDestination = $mailerFromEmail;
            }

            if (!filter_var($mailerTestDestination, FILTER_VALIDATE_EMAIL)) {
                json_response([
                    'success' => false,
                    'message' => 'Invalid test destination email address.',
                ], 400);
            }

            try {
                $transport = Transport::fromDsn($mailerDsn);
                $mailer = new Mailer($transport);

                $email = (new Email())
                    ->from(new Address($mailerFromEmail, $mailerFromName))
                    ->to($mailerTestDestination)
                    ->subject('Chamilo Mail Test')
                    ->text('This is a test e-mail sent from the Chamilo installation wizard.');

                $mailer->send($email);

                json_response(['success' => true]);
            } catch (\Throwable $e) {
                json_response([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

        default:
            echo '';
    }
} catch (\Throwable $e) {
    // Last-resort safety net.
    // If the frontend expects JSON for test_mailer, try to return JSON.
    $actionFallback = get_query_param('a', '');
    if ($actionFallback === 'test_mailer') {
        json_response([
            'success' => false,
            'message' => 'Unexpected server error: ' . $e->getMessage(),
        ], 500);
    }

    echo '';
}
exit;
