<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LanguageCode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LanguageCode';

    protected $FullName = 'mixed';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Language Code';

    protected $Values = array(
        0000 => array(
            'Id' => 0000,
            'Label' => 'Neutral',
        ),
        0400 => array(
            'Id' => 0400,
            'Label' => 'Process default',
        ),
        0401 => array(
            'Id' => 0401,
            'Label' => 'Arabic',
        ),
        0402 => array(
            'Id' => 0402,
            'Label' => 'Bulgarian',
        ),
        0403 => array(
            'Id' => 0403,
            'Label' => 'Catalan',
        ),
        0404 => array(
            'Id' => 0404,
            'Label' => 'Chinese (Traditional)',
        ),
        0405 => array(
            'Id' => 0405,
            'Label' => 'Czech',
        ),
        0406 => array(
            'Id' => 0406,
            'Label' => 'Danish',
        ),
        0407 => array(
            'Id' => 0407,
            'Label' => 'German',
        ),
        0408 => array(
            'Id' => 0408,
            'Label' => 'Greek',
        ),
        0409 => array(
            'Id' => 0409,
            'Label' => 'English (U.S.)',
        ),
        0410 => array(
            'Id' => 0410,
            'Label' => 'Italian',
        ),
        0411 => array(
            'Id' => 0411,
            'Label' => 'Japanese',
        ),
        0412 => array(
            'Id' => 0412,
            'Label' => 'Korean',
        ),
        0413 => array(
            'Id' => 0413,
            'Label' => 'Dutch',
        ),
        0414 => array(
            'Id' => 0414,
            'Label' => 'Norwegian (Bokml)',
        ),
        0415 => array(
            'Id' => 0415,
            'Label' => 'Polish',
        ),
        0416 => array(
            'Id' => 0416,
            'Label' => 'Portuguese (Brazilian)',
        ),
        0417 => array(
            'Id' => 0417,
            'Label' => 'Rhaeto-Romanic',
        ),
        0418 => array(
            'Id' => 0418,
            'Label' => 'Romanian',
        ),
        0419 => array(
            'Id' => 0419,
            'Label' => 'Russian',
        ),
        0420 => array(
            'Id' => 0420,
            'Label' => 'Urdu',
        ),
        0421 => array(
            'Id' => 0421,
            'Label' => 'Indonesian',
        ),
        0422 => array(
            'Id' => 0422,
            'Label' => 'Ukrainian',
        ),
        0423 => array(
            'Id' => 0423,
            'Label' => 'Belarusian',
        ),
        0424 => array(
            'Id' => 0424,
            'Label' => 'Slovenian',
        ),
        0425 => array(
            'Id' => 0425,
            'Label' => 'Estonian',
        ),
        0426 => array(
            'Id' => 0426,
            'Label' => 'Latvian',
        ),
        0427 => array(
            'Id' => 0427,
            'Label' => 'Lithuanian',
        ),
        0428 => array(
            'Id' => 0428,
            'Label' => 'Maori',
        ),
        0429 => array(
            'Id' => 0429,
            'Label' => 'Farsi',
        ),
        0430 => array(
            'Id' => 0430,
            'Label' => 'Sutu',
        ),
        0431 => array(
            'Id' => 0431,
            'Label' => 'Tsonga',
        ),
        0432 => array(
            'Id' => 0432,
            'Label' => 'Tswana',
        ),
        0433 => array(
            'Id' => 0433,
            'Label' => 'Venda',
        ),
        0434 => array(
            'Id' => 0434,
            'Label' => 'Xhosa',
        ),
        0435 => array(
            'Id' => 0435,
            'Label' => 'Zulu',
        ),
        0436 => array(
            'Id' => 0436,
            'Label' => 'Afrikaans',
        ),
        0437 => array(
            'Id' => 0437,
            'Label' => 'Georgian',
        ),
        0438 => array(
            'Id' => 0438,
            'Label' => 'Faeroese',
        ),
        0439 => array(
            'Id' => 0439,
            'Label' => 'Hindi',
        ),
        0440 => array(
            'Id' => 0440,
            'Label' => 'Kyrgyz',
        ),
        0441 => array(
            'Id' => 0441,
            'Label' => 'Swahili',
        ),
        0443 => array(
            'Id' => 0443,
            'Label' => 'Uzbek',
        ),
        0444 => array(
            'Id' => 0444,
            'Label' => 'Tatar',
        ),
        0445 => array(
            'Id' => 0445,
            'Label' => 'Bengali',
        ),
        0446 => array(
            'Id' => 0446,
            'Label' => 'Punjabi',
        ),
        0447 => array(
            'Id' => 0447,
            'Label' => 'Gujarati',
        ),
        0448 => array(
            'Id' => 0448,
            'Label' => 'Oriya',
        ),
        0449 => array(
            'Id' => 0449,
            'Label' => 'Tamil',
        ),
        0450 => array(
            'Id' => 0450,
            'Label' => 'Mongolian',
        ),
        0456 => array(
            'Id' => 0456,
            'Label' => 'Galician',
        ),
        0457 => array(
            'Id' => 0457,
            'Label' => 'Konkani',
        ),
        0458 => array(
            'Id' => 0458,
            'Label' => 'Manipuri',
        ),
        0459 => array(
            'Id' => 0459,
            'Label' => 'Sindhi',
        ),
        0460 => array(
            'Id' => 0460,
            'Label' => 'Kashmiri',
        ),
        0461 => array(
            'Id' => 0461,
            'Label' => 'Nepali',
        ),
        0465 => array(
            'Id' => 0465,
            'Label' => 'Divehi',
        ),
        0490 => array(
            'Id' => 0490,
            'Label' => 'Walon',
        ),
        0491 => array(
            'Id' => 0491,
            'Label' => 'Cornish',
        ),
        0492 => array(
            'Id' => 0492,
            'Label' => 'Welsh',
        ),
        0493 => array(
            'Id' => 0493,
            'Label' => 'Breton',
        ),
        0800 => array(
            'Id' => 0800,
            'Label' => 'Neutral 2',
        ),
        0804 => array(
            'Id' => 0804,
            'Label' => 'Chinese (Simplified)',
        ),
        0807 => array(
            'Id' => 0807,
            'Label' => 'German (Swiss)',
        ),
        0809 => array(
            'Id' => 0809,
            'Label' => 'English (British)',
        ),
        0810 => array(
            'Id' => 0810,
            'Label' => 'Italian (Swiss)',
        ),
        0813 => array(
            'Id' => 0813,
            'Label' => 'Dutch (Belgian)',
        ),
        0814 => array(
            'Id' => 0814,
            'Label' => 'Norwegian (Nynorsk)',
        ),
        0816 => array(
            'Id' => 0816,
            'Label' => 'Portuguese',
        ),
        1009 => array(
            'Id' => 1009,
            'Label' => 'English (Canadian)',
        ),
        '007F' => array(
            'Id' => '007F',
            'Label' => 'Invariant',
        ),
        '040A' => array(
            'Id' => '040A',
            'Label' => 'Spanish (Castilian)',
        ),
        '040B' => array(
            'Id' => '040B',
            'Label' => 'Finnish',
        ),
        '040C' => array(
            'Id' => '040C',
            'Label' => 'French',
        ),
        '040D' => array(
            'Id' => '040D',
            'Label' => 'Hebrew',
        ),
        '040E' => array(
            'Id' => '040E',
            'Label' => 'Hungarian',
        ),
        '040F' => array(
            'Id' => '040F',
            'Label' => 'Icelandic',
        ),
        '041A' => array(
            'Id' => '041A',
            'Label' => 'Croato-Serbian (Latin)',
        ),
        '041B' => array(
            'Id' => '041B',
            'Label' => 'Slovak',
        ),
        '041C' => array(
            'Id' => '041C',
            'Label' => 'Albanian',
        ),
        '041D' => array(
            'Id' => '041D',
            'Label' => 'Swedish',
        ),
        '041E' => array(
            'Id' => '041E',
            'Label' => 'Thai',
        ),
        '041F' => array(
            'Id' => '041F',
            'Label' => 'Turkish',
        ),
        '042a' => array(
            'Id' => '042a',
            'Label' => 'Vietnamese',
        ),
        '042b' => array(
            'Id' => '042b',
            'Label' => 'Armenian',
        ),
        '042c' => array(
            'Id' => '042c',
            'Label' => 'Azeri',
        ),
        '042d' => array(
            'Id' => '042d',
            'Label' => 'Basque',
        ),
        '042e' => array(
            'Id' => '042e',
            'Label' => 'Sorbian',
        ),
        '042f' => array(
            'Id' => '042f',
            'Label' => 'Macedonian',
        ),
        '043a' => array(
            'Id' => '043a',
            'Label' => 'Maltese',
        ),
        '043b' => array(
            'Id' => '043b',
            'Label' => 'Saami',
        ),
        '043c' => array(
            'Id' => '043c',
            'Label' => 'Gaelic',
        ),
        '043e' => array(
            'Id' => '043e',
            'Label' => 'Malay',
        ),
        '043f' => array(
            'Id' => '043f',
            'Label' => 'Kazak',
        ),
        '044a' => array(
            'Id' => '044a',
            'Label' => 'Telugu',
        ),
        '044b' => array(
            'Id' => '044b',
            'Label' => 'Kannada',
        ),
        '044c' => array(
            'Id' => '044c',
            'Label' => 'Malayalam',
        ),
        '044d' => array(
            'Id' => '044d',
            'Label' => 'Assamese',
        ),
        '044e' => array(
            'Id' => '044e',
            'Label' => 'Marathi',
        ),
        '044f' => array(
            'Id' => '044f',
            'Label' => 'Sanskrit',
        ),
        '045a' => array(
            'Id' => '045a',
            'Label' => 'Syriac',
        ),
        '047f' => array(
            'Id' => '047f',
            'Label' => 'Invariant',
        ),
        '048f' => array(
            'Id' => '048f',
            'Label' => 'Esperanto',
        ),
        '080A' => array(
            'Id' => '080A',
            'Label' => 'Spanish (Mexican)',
        ),
        '080C' => array(
            'Id' => '080C',
            'Label' => 'French (Belgian)',
        ),
        '081A' => array(
            'Id' => '081A',
            'Label' => 'Serbo-Croatian (Cyrillic)',
        ),
        '0C07' => array(
            'Id' => '0C07',
            'Label' => 'German (Austrian)',
        ),
        '0C09' => array(
            'Id' => '0C09',
            'Label' => 'English (Australian)',
        ),
        '0C0A' => array(
            'Id' => '0C0A',
            'Label' => 'Spanish (Modern)',
        ),
        '0C0C' => array(
            'Id' => '0C0C',
            'Label' => 'French (Canadian)',
        ),
        '100C' => array(
            'Id' => '100C',
            'Label' => 'French (Swiss)',
        ),
    );

}
