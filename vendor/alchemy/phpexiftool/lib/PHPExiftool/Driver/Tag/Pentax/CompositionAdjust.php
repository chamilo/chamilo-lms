<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CompositionAdjust extends AbstractTag
{

    protected $Id = '0.1';

    protected $Name = 'CompositionAdjust';

    protected $FullName = 'Pentax::LevelInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8s';

    protected $Writable = true;

    protected $Description = 'Composition Adjust';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Composition Adjust',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Composition Adjust + Horizon Correction',
        ),
        192 => array(
            'Id' => 192,
            'Label' => 'Horizon Correction',
        ),
    );

}
