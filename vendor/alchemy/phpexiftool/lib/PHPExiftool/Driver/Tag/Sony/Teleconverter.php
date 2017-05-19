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
class Teleconverter extends AbstractTag
{

    protected $Id = 261;

    protected $Name = 'Teleconverter';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Teleconverter';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Minolta/Sony AF 1.4x APO (D) (0x04)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Minolta/Sony AF 2x APO (D) (0x05)',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'Minolta/Sony AF 2x APO (D)',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Minolta AF 2x APO II',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'Minolta AF 2x APO',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Minolta/Sony AF 1.4x APO (D)',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'Minolta AF 1.4x APO II',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Minolta AF 1.4x APO',
        ),
    );

}
