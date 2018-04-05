<?php
/* For licensing terms, see /license.txt */

use Chamilo\UserBundle\Entity\User;

/**
 * @package chamilo.webservices
 */
require_once '../inc/global.inc.php';

error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR);

$libpath = api_get_path(LIBRARY_PATH);

$debug = true;

define('WS_ERROR_SECRET_KEY', 1);
define('WS_ERROR_NOT_FOUND_RESULT', 2);
define('WS_ERROR_INVALID_INPUT', 3);
define('WS_ERROR_SETTING', 4);

/**
 * @param string $code
 *
 * @return null|soap_fault
 */
function returnError($code)
{
    $fault = null;
    switch ($code) {
        case WS_ERROR_SECRET_KEY:
            $fault = new soap_fault('Server', '', 'Secret key is not correct or params are not correctly set');
            break;
        case WS_ERROR_NOT_FOUND_RESULT:
            $fault = new soap_fault('Server', '', 'No result was found for this query');
            break;
        case WS_ERROR_INVALID_INPUT:
            $fault = new soap_fault('Server', '', 'The input variables are invalid o are not correctly set');
            break;
        case WS_ERROR_SETTING:
            $fault = new soap_fault('Server', '', 'Please check the configuration for this webservice');
            break;
    }

    return $fault;
}

/**
 * @param array $params
 *
 * @return bool
 */
function WSHelperVerifyKey($params)
{
    global $_configuration, $debug;
    if (is_array($params)) {
        $secret_key = $params['secret_key'];
    } else {
        $secret_key = $params;
    }
    //error_log(print_r($params,1));
    $check_ip = false;
    $ip_matches = false;
    $ip = trim($_SERVER['REMOTE_ADDR']);
    // if we are behind a reverse proxy, assume it will send the
    // HTTP_X_FORWARDED_FOR header and use this IP instead
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        list($ip1) = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
    if ($debug) {
        error_log("ip: $ip");
    }
    // Check if a file that limits access from webservices exists and contains
    // the restraining check
    if (is_file('webservice-auth-ip.conf.php')) {
        include 'webservice-auth-ip.conf.php';
        if ($debug) {
            error_log("webservice-auth-ip.conf.php file included");
        }
        if (!empty($ws_auth_ip)) {
            $check_ip = true;
            $ip_matches = api_check_ip_in_range($ip, $ws_auth_ip);
            if ($debug) {
                error_log("ip_matches: $ip_matches");
            }
        }
    }

    if ($debug) {
        error_log("checkip ".intval($check_ip));
    }

    if ($check_ip) {
        $security_key = $_configuration['security_key'];
    } else {
        $security_key = $ip.$_configuration['security_key'];
        //error_log($secret_key.'-'.$security_key);
    }

    $result = api_is_valid_secret_key($secret_key, $security_key);

    if ($debug) {
        error_log('WSHelperVerifyKey result: '.intval($result));
    }

    return $result;
}

// Create the server instance
$server = new soap_server();

$server->soap_defencoding = 'UTF-8';

// Initialize WSDL support
$server->configureWSDL('WSTCC', 'urn:WSTCC');

/* Register WSCreateUserPasswordCrypted function */
// Register the data structures used by the service

// Input params for editing users
$server->wsdl->addComplexType(
    'paramsUpdateTCCUserIdAndGetUser',
    'complexType',
    'struct',
    'all',
    '',
    [
        'email' => ['name' => 'email', 'type' => 'xsd:string'],
        'tcc_user_id' => ['name' => 'tcc_user_id', 'type' => 'xsd:string'],
        'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
    ]
);

$fields = [
    'Genre' => ['name' => 'Genre', 'type' => 'xsd:string'],
    'Nom' => ['name' => 'Nom', 'type' => 'xsd:string'],
    'Prenom' => ['name' => 'Prenom', 'type' => 'xsd:string'],
    'DateNaissance' => ['name' => 'DateNaissance', 'type' => 'xsd:string'],
    'Langue' => ['name' => 'Langue', 'type' => 'xsd:string'],
    'Nationalite' => ['name' => 'Nationalite', 'type' => 'xsd:string'],
    'Pays' => ['name' => 'Pays', 'type' => 'xsd:string'],
    'Adresse' => ['name' => 'Adresse', 'type' => 'xsd:string'],
    'CodePostal' => ['name' => 'CodePostal', 'type' => 'xsd:string'],
    'Ville' => ['name' => 'Ville', 'type' => 'xsd:string'],
    'Email' => ['name' => 'Email', 'type' => 'xsd:string'],
];

