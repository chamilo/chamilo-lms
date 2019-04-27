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
class OKButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'OKButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'OK Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 8,
            'Label' => 'Select Center Focus Point',
        ),
        2 => array(
            'Id' => 16,
            'Label' => 'Highlight Active Focus Point',
        ),
        3 => array(
            'Id' => 24,
            'Label' => 'Not Used',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Not Used',
        ),
        5 => array(
            'Id' => 8,
            'Label' => 'Select Center Focus Point',
        ),
        6 => array(
            'Id' => 16,
            'Label' => 'Highlight Active Focus Point',
        ),
        7 => array(
            'Id' => 24,
            'Label' => 'Not Used',
        ),
    );

}
