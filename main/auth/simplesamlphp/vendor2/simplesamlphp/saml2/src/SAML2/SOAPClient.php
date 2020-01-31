<?php

namespace SAML2;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Exception\RuntimeException;
use SimpleSAML\Configuration;
use Webmozart\Assert\Assert;

/**
 * Implementation of the SAML 2.0 SOAP binding.
 *
 * @author Shoaib Ali
 * @package SimpleSAMLphp
 */
class SOAPClient
{
    const START_SOAP_ENVELOPE = '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/"><soap-env:Header/><soap-env:Body>';
    const END_SOAP_ENVELOPE = '</soap-env:Body></soap-env:Envelope>';


    /**
     * This function sends the SOAP message to the service location and returns SOAP response
     *
     * @param  \SAML2\Message            $msg         The request that should be sent.
     * @param  \SimpleSAML\Configuration $srcMetadata The metadata of the issuer of the message.
     * @param  \SimpleSAML\Configuration $dstMetadata The metadata of the destination of the message.
     * @throws \Exception
     * @return \SAML2\Message            The response we received.
     */
    public function send(Message $msg, Configuration $srcMetadata, Configuration $dstMetadata = null)
    {
        $issuer = $msg->getIssuer();

        $ctxOpts = [
            'ssl' => [
                'capture_peer_cert' => true,
                'allow_self_signed' => true
            ],
        ];

        // Determine if we are going to do a MutualSSL connection between the IdP and SP  - Shoaib
        if ($srcMetadata->hasValue('saml.SOAPClient.certificate')) {
            $cert = $srcMetadata->getValue('saml.SOAPClient.certificate');
            if ($cert !== false) {
                $ctxOpts['ssl']['local_cert'] = \SimpleSAML\Utils\Config::resolveCert(
                    $srcMetadata->getString('saml.SOAPClient.certificate')
                );
                if ($srcMetadata->hasValue('saml.SOAPClient.privatekey_pass')) {
                    $ctxOpts['ssl']['passphrase'] = $srcMetadata->getString('saml.SOAPClient.privatekey_pass');
                }
            }
        } else {
            /* Use the SP certificate and privatekey if it is configured. */
            $privateKey = \SimpleSAML\Utils\Crypto::loadPrivateKey($srcMetadata);
            $publicKey = \SimpleSAML\Utils\Crypto::loadPublicKey($srcMetadata);
            if ($privateKey !== null && $publicKey !== null && isset($publicKey['PEM'])) {
                $keyCertData = $privateKey['PEM'].$publicKey['PEM'];
                $file = \SimpleSAML\Utils\System::getTempDir().'/'.sha1($keyCertData).'.pem';
                if (!file_exists($file)) {
                    \SimpleSAML\Utils\System::writeFile($file, $keyCertData);
                }
                $ctxOpts['ssl']['local_cert'] = $file;
                if (isset($privateKey['password'])) {
                    $ctxOpts['ssl']['passphrase'] = $privateKey['password'];
                }
            }
        }

        // do peer certificate verification
        if ($dstMetadata !== null) {
            $peerPublicKeys = $dstMetadata->getPublicKeys('signing', true);
            $certData = '';
            foreach ($peerPublicKeys as $key) {
                if ($key['type'] !== 'X509Certificate') {
                    continue;
                }
                $certData .= "-----BEGIN CERTIFICATE-----\n".
                    chunk_split($key['X509Certificate'], 64).
                    "-----END CERTIFICATE-----\n";
            }
            $peerCertFile = \SimpleSAML\Utils\System::getTempDir().'/'.sha1($certData).'.pem';
            if (!file_exists($peerCertFile)) {
                \SimpleSAML\Utils\System::writeFile($peerCertFile, $certData);
            }
            // create ssl context
            $ctxOpts['ssl']['verify_peer'] = true;
            $ctxOpts['ssl']['verify_depth'] = 1;
            $ctxOpts['ssl']['cafile'] = $peerCertFile;
        }

        if ($srcMetadata->hasValue('saml.SOAPClient.stream_context.ssl.peer_name')) {
            $ctxOpts['ssl']['peer_name'] = $srcMetadata->getString('saml.SOAPClient.stream_context.ssl.peer_name');
        }

        $context = stream_context_create($ctxOpts);
        if ($context === null) {
            throw new \Exception('Unable to create SSL stream context');
        }

        $options = [
            'uri' => $issuer,
            'location' => $msg->getDestination(),
            'stream_context' => $context,
        ];

        if ($srcMetadata->hasValue('saml.SOAPClient.proxyhost')) {
            $options['proxy_host'] = $srcMetadata->getValue('saml.SOAPClient.proxyhost');
        }

        if ($srcMetadata->hasValue('saml.SOAPClient.proxyport')) {
            $options['proxy_port'] = $srcMetadata->getValue('saml.SOAPClient.proxyport');
        }

        $x = new \SoapClient(null, $options);

        // Add soap-envelopes
        $request = $msg->toSignedXML();
        $request = self::START_SOAP_ENVELOPE.$request->ownerDocument->saveXML($request).self::END_SOAP_ENVELOPE;

        Utils::getContainer()->debugMessage($request, 'out');

        $action = 'http://www.oasis-open.org/committees/security';
        $version = SOAP_1_1;
        $destination = $msg->getDestination();

        /* Perform SOAP Request over HTTP */
        $soapresponsexml = $x->__doRequest($request, $destination, $action, $version);
        if ($soapresponsexml === null || $soapresponsexml === "") {
            throw new \Exception('Empty SOAP response, check peer certificate.');
        }

        Utils::getContainer()->debugMessage($soapresponsexml, 'in');

        // Convert to SAML2\Message (\DOMElement)
        try {
            $dom = DOMDocumentFactory::fromString($soapresponsexml);
        } catch (RuntimeException $e) {
            throw new \Exception('Not a SOAP response.', 0, $e);
        }

        $soapfault = $this->getSOAPFault($dom);
        if (isset($soapfault)) {
            throw new \Exception($soapfault);
        }
        //Extract the message from the response
        $samlresponse = Utils::xpQuery($dom->firstChild, '/soap-env:Envelope/soap-env:Body/*[1]');
        $samlresponse = Message::fromXML($samlresponse[0]);

        /* Add validator to message which uses the SSL context. */
        self::addSSLValidator($samlresponse, $context);

        Utils::getContainer()->getLogger()->debug("Valid ArtifactResponse received from IdP");

        return $samlresponse;
    }


