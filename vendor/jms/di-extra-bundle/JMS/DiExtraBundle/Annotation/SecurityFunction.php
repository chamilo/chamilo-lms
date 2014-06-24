<?php

namespace JMS\DiExtraBundle\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class SecurityFunction
{
    /**
     * @Required
     * @var string
     */
    public $function;
}