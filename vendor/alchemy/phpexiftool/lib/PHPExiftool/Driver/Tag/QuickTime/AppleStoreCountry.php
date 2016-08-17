<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AppleStoreCountry extends AbstractTag
{

    protected $Id = 'sfID';

    protected $Name = 'AppleStoreCountry';

    protected $FullName = 'QuickTime::ItemList';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Apple Store Country';

    protected $Values = array(
        143441 => array(
            'Id' => 143441,
            'Label' => 'United States',
        ),
        143442 => array(
            'Id' => 143442,
            'Label' => 'France',
        ),
        143443 => array(
            'Id' => 143443,
            'Label' => 'Germany',
        ),
        143444 => array(
            'Id' => 143444,
            'Label' => 'United Kingdom',
        ),
        143445 => array(
            'Id' => 143445,
            'Label' => 'Austria',
        ),
        143446 => array(
            'Id' => 143446,
            'Label' => 'Belgium',
        ),
        143447 => array(
            'Id' => 143447,
            'Label' => 'Finland',
        ),
        143448 => array(
            'Id' => 143448,
            'Label' => 'Greece',
        ),
        143449 => array(
            'Id' => 143449,
            'Label' => 'Ireland',
        ),
        143450 => array(
            'Id' => 143450,
            'Label' => 'Italy',
        ),
        143451 => array(
            'Id' => 143451,
            'Label' => 'Luxembourg',
        ),
        143452 => array(
            'Id' => 143452,
            'Label' => 'Netherlands',
        ),
        143453 => array(
            'Id' => 143453,
            'Label' => 'Portugal',
        ),
        143454 => array(
            'Id' => 143454,
            'Label' => 'Spain',
        ),
        143455 => array(
            'Id' => 143455,
            'Label' => 'Canada',
        ),
        143456 => array(
            'Id' => 143456,
            'Label' => 'Sweden',
        ),
        143457 => array(
            'Id' => 143457,
            'Label' => 'Norway',
        ),
        143458 => array(
            'Id' => 143458,
            'Label' => 'Denmark',
        ),
        143459 => array(
            'Id' => 143459,
            'Label' => 'Switzerland',
        ),
        143460 => array(
            'Id' => 143460,
            'Label' => 'Australia',
        ),
        143461 => array(
            'Id' => 143461,
            'Label' => 'New Zealand',
        ),
        143462 => array(
            'Id' => 143462,
            'Label' => 'Japan',
        ),
        143463 => array(
            'Id' => 143463,
            'Label' => 'Hong Kong',
        ),
        143464 => array(
            'Id' => 143464,
            'Label' => 'Singapore',
        ),
        143465 => array(
            'Id' => 143465,
            'Label' => 'China',
        ),
        143466 => array(
            'Id' => 143466,
            'Label' => 'Republic of Korea',
        ),
        143467 => array(
            'Id' => 143467,
            'Label' => 'India',
        ),
        143468 => array(
            'Id' => 143468,
            'Label' => 'Mexico',
        ),
        143469 => array(
            'Id' => 143469,
            'Label' => 'Russia',
        ),
        143470 => array(
            'Id' => 143470,
            'Label' => 'Taiwan',
        ),
        143471 => array(
            'Id' => 143471,
            'Label' => 'Vietnam',
        ),
        143472 => array(
            'Id' => 143472,
            'Label' => 'South Africa',
        ),
        143473 => array(
            'Id' => 143473,
            'Label' => 'Malaysia',
        ),
        143474 => array(
            'Id' => 143474,
            'Label' => 'Philippines',
        ),
        143475 => array(
            'Id' => 143475,
            'Label' => 'Thailand',
        ),
        143476 => array(
            'Id' => 143476,
            'Label' => 'Indonesia',
        ),
        143477 => array(
            'Id' => 143477,
            'Label' => 'Pakistan',
        ),
        143478 => array(
            'Id' => 143478,
            'Label' => 'Poland',
        ),
        143479 => array(
            'Id' => 143479,
            'Label' => 'Saudi Arabia',
        ),
        143480 => array(
            'Id' => 143480,
            'Label' => 'Turkey',
        ),
        143481 => array(
            'Id' => 143481,
            'Label' => 'United Arab Emirates',
        ),
        143482 => array(
            'Id' => 143482,
            'Label' => 'Hungary',
        ),
        143483 => array(
            'Id' => 143483,
            'Label' => 'Chile',
        ),
        143484 => array(
            'Id' => 143484,
            'Label' => 'Nepal',
        ),
        143485 => array(
            'Id' => 143485,
            'Label' => 'Panama',
        ),
        143486 => array(
            'Id' => 143486,
            'Label' => 'Sri Lanka',
        ),
        143487 => array(
            'Id' => 143487,
            'Label' => 'Romania',
        ),
        143489 => array(
            'Id' => 143489,
            'Label' => 'Czech Republic',
        ),
        143491 => array(
            'Id' => 143491,
            'Label' => 'Israel',
        ),
        143492 => array(
            'Id' => 143492,
            'Label' => 'Ukraine',
        ),
        143493 => array(
            'Id' => 143493,
            'Label' => 'Kuwait',
        ),
        143494 => array(
            'Id' => 143494,
            'Label' => 'Croatia',
        ),
        143495 => array(
            'Id' => 143495,
            'Label' => 'Costa Rica',
        ),
        143496 => array(
            'Id' => 143496,
            'Label' => 'Slovakia',
        ),
        143497 => array(
            'Id' => 143497,
            'Label' => 'Lebanon',
        ),
        143498 => array(
            'Id' => 143498,
            'Label' => 'Qatar',
        ),
        143499 => array(
            'Id' => 143499,
            'Label' => 'Slovenia',
        ),
        143501 => array(
            'Id' => 143501,
            'Label' => 'Colombia',
        ),
        143502 => array(
            'Id' => 143502,
            'Label' => 'Venezuela',
        ),
        143503 => array(
            'Id' => 143503,
            'Label' => 'Brazil',
        ),
        143504 => array(
            'Id' => 143504,
            'Label' => 'Guatemala',
        ),
        143505 => array(
            'Id' => 143505,
            'Label' => 'Argentina',
        ),
        143506 => array(
            'Id' => 143506,
            'Label' => 'El Salvador',
        ),
        143507 => array(
            'Id' => 143507,
            'Label' => 'Peru',
        ),
        143508 => array(
            'Id' => 143508,
            'Label' => 'Dominican Republic',
        ),
        143509 => array(
            'Id' => 143509,
            'Label' => 'Ecuador',
        ),
        143510 => array(
            'Id' => 143510,
            'Label' => 'Honduras',
        ),
        143511 => array(
            'Id' => 143511,
            'Label' => 'Jamaica',
        ),
        143512 => array(
            'Id' => 143512,
            'Label' => 'Nicaragua',
        ),
        143513 => array(
            'Id' => 143513,
            'Label' => 'Paraguay',
        ),
        143514 => array(
            'Id' => 143514,
            'Label' => 'Uruguay',
        ),
        143515 => array(
            'Id' => 143515,
            'Label' => 'Macau',
        ),
        143516 => array(
            'Id' => 143516,
            'Label' => 'Egypt',
        ),
        143517 => array(
            'Id' => 143517,
            'Label' => 'Kazakhstan',
        ),
        143518 => array(
            'Id' => 143518,
            'Label' => 'Estonia',
        ),
        143519 => array(
            'Id' => 143519,
            'Label' => 'Latvia',
        ),
        143520 => array(
            'Id' => 143520,
            'Label' => 'Lithuania',
        ),
        143521 => array(
            'Id' => 143521,
            'Label' => 'Malta',
        ),
        143523 => array(
            'Id' => 143523,
            'Label' => 'Moldova',
        ),
        143524 => array(
            'Id' => 143524,
            'Label' => 'Armenia',
        ),
        143525 => array(
            'Id' => 143525,
            'Label' => 'Botswana',
        ),
        143526 => array(
            'Id' => 143526,
            'Label' => 'Bulgaria',
        ),
        143528 => array(
            'Id' => 143528,
            'Label' => 'Jordan',
        ),
        143529 => array(
            'Id' => 143529,
            'Label' => 'Kenya',
        ),
        143530 => array(
            'Id' => 143530,
            'Label' => 'Macedonia',
        ),
        143531 => array(
            'Id' => 143531,
            'Label' => 'Madagascar',
        ),
        143532 => array(
            'Id' => 143532,
            'Label' => 'Mali',
        ),
        143533 => array(
            'Id' => 143533,
            'Label' => 'Mauritius',
        ),
        143534 => array(
            'Id' => 143534,
            'Label' => 'Niger',
        ),
        143535 => array(
            'Id' => 143535,
            'Label' => 'Senegal',
        ),
        143536 => array(
            'Id' => 143536,
            'Label' => 'Tunisia',
        ),
        143537 => array(
            'Id' => 143537,
            'Label' => 'Uganda',
        ),
        143538 => array(
            'Id' => 143538,
            'Label' => 'Anguilla',
        ),
        143539 => array(
            'Id' => 143539,
            'Label' => 'Bahamas',
        ),
        143540 => array(
            'Id' => 143540,
            'Label' => 'Antigua and Barbuda',
        ),
        143541 => array(
            'Id' => 143541,
            'Label' => 'Barbados',
        ),
        143542 => array(
            'Id' => 143542,
            'Label' => 'Bermuda',
        ),
        143543 => array(
            'Id' => 143543,
            'Label' => 'British Virgin Islands',
        ),
        143544 => array(
            'Id' => 143544,
            'Label' => 'Cayman Islands',
        ),
        143545 => array(
            'Id' => 143545,
            'Label' => 'Dominica',
        ),
        143546 => array(
            'Id' => 143546,
            'Label' => 'Grenada',
        ),
        143547 => array(
            'Id' => 143547,
            'Label' => 'Montserrat',
        ),
        143548 => array(
            'Id' => 143548,
            'Label' => 'St. Kitts and Nevis',
        ),
        143549 => array(
            'Id' => 143549,
            'Label' => 'St. Lucia',
        ),
        143550 => array(
            'Id' => 143550,
            'Label' => 'St. Vincent and The Grenadines',
        ),
        143551 => array(
            'Id' => 143551,
            'Label' => 'Trinidad and Tobago',
        ),
        143552 => array(
            'Id' => 143552,
            'Label' => 'Turks and Caicos',
        ),
        143553 => array(
            'Id' => 143553,
            'Label' => 'Guyana',
        ),
        143554 => array(
            'Id' => 143554,
            'Label' => 'Suriname',
        ),
        143555 => array(
            'Id' => 143555,
            'Label' => 'Belize',
        ),
        143556 => array(
            'Id' => 143556,
            'Label' => 'Bolivia',
        ),
        143557 => array(
            'Id' => 143557,
            'Label' => 'Cyprus',
        ),
        143558 => array(
            'Id' => 143558,
            'Label' => 'Iceland',
        ),
        143559 => array(
            'Id' => 143559,
            'Label' => 'Bahrain',
        ),
        143560 => array(
            'Id' => 143560,
            'Label' => 'Brunei Darussalam',
        ),
        143561 => array(
            'Id' => 143561,
            'Label' => 'Nigeria',
        ),
        143562 => array(
            'Id' => 143562,
            'Label' => 'Oman',
        ),
        143563 => array(
            'Id' => 143563,
            'Label' => 'Algeria',
        ),
        143564 => array(
            'Id' => 143564,
            'Label' => 'Angola',
        ),
        143565 => array(
            'Id' => 143565,
            'Label' => 'Belarus',
        ),
        143566 => array(
            'Id' => 143566,
            'Label' => 'Uzbekistan',
        ),
        143568 => array(
            'Id' => 143568,
            'Label' => 'Azerbaijan',
        ),
        143571 => array(
            'Id' => 143571,
            'Label' => 'Yemen',
        ),
        143572 => array(
            'Id' => 143572,
            'Label' => 'Tanzania',
        ),
        143573 => array(
            'Id' => 143573,
            'Label' => 'Ghana',
        ),
        143575 => array(
            'Id' => 143575,
            'Label' => 'Albania',
        ),
        143576 => array(
            'Id' => 143576,
            'Label' => 'Benin',
        ),
        143577 => array(
            'Id' => 143577,
            'Label' => 'Bhutan',
        ),
        143578 => array(
            'Id' => 143578,
            'Label' => 'Burkina Faso',
        ),
        143579 => array(
            'Id' => 143579,
            'Label' => 'Cambodia',
        ),
        143580 => array(
            'Id' => 143580,
            'Label' => 'Cape Verde',
        ),
        143581 => array(
            'Id' => 143581,
            'Label' => 'Chad',
        ),
        143582 => array(
            'Id' => 143582,
            'Label' => 'Republic of the Congo',
        ),
        143583 => array(
            'Id' => 143583,
            'Label' => 'Fiji',
        ),
        143584 => array(
            'Id' => 143584,
            'Label' => 'Gambia',
        ),
        143585 => array(
            'Id' => 143585,
            'Label' => 'Guinea-Bissau',
        ),
        143586 => array(
            'Id' => 143586,
            'Label' => 'Kyrgyzstan',
        ),
        143587 => array(
            'Id' => 143587,
            'Label' => 'Lao People\'s Democratic Republic',
        ),
        143588 => array(
            'Id' => 143588,
            'Label' => 'Liberia',
        ),
        143589 => array(
            'Id' => 143589,
            'Label' => 'Malawi',
        ),
        143590 => array(
            'Id' => 143590,
            'Label' => 'Mauritania',
        ),
        143591 => array(
            'Id' => 143591,
            'Label' => 'Federated States of Micronesia',
        ),
        143592 => array(
            'Id' => 143592,
            'Label' => 'Mongolia',
        ),
        143593 => array(
            'Id' => 143593,
            'Label' => 'Mozambique',
        ),
        143594 => array(
            'Id' => 143594,
            'Label' => 'Namibia',
        ),
        143595 => array(
            'Id' => 143595,
            'Label' => 'Palau',
        ),
        143597 => array(
            'Id' => 143597,
            'Label' => 'Papua New Guinea',
        ),
        143598 => array(
            'Id' => 143598,
            'Label' => 'Sao Tome and Principe',
        ),
        143599 => array(
            'Id' => 143599,
            'Label' => 'Seychelles',
        ),
        143600 => array(
            'Id' => 143600,
            'Label' => 'Sierra Leone',
        ),
        143601 => array(
            'Id' => 143601,
            'Label' => 'Solomon Islands',
        ),
        143602 => array(
            'Id' => 143602,
            'Label' => 'Swaziland',
        ),
        143603 => array(
            'Id' => 143603,
            'Label' => 'Tajikistan',
        ),
        143604 => array(
            'Id' => 143604,
            'Label' => 'Turkmenistan',
        ),
        143605 => array(
            'Id' => 143605,
            'Label' => 'Zimbabwe',
        ),
    );

}
