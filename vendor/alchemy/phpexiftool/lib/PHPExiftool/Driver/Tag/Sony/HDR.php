<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HDR extends AbstractTag
{

    protected $Id = 8202;

    protected $Name = 'HDR';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'HDR';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '1.0 EV',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '1.5 EV',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '2.0 EV',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '2.5 EV',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '3.0 EV',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '3.5 EV',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '4.0 EV',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '4.5 EV',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '5.0 EV',
        ),
        25 => array(
            'Id' => 25,
            'Label' => '5.5 EV',
        ),
        26 => array(
            'Id' => 26,
            'Label' => '6.0 EV',
        ),
    );

}
