<?php

namespace SAML2;

use Webmozart\Assert\Assert;

/**
 * Base class for SAML 2 bindings.
 *
 * @package SimpleSAMLphp
 */
abstract class Binding
{
    /**
     * The destination of messages.
     *
     * This can be null, in which case the destination in the message is used.
     */
    protected $destination;


    /**
     * Retrieve a binding with the given URN.
     *
     * Will throw an exception if it is unable to locate the binding.
     *
     * @param string $urn The URN of the binding.
     * @throws \Exception
     * @return \SAML2\Binding The binding.
     */
    public static function getBinding($urn)
    {
        Assert::string($urn);

        switch ($urn) {
            case Constants::BINDING_HTTP_POST:
                return new HTTPPost();
            case Constants::BINDING_HTTP_REDIRECT:
                return new HTTPRedirect();
            case Constants::BINDING_HTTP_ARTIFACT:
                return new HTTPArtifact();
            case Constants::BINDING_HOK_SSO:
                return new HTTPPost();
            // ECP ACS is defined with the PAOS binding, but as the IdP, we
            // talk to the ECP using SOAP -- if support for ECP as an SP is
            // implemented, this logic may need to change
            case Constants::BINDING_PAOS:
                return new SOAP();
            default:
                throw new \Exception('Unsupported binding: '.var_export($urn, true));
        }
    }


    /**
     * Guess the current binding.
     *
     * This function guesses the current binding and creates an instance
     * of \SAML2\Binding matching that binding.
     *
     * An exception will be thrown if it is unable to guess the binding.
     *
     * @throws \Exception
     * @return \SAML2\Binding The binding.
     */
    public static function getCurrentBinding()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if (array_key_exists('SAMLRequest', $_GET) || array_key_exists('SAMLResponse', $_GET)) {
                    return new HTTPRedirect();
                } elseif (array_key_exists('SAMLart', $_GET)) {
                    return new HTTPArtifact();
                }
                break;

            case 'POST':
                if (isset($_SERVER['CONTENT_TYPE'])) {
                    $contentType = $_SERVER['CONTENT_TYPE'];
                    $contentType = explode(';', $contentType);
                    $contentType = $contentType[0]; /* Remove charset. */
                } else {
                    $contentType = null;
                }
                if (array_key_exists('SAMLRequest', $_POST) || array_key_exists('SAMLResponse', $_POST)) {
                    return new HTTPPost();
                } elseif (array_key_exists('SAMLart', $_POST)) {
                    return new HTTPArtifact();
                } elseif ($contentType === 'text/xml') {
                    return new SOAP();
                }
                break;
        }

        $logger = Utils::getContainer()->getLogger();
        $logger->warning('Unable to find the SAML 2 binding used for this request.');
        $logger->warning('Request method: '.var_export($_SERVER['REQUEST_METHOD'], true));
        if (!empty($_GET)) {
            $logger->warning("GET parameters: '".implode("', '", array_map('addslashes', array_keys($_GET)))."'");
        }
        if (!empty($_POST)) {
            $logger->warning("POST parameters: '".implode("', '", array_map('addslashes', array_keys($_POST)))."'");
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $logger->warning('Content-Type: '.var_export($_SERVER['CONTENT_TYPE'], true));
        }

        throw new \Exception('Unable to find the current binding.');
    }


    /**
     * Retrieve the destination of a message.
     *
     * @return string|null $destination  The destination the message will be delivered to.
     */
    public function getDestination()
    {
        return $this->destination;
    }


    /**
     * Override the destination of a message.
     *
     * Set to null to use the destination set in the message.
     *
     * @param string|null $destination The destination the message should be delivered to.
     * @return void
     */
    public function setDestination($destination)
    {
        Assert::nullOrString($destination);

        $this->destination = $destination;
    }


    /**
     * Send a SAML 2 message.
     *
     * This function will send a message using the specified binding.
     * The message will be delivered to the destination set in the message.
     *
     * @param \SAML2\Message $message The message which should be sent.
     * @return void
     */
    abstract public function send(Message $message);


    /**
     * Receive a SAML 2 message.
     *
     * This function will extract the message from the current request.
     * An exception will be thrown if we are unable to process the message.
     *
     * @return \SAML2\Message The received message.
     */
    abstract public function receive();
}
