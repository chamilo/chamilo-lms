<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCapture;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Unsharp2Color extends AbstractTag
{

    protected $Id = 46;

    protected $Name = 'Unsharp2Color';

    protected $FullName = 'NikonCapture::UnsharpData';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Unsharp 2 Color';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'RGB',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Red',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Green',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Blue',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Yellow',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Magenta',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Cyan',
        ),
    );

}
