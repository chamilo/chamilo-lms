<?php

namespace SAML2;

use DOMDocument;
use SAML2\XML\ecp\Response as ECPResponse;

/**
 * Class which implements the SOAP binding.
 *
 * @package SimpleSAMLphp
 */
class SOAP extends Binding
{
    /**
     * @param Message $message
     * @return string|bool The XML or false on error
     */
    public function getOutputToSend(Message $message)
    {
        $envelope = <<<SOAP
<?xml version="1.0" encoding="utf-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="%s">
    <SOAP-ENV:Header />
    <SOAP-ENV:Body />
</SOAP-ENV:Envelope>
SOAP;
        $envelope = sprintf($envelope, Constants::NS_SOAP);

        $doc = new DOMDocument;
        $doc->loadXML($envelope);

        // In the Artifact Resolution profile, this will be an ArtifactResolve
        // containing another message (e.g. a Response), however in the ECP
        // profile, this is the Response itself.
        if ($message instanceof Response) {
            $header = $doc->getElementsByTagNameNS(Constants::NS_SOAP, 'Header')->item(0);

            $response = new ECPResponse;
            $response->setAssertionConsumerServiceURL($this->getDestination() ?: $message->getDestination());

            $response->toXML($header);

            // TODO We SHOULD add ecp:RequestAuthenticated SOAP header if we
            // authenticated the AuthnRequest. It may make sense to have a
            // standardized way for Message objects to contain (optional) SOAP
            // headers for use with the SOAP binding.
            //
            // https://docs.oasis-open.org/security/saml/Post2.0/saml-ecp/v2.0/cs01/saml-ecp-v2.0-cs01.html#_Toc366664733
            // See Section 2.3.6.1
        }

        $body = $doc->getElementsByTagNameNs(Constants::NS_SOAP, 'Body')->item(0);

        $body->appendChild($doc->importNode($message->toSignedXML(), true));

        return $doc->saveXML();
    }


    /**
     * Send a SAML 2 message using the SOAP binding.
     *
     * Note: This function never returns.
     *
     * @param \SAML2\Message $message The message we should send.
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function send(Message $message)
    {
        header('Content-Type: text/xml', true);

        $xml = $this->getOutputToSend($message);
        Utils::getContainer()->debugMessage($xml, 'out');
        echo $xml;

        exit(0);
    }


    /**
     * Receive a SAML 2 message sent using the HTTP-POST binding.
     *
     * @throws \Exception If unable to receive the message
     * @return \SAML2\Message The received message.
     */
    public function receive()
    {
        $postText = $this->getInputStream();

        if (empty($postText)) {
            throw new \Exception('Invalid message received to AssertionConsumerService endpoint.');
        }

        $document = DOMDocumentFactory::fromString($postText);
        $xml = $document->firstChild;
        Utils::getContainer()->debugMessage($xml, 'in');
        $results = Utils::xpQuery($xml, '/soap-env:Envelope/soap-env:Body/*[1]');

        return Message::fromXML($results[0]);
    }

    /**
     * @return string|bool
     */
    protected function getInputStream()
    {
        return file_get_contents('php://input');
    }
}
