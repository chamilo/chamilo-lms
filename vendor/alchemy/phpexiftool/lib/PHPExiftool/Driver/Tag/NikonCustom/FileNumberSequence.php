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
class FileNumberSequence extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FileNumberSequence';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'File Number Sequence';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Off',
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
            'Id' => 0,
            'Label' => 'On',
        ),
        5 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        7 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        9 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        11 => array(
            'Id' => 64,
            'Label' => 'Off',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        13 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'On',
        ),
        15 => array(
            'Id' => 8,
            'Label' => 'Off',
        ),
    );

    protected $Index = 'mixed';

}
