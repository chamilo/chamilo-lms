<?php

namespace SAML2\Certificate;

/**
 * Specific Certificate Key.
 */
class X509 extends Key
{
    /**
     * @var \SAML2\Certificate\Fingerprint
     */
    private $fingerprint;


    /**
     * @param string $certificateContents
     * @return X509
     */
    public static function createFromCertificateData($certificateContents)
    {
        $data = [
            'encryption'      => true,
            'signing'         => true,
            'type'            => 'X509Certificate',
            'X509Certificate' => $certificateContents
        ];

        return new self($data);
    }


    /**
     * {@inheritdoc} Best place to ensure the logic is encapsulated in a single place
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === 'X509Certificate') {
            $value = preg_replace('~\s+~', '', $value);
        }

        parent::offsetSet($offset, $value);
    }


    /**
     * Get the certificate representation
     *
     * @return string
     */
    public function getCertificate()
    {
        return "-----BEGIN CERTIFICATE-----\n"
                . chunk_split($this->keyData['X509Certificate'], 64)
                . "-----END CERTIFICATE-----\n";
    }


    /**
     * @return \SAML2\Certificate\Fingerprint
     *
     * @deprecated Please use full certificates instead.
     */
    public function getFingerprint()
    {
        if (isset($this->fingerprint)) {
            return $this->fingerprint;
        }

        $fingerprint = strtolower(sha1(base64_decode($this->keyData['X509Certificate'])));

        return $this->fingerprint = new Fingerprint($fingerprint);
    }
}
