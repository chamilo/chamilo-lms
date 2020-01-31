<?php

namespace SAML2;

use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Interface to a SAML 2 element which may be signed.
 *
 * @package SimpleSAMLphp
 */
interface SignedElement
{
    /**
     * Validate this element against a public key.
     *
     * If no signature is present, false is returned. If a signature is present,
     * but cannot be verified, an exception will be thrown.
     *
     * @param  XMLSecurityKey $key The key we should check against.
     * @return boolean        true if successful, false if we don't have a signature that can be verified.
     */
    public function validate(XMLSecurityKey $key);


    /**
     * Set the certificates that should be included in the element.
     * The certificates should be strings with the PEM encoded data.
     *
     * @param array $certificates An array of certificates.
     * @return void
     */
    public function setCertificates(array $certificates);


    /**
     * Retrieve the certificates that are included in the element (if any).
     *
     * @return array An array of certificates.
     */
    public function getCertificates();


    /**
     * Retrieve the private key we should use to sign the element.
     *
     * @return XMLSecurityKey|null The key, or NULL if no key is specified.
     */
    public function getSignatureKey();


    /**
     * Set the private key we should use to sign the element.
     * If the key is null, the message will be sent unsigned.
     *
     * @param XMLSecurityKey|null $signatureKey
     * @return void
     */
    public function setSignatureKey(XMLSecurityKey $signatureKey = null);
}
