<?php

namespace SAML2\Signature;

use Psr\Log\LoggerInterface;
use SAML2\Certificate\FingerprintLoader;
use SAML2\Certificate\KeyLoader;
use SAML2\Configuration\CertificateProvider;
use SAML2\SignedElement;

/**
 * Signature Validator.
 */
class Validator
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;


    /**
     * Constructor for Validator
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param SignedElement $signedElement
     * @oaram CertificateProvider $configuration
     * @return bool
     */
    public function hasValidSignature(
        SignedElement $signedElement,
        CertificateProvider $configuration
    ) {
        // should be DI
        $validator = new ValidatorChain(
            $this->logger,
            [
                new PublicKeyValidator($this->logger, new KeyLoader()),
                new FingerprintValidator($this->logger, new FingerprintLoader())
            ]
        );

        return $validator->hasValidSignature($signedElement, $configuration);
    }
}
