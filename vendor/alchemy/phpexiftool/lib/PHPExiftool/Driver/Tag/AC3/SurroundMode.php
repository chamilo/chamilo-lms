<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\AC3;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SurroundMode extends AbstractTag
{

    protected $Id = 'SurroundMode';

    protected $Name = 'SurroundMode';

    protected $FullName = 'M2TS::AC3';

    protected $GroupName = 'AC3';

    protected $g0 = 'M2TS';

    protected $g1 = 'AC3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Surround Mode';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Not indicated',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Not Dolby surround',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Dolby surround',
        ),
    );

}
