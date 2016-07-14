<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MinoltaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BayerPattern extends AbstractTag
{

    protected $Id = 23;

    protected $Name = 'BayerPattern';

    protected $FullName = 'MinoltaRaw::PRD';

    protected $GroupName = 'MinoltaRaw';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'MinoltaRaw';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Bayer Pattern';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'RGGB',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'GBRG',
        ),
    );

}
