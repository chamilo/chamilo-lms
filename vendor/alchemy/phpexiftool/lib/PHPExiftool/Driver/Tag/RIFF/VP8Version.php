<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\RIFF;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VP8Version extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'VP8Version';

    protected $FullName = 'RIFF::VP8';

    protected $GroupName = 'RIFF';

    protected $g0 = 'RIFF';

    protected $g1 = 'RIFF';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'VP8 Version';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0 (bicubic reconstruction, normal loop)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1 (bilinear reconstruction, simple loop)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2 (bilinear reconstruction, no loop)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3 (no reconstruction, no loop)',
        ),
    );

}
