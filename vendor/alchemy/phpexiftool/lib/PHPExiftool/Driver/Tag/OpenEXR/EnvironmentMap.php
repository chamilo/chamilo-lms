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
class EnvironmentMap extends AbstractTag
{

    protected $Id = 'envmap';

    protected $Name = 'EnvironmentMap';

    protected $FullName = 'OpenEXR::Main';

    protected $GroupName = 'OpenEXR';

    protected $g0 = 'OpenEXR';

    protected $g1 = 'OpenEXR';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Environment Map';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Latitude/Longitude',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Cube',
        ),
    );

}
