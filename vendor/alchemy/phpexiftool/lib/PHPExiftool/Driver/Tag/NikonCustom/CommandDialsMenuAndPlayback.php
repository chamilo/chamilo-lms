<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CommandDialsMenuAndPlayback extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsMenuAndPlayback';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Menu And Playback';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 16,
            'Label' => 'On (Image Review Excluded)',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 16,
            'Label' => 'On',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        9 => array(
            'Id' => 16,
            'Label' => 'On (Image Review Exclude)',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        11 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        12 => array(
            'Id' => 16,
            'Label' => 'On (Image Review Excluded)',
        ),
    );

}
