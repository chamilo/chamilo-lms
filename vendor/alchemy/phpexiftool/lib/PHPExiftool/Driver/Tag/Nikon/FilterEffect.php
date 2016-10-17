<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FilterEffect extends AbstractTag
{

    protected $Id = 55;

    protected $Name = 'FilterEffect';

    protected $FullName = 'Nikon::PictureControl';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Filter Effect';

    protected $flag_Permanent = true;

    protected $Values = array(
        128 => array(
            'Id' => 128,
            'Label' => 'Off',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Yellow',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Orange',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Red',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Green',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'n/a',
        ),
    );

}
