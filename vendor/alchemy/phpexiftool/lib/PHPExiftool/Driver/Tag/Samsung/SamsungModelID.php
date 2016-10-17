<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SamsungModelID extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'SamsungModelID';

    protected $FullName = 'Samsung::Type2';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Samsung Model ID';

    protected $flag_Permanent = true;

    protected $Values = array(
        16781340 => array(
            'Id' => 16781340,
            'Label' => 'NX10',
        ),
        16781862 => array(
            'Id' => 16781862,
            'Label' => 'HMX-S15BP',
        ),
        16781875 => array(
            'Id' => 16781875,
            'Label' => 'HMX-Q10',
        ),
        16781876 => array(
            'Id' => 16781876,
            'Label' => 'HMX-H304',
        ),
        16782092 => array(
            'Id' => 16782092,
            'Label' => 'NX100',
        ),
        16782119 => array(
            'Id' => 16782119,
            'Label' => 'NX11',
        ),
        24121422 => array(
            'Id' => 24121422,
            'Label' => 'ES70, ES71 / VLUU ES70, ES71 / SL600',
        ),
        24121426 => array(
            'Id' => 24121426,
            'Label' => 'ES73 / VLUU ES73 / SL605',
        ),
        24122112 => array(
            'Id' => 24122112,
            'Label' => 'ES28 / VLUU ES28',
        ),
        24122115 => array(
            'Id' => 24122115,
            'Label' => 'ES74,ES75,ES78 / VLUU ES75,ES78',
        ),
        33558598 => array(
            'Id' => 33558598,
            'Label' => 'PL150 / VLUU PL150 / TL210 / PL151',
        ),
        33559313 => array(
            'Id' => 33559313,
            'Label' => 'PL120,PL121 / VLUU PL120,PL121',
        ),
        33559317 => array(
            'Id' => 33559317,
            'Label' => 'PL170,PL171 / VLUUPL170,PL171',
        ),
        33559326 => array(
            'Id' => 33559326,
            'Label' => 'PL210, PL211 / VLUU PL210, PL211',
        ),
        40899351 => array(
            'Id' => 40899351,
            'Label' => 'PL20,PL21 / VLUU PL20,PL21',
        ),
        44040219 => array(
            'Id' => 44040219,
            'Label' => 'WP10 / VLUU WP10 / AQ100',
        ),
        50331648 => array(
            'Id' => 50331648,
            'Label' => 'Various Models (0x3000000)',
        ),
        60817432 => array(
            'Id' => 60817432,
            'Label' => 'Various Models (0x3a00018)',
        ),
        67112991 => array(
            'Id' => 67112991,
            'Label' => 'ST1000 / ST1100 / VLUU ST1000 / CL65',
        ),
        67112994 => array(
            'Id' => 67112994,
            'Label' => 'ST550 / VLUU ST550 / TL225',
        ),
        67112997 => array(
            'Id' => 67112997,
            'Label' => 'Various Models (0x4001025)',
        ),
        67113022 => array(
            'Id' => 67113022,
            'Label' => 'VLUU ST5500, ST5500, CL80',
        ),
        67113025 => array(
            'Id' => 67113025,
            'Label' => 'VLUU ST5000, ST5000, TL240',
        ),
        67113027 => array(
            'Id' => 67113027,
            'Label' => 'ST70 / VLUU ST70 / ST71',
        ),
        67113738 => array(
            'Id' => 67113738,
            'Label' => 'Various Models (0x400130a)',
        ),
        67113742 => array(
            'Id' => 67113742,
            'Label' => 'ST90,ST91 / VLUU ST90,ST91',
        ),
        67113747 => array(
            'Id' => 67113747,
            'Label' => 'VLUU ST95, ST95',
        ),
        77594645 => array(
            'Id' => 77594645,
            'Label' => 'VLUU ST60',
        ),
        77599579 => array(
            'Id' => 77599579,
            'Label' => 'ST30, ST65 / VLUU ST65 / ST67',
        ),
        83886080 => array(
            'Id' => 83886080,
            'Label' => 'Various Models (0x5000000)',
        ),
        83890232 => array(
            'Id' => 83890232,
            'Label' => 'Various Models (0x5001038)',
        ),
        83890234 => array(
            'Id' => 83890234,
            'Label' => 'WB650 / VLUU WB650 / WB660',
        ),
        83890236 => array(
            'Id' => 83890236,
            'Label' => 'WB600 / VLUU WB600 / WB610',
        ),
        83891006 => array(
            'Id' => 83891006,
            'Label' => 'WB150 / WB150F / WB152 / WB152F / WB151',
        ),
        94371855 => array(
            'Id' => 94371855,
            'Label' => 'WB5000 / HZ25W',
        ),
        100667446 => array(
            'Id' => 100667446,
            'Label' => 'EX1',
        ),
        117445404 => array(
            'Id' => 117445404,
            'Label' => 'VLUU SH100, SH100',
        ),
        655519746 => array(
            'Id' => 655519746,
            'Label' => 'SMX-C20N',
        ),
    );

}
