<?php

namespace SAML2;

use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Utilities\Temporal;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Store;

/**
 * Class which implements the HTTP-Artifact binding.
 *
 * @author  Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package SimpleSAMLphp
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HTTPArtifact extends Binding
{
    /**
     * @var \SimpleSAML\Configuration
     */
    private $spMetadata;


    /**
     * Create the redirect URL for a message.
     *
     * @param  \SAML2\Message $message The message.
     * @throws \Exception
     * @return string        The URL the user should be redirected to in order to send a message.
     */
    public function getRedirectURL(Message $message)
    {
        $store = Store::getInstance();
        if ($store === false) {
            throw new \Exception('Unable to send artifact without a datastore configured.');
        }

        $generatedId = pack('H*', bin2hex(openssl_random_pseudo_bytes(20)));
        $artifact = base64_encode("\x00\x04\x00\x00".sha1($message->getIssuer(), true).$generatedId);
        $artifactData = $message->toUnsignedXML();
        $artifactDataString = $artifactData->ownerDocument->saveXML($artifactData);

        $store->set('artifact', $artifact, $artifactDataString, Temporal::getTime() + 15*60);

        $params = [
            'SAMLart' => $artifact,
        ];
        $relayState = $message->getRelayState();
        if ($relayState !== null) {
            $params['RelayState'] = $relayState;
        }

        return \SimpleSAML\Utils\HTTP::addURLparameter($message->getDestination(), $params);
    }


    /**
     * Send a SAML 2 message using the HTTP-Redirect binding.
     *
     * Note: This function never returns.
     *
     * @param \SAML2\Message $message The message we should send.
     * @retrun void
     */
    public function send(Message $message)
    {
        $destination = $this->getRedirectURL($message);
        Utils::getContainer()->redirect($destination);
    }


    /**
     * Receive a SAML 2 message sent using the HTTP-Artifact binding.
     *
     * Throws an exception if it is unable receive the message.
     *
     * @throws \Exception
     * @return \SAML2\Message The received message.
     */
    public function receive()
    {
        if (array_key_exists('SAMLart', $_REQUEST)) {
            $artifact = base64_decode($_REQUEST['SAMLart']);
            $endpointIndex = bin2hex(substr($artifact, 2, 2));
            $sourceId = bin2hex(substr($artifact, 4, 20));
        } else {
            throw new \Exception('Missing SAMLart parameter.');
        }

        $metadataHandler = MetaDataStorageHandler::getMetadataHandler();

        $idpMetadata = $metadataHandler->getMetaDataConfigForSha1($sourceId, 'saml20-idp-remote');

        if ($idpMetadata === null) {
            throw new \Exception('No metadata found for remote provider with SHA1 ID: '.var_export($sourceId, true));
        }

        $endpoint = null;
        foreach ($idpMetadata->getEndpoints('ArtifactResolutionService') as $ep) {
            if ($ep['index'] === hexdec($endpointIndex)) {
                $endpoint = $ep;
                break;
            }
        }

        if ($endpoint === null) {
            throw new \Exception('No ArtifactResolutionService with the correct index.');
        }

        Utils::getContainer()->getLogger()->debug("ArtifactResolutionService endpoint being used is := ".$endpoint['Location']);

        //Construct the ArtifactResolve Request
        $ar = new ArtifactResolve();

        /* Set the request attributes */

        $ar->setIssuer($this->spMetadata->getString('entityid'));
        $ar->setArtifact($_REQUEST['SAMLart']);
        $ar->setDestination($endpoint['Location']);

        /* Sign the request */
        \SimpleSAML\Module\saml\Message::addSign($this->spMetadata, $idpMetadata, $ar); // Shoaib - moved from the SOAPClient.

        $soap = new SOAPClient();

        // Send message through SoapClient
        /** @var \SAML2\ArtifactResponse $artifactResponse */
        $artifactResponse = $soap->send($ar, $this->spMetadata);

        if (!$artifactResponse->isSuccess()) {
            throw new \Exception('Received error from ArtifactResolutionService.');
        }

        $xml = $artifactResponse->getAny();
        if ($xml === null) {
            /* Empty ArtifactResponse - possibly because of Artifact replay? */

            return null;
        }

        $samlResponse = Message::fromXML($xml);
        $samlResponse->addValidator([get_class($this), 'validateSignature'], $artifactResponse);

        if (isset($_REQUEST['RelayState'])) {
            $samlResponse->setRelayState($_REQUEST['RelayState']);
        }

        return $samlResponse;
    }


    /**
     * @param \SimpleSAML\Configuration $sp
     * @return void
     */
    public function setSPMetadata(Configuration $sp)
    {
        $this->spMetadata = $sp;
    }


    /**
     * A validator which returns true if the ArtifactResponse was signed with the given key
     *
     * @param \SAML2\ArtifactResponse $message
     * @param XMLSecurityKey $key
     * @return bool
     */
    public static function validateSignature(ArtifactResponse $message, XMLSecurityKey $key)
    {
        return $message->validate($key);
    }
}
