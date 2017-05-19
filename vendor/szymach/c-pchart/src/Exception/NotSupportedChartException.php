<?php

namespace CpChart\Exception;

/**
 * @author Piotr Szymaszek
 */
class NotSupportedChartException extends FactoryException
{
    protected $message = 'The requested chart class does not exist!';
}
