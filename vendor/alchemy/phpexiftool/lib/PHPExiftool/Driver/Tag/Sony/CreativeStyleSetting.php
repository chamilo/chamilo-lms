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
class CreativeStyleSetting extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CreativeStyleSetting';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Creative Style Setting';

    protected $flag_Permanent = true;

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Standard',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Vivid',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Portrait',
        ),
        80 => array(
            'Id' => 80,
            'Label' => 'Landscape',
        ),
        96 => array(
            'Id' => 96,
            'Label' => 'B&W',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Sunset',
        ),
    );

}