$server->wsdl->addComplexType(
    'resultUpdateTCCUserIdAndGetUserArray',
    'complexType',
    'struct',
    'all',
    '',
    $fields
);

// Register the method to expose
$server->register('WSUpdateTCCUserIdAndGetUser',                            // method name
    ['paramsUpdateTCCUserIdAndGetUser' => 'tns:paramsUpdateTCCUserIdAndGetUser'],  // input parameters
    ['return' => 'tns:resultUpdateTCCUserIdAndGetUserArray'],                                        // output parameters
    'urn:WSTCC',                                                   // namespace
    'urn:WSTCC#WSCreateUserPasswordCrypted',                       // soapaction
    'rpc',                                                                  // style
    'encoded',                                                              // use
    'This service adds users'                                               // documentation
);

// Define the method WSUpdateTCCUserIdAndGetUser
function WSUpdateTCCUserIdAndGetUser($params)
{
    global $_configuration, $debug;
    $debug = 1;
    if ($debug) {
        error_log('WSUpdateTCCUserIdAndGetUser');
    }
    if ($debug) {
        error_log(print_r($params, 1));
    }

    if (!WSHelperVerifyKey($params)) {
        return returnError(WS_ERROR_SECRET_KEY);
    }

    $users = UserManager::getRepository()->getUsersByEmail($params['email']);

    if (!empty($users)) {
        if (isset($users[0]) && $users[0] instanceof User) {
            /** @var User $user */
            $user = $users[0];

            $userInfo = api_get_user_info(
                $user->getId(),
                false,
                false,
                true,
                false
            );

            if ($params['tcc_user_id'] !== '') {
                $extraFieldValue = new ExtraFieldValue('user');

                $extraField = new ExtraField('user');
                $extraFieldData = $extraField->get_handler_field_info_by_field_variable('tcc_user_id');
                $params = [
                    'field_id' => $extraFieldData['id'],
                    'value' => $params['tcc_user_id'],
                    'item_id' => $user->getId(),
                ];
                $extraFieldValue->save($params);
            }

            $extraFields = [
                'terms_genre',
                'terms_datedenaissance',
                'terms_ville',
                'terms_paysresidence',
                'terms_nationalite',
                'terms_codepostal',
                'terms_adresse',
            ];

            $extraFieldResults = [];

            foreach ($userInfo['extra'] as $field) {
                /** @var \Chamilo\CoreBundle\Entity\ExtraFieldValues $extraFieldValue */
                $extraFieldValue = $field['value'];
                $variable = $extraFieldValue->getField()->getVariable();
                $extraFieldResults[$variable] = '';
                if (in_array($variable, $extraFields)) {
                    $extraFieldResults[$variable] = $extraFieldValue->getValue();
                }
            }

            $parts = explode('-', $extraFieldResults['terms_datedenaissance']);
            $extraFieldResults['terms_datedenaissance'] = $parts[0].'/'.$parts[1].'/'.$parts[2];
            $extraFieldResults['terms_genre'] = $extraFieldResults['terms_genre'] === 'homme' ? 'Masculin' : 'Féminin';

            $language = 'fr-FR';
            switch ($user->getLanguage()) {
                case 'french2':
                case 'french':
                    $language = 'fr-FR';
                    break;
                case 'german':
                case 'german2':
                    $language = 'de-DE';
                    break;
            }

            $result = [
                'Genre' => $extraFieldResults['terms_genre'],
                'Nom' => $user->getLastname(),
                'Prenom' => $user->getFirstname(),
                'DateNaissance' => $extraFieldResults['terms_datedenaissance'],
                'Langue' => $language,
                'Nationalite' => $extraFieldResults['terms_nationalite'],
                'Pays' => $extraFieldResults['terms_paysresidence'],
                'Adresse' => $extraFieldResults['terms_adresse'],
                'CodePostal' => $extraFieldResults['terms_codepostal'],
                'Ville' => $extraFieldResults['terms_ville'],
                'Email' => $user->getEmail(),
            ];

            if ($debug) {
                error_log(print_r($result, 1));
            }

            return $result;
        }
    }

    return [];
}

