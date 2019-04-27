<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PNG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Interlace extends AbstractTag
{

    protected $Id = 12;

    protected $Name = 'Interlace';

    protected $FullName = 'PNG::ImageHeader';

    protected $GroupName = 'PNG';

    protected $g0 = 'PNG';

    protected $g1 = 'PNG';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Interlace';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Noninterlaced',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Adam7 Interlace',
        ),
    );

}
