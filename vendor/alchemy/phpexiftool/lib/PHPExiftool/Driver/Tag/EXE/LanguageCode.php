<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
        0 => array(
            'Id' => 0,
            'Label' => 'Neutral',
        ),
        400 => array(
            'Id' => 400,
            'Label' => 'Process default',
        ),
        401 => array(
            'Id' => 401,
            'Label' => 'Arabic',
        ),
        402 => array(
            'Id' => 402,
            'Label' => 'Bulgarian',
        ),
        403 => array(
            'Id' => 403,
            'Label' => 'Catalan',
        ),
        404 => array(
            'Id' => 404,
            'Label' => 'Chinese (Traditional)',
        ),
        405 => array(
            'Id' => 405,
            'Label' => 'Czech',
        ),
        406 => array(
            'Id' => 406,
            'Label' => 'Danish',
        ),
        407 => array(
            'Id' => 407,
            'Label' => 'German',
        ),
        408 => array(
            'Id' => 408,
            'Label' => 'Greek',
        ),
        409 => array(
            'Id' => 409,
            'Label' => 'English (U.S.)',
        ),
        410 => array(
            'Id' => 410,
            'Label' => 'Italian',
        ),
        411 => array(
            'Id' => 411,
            'Label' => 'Japanese',
        ),
        412 => array(
            'Id' => 412,
            'Label' => 'Korean',
        ),
        413 => array(
            'Id' => 413,
            'Label' => 'Dutch',
        ),
        414 => array(
            'Id' => 414,
            'Label' => 'Norwegian (Bokml)',
        ),
        415 => array(
            'Id' => 415,
            'Label' => 'Polish',
        ),
        416 => array(
            'Id' => 416,
            'Label' => 'Portuguese (Brazilian)',
        ),
        417 => array(
            'Id' => 417,
            'Label' => 'Rhaeto-Romanic',
        ),
        418 => array(
            'Id' => 418,
            'Label' => 'Romanian',
        ),
        419 => array(
            'Id' => 419,
            'Label' => 'Russian',
        ),
        420 => array(
            'Id' => 420,
            'Label' => 'Urdu',
        ),
        421 => array(
            'Id' => 421,
            'Label' => 'Indonesian',
        ),
        422 => array(
            'Id' => 422,
            'Label' => 'Ukrainian',
        ),
        423 => array(
            'Id' => 423,
            'Label' => 'Belarusian',
        ),
        424 => array(
            'Id' => 424,
            'Label' => 'Slovenian',
        ),
        425 => array(
            'Id' => 425,
            'Label' => 'Estonian',
        ),
        426 => array(
            'Id' => 426,
            'Label' => 'Latvian',
        ),
        427 => array(
            'Id' => 427,
            'Label' => 'Lithuanian',
        ),
        428 => array(
            'Id' => 428,
            'Label' => 'Maori',
        ),
        429 => array(
            'Id' => 429,
            'Label' => 'Farsi',
        ),
        430 => array(
            'Id' => 430,
            'Label' => 'Sutu',
        ),
        431 => array(
            'Id' => 431,
            'Label' => 'Tsonga',
        ),
        432 => array(
            'Id' => 432,
            'Label' => 'Tswana',
        ),
        433 => array(
            'Id' => 433,
            'Label' => 'Venda',
        ),
        434 => array(
            'Id' => 434,
            'Label' => 'Xhosa',
        ),
        435 => array(
            'Id' => 435,
            'Label' => 'Zulu',
        ),
        436 => array(
            'Id' => 436,
            'Label' => 'Afrikaans',
        ),
        437 => array(
            'Id' => 437,
            'Label' => 'Georgian',
        ),
        438 => array(
            'Id' => 438,
            'Label' => 'Faeroese',
        ),
        439 => array(
            'Id' => 439,
            'Label' => 'Hindi',
        ),
        440 => array(
            'Id' => 440,
            'Label' => 'Kyrgyz',
        ),
        441 => array(
            'Id' => 441,
            'Label' => 'Swahili',
        ),
        443 => array(
            'Id' => 443,
            'Label' => 'Uzbek',
        ),
        444 => array(
            'Id' => 444,
            'Label' => 'Tatar',
        ),
        445 => array(
            'Id' => 445,
            'Label' => 'Bengali',
        ),
        446 => array(
            'Id' => 446,
            'Label' => 'Punjabi',
        ),
        447 => array(
            'Id' => 447,
            'Label' => 'Gujarati',
        ),
        448 => array(
            'Id' => 448,
            'Label' => 'Oriya',
        ),
        449 => array(
            'Id' => 449,
            'Label' => 'Tamil',
        ),
        450 => array(
            'Id' => 450,
            'Label' => 'Mongolian',
        ),
        456 => array(
            'Id' => 456,
            'Label' => 'Galician',
        ),
        457 => array(
            'Id' => 457,
            'Label' => 'Konkani',
        ),
        458 => array(
            'Id' => 458,
            'Label' => 'Manipuri',
        ),
        459 => array(
            'Id' => 459,
            'Label' => 'Sindhi',
        ),
        460 => array(
            'Id' => 460,
            'Label' => 'Kashmiri',
        ),
        461 => array(
            'Id' => 461,
            'Label' => 'Nepali',
        ),
        465 => array(
            'Id' => 465,
            'Label' => 'Divehi',
        ),
        490 => array(
            'Id' => 490,
            'Label' => 'Walon',
        ),
        491 => array(
            'Id' => 491,
            'Label' => 'Cornish',
        ),
        492 => array(
            'Id' => 492,
            'Label' => 'Welsh',
        ),
        493 => array(
            'Id' => 493,
            'Label' => 'Breton',
        ),
        800 => array(
            'Id' => 800,
            'Label' => 'Neutral 2',
        ),
        804 => array(
            'Id' => 804,
            'Label' => 'Chinese (Simplified)',
        ),
        807 => array(
            'Id' => 807,
            'Label' => 'German (Swiss)',
        ),
        809 => array(
            'Id' => 809,
            'Label' => 'English (British)',
        ),
        810 => array(
            'Id' => 810,
            'Label' => 'Italian (Swiss)',
        ),
        813 => array(
            'Id' => 813,
            'Label' => 'Dutch (Belgian)',
        ),
        814 => array(
            'Id' => 814,
            'Label' => 'Norwegian (Nynorsk)',
        ),
        816 => array(
            'Id' => 816,
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
