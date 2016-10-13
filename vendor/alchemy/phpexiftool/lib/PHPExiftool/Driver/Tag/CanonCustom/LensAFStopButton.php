<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensAFStopButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensAFStopButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Lens AF Stop Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AE lock while metering',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AF point: M->Auto/Auto->ctr',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'One Shot <-> AI servo',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'AE lock while metering',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'AF point: M -> Auto / Auto -> Ctr.',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'AF mode: ONE SHOT <-> AI SERVO',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'AE lock',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'AF point: M->Auto/Auto->ctr',
        ),
        16 => array(
            'Id' => 4,
            'Label' => 'One Shot <-> AI servo',
        ),
        17 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        18 => array(
            'Id' => 6,
            'Label' => 'Switch to registered AF point',
        ),
        19 => array(
            'Id' => 7,
            'Label' => 'Spot AF',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        22 => array(
            'Id' => 2,
            'Label' => 'AE lock while metering',
        ),
        23 => array(
            'Id' => 3,
            'Label' => 'AF point: M -> Auto / Auto -> Ctr.',
        ),
        24 => array(
            'Id' => 4,
            'Label' => 'ONE SHOT <-> AI SERVO',
        ),
        25 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'AE lock while metering',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'AF point: M -> Auto / Auto -> Ctr.',
        ),
        30 => array(
            'Id' => 4,
            'Label' => 'ONE SHOT <-> AI SERVO',
        ),
        31 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'AF stop',
        ),
        33 => array(
            'Id' => 1,
            'Label' => 'AF start',
        ),
        34 => array(
            'Id' => 2,
            'Label' => 'AE lock while metering',
        ),
        35 => array(
            'Id' => 3,
            'Label' => 'AF point: M -> Auto / Auto -> Ctr.',
        ),
        36 => array(
            'Id' => 4,
            'Label' => 'ONE SHOT <-> AI SERVO',
        ),
        37 => array(
            'Id' => 5,
            'Label' => 'IS start',
        ),
        38 => array(
            'Id' => 0,
            'Label' => 'AF Stop',
        ),
        39 => array(
            'Id' => 1,
            'Label' => 'Operate AF',
        ),
        40 => array(
            'Id' => 2,
            'Label' => 'Lock AE and start timer',
        ),
    );

}
