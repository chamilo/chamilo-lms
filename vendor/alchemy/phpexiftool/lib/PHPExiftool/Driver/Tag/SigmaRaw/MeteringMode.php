<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SigmaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MeteringMode extends AbstractTag
{

    protected $Id = 'AEMODE';

    protected $Name = 'MeteringMode';

    protected $FullName = 'SigmaRaw::Properties';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Metering Mode';

    protected $Values = array(
        8 => array(
            'Id' => 8,
            'Label' => '8-segment',
        ),
        'A' => array(
            'Id' => 'A',
            'Label' => 'Average',
        ),
        'C' => array(
            'Id' => 'C',
            'Label' => 'Center-weighted average',
        ),
    );

}
