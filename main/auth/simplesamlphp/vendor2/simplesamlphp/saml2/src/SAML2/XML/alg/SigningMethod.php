<?php

namespace SAML2\XML\alg;

use Webmozart\Assert\Assert;

/**
 * Class for handling the alg:SigningMethod element.
 *
 * @link http://docs.oasis-open.org/security/saml/Post2.0/sstc-saml-metadata-algsupport.pdf
 * @author Jaime Pérez Crespo, UNINETT AS <jaime.perez@uninett.no>
 * @package simplesamlphp/saml2
 */
class SigningMethod
{
    /**
     * An URI identifying the algorithm supported for XML signature operations.
     *
     * @var string
     */
    public $Algorithm;


    /**
     * The smallest key size, in bits, that the entity supports in conjunction with the algorithm. If omitted, no
     * minimum is implied.
     *
     * @var int|null
     */
    public $MinKeySize;


    /**
     * The largest key size, in bits, that the entity supports in conjunction with the algorithm. If omitted, no
     * maximum is implied.
     *
     * @var int|null
     */
    public $MaxKeySize;


    /**
     * Collect the value of the Algorithm-property
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->Algorithm;
    }


    /**
     * Set the value of the Algorithm-property
     * @param string $algorithm
     * @return void
     */
    public function setAlgorithm($algorithm)
    {
        Assert::string($algorithm);
        $this->Algorithm = $algorithm;
    }


    /**
     * Collect the value of the MinKeySize-property
     * @return int|null
     */
    public function getMinKeySize()
    {
        return $this->MinKeySize;
    }


    /**
     * Set the value of the MinKeySize-property
     * @param int|null $minKeySize
     * @return void
     */
    public function setMinKeySize($minKeySize = null)
    {
        Assert::nullOrInteger($minKeySize);
        $this->MinKeySize = $minKeySize;
    }


    /**
     * Collect the value of the MaxKeySize-property
     * @return int|null
     */
    public function getMaxKeySize()
    {
        return $this->MaxKeySize;
    }


    /**
     * Set the value of the MaxKeySize-property
     * @param int|null $maxKeySize
     * @return void
     */
    public function setMaxKeySize($maxKeySize = null)
    {
        Assert::nullOrInteger($maxKeySize);
        $this->MaxKeySize = $maxKeySize;
    }


    /**
     * Create/parse an alg:SigningMethod element.
     *
     * @param \DOMElement|null $xml The XML element we should load or null to create a new one from scratch.
     * @throws \Exception
     * @return void
     */
    public function __construct(\DOMElement $xml = null)
    {
        if ($xml === null) {
            return;
        }

        if (!$xml->hasAttribute('Algorithm')) {
            throw new \Exception('Missing required attribute "Algorithm" in alg:SigningMethod element.');
        }
        $this->setAlgorithm($xml->getAttribute('Algorithm'));

        if ($xml->hasAttribute('MinKeySize')) {
            $this->setMinKeySize(intval($xml->getAttribute('MinKeySize')));
        }

        if ($xml->hasAttribute('MaxKeySize')) {
            $this->setMaxKeySize(intval($xml->getAttribute('MaxKeySize')));
        }
    }


    /**
     * Convert this element to XML.
     *
     * @param \DOMElement $parent The element we should append to.
     * @return \DOMElement
     */
    public function toXML(\DOMElement $parent)
    {
        Assert::string($this->getAlgorithm());
        Assert::nullOrInteger($this->getMinKeySize());
        Assert::nullOrInteger($this->getMaxKeySize());

        $doc = $parent->ownerDocument;
        $e = $doc->createElementNS(Common::NS, 'alg:SigningMethod');
        $parent->appendChild($e);
        $e->setAttribute('Algorithm', $this->getAlgorithm());

        if ($this->getMinKeySize() !== null) {
            $e->setAttribute('MinKeySize', $this->getMinKeySize());
        }

        if ($this->getMaxKeySize() !== null) {
            $e->setAttribute('MaxKeySize', $this->getMaxKeySize());
        }

        return $e;
    }
}
