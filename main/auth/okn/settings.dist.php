<?php

exit;

require_once '../../../main/inc/global.inc.php';

$spBaseUrl = api_get_path(WEB_CODE_PATH).'auth/okn/';

$url = 'https://example.es/';
$realm = 'master';
$path = '/path';

//$certificate = file_get_contents($path);

$settingsInfo = [
    'course_list' => ['ABC', 'CDE'],
    'strict' => false,
    'debug' => true,
    'sp' => [
        'entityId' => $spBaseUrl.'metadata.php',
        'assertionConsumerService' => [
            'url' => $spBaseUrl.'start.php?acs',
        ],
        'singleLogoutService' => [
            'url' => $spBaseUrl.'start.php?sls',
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified',
    ],
    'idp' => [
        'entityId' => $url.'auth/realms/'.$realm, // Example http://localhost:8080/auth/realms/master
        'singleSignOnService' => [
            'url' => $url.'auth/realms/'.$realm.'/protocol/saml', // example http://localhost:8080/auth/realms/master/protocol/saml
        ],
        'singleLogoutService' => [
            'url' => $url.'auth/realms/'.$realm.'/protocol/saml', // example http://localhost:8080/auth/realms/master/protocol/saml
        ],
        //'x509cert' => $certificate,
    ],
];

// advanced settings
//
//
//// Compression settings
//'compress' => array (
//    'requests' => true,
//    'responses' => true
//),
//    // Security settings
//    'security' => array (
//
//    /** signatures and encryptions offered */
//
//    // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
//    // will be encrypted.
//    'nameIdEncrypted' => false,
//
//    // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
//    // will be signed.  [Metadata of the SP will offer this info]
//    'authnRequestsSigned' => false,
//
//    // Indicates whether the <samlp:logoutRequest> messages sent by this SP
//    // will be signed.
//    'logoutRequestSigned' => false,
//
//    // Indicates whether the <samlp:logoutResponse> messages sent by this SP
//    // will be signed.
//    'logoutResponseSigned' => false,
//
//    /* Sign the Metadata
//     False || True (use sp certs) || array (
//                                                keyFileName => 'metadata.key',
//                                                certFileName => 'metadata.crt'
//                                            )
//    */
//    'signMetadata' => false,
//
//    /** signatures and encryptions required **/
//
//    // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest>
//    // and <samlp:LogoutResponse> elements received by this SP to be signed.
//    'wantMessagesSigned' => false,
//
//    // Indicates a requirement for the <saml:Assertion> elements received by
//    // this SP to be encrypted.
//    'wantAssertionsEncrypted' => false,
//
//    // Indicates a requirement for the <saml:Assertion> elements received by
//    // this SP to be signed. [Metadata of the SP will offer this info]
//    'wantAssertionsSigned' => false,
//
//    // Indicates a requirement for the NameID element on the SAMLResponse
//    // received by this SP to be present.
//    'wantNameId' => true,
//
//    // Indicates a requirement for the NameID received by
//    // this SP to be encrypted.
//    'wantNameIdEncrypted' => false,
//
//    // Authentication context.
//    // Set to false and no AuthContext will be sent in the AuthNRequest.
//    // Set true or don't present this parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'.
//    // Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509').
//    'requestedAuthnContext' => true,
//
//    // Indicates if the SP will validate all received xmls.
//    // (In order to validate the xml, 'strict' and 'wantXMLValidation' must be true).
//    'wantXMLValidation' => true,
//
//    // If true, SAMLResponses with an empty value at its Destination
//    // attribute will not be rejected for this fact.
//    'relaxDestinationValidation' => false,
//
//    // Algorithm that the toolkit will use on signing process. Options:
//    //    'http://www.w3.org/2000/09/xmldsig#rsa-sha1'
//    //    'http://www.w3.org/2000/09/xmldsig#dsa-sha1'
//    //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256'
//    //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384'
//    //    'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512'
//    // Notice that sha1 is a deprecated algorithm and should not be used
//    'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
//
//    // Algorithm that the toolkit will use on digest process. Options:
//    //    'http://www.w3.org/2000/09/xmldsig#sha1'
//    //    'http://www.w3.org/2001/04/xmlenc#sha256'
//    //    'http://www.w3.org/2001/04/xmldsig-more#sha384'
//    //    'http://www.w3.org/2001/04/xmlenc#sha512'
//    // Notice that sha1 is a deprecated algorithm and should not be used
//    'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
//
//    // ADFS URL-Encodes SAML data as lowercase, and the toolkit by default uses
//    // uppercase. Turn it True for ADFS compatibility on signature verification
//    'lowercaseUrlencoding' => false,
//),
//
//    // Contact information template, it is recommended to supply a
//    // technical and support contacts.
//    'contactPerson' => array (
//    'technical' => array (
//        'givenName' => 'example',
//        'emailAddress' => 'test@example.org'
//    ),
//    'support' => array (
//        'givenName' => 'example',
//        'emailAddress' => 'test@example.org'
//    ),
//),
//
//    // Organization information template, the info in en_US lang is
//    // recomended, add more if required.
//    'organization' => array (
//    'en-US' => array(
//        'name' => 'chamilo',
//        'displayname' => 'chamilo',
//        'url' => 'chamilo.org'
//    ),
//),
