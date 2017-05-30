<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MIEFlash;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashFired extends AbstractTag
{

    protected $Id = 'Fired';

    protected $Name = 'FlashFired';

    protected $FullName = 'MIE::Flash';

    protected $GroupName = 'MIE-Flash';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Flash';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Fired';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
    );

}