$fields = $fields + [
    'tcc_user_id' => ['name' => 'tcc_user_id', 'type' => 'xsd:string'],
    'secret_key' => ['name' => 'secret_key', 'type' => 'xsd:string'],
];

/* Register WSEditUser function */
// Register the data structures used by the service
$server->wsdl->addComplexType(
    'editUser',
    'complexType',
    'struct',
    'all',
    '',
    $fields
);

// Register the method to expose
$server->register('WSEditUserTCC',              // method name
    ['editUser' => 'tns:editUser'],     // input parameters
    ['return' => 'xsd:string'],         // output parameters
    'urn:WSTCC',                    // namespace
    'urn:WSTCC#WSEditUserTCC',         // soapaction
    'rpc',                                   // style
    'encoded',                               // use
    'This service edits a user from wiener'  // documentation
);

// Define the method WSEditUser
function WSEditUserTCC($params)
{
    if (!WSHelperVerifyKey($params)) {
        return returnError(WS_ERROR_SECRET_KEY);
    }

    $extraFieldValue = new ExtraFieldValue('user');
    $data = $extraFieldValue->get_item_id_from_field_variable_and_field_value('tcc_user_id', $params['tcc_user_id']);
    if ($data && isset($data['item_id'])) {
        $userId = $data['item_id'];
        $user = api_get_user_entity($userId);
        if (!empty($user)) {
            switch ($params['Langue']) {
                case 'fr-FR':
                    $params['Langue'] = 'french2';
                    break;
                case 'de-DE':
                    $params['Langue'] = 'german2';
                    break;
            }

            $user
                ->setFirstname($params['Prenom'])
                ->setLastname($params['Nom'])
                ->setLanguage($params['Langue'])
                ->setEmail($params['Email'])
            ;

            $em = Database::getManager();
            $em->merge($user);
            $em->flush();

            $extraField = new ExtraField('user');
            $extraFieldValue = new ExtraFieldValue('user');

            $fields = [
                'terms_genre' => 'Genre',
                'terms_datedenaissance' => 'DateNaissance',
                'terms_ville' => 'Ville',
                'terms_paysresidence' => 'Pays',
                'terms_nationalite' => 'Nationalite',
                'terms_codepostal' => 'CodePostal',
                'terms_adresse' => 'Adresse',
            ];

            foreach ($fields as $extraFieldName => $externalName) {
                $fieldInfo = $extraField->get_handler_field_info_by_field_variable($extraFieldName);

                switch ($extraFieldName) {
                    case 'terms_genre':
                        $params[$externalName] = $params[$externalName] === 'Masculin' ? 'homme' : 'femme';
                        break;
                    case 'terms_datedenaissance':
                        if (!empty($params[$externalName])) {
                            $parts = explode('/', $params[$externalName]); // dd/mm/yyyy
                            $params[$externalName] = $parts[2].'-'.$parts[1].'-'.$parts[0];
                        }
                        break;
                }

                if ($fieldInfo) {
                    $paramsToSave = [
                        'field_id' => $fieldInfo['id'],
                        'item_id' => $userId,
                        'value' => $params[$externalName],
                    ];
                    error_log($extraFieldName);
                    error_log(print_r($paramsToSave, 1));
                    $extraFieldValue->save($paramsToSave);
                }
            }

            return 1;
        }

        return 0;
    }
}

// If you send your data in utf8 then this value must be false.
$decodeUTF8 = api_get_setting('registration.soap.php.decode_utf8');
if ($decodeUTF8 === 'true') {
    $server->decode_utf8 = true;
} else {
    $server->decode_utf8 = false;
}
$server->service(file_get_contents('php://input'));
