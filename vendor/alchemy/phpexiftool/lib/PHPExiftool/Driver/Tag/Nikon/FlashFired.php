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
class FlashFired extends AbstractTag
{

    protected $Id = '590.3';

    protected $Name = 'FlashFired';

    protected $FullName = 'Nikon::ShotInfoD80';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Flash Fired';

    protected $flag_Permanent = true;

    protected $Values = array(
        64 => array(
            'Id' => 64,
            'Label' => 'Internal',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'External',
        ),
    );

}
