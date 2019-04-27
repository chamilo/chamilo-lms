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
class ColorBoostType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ColorBoostType';

    protected $FullName = 'NikonCapture::ColorBoost';

    protected $GroupName = 'NikonCapture';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCapture';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Color Boost Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Nature',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'People',
        ),
    );

}
