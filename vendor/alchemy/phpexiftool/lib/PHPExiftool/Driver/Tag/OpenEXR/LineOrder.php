<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\OpenEXR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LineOrder extends AbstractTag
{

    protected $Id = 'lineOrder';

    protected $Name = 'LineOrder';

    protected $FullName = 'OpenEXR::Main';

    protected $GroupName = 'OpenEXR';

    protected $g0 = 'OpenEXR';

    protected $g1 = 'OpenEXR';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Line Order';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Increasing Y',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Decreasing Y',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Random Y',
        ),
    );

}
