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
class PictureEffect2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PictureEffect2';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Picture Effect 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Toy Camera',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Pop Color',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Posterization',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Retro Photo',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Soft High Key',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Partial Color',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'High Contrast Monochrome',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Soft Focus',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'HDR Painting',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Rich-tone Monochrome',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Miniature',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Water Color',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Illustration',
        ),
    );

}
