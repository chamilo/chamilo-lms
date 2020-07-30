<?php
/**
 * @see       https://github.com/zendframework/zend-soap for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-soap/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Soap\Wsdl\ComplexTypeStrategy;

use Zend\Soap\Wsdl;
use Zend\Soap\Wsdl\DocumentationStrategy\DocumentationStrategyInterface;

/**
 * Abstract class for Zend\Soap\Wsdl\Strategy.
 */
abstract class AbstractComplexTypeStrategy implements ComplexTypeStrategyInterface
{
    /**
     * Context object
     *
     * @var Wsdl
     */
    protected $context;

    /**
     * @var DocumentationStrategyInterface
     */
    protected $documentationStrategy;

    /**
     * Set the WSDL Context object this strategy resides in.
     *
     * @param Wsdl $context
     */
    public function setContext(Wsdl $context)
    {
        $this->context = $context;
    }

    /**
     * Return the current WSDL context object
     *
     * @return Wsdl
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Look through registered types
     *
     * @param string $phpType
     * @return null|string
     */
    public function scanRegisteredTypes($phpType)
    {
        if (array_key_exists($phpType, $this->getContext()->getTypes())) {
            $soapTypes = $this->getContext()->getTypes();
            return $soapTypes[$phpType];
        }
        return;
    }

    /**
     * Sets the strategy for generating complex type documentation
     *
     * @param DocumentationStrategyInterface $documentationStrategy
     * @return void
     */
    public function setDocumentationStrategy(DocumentationStrategyInterface $documentationStrategy)
    {
        $this->documentationStrategy = $documentationStrategy;
    }
}
