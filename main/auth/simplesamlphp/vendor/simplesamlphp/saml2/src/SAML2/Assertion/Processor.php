<?php

namespace SAML2\Assertion;

use Psr\Log\LoggerInterface;
use SAML2\Assertion;
use SAML2\Assertion\Exception\InvalidAssertionException;
use SAML2\Assertion\Exception\InvalidSubjectConfirmationException;
use SAML2\Assertion\Transformer\Transformer;
use SAML2\Assertion\Validation\AssertionValidator;
use SAML2\Assertion\Validation\SubjectConfirmationValidator;
use SAML2\Configuration\IdentityProvider;
use SAML2\Response\Exception\InvalidSignatureException;
use SAML2\Response\Exception\UnencryptedAssertionFoundException;
use SAML2\Signature\Validator;
use SAML2\Utilities\ArrayCollection;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) - due to all the named exceptions
 */
class Processor
{
    /**
     * @var \SAML2\Assertion\Decrypter
     */
    private $decrypter;

    /**
     * @var \SAML2\Assertion\Validation\AssertionValidator
     */
    private $assertionValidator;

    /**
     * @var \SAML2\Assertion\Validation\SubjectConfirmationValidator
     */
    private $subjectConfirmationValidator;

    /**
     * @var \SAML2\Assertion\Transformer\Transformer
     */
    private $transformer;

    /**
     * @var \SAML2\Signature\Validator
     */
    private $signatureValidator;

    /**
     * @var \SAML2\Configuration\IdentityProvider
     */
    private $identityProviderConfiguration;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;


    /**
     * @param Decrypter $decrypter
     * @param Validator $signatureValidator
     * @param AssertionValidator $assertionValidator
     * @param SubjectConfirmationValidator $subjectConfirmationValidator
     * @param Transformer $transformer
     * @param IdentityProvider $identityProviderConfiguration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Decrypter $decrypter,
        Validator $signatureValidator,
        AssertionValidator $assertionValidator,
        SubjectConfirmationValidator $subjectConfirmationValidator,
        Transformer $transformer,
        IdentityProvider $identityProviderConfiguration,
        LoggerInterface $logger
    ) {
        $this->assertionValidator            = $assertionValidator;
        $this->signatureValidator            = $signatureValidator;
        $this->decrypter                     = $decrypter;
        $this->subjectConfirmationValidator  = $subjectConfirmationValidator;
        $this->transformer                   = $transformer;
        $this->identityProviderConfiguration = $identityProviderConfiguration;
        $this->logger                        = $logger;
    }


    /**
     * @param \SAML2\Utilities\ArrayCollection $assertions
     * @return \SAML2\Assertion[] Collection (\SAML2\Utilities\ArrayCollection) of processed assertions
     */
    public function processAssertions($assertions)
    {
        $processed = new ArrayCollection();
        foreach ($assertions as $assertion) {
            $processed->add($this->process($assertion));
        }

        return $processed;
    }


    /**
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion
     * @return \SAML2\Assertion
     */
    public function process($assertion)
    {
        $assertion = $this->decryptAssertion($assertion);

        if (!$assertion->getWasSignedAtConstruction()) {
            $this->logger->info(sprintf(
                'Assertion with id "%s" was not signed at construction, not verifying the signature',
                $assertion->getId()
            ));
        } else {
            $this->logger->info(sprintf('Verifying signature of Assertion with id "%s"', $assertion->getId()));

            if (!$this->signatureValidator->hasValidSignature($assertion, $this->identityProviderConfiguration)) {
                throw new InvalidSignatureException();
            }
        }

        $this->validateAssertion($assertion);

        $assertion = $this->transformAssertion($assertion);

        return $assertion;
    }


    /**
     * @param \SAML2\Assertion|\SAML2\EncryptedAssertion $assertion
     * @return \SAML2\Assertion
     */
    private function decryptAssertion($assertion)
    {
        if ($this->decrypter->isEncryptionRequired() && $assertion instanceof Assertion) {
            throw new UnencryptedAssertionFoundException();
        }

        if ($assertion instanceof Assertion) {
            return $assertion;
        }

        return $this->decrypter->decrypt($assertion);
    }


    /**
     * @param \SAML2\Assertion $assertion
     * @return void
     */
    public function validateAssertion(Assertion $assertion)
    {
        $assertionValidationResult = $this->assertionValidator->validate($assertion);
        if (!$assertionValidationResult->isValid()) {
            throw new InvalidAssertionException(sprintf(
                'Invalid Assertion in SAML Response, erorrs: "%s"',
                implode('", "', $assertionValidationResult->getErrors())
            ));
        }

        foreach ($assertion->getSubjectConfirmation() as $subjectConfirmation) {
            $subjectConfirmationValidationResult = $this->subjectConfirmationValidator->validate(
                $subjectConfirmation
            );
            if (!$subjectConfirmationValidationResult->isValid()) {
                throw new InvalidSubjectConfirmationException(sprintf(
                    'Invalid SubjectConfirmation in Assertion, errors: "%s"',
                    implode('", "', $subjectConfirmationValidationResult->getErrors())
                ));
            }
        }
    }


    /**
     * @param \SAML2\Assertion $assertion
     * @return \SAML2\Assertion
     */
    private function transformAssertion(Assertion $assertion)
    {
        return $this->transformer->transform($assertion);
    }
}
