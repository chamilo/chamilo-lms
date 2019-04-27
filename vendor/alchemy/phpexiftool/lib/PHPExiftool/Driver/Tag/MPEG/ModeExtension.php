<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPEG;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ModeExtension extends AbstractTag
{

    protected $Id = 'Bit26-27';

    protected $Name = 'ModeExtension';

    protected $FullName = 'MPEG::Audio';

    protected $GroupName = 'MPEG';

    protected $g0 = 'MPEG';

    protected $g1 = 'MPEG';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Mode Extension';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Bands 4-31',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Bands 8-31',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Bands 12-31',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Bands 16-31',
        ),
    );

}
