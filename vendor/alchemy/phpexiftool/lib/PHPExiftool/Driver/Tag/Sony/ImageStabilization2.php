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
class ImageStabilization2 extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'ImageStabilization2';

    protected $FullName = 'Sony::ExtraInfo';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Image Stabilization 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        191 => array(
            'Id' => 191,
            'Label' => 'On (191)',
        ),
        207 => array(
            'Id' => 207,
            'Label' => 'On (207)',
        ),
        210 => array(
            'Id' => 210,
            'Label' => 'On (210)',
        ),
        213 => array(
            'Id' => 213,
            'Label' => 'On',
        ),
        246 => array(
            'Id' => 246,
            'Label' => 'Off',
        ),
    );

}
