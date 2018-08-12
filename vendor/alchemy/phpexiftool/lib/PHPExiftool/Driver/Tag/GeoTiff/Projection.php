<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GeoTiff;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Projection extends AbstractTag
{

    protected $Id = 3074;

    protected $Name = 'Projection';

    protected $FullName = 'GeoTiff::Main';

    protected $GroupName = 'GeoTiff';

    protected $g0 = 'GeoTiff';

    protected $g1 = 'GeoTiff';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Projection';

    protected $Values = array(
        10101 => array(
            'Id' => 10101,
            'Label' => 'Alabama CS27 East',
        ),
        10102 => array(
            'Id' => 10102,
            'Label' => 'Alabama CS27 West',
        ),
        10131 => array(
            'Id' => 10131,
            'Label' => 'Alabama CS83 East',
        ),
        10132 => array(
            'Id' => 10132,
            'Label' => 'Alabama CS83 West',
        ),
        10201 => array(
            'Id' => 10201,
            'Label' => 'Arizona Coordinate System east',
        ),
        10202 => array(
            'Id' => 10202,
            'Label' => 'Arizona Coordinate System Central',
        ),
        10203 => array(
            'Id' => 10203,
            'Label' => 'Arizona Coordinate System west',
        ),
        10231 => array(
            'Id' => 10231,
            'Label' => 'Arizona CS83 east',
        ),
        10232 => array(
            'Id' => 10232,
            'Label' => 'Arizona CS83 Central',
        ),
        10233 => array(
            'Id' => 10233,
            'Label' => 'Arizona CS83 west',
        ),
        10301 => array(
            'Id' => 10301,
            'Label' => 'Arkansas CS27 North',
        ),
        10302 => array(
            'Id' => 10302,
            'Label' => 'Arkansas CS27 South',
        ),
        10331 => array(
            'Id' => 10331,
            'Label' => 'Arkansas CS83 North',
        ),
        10332 => array(
            'Id' => 10332,
            'Label' => 'Arkansas CS83 South',
        ),
        10401 => array(
            'Id' => 10401,
            'Label' => 'California CS27 I',
        ),
        10402 => array(
            'Id' => 10402,
            'Label' => 'California CS27 II',
        ),
        10403 => array(
            'Id' => 10403,
            'Label' => 'California CS27 III',
        ),
        10404 => array(
            'Id' => 10404,
            'Label' => 'California CS27 IV',
        ),
        10405 => array(
            'Id' => 10405,
            'Label' => 'California CS27 V',
        ),
        10406 => array(
            'Id' => 10406,
            'Label' => 'California CS27 VI',
        ),
        10407 => array(
            'Id' => 10407,
            'Label' => 'California CS27 VII',
        ),
        10431 => array(
            'Id' => 10431,
            'Label' => 'California CS83 1',
        ),
        10432 => array(
            'Id' => 10432,
            'Label' => 'California CS83 2',
        ),
        10433 => array(
            'Id' => 10433,
            'Label' => 'California CS83 3',
        ),
        10434 => array(
            'Id' => 10434,
            'Label' => 'California CS83 4',
        ),
        10435 => array(
            'Id' => 10435,
            'Label' => 'California CS83 5',
        ),
        10436 => array(
            'Id' => 10436,
            'Label' => 'California CS83 6',
        ),
        10501 => array(
            'Id' => 10501,
            'Label' => 'Colorado CS27 North',
        ),
        10502 => array(
            'Id' => 10502,
            'Label' => 'Colorado CS27 Central',
        ),
        10503 => array(
            'Id' => 10503,
            'Label' => 'Colorado CS27 South',
        ),
        10531 => array(
            'Id' => 10531,
            'Label' => 'Colorado CS83 North',
        ),
        10532 => array(
            'Id' => 10532,
            'Label' => 'Colorado CS83 Central',
        ),
        10533 => array(
            'Id' => 10533,
            'Label' => 'Colorado CS83 South',
        ),
        10600 => array(
            'Id' => 10600,
            'Label' => 'Connecticut CS27',
        ),
        10630 => array(
            'Id' => 10630,
            'Label' => 'Connecticut CS83',
        ),
        10700 => array(
            'Id' => 10700,
            'Label' => 'Delaware CS27',
        ),
        10730 => array(
            'Id' => 10730,
            'Label' => 'Delaware CS83',
        ),
        10901 => array(
            'Id' => 10901,
            'Label' => 'Florida CS27 East',
        ),
        10902 => array(
            'Id' => 10902,
            'Label' => 'Florida CS27 West',
        ),
        10903 => array(
            'Id' => 10903,
            'Label' => 'Florida CS27 North',
        ),
        10931 => array(
            'Id' => 10931,
            'Label' => 'Florida CS83 East',
        ),
        10932 => array(
            'Id' => 10932,
            'Label' => 'Florida CS83 West',
        ),
        10933 => array(
            'Id' => 10933,
            'Label' => 'Florida CS83 North',
        ),
        11001 => array(
            'Id' => 11001,
            'Label' => 'Georgia CS27 East',
        ),
        11002 => array(
            'Id' => 11002,
            'Label' => 'Georgia CS27 West',
        ),
        11031 => array(
            'Id' => 11031,
            'Label' => 'Georgia CS83 East',
        ),
        11032 => array(
            'Id' => 11032,
            'Label' => 'Georgia CS83 West',
        ),
        11101 => array(
            'Id' => 11101,
            'Label' => 'Idaho CS27 East',
        ),
        11102 => array(
            'Id' => 11102,
            'Label' => 'Idaho CS27 Central',
        ),
        11103 => array(
            'Id' => 11103,
            'Label' => 'Idaho CS27 West',
        ),
        11131 => array(
            'Id' => 11131,
            'Label' => 'Idaho CS83 East',
        ),
        11132 => array(
            'Id' => 11132,
            'Label' => 'Idaho CS83 Central',
        ),
        11133 => array(
            'Id' => 11133,
            'Label' => 'Idaho CS83 West',
        ),
        11201 => array(
            'Id' => 11201,
            'Label' => 'Illinois CS27 East',
        ),
        11202 => array(
            'Id' => 11202,
            'Label' => 'Illinois CS27 West',
        ),
        11231 => array(
            'Id' => 11231,
            'Label' => 'Illinois CS83 East',
        ),
        11232 => array(
            'Id' => 11232,
            'Label' => 'Illinois CS83 West',
        ),
        11301 => array(
            'Id' => 11301,
            'Label' => 'Indiana CS27 East',
        ),
        11302 => array(
            'Id' => 11302,
            'Label' => 'Indiana CS27 West',
        ),
        11331 => array(
            'Id' => 11331,
            'Label' => 'Indiana CS83 East',
        ),
        11332 => array(
            'Id' => 11332,
            'Label' => 'Indiana CS83 West',
        ),
        11401 => array(
            'Id' => 11401,
            'Label' => 'Iowa CS27 North',
        ),
        11402 => array(
            'Id' => 11402,
            'Label' => 'Iowa CS27 South',
        ),
        11431 => array(
            'Id' => 11431,
            'Label' => 'Iowa CS83 North',
        ),
        11432 => array(
            'Id' => 11432,
            'Label' => 'Iowa CS83 South',
        ),
        11501 => array(
            'Id' => 11501,
            'Label' => 'Kansas CS27 North',
        ),
        11502 => array(
            'Id' => 11502,
            'Label' => 'Kansas CS27 South',
        ),
        11531 => array(
            'Id' => 11531,
            'Label' => 'Kansas CS83 North',
        ),
        11532 => array(
            'Id' => 11532,
            'Label' => 'Kansas CS83 South',
        ),
        11601 => array(
            'Id' => 11601,
            'Label' => 'Kentucky CS27 North',
        ),
        11602 => array(
            'Id' => 11602,
            'Label' => 'Kentucky CS27 South',
        ),
        11631 => array(
            'Id' => 11631,
            'Label' => 'Kentucky CS83 North',
        ),
        11632 => array(
            'Id' => 11632,
            'Label' => 'Kentucky CS83 South',
        ),
        11701 => array(
            'Id' => 11701,
            'Label' => 'Louisiana CS27 North',
        ),
        11702 => array(
            'Id' => 11702,
            'Label' => 'Louisiana CS27 South',
        ),
        11731 => array(
            'Id' => 11731,
            'Label' => 'Louisiana CS83 North',
        ),
        11732 => array(
            'Id' => 11732,
            'Label' => 'Louisiana CS83 South',
        ),
        11801 => array(
            'Id' => 11801,
            'Label' => 'Maine CS27 East',
        ),
        11802 => array(
            'Id' => 11802,
            'Label' => 'Maine CS27 West',
        ),
        11831 => array(
            'Id' => 11831,
            'Label' => 'Maine CS83 East',
        ),
        11832 => array(
            'Id' => 11832,
            'Label' => 'Maine CS83 West',
        ),
        11900 => array(
            'Id' => 11900,
            'Label' => 'Maryland CS27',
        ),
        11930 => array(
            'Id' => 11930,
            'Label' => 'Maryland CS83',
        ),
        12001 => array(
            'Id' => 12001,
            'Label' => 'Massachusetts CS27 Mainland',
        ),
        12002 => array(
            'Id' => 12002,
            'Label' => 'Massachusetts CS27 Island',
        ),
        12031 => array(
            'Id' => 12031,
            'Label' => 'Massachusetts CS83 Mainland',
        ),
        12032 => array(
            'Id' => 12032,
            'Label' => 'Massachusetts CS83 Island',
        ),
        12101 => array(
            'Id' => 12101,
            'Label' => 'Michigan State Plane East',
        ),
        12102 => array(
            'Id' => 12102,
            'Label' => 'Michigan State Plane Old Central',
        ),
        12103 => array(
            'Id' => 12103,
            'Label' => 'Michigan State Plane West',
        ),
        12111 => array(
            'Id' => 12111,
            'Label' => 'Michigan CS27 North',
        ),
        12112 => array(
            'Id' => 12112,
            'Label' => 'Michigan CS27 Central',
        ),
        12113 => array(
            'Id' => 12113,
            'Label' => 'Michigan CS27 South',
        ),
        12141 => array(
            'Id' => 12141,
            'Label' => 'Michigan CS83 North',
        ),
        12142 => array(
            'Id' => 12142,
            'Label' => 'Michigan CS83 Central',
        ),
        12143 => array(
            'Id' => 12143,
            'Label' => 'Michigan CS83 South',
        ),
        12201 => array(
            'Id' => 12201,
            'Label' => 'Minnesota CS27 North',
        ),
        12202 => array(
            'Id' => 12202,
            'Label' => 'Minnesota CS27 Central',
        ),
        12203 => array(
            'Id' => 12203,
            'Label' => 'Minnesota CS27 South',
        ),
        12231 => array(
            'Id' => 12231,
            'Label' => 'Minnesota CS83 North',
        ),
        12232 => array(
            'Id' => 12232,
            'Label' => 'Minnesota CS83 Central',
        ),
        12233 => array(
            'Id' => 12233,
            'Label' => 'Minnesota CS83 South',
        ),
        12301 => array(
            'Id' => 12301,
            'Label' => 'Mississippi CS27 East',
        ),
        12302 => array(
            'Id' => 12302,
            'Label' => 'Mississippi CS27 West',
        ),
        12331 => array(
            'Id' => 12331,
            'Label' => 'Mississippi CS83 East',
        ),
        12332 => array(
            'Id' => 12332,
            'Label' => 'Mississippi CS83 West',
        ),
        12401 => array(
            'Id' => 12401,
            'Label' => 'Missouri CS27 East',
        ),
        12402 => array(
            'Id' => 12402,
            'Label' => 'Missouri CS27 Central',
        ),
        12403 => array(
            'Id' => 12403,
            'Label' => 'Missouri CS27 West',
        ),
        12431 => array(
            'Id' => 12431,
            'Label' => 'Missouri CS83 East',
        ),
        12432 => array(
            'Id' => 12432,
            'Label' => 'Missouri CS83 Central',
        ),
        12433 => array(
            'Id' => 12433,
            'Label' => 'Missouri CS83 West',
        ),
        12501 => array(
            'Id' => 12501,
            'Label' => 'Montana CS27 North',
        ),
        12502 => array(
            'Id' => 12502,
            'Label' => 'Montana CS27 Central',
        ),
        12503 => array(
            'Id' => 12503,
            'Label' => 'Montana CS27 South',
        ),
        12530 => array(
            'Id' => 12530,
            'Label' => 'Montana CS83',
        ),
        12601 => array(
            'Id' => 12601,
            'Label' => 'Nebraska CS27 North',
        ),
        12602 => array(
            'Id' => 12602,
            'Label' => 'Nebraska CS27 South',
        ),
        12630 => array(
            'Id' => 12630,
            'Label' => 'Nebraska CS83',
        ),
        12701 => array(
            'Id' => 12701,
            'Label' => 'Nevada CS27 East',
        ),
        12702 => array(
            'Id' => 12702,
            'Label' => 'Nevada CS27 Central',
        ),
        12703 => array(
            'Id' => 12703,
            'Label' => 'Nevada CS27 West',
        ),
        12731 => array(
            'Id' => 12731,
            'Label' => 'Nevada CS83 East',
        ),
        12732 => array(
            'Id' => 12732,
            'Label' => 'Nevada CS83 Central',
        ),
        12733 => array(
            'Id' => 12733,
            'Label' => 'Nevada CS83 West',
        ),
        12800 => array(
            'Id' => 12800,
            'Label' => 'New Hampshire CS27',
        ),
        12830 => array(
            'Id' => 12830,
            'Label' => 'New Hampshire CS83',
        ),
        12900 => array(
            'Id' => 12900,
            'Label' => 'New Jersey CS27',
        ),
        12930 => array(
            'Id' => 12930,
            'Label' => 'New Jersey CS83',
        ),
        13001 => array(
            'Id' => 13001,
            'Label' => 'New Mexico CS27 East',
        ),
        13002 => array(
            'Id' => 13002,
            'Label' => 'New Mexico CS27 Central',
        ),
        13003 => array(
            'Id' => 13003,
            'Label' => 'New Mexico CS27 West',
        ),
        13031 => array(
            'Id' => 13031,
            'Label' => 'New Mexico CS83 East',
        ),
        13032 => array(
            'Id' => 13032,
            'Label' => 'New Mexico CS83 Central',
        ),
        13033 => array(
            'Id' => 13033,
            'Label' => 'New Mexico CS83 West',
        ),
        13101 => array(
            'Id' => 13101,
            'Label' => 'New York CS27 East',
        ),
        13102 => array(
            'Id' => 13102,
            'Label' => 'New York CS27 Central',
        ),
        13103 => array(
            'Id' => 13103,
            'Label' => 'New York CS27 West',
        ),
        13104 => array(
            'Id' => 13104,
            'Label' => 'New York CS27 Long Island',
        ),
        13131 => array(
            'Id' => 13131,
            'Label' => 'New York CS83 East',
        ),
        13132 => array(
            'Id' => 13132,
            'Label' => 'New York CS83 Central',
        ),
        13133 => array(
            'Id' => 13133,
            'Label' => 'New York CS83 West',
        ),
        13134 => array(
            'Id' => 13134,
            'Label' => 'New York CS83 Long Island',
        ),
        13200 => array(
            'Id' => 13200,
            'Label' => 'North Carolina CS27',
        ),
        13230 => array(
            'Id' => 13230,
            'Label' => 'North Carolina CS83',
        ),
        13301 => array(
            'Id' => 13301,
            'Label' => 'North Dakota CS27 North',
        ),
        13302 => array(
            'Id' => 13302,
            'Label' => 'North Dakota CS27 South',
        ),
        13331 => array(
            'Id' => 13331,
            'Label' => 'North Dakota CS83 North',
        ),
        13332 => array(
            'Id' => 13332,
            'Label' => 'North Dakota CS83 South',
        ),
        13401 => array(
            'Id' => 13401,
            'Label' => 'Ohio CS27 North',
        ),
        13402 => array(
            'Id' => 13402,
            'Label' => 'Ohio CS27 South',
        ),
        13431 => array(
            'Id' => 13431,
            'Label' => 'Ohio CS83 North',
        ),
        13432 => array(
            'Id' => 13432,
            'Label' => 'Ohio CS83 South',
        ),
        13501 => array(
            'Id' => 13501,
            'Label' => 'Oklahoma CS27 North',
        ),
        13502 => array(
            'Id' => 13502,
            'Label' => 'Oklahoma CS27 South',
        ),
        13531 => array(
            'Id' => 13531,
            'Label' => 'Oklahoma CS83 North',
        ),
        13532 => array(
            'Id' => 13532,
            'Label' => 'Oklahoma CS83 South',
        ),
        13601 => array(
            'Id' => 13601,
            'Label' => 'Oregon CS27 North',
        ),
        13602 => array(
            'Id' => 13602,
            'Label' => 'Oregon CS27 South',
        ),
        13631 => array(
            'Id' => 13631,
            'Label' => 'Oregon CS83 North',
        ),
        13632 => array(
            'Id' => 13632,
            'Label' => 'Oregon CS83 South',
        ),
        13701 => array(
            'Id' => 13701,
            'Label' => 'Pennsylvania CS27 North',
        ),
        13702 => array(
            'Id' => 13702,
            'Label' => 'Pennsylvania CS27 South',
        ),
        13731 => array(
            'Id' => 13731,
            'Label' => 'Pennsylvania CS83 North',
        ),
        13732 => array(
            'Id' => 13732,
            'Label' => 'Pennsylvania CS83 South',
        ),
        13800 => array(
            'Id' => 13800,
            'Label' => 'Rhode Island CS27',
        ),
        13830 => array(
            'Id' => 13830,
            'Label' => 'Rhode Island CS83',
        ),
        13901 => array(
            'Id' => 13901,
            'Label' => 'South Carolina CS27 North',
        ),
        13902 => array(
            'Id' => 13902,
            'Label' => 'South Carolina CS27 South',
        ),
        13930 => array(
            'Id' => 13930,
            'Label' => 'South Carolina CS83',
        ),
        14001 => array(
            'Id' => 14001,
            'Label' => 'South Dakota CS27 North',
        ),
        14002 => array(
            'Id' => 14002,
            'Label' => 'South Dakota CS27 South',
        ),
        14031 => array(
            'Id' => 14031,
            'Label' => 'South Dakota CS83 North',
        ),
        14032 => array(
            'Id' => 14032,
            'Label' => 'South Dakota CS83 South',
        ),
        14100 => array(
            'Id' => 14100,
            'Label' => 'Tennessee CS27',
        ),
        14130 => array(
            'Id' => 14130,
            'Label' => 'Tennessee CS83',
        ),
        14201 => array(
            'Id' => 14201,
            'Label' => 'Texas CS27 North',
        ),
        14202 => array(
            'Id' => 14202,
            'Label' => 'Texas CS27 North Central',
        ),
        14203 => array(
            'Id' => 14203,
            'Label' => 'Texas CS27 Central',
        ),
        14204 => array(
            'Id' => 14204,
            'Label' => 'Texas CS27 South Central',
        ),
        14205 => array(
            'Id' => 14205,
            'Label' => 'Texas CS27 South',
        ),
        14231 => array(
            'Id' => 14231,
            'Label' => 'Texas CS83 North',
        ),
        14232 => array(
            'Id' => 14232,
            'Label' => 'Texas CS83 North Central',
        ),
        14233 => array(
            'Id' => 14233,
            'Label' => 'Texas CS83 Central',
        ),
        14234 => array(
            'Id' => 14234,
            'Label' => 'Texas CS83 South Central',
        ),
        14235 => array(
            'Id' => 14235,
            'Label' => 'Texas CS83 South',
        ),
        14301 => array(
            'Id' => 14301,
            'Label' => 'Utah CS27 North',
        ),
        14302 => array(
            'Id' => 14302,
            'Label' => 'Utah CS27 Central',
        ),
        14303 => array(
            'Id' => 14303,
            'Label' => 'Utah CS27 South',
        ),
        14331 => array(
            'Id' => 14331,
            'Label' => 'Utah CS83 North',
        ),
        14332 => array(
            'Id' => 14332,
            'Label' => 'Utah CS83 Central',
        ),
        14333 => array(
            'Id' => 14333,
            'Label' => 'Utah CS83 South',
        ),
        14400 => array(
            'Id' => 14400,
            'Label' => 'Vermont CS27',
        ),
        14430 => array(
            'Id' => 14430,
            'Label' => 'Vermont CS83',
        ),
        14501 => array(
            'Id' => 14501,
            'Label' => 'Virginia CS27 North',
        ),
        14502 => array(
            'Id' => 14502,
            'Label' => 'Virginia CS27 South',
        ),
        14531 => array(
            'Id' => 14531,
            'Label' => 'Virginia CS83 North',
        ),
        14532 => array(
            'Id' => 14532,
            'Label' => 'Virginia CS83 South',
        ),
        14601 => array(
            'Id' => 14601,
            'Label' => 'Washington CS27 North',
        ),
        14602 => array(
            'Id' => 14602,
            'Label' => 'Washington CS27 South',
        ),
        14631 => array(
            'Id' => 14631,
            'Label' => 'Washington CS83 North',
        ),
        14632 => array(
            'Id' => 14632,
            'Label' => 'Washington CS83 South',
        ),
        14701 => array(
            'Id' => 14701,
            'Label' => 'West Virginia CS27 North',
        ),
        14702 => array(
            'Id' => 14702,
            'Label' => 'West Virginia CS27 South',
        ),
        14731 => array(
            'Id' => 14731,
            'Label' => 'West Virginia CS83 North',
        ),
        14732 => array(
            'Id' => 14732,
            'Label' => 'West Virginia CS83 South',
        ),
        14801 => array(
            'Id' => 14801,
            'Label' => 'Wisconsin CS27 North',
        ),
        14802 => array(
            'Id' => 14802,
            'Label' => 'Wisconsin CS27 Central',
        ),
        14803 => array(
            'Id' => 14803,
            'Label' => 'Wisconsin CS27 South',
        ),
        14831 => array(
            'Id' => 14831,
            'Label' => 'Wisconsin CS83 North',
        ),
        14832 => array(
            'Id' => 14832,
            'Label' => 'Wisconsin CS83 Central',
        ),
        14833 => array(
            'Id' => 14833,
            'Label' => 'Wisconsin CS83 South',
        ),
        14901 => array(
            'Id' => 14901,
            'Label' => 'Wyoming CS27 East',
        ),
        14902 => array(
            'Id' => 14902,
            'Label' => 'Wyoming CS27 East Central',
        ),
        14903 => array(
            'Id' => 14903,
            'Label' => 'Wyoming CS27 West Central',
        ),
        14904 => array(
            'Id' => 14904,
            'Label' => 'Wyoming CS27 West',
        ),
        14931 => array(
            'Id' => 14931,
            'Label' => 'Wyoming CS83 East',
        ),
        14932 => array(
            'Id' => 14932,
            'Label' => 'Wyoming CS83 East Central',
        ),
        14933 => array(
            'Id' => 14933,
            'Label' => 'Wyoming CS83 West Central',
        ),
        14934 => array(
            'Id' => 14934,
            'Label' => 'Wyoming CS83 West',
        ),
        15001 => array(
            'Id' => 15001,
            'Label' => 'Alaska CS27 1',
        ),
        15002 => array(
            'Id' => 15002,
            'Label' => 'Alaska CS27 2',
        ),
        15003 => array(
            'Id' => 15003,
            'Label' => 'Alaska CS27 3',
        ),
        15004 => array(
            'Id' => 15004,
            'Label' => 'Alaska CS27 4',
        ),
        15005 => array(
            'Id' => 15005,
            'Label' => 'Alaska CS27 5',
        ),
        15006 => array(
            'Id' => 15006,
            'Label' => 'Alaska CS27 6',
        ),
        15007 => array(
            'Id' => 15007,
            'Label' => 'Alaska CS27 7',
        ),
        15008 => array(
            'Id' => 15008,
            'Label' => 'Alaska CS27 8',
        ),
        15009 => array(
            'Id' => 15009,
            'Label' => 'Alaska CS27 9',
        ),
        15010 => array(
            'Id' => 15010,
            'Label' => 'Alaska CS27 10',
        ),
        15031 => array(
            'Id' => 15031,
            'Label' => 'Alaska CS83 1',
        ),
        15032 => array(
            'Id' => 15032,
            'Label' => 'Alaska CS83 2',
        ),
        15033 => array(
            'Id' => 15033,
            'Label' => 'Alaska CS83 3',
        ),
        15034 => array(
            'Id' => 15034,
            'Label' => 'Alaska CS83 4',
        ),
        15035 => array(
            'Id' => 15035,
            'Label' => 'Alaska CS83 5',
        ),
        15036 => array(
            'Id' => 15036,
            'Label' => 'Alaska CS83 6',
        ),
        15037 => array(
            'Id' => 15037,
            'Label' => 'Alaska CS83 7',
        ),
        15038 => array(
            'Id' => 15038,
            'Label' => 'Alaska CS83 8',
        ),
        15039 => array(
            'Id' => 15039,
            'Label' => 'Alaska CS83 9',
        ),
        15040 => array(
            'Id' => 15040,
            'Label' => 'Alaska CS83 10',
        ),
        15101 => array(
            'Id' => 15101,
            'Label' => 'Hawaii CS27 1',
        ),
        15102 => array(
            'Id' => 15102,
            'Label' => 'Hawaii CS27 2',
        ),
        15103 => array(
            'Id' => 15103,
            'Label' => 'Hawaii CS27 3',
        ),
        15104 => array(
            'Id' => 15104,
            'Label' => 'Hawaii CS27 4',
        ),
        15105 => array(
            'Id' => 15105,
            'Label' => 'Hawaii CS27 5',
        ),
        15131 => array(
            'Id' => 15131,
            'Label' => 'Hawaii CS83 1',
        ),
        15132 => array(
            'Id' => 15132,
            'Label' => 'Hawaii CS83 2',
        ),
        15133 => array(
            'Id' => 15133,
            'Label' => 'Hawaii CS83 3',
        ),
        15134 => array(
            'Id' => 15134,
            'Label' => 'Hawaii CS83 4',
        ),
        15135 => array(
            'Id' => 15135,
            'Label' => 'Hawaii CS83 5',
        ),
        15201 => array(
            'Id' => 15201,
            'Label' => 'Puerto Rico CS27',
        ),
        15202 => array(
            'Id' => 15202,
            'Label' => 'St Croix',
        ),
        15230 => array(
            'Id' => 15230,
            'Label' => 'Puerto Rico Virgin Is',
        ),
        15302 => array(
            'Id' => 15302,
            'Label' => 'Kentucky CS27',
        ),
        15303 => array(
            'Id' => 15303,
            'Label' => 'Kentucky CS83 North',
        ),
        15914 => array(
            'Id' => 15914,
            'Label' => 'BLM 14N feet',
        ),
        15915 => array(
            'Id' => 15915,
            'Label' => 'BLM 15N feet',
        ),
        15916 => array(
            'Id' => 15916,
            'Label' => 'BLM 16N feet',
        ),
        15917 => array(
            'Id' => 15917,
            'Label' => 'BLM 17N feet',
        ),
        16001 => array(
            'Id' => 16001,
            'Label' => 'UTM zone 1N',
        ),
        16002 => array(
            'Id' => 16002,
            'Label' => 'UTM zone 2N',
        ),
        16003 => array(
            'Id' => 16003,
            'Label' => 'UTM zone 3N',
        ),
        16004 => array(
            'Id' => 16004,
            'Label' => 'UTM zone 4N',
        ),
        16005 => array(
            'Id' => 16005,
            'Label' => 'UTM zone 5N',
        ),
        16006 => array(
            'Id' => 16006,
            'Label' => 'UTM zone 6N',
        ),
        16007 => array(
            'Id' => 16007,
            'Label' => 'UTM zone 7N',
        ),
        16008 => array(
            'Id' => 16008,
            'Label' => 'UTM zone 8N',
        ),
        16009 => array(
            'Id' => 16009,
            'Label' => 'UTM zone 9N',
        ),
        16010 => array(
            'Id' => 16010,
            'Label' => 'UTM zone 10N',
        ),
        16011 => array(
            'Id' => 16011,
            'Label' => 'UTM zone 11N',
        ),
        16012 => array(
            'Id' => 16012,
            'Label' => 'UTM zone 12N',
        ),
        16013 => array(
            'Id' => 16013,
            'Label' => 'UTM zone 13N',
        ),
        16014 => array(
            'Id' => 16014,
            'Label' => 'UTM zone 14N',
        ),
        16015 => array(
            'Id' => 16015,
            'Label' => 'UTM zone 15N',
        ),
        16016 => array(
            'Id' => 16016,
            'Label' => 'UTM zone 16N',
        ),
        16017 => array(
            'Id' => 16017,
            'Label' => 'UTM zone 17N',
        ),
        16018 => array(
            'Id' => 16018,
            'Label' => 'UTM zone 18N',
        ),
        16019 => array(
            'Id' => 16019,
            'Label' => 'UTM zone 19N',
        ),
        16020 => array(
            'Id' => 16020,
            'Label' => 'UTM zone 20N',
        ),
        16021 => array(
            'Id' => 16021,
            'Label' => 'UTM zone 21N',
        ),
        16022 => array(
            'Id' => 16022,
            'Label' => 'UTM zone 22N',
        ),
        16023 => array(
            'Id' => 16023,
            'Label' => 'UTM zone 23N',
        ),
        16024 => array(
            'Id' => 16024,
            'Label' => 'UTM zone 24N',
        ),
        16025 => array(
            'Id' => 16025,
            'Label' => 'UTM zone 25N',
        ),
        16026 => array(
            'Id' => 16026,
            'Label' => 'UTM zone 26N',
        ),
        16027 => array(
            'Id' => 16027,
            'Label' => 'UTM zone 27N',
        ),
        16028 => array(
            'Id' => 16028,
            'Label' => 'UTM zone 28N',
        ),
        16029 => array(
            'Id' => 16029,
            'Label' => 'UTM zone 29N',
        ),
        16030 => array(
            'Id' => 16030,
            'Label' => 'UTM zone 30N',
        ),
        16031 => array(
            'Id' => 16031,
            'Label' => 'UTM zone 31N',
        ),
        16032 => array(
            'Id' => 16032,
            'Label' => 'UTM zone 32N',
        ),
        16033 => array(
            'Id' => 16033,
            'Label' => 'UTM zone 33N',
        ),
        16034 => array(
            'Id' => 16034,
            'Label' => 'UTM zone 34N',
        ),
        16035 => array(
            'Id' => 16035,
            'Label' => 'UTM zone 35N',
        ),
        16036 => array(
            'Id' => 16036,
            'Label' => 'UTM zone 36N',
        ),
        16037 => array(
            'Id' => 16037,
            'Label' => 'UTM zone 37N',
        ),
        16038 => array(
            'Id' => 16038,
            'Label' => 'UTM zone 38N',
        ),
        16039 => array(
            'Id' => 16039,
            'Label' => 'UTM zone 39N',
        ),
        16040 => array(
            'Id' => 16040,
            'Label' => 'UTM zone 40N',
        ),
        16041 => array(
            'Id' => 16041,
            'Label' => 'UTM zone 41N',
        ),
        16042 => array(
            'Id' => 16042,
            'Label' => 'UTM zone 42N',
        ),
        16043 => array(
            'Id' => 16043,
            'Label' => 'UTM zone 43N',
        ),
        16044 => array(
            'Id' => 16044,
            'Label' => 'UTM zone 44N',
        ),
        16045 => array(
            'Id' => 16045,
            'Label' => 'UTM zone 45N',
        ),
        16046 => array(
            'Id' => 16046,
            'Label' => 'UTM zone 46N',
        ),
        16047 => array(
            'Id' => 16047,
            'Label' => 'UTM zone 47N',
        ),
        16048 => array(
            'Id' => 16048,
            'Label' => 'UTM zone 48N',
        ),
        16049 => array(
            'Id' => 16049,
            'Label' => 'UTM zone 49N',
        ),
        16050 => array(
            'Id' => 16050,
            'Label' => 'UTM zone 50N',
        ),
        16051 => array(
            'Id' => 16051,
            'Label' => 'UTM zone 51N',
        ),
        16052 => array(
            'Id' => 16052,
            'Label' => 'UTM zone 52N',
        ),
        16053 => array(
            'Id' => 16053,
            'Label' => 'UTM zone 53N',
        ),
        16054 => array(
            'Id' => 16054,
            'Label' => 'UTM zone 54N',
        ),
        16055 => array(
            'Id' => 16055,
            'Label' => 'UTM zone 55N',
        ),
        16056 => array(
            'Id' => 16056,
            'Label' => 'UTM zone 56N',
        ),
        16057 => array(
            'Id' => 16057,
            'Label' => 'UTM zone 57N',
        ),
        16058 => array(
            'Id' => 16058,
            'Label' => 'UTM zone 58N',
        ),
        16059 => array(
            'Id' => 16059,
            'Label' => 'UTM zone 59N',
        ),
        16060 => array(
            'Id' => 16060,
            'Label' => 'UTM zone 60N',
        ),
        16101 => array(
            'Id' => 16101,
            'Label' => 'UTM zone 1S',
        ),
        16102 => array(
            'Id' => 16102,
            'Label' => 'UTM zone 2S',
        ),
        16103 => array(
            'Id' => 16103,
            'Label' => 'UTM zone 3S',
        ),
        16104 => array(
            'Id' => 16104,
            'Label' => 'UTM zone 4S',
        ),
        16105 => array(
            'Id' => 16105,
            'Label' => 'UTM zone 5S',
        ),
        16106 => array(
            'Id' => 16106,
            'Label' => 'UTM zone 6S',
        ),
        16107 => array(
            'Id' => 16107,
            'Label' => 'UTM zone 7S',
        ),
        16108 => array(
            'Id' => 16108,
            'Label' => 'UTM zone 8S',
        ),
        16109 => array(
            'Id' => 16109,
            'Label' => 'UTM zone 9S',
        ),
        16110 => array(
            'Id' => 16110,
            'Label' => 'UTM zone 10S',
        ),
        16111 => array(
            'Id' => 16111,
            'Label' => 'UTM zone 11S',
        ),
        16112 => array(
            'Id' => 16112,
            'Label' => 'UTM zone 12S',
        ),
        16113 => array(
            'Id' => 16113,
            'Label' => 'UTM zone 13S',
        ),
        16114 => array(
            'Id' => 16114,
            'Label' => 'UTM zone 14S',
        ),
        16115 => array(
            'Id' => 16115,
            'Label' => 'UTM zone 15S',
        ),
        16116 => array(
            'Id' => 16116,
            'Label' => 'UTM zone 16S',
        ),
        16117 => array(
            'Id' => 16117,
            'Label' => 'UTM zone 17S',
        ),
        16118 => array(
            'Id' => 16118,
            'Label' => 'UTM zone 18S',
        ),
        16119 => array(
            'Id' => 16119,
            'Label' => 'UTM zone 19S',
        ),
        16120 => array(
            'Id' => 16120,
            'Label' => 'UTM zone 20S',
        ),
        16121 => array(
            'Id' => 16121,
            'Label' => 'UTM zone 21S',
        ),
        16122 => array(
            'Id' => 16122,
            'Label' => 'UTM zone 22S',
        ),
        16123 => array(
            'Id' => 16123,
            'Label' => 'UTM zone 23S',
        ),
        16124 => array(
            'Id' => 16124,
            'Label' => 'UTM zone 24S',
        ),
        16125 => array(
            'Id' => 16125,
            'Label' => 'UTM zone 25S',
        ),
        16126 => array(
            'Id' => 16126,
            'Label' => 'UTM zone 26S',
        ),
        16127 => array(
            'Id' => 16127,
            'Label' => 'UTM zone 27S',
        ),
        16128 => array(
            'Id' => 16128,
            'Label' => 'UTM zone 28S',
        ),
        16129 => array(
            'Id' => 16129,
            'Label' => 'UTM zone 29S',
        ),
        16130 => array(
            'Id' => 16130,
            'Label' => 'UTM zone 30S',
        ),
        16131 => array(
            'Id' => 16131,
            'Label' => 'UTM zone 31S',
        ),
        16132 => array(
            'Id' => 16132,
            'Label' => 'UTM zone 32S',
        ),
        16133 => array(
            'Id' => 16133,
            'Label' => 'UTM zone 33S',
        ),
        16134 => array(
            'Id' => 16134,
            'Label' => 'UTM zone 34S',
        ),
        16135 => array(
            'Id' => 16135,
            'Label' => 'UTM zone 35S',
        ),
        16136 => array(
            'Id' => 16136,
            'Label' => 'UTM zone 36S',
        ),
        16137 => array(
            'Id' => 16137,
            'Label' => 'UTM zone 37S',
        ),
        16138 => array(
            'Id' => 16138,
            'Label' => 'UTM zone 38S',
        ),
        16139 => array(
            'Id' => 16139,
            'Label' => 'UTM zone 39S',
        ),
        16140 => array(
            'Id' => 16140,
            'Label' => 'UTM zone 40S',
        ),
        16141 => array(
            'Id' => 16141,
            'Label' => 'UTM zone 41S',
        ),
        16142 => array(
            'Id' => 16142,
            'Label' => 'UTM zone 42S',
        ),
        16143 => array(
            'Id' => 16143,
            'Label' => 'UTM zone 43S',
        ),
        16144 => array(
            'Id' => 16144,
            'Label' => 'UTM zone 44S',
        ),
        16145 => array(
            'Id' => 16145,
            'Label' => 'UTM zone 45S',
        ),
        16146 => array(
            'Id' => 16146,
            'Label' => 'UTM zone 46S',
        ),
        16147 => array(
            'Id' => 16147,
            'Label' => 'UTM zone 47S',
        ),
        16148 => array(
            'Id' => 16148,
            'Label' => 'UTM zone 48S',
        ),
        16149 => array(
            'Id' => 16149,
            'Label' => 'UTM zone 49S',
        ),
        16150 => array(
            'Id' => 16150,
            'Label' => 'UTM zone 50S',
        ),
        16151 => array(
            'Id' => 16151,
            'Label' => 'UTM zone 51S',
        ),
        16152 => array(
            'Id' => 16152,
            'Label' => 'UTM zone 52S',
        ),
        16153 => array(
            'Id' => 16153,
            'Label' => 'UTM zone 53S',
        ),
        16154 => array(
            'Id' => 16154,
            'Label' => 'UTM zone 54S',
        ),
        16155 => array(
            'Id' => 16155,
            'Label' => 'UTM zone 55S',
        ),
        16156 => array(
            'Id' => 16156,
            'Label' => 'UTM zone 56S',
        ),
        16157 => array(
            'Id' => 16157,
            'Label' => 'UTM zone 57S',
        ),
        16158 => array(
            'Id' => 16158,
            'Label' => 'UTM zone 58S',
        ),
        16159 => array(
            'Id' => 16159,
            'Label' => 'UTM zone 59S',
        ),
        16160 => array(
            'Id' => 16160,
            'Label' => 'UTM zone 60S',
        ),
        16200 => array(
            'Id' => 16200,
            'Label' => 'Gauss Kruger zone 0',
        ),
        16201 => array(
            'Id' => 16201,
            'Label' => 'Gauss Kruger zone 1',
        ),
        16202 => array(
            'Id' => 16202,
            'Label' => 'Gauss Kruger zone 2',
        ),
        16203 => array(
            'Id' => 16203,
            'Label' => 'Gauss Kruger zone 3',
        ),
        16204 => array(
            'Id' => 16204,
            'Label' => 'Gauss Kruger zone 4',
        ),
        16205 => array(
            'Id' => 16205,
            'Label' => 'Gauss Kruger zone 5',
        ),
        17348 => array(
            'Id' => 17348,
            'Label' => 'Map Grid of Australia 48',
        ),
        17349 => array(
            'Id' => 17349,
            'Label' => 'Map Grid of Australia 49',
        ),
        17350 => array(
            'Id' => 17350,
            'Label' => 'Map Grid of Australia 50',
        ),
        17351 => array(
            'Id' => 17351,
            'Label' => 'Map Grid of Australia 51',
        ),
        17352 => array(
            'Id' => 17352,
            'Label' => 'Map Grid of Australia 52',
        ),
        17353 => array(
            'Id' => 17353,
            'Label' => 'Map Grid of Australia 53',
        ),
        17354 => array(
            'Id' => 17354,
            'Label' => 'Map Grid of Australia 54',
        ),
        17355 => array(
            'Id' => 17355,
            'Label' => 'Map Grid of Australia 55',
        ),
        17356 => array(
            'Id' => 17356,
            'Label' => 'Map Grid of Australia 56',
        ),
        17357 => array(
            'Id' => 17357,
            'Label' => 'Map Grid of Australia 57',
        ),
        17358 => array(
            'Id' => 17358,
            'Label' => 'Map Grid of Australia 58',
        ),
        17448 => array(
            'Id' => 17448,
            'Label' => 'Australian Map Grid 48',
        ),
        17449 => array(
            'Id' => 17449,
            'Label' => 'Australian Map Grid 49',
        ),
        17450 => array(
            'Id' => 17450,
            'Label' => 'Australian Map Grid 50',
        ),
        17451 => array(
            'Id' => 17451,
            'Label' => 'Australian Map Grid 51',
        ),
        17452 => array(
            'Id' => 17452,
            'Label' => 'Australian Map Grid 52',
        ),
        17453 => array(
            'Id' => 17453,
            'Label' => 'Australian Map Grid 53',
        ),
        17454 => array(
            'Id' => 17454,
            'Label' => 'Australian Map Grid 54',
        ),
        17455 => array(
            'Id' => 17455,
            'Label' => 'Australian Map Grid 55',
        ),
        17456 => array(
            'Id' => 17456,
            'Label' => 'Australian Map Grid 56',
        ),
        17457 => array(
            'Id' => 17457,
            'Label' => 'Australian Map Grid 57',
        ),
        17458 => array(
            'Id' => 17458,
            'Label' => 'Australian Map Grid 58',
        ),
        18031 => array(
            'Id' => 18031,
            'Label' => 'Argentina 1',
        ),
        18032 => array(
            'Id' => 18032,
            'Label' => 'Argentina 2',
        ),
        18033 => array(
            'Id' => 18033,
            'Label' => 'Argentina 3',
        ),
        18034 => array(
            'Id' => 18034,
            'Label' => 'Argentina 4',
        ),
        18035 => array(
            'Id' => 18035,
            'Label' => 'Argentina 5',
        ),
        18036 => array(
            'Id' => 18036,
            'Label' => 'Argentina 6',
        ),
        18037 => array(
            'Id' => 18037,
            'Label' => 'Argentina 7',
        ),
        18051 => array(
            'Id' => 18051,
            'Label' => 'Colombia 3W',
        ),
        18052 => array(
            'Id' => 18052,
            'Label' => 'Colombia Bogota',
        ),
        18053 => array(
            'Id' => 18053,
            'Label' => 'Colombia 3E',
        ),
        18054 => array(
            'Id' => 18054,
            'Label' => 'Colombia 6E',
        ),
        18072 => array(
            'Id' => 18072,
            'Label' => 'Egypt Red Belt',
        ),
        18073 => array(
            'Id' => 18073,
            'Label' => 'Egypt Purple Belt',
        ),
        18074 => array(
            'Id' => 18074,
            'Label' => 'Extended Purple Belt',
        ),
        18141 => array(
            'Id' => 18141,
            'Label' => 'New Zealand North Island Nat Grid',
        ),
        18142 => array(
            'Id' => 18142,
            'Label' => 'New Zealand South Island Nat Grid',
        ),
        19900 => array(
            'Id' => 19900,
            'Label' => 'Bahrain Grid',
        ),
        19905 => array(
            'Id' => 19905,
            'Label' => 'Netherlands E Indies Equatorial',
        ),
        19912 => array(
            'Id' => 19912,
            'Label' => 'RSO Borneo',
        ),
        19926 => array(
            'Id' => 19926,
            'Label' => 'Stereo 70',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'User Defined',
        ),
    );

}
