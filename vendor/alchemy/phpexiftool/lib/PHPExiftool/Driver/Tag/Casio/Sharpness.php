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
class Sharpness extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Sharpness';

    protected $FullName = 'mixed';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Sharpness';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Soft',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Hard',
        ),
        3 => array(
            'Id' => 16,
            'Label' => 'Normal',
        ),
        4 => array(
            'Id' => 17,
            'Label' => '+1',
        ),
        5 => array(
            'Id' => 18,
            'Label' => '-1',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Soft',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Hard',
        ),
    );

}
