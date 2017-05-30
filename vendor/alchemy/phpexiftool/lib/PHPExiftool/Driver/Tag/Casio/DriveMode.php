<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DriveMode extends AbstractTag
{

    protected $Id = 12547;

    protected $Name = 'DriveMode';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Drive Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Shot',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous Shooting',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Continuous (2 fps)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Continuous (3 fps)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Continuous (4 fps)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Continuous (5 fps)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Continuous (6 fps)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Continuous (7 fps)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Continuous (10 fps)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Continuous (12 fps)',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Continuous (15 fps)',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Continuous (20 fps)',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Continuous (30 fps)',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Continuous (40 fps)',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'Continuous (60 fps)',
        ),
        240 => array(
            'Id' => 240,
            'Label' => 'Auto-N',
        ),
    );

}