    /**
     * Add a signature validator based on a SSL context.
     *
     * @param \SAML2\Message $msg     The message we should add a validator to.
     * @param resource      $context The stream context.
     * @return void
     */
    private static function addSSLValidator(Message $msg, $context)
    {
        $options = stream_context_get_options($context);
        if (!isset($options['ssl']['peer_certificate'])) {
            return;
        }

        //$out = '';
        //openssl_x509_export($options['ssl']['peer_certificate'], $out);

        $key = openssl_pkey_get_public($options['ssl']['peer_certificate']);
        if ($key === false) {
            Utils::getContainer()->getLogger()->warning('Unable to get public key from peer certificate.');

            return;
        }

        $keyInfo = openssl_pkey_get_details($key);
        if ($keyInfo === false) {
            Utils::getContainer()->getLogger()->warning('Unable to get key details from public key.');

            return;
        }

        if (!isset($keyInfo['key'])) {
            Utils::getContainer()->getLogger()->warning('Missing key in public key details.');

            return;
        }

        $msg->addValidator(['\SAML2\SOAPClient', 'validateSSL'], $keyInfo['key']);
    }


    /**
     * Validate a SOAP message against the certificate on the SSL connection.
     *
     * @param string         $data The public key that was used on the connection.
     * @param XMLSecurityKey $key  The key we should validate the certificate against.
     * @throws \Exception
     */
    public static function validateSSL($data, XMLSecurityKey $key)
    {
        Assert::string($data);

        $keyInfo = openssl_pkey_get_details($key->key);
        if ($keyInfo === false) {
            throw new \Exception('Unable to get key details from XMLSecurityKey.');
        }

        if (!isset($keyInfo['key'])) {
            throw new \Exception('Missing key in public key details.');
        }

        if ($keyInfo['key'] !== $data) {
            Utils::getContainer()->getLogger()->debug('Key on SSL connection did not match key we validated against.');

            return;
        }

        Utils::getContainer()->getLogger()->debug('Message validated based on SSL certificate.');
    }


    /*
     * Extracts the SOAP Fault from SOAP message
     *
     * @param $soapmessage Soap response needs to be type DOMDocument
     * @return string|null $soapfaultstring
     */
    private function getSOAPFault($soapMessage)
    {
        $soapFault = Utils::xpQuery($soapMessage->firstChild, '/soap-env:Envelope/soap-env:Body/soap-env:Fault');

        if (empty($soapFault)) {
            /* No fault. */

            return null;
        }
        $soapFaultElement = $soapFault[0];
        // There is a fault element but we haven't found out what the fault string is
        $soapFaultString = "Unknown fault string found";
        // find out the fault string
        $faultStringElement = Utils::xpQuery($soapFaultElement, './soap-env:faultstring');
        if (!empty($faultStringElement)) {
            return $faultStringElement[0]->textContent;
        }

        return $soapFaultString;
    }
}
