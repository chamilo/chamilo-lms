<?php

namespace Chamilo\CoreBundle\Component\Naming;

use Behat\Transliterator\Transliterator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * @todo this file will be remove after updating to vich_uploader > 1.10
 * This namer makes filename unique by appending a uniqid.
 * Also, filename is made web-friendly by transliteration.
 *
 * @author Massimiliano Arione <garakkio@gmail.com>
 */
final class SmartUniqueNamer implements NamerInterface
{
    public function name($object, PropertyMapping $mapping): string
    {
        /** @var UploadedFile $file */
        $file = $mapping->getFile($object);
        $originalName = $file->getClientOriginalName();
        $originalExtension = \pathinfo($originalName, PATHINFO_EXTENSION);
        $originalBasename = \basename($originalName, '.'.$originalExtension);
        $originalBasename = Transliterator::transliterate($originalBasename);

        return \sprintf(
            '%s%s.%s',
            $originalBasename,
            \str_replace('.', '', \uniqid('-', true)),
            $originalExtension
        );
    }
}
