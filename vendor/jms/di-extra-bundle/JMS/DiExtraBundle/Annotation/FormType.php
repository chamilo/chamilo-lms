<?php

namespace JMS\DiExtraBundle\Annotation;

use JMS\DiExtraBundle\Exception\InvalidTypeException;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
final class FormType
{
    /** @var string */
    public $alias;
}
