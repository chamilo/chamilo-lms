<?php

namespace Xabbuh\XApi\DataFixtures;

use Xabbuh\XApi\Model\Extensions;
use Xabbuh\XApi\Model\IRI;

/**
 * xAPI statement extensions fixtures.
 *
 * These fixtures are borrowed from the
 * {@link https://github.com/adlnet/xAPI_LRS_Test Experience API Learning Record Store Conformance Test} package.
 */
class ExtensionsFixtures
{
    public static function getEmptyExtensions()
    {
        return new Extensions();
    }

    public static function getTypicalExtensions()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');

        return new Extensions($extensions);
    }

    public static function getWithObjectValueExtensions()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/color'), array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));

        return new Extensions($extensions);
    }

    public static function getWithIntegerValueExtensions()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);

        return new Extensions($extensions);
    }

    public static function getMultiplePairsExtensions()
    {
        $extensions = new \SplObjectStorage();
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/topic'), 'Conformance Testing');
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/color'), array(
            'model' => 'RGB',
            'value' => '#FFFFFF',
        ));
        $extensions->attach(IRI::fromString('http://id.tincanapi.com/extension/starting-position'), 1);

        return new Extensions($extensions);
    }
}
