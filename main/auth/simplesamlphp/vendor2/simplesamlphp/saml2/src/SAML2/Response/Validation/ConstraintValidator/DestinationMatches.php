<?php

namespace SAML2\Response\Validation\ConstraintValidator;

use SAML2\Configuration\Destination;
use SAML2\Response;
use SAML2\Response\Validation\ConstraintValidator;
use SAML2\Response\Validation\Result;

class DestinationMatches implements
    ConstraintValidator
{
    /**
     * @var \SAML2\Configuration\Destination
     */
    private $expectedDestination;

    /**
     * Constructor for DestinationMatches
     * @param Destination $destination
     */
    public function __construct(Destination $destination)
    {
        $this->expectedDestination = $destination;
    }


    /**
     * @param Response $response
     * @param Result $result
     * @return void
     */
    public function validate(Response $response, Result $result)
    {
        $destination = $response->getDestination();
        if (!$this->expectedDestination->equals(new Destination($destination))) {
            $result->addError(sprintf(
                'Destination in response "%s" does not match the expected destination "%s"',
                $destination,
                $this->expectedDestination
            ));
        }
    }
}
